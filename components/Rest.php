<?php

namespace hrzg\filefly\components;

use Yii;
use yii\base\NotSupportedException;
use yii\web\HttpException;

/**
 * REST class
 *
 * For server side REST API implementation (POST, GET, PUT, DELETE as CRUD)
 * Chaineable
 *
 * @author      Jakub Ďuraš <jakub@duras.me>
 */
class Rest
{
    /**
     * List of callbacks which are assigned and later called in handle method: post, get, put, delete, before and after
     *
     * @var array
     */
    private $callbacks = [];

    /**
     * @var boolean
     */
    private $requireAuthentication = false;

    /**
     * Add callback for specific HTTP method (post, get, put, delete)
     *
     * @param string $method name of the HTTP method
     * @param array $arguments expects only one argument, callback, with number of arguments based on request method ($queries, $body['data'], $body['files'])
     *
     * @return object            this
     * @throws NotSupportedException
     */
    public function __call($method, $arguments)
    {
        if (!in_array($method, ['post', 'get', 'put', 'delete'])) {
            throw new NotSupportedException('REST method "' . $method . '" not supported.');
        }

        $this->callbacks[$method] = $arguments[0];

        return $this;
    }

    /**
     * Should authentication be required
     *
     * @param boolean $option defaults to true
     *
     * @return Rest
     */
    public function setRequireAuthentication($option = true)
    {
        $this->requireAuthentication = $option;

        return $this;
    }

    /**
     * Add callback called before every request
     *
     * @param callable $callback arguments: $queries, $body['data'], $body['files']
     *
     * @return object              this
     */
    public function before($callback)
    {
        $this->callbacks['before'] = $callback;

        return $this;
    }

    /**
     * Add callback called after every request
     *
     * @param callable $callback arguments: $queries, $body['data'], $body['files']
     *
     * @return object              this
     */
    public function after($callback)
    {
        $this->callbacks['after'] = $callback;

        return $this;
    }

    /**
     * Should be called manually as last method
     *
     * @throws HttpException
     */
    public function handle()
    {
        if ($this->requireAuthentication) {
            $authenticateResponse = $this->verifyAuthentication();
            if ($authenticateResponse instanceof Response) {
                $this->respond($authenticateResponse);
                return;
            }
        }

        $request_method = $_SERVER['REQUEST_METHOD'];

        $body = [
            'data' => null,
            'files' => []
        ];
        $queries = [];

        //Get body data and files only from requests with body
        if ($request_method === 'POST' OR $request_method === 'PUT') {
            $body = $this->parseBody();
        }

        if (!empty($_GET)) {
            $queries = $_GET;
        }

        if (isset($this->callbacks['before'])) {
            $this->callbacks['before']($queries, $body['data'], $body['files']);
        }

        switch ($request_method) {
            case 'POST':
                $response = $this->callbacks['post']($queries, $body['data'], $body['files']);
                break;

            case 'GET':
                $response = $this->callbacks['get']($queries);
                break;

            case 'PUT':
                $response = $this->callbacks['put']($queries, $body['data'], $body['files']);
                break;

            case 'DELETE':
                $response = $this->callbacks['delete']($queries);
                break;

            default:
                //Not supported
                $response = new Response();
                $response->setStatus(501, 'Not Implemented');
                $response->setData([
                    'result' => [
                        'success' => false,
                        'error' => 'Not Implemented'
                    ]
                ]);
                break;
        }

        if (isset($this->callbacks['after'])) {
            $this->callbacks['after']($queries, $body['data'], $body['files']);
        }

        $this->respond($response);
    }

    /**
     * Uses _POST and _FILES superglobals if available, otherwise tries to parse JSON body if Content Type header is set to application/json, otherwise manually parses body as form data
     *
     * @return array associative array with data and files ['data' => ?, 'files' => ?]
     */
    private function parseBody()
    {
        $data = null;
        $files = null;

        if (!empty($_POST)) {
            $data = $_POST;
        }

        if (!empty($_FILES)) {
            $files = $_FILES;
        }

        //In case of PUT request or request with json body
        if ($data === null) {
            if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $input = file_get_contents('php://input');

                $data = json_decode($input, true);
            } else {
                $stream = [];
                new stream($stream);

                $data = $stream['post'];
                $files = $stream['file'];
            }
        }

        return [
            'data' => $data,
            'files' => $files
        ];
    }

    /**
     * Check whether client is authorized and returns Response object with autorization request if not
     *
     * @return mixed Response object if client is not authorized, otherwise nothing
     */
    private function verifyAuthentication()
    {
        $authenticated = false;
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $token = str_replace('Token ', '', $headers['Authorization']);

            $authenticated = token::verify($token);
        }

        if ($authenticated === false) {
            $response = new Response();
            $response->setStatus(401, 'Unauthorized')
                ->addHeaders('WWW-Authenticate: Token');

            return $response;
        }
        return true;
    }

    /**
     * Use Response object to modify headers and output body
     *
     * @param Response $response
     *
     * @throws HttpException
     */
    private function respond(Response $response)
    {
        $status = $response->getStatus();

        if ($status['code'] !== 200) {
            throw new HttpException($status['code'], $status['status']);
        }

        foreach ($response->getHeaders() as $header) {
            header($header);
        }

        Yii::$app->response->content = $response->getBody();
    }
}
