<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "patient_member_files".
 *
 * @property int $id
 * @property string $image_base_url
 * @property string $image_path
 * @property string $image
 * @property int $created_at
 * @property int $updated_at
 */
class PatientMemberFiles extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'patient_member_files';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),

        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['image_name','image'], 'required'],
            [['image_base_url', 'image_path', 'image'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'image_base_url' => 'Image Base Url',
            'image_path' => 'Image Path',
            'image' => 'Image',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @inheritdoc
     * @return PatientMemberFilesQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PatientMemberFilesQuery(get_called_class());
    }
}
