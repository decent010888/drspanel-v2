<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "patient_members_record".
 *
 * @property int $id
 * @property int $files_id
 * @property int $member_id
 * @property int $created_at
 * @property int $updated_at
 */
class PatientMemberRecords extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'patient_member_records';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['files_id', 'member_id'], 'required'],
            [['files_id', 'member_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'files_id' => 'Files ID',
            'member_id' => 'Member ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     * @return PatientMemberRecordsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PatientMemberRecordsQuery(get_called_class());
    }
}
