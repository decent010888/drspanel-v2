<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_plan_detail_log".
 *
 * @property int $id
 * @property int $user_plan_detail_id
 * @property int $user_id
 * @property int $from_date
 * @property int $to_date
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 */
class UserPlanDetailLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_plan_detail_log';
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
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_plan_detail_id', 'user_id', 'from_date', 'to_date'], 'required'],
            [['user_plan_detail_id', 'user_id'], 'integer'],
            [['status'], 'string'],
            [['from_date', 'to_date'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_plan_detail_id' => 'User Plan Detail ID',
            'user_id' => 'User ID',
            'from_date' => 'From Date',
            'to_date' => 'To Date',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     * @return UserPlanDetailLogQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserPlanDetailLogQuery(get_called_class());
    }
}
