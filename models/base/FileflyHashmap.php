<?php
// This class was automatically generated by a giiant build task
// You should not change it manually as it will be overwritten on next build

namespace hrzg\filefly\models\base;

use Yii;

/**
 * This is the base-model class for table "filefly_hashmap".
 *
 * @property integer $id
 * @property string $component
 * @property string $path
 * @property string $access_domain
 * @property integer $access_owner
 * @property string $access_read
 * @property string $access_update
 * @property string $access_delete
 * @property string $created_at
 * @property string $updated_at
 */
abstract class FileflyHashmap extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'filefly_hashmap';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['component', 'path'], 'required'],
            [['access_owner'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['component'], 'string', 'max' => 45],
            [['path'], 'string', 'max' => 745],
            [['access_domain', 'access_read', 'access_update', 'access_delete'], 'string', 'max' => 255],
            [
                ['component', 'path', 'access_domain', 'access_owner'],
                'unique',
                'targetAttribute' => ['component', 'path', 'access_domain', 'access_owner'],
                'message'         => 'The combination of Component, Path, Domain and Owner has already been taken.'
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => Yii::t('app', 'ID'),
            'component'     => Yii::t('app', 'Component'),
            'path'          => Yii::t('app', 'Path'),
            'access_domain' => Yii::t('app', 'Access Domain'),
            'access_owner'  => Yii::t('app', 'Access Owner'),
            'access_read'   => Yii::t('app', 'Access Read'),
            'access_update' => Yii::t('app', 'Access Update'),
            'access_delete' => Yii::t('app', 'Access Delete'),
            'created_at'    => Yii::t('app', 'Created At'),
            'updated_at'    => Yii::t('app', 'Updated At'),
        ];
    }
}
