<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

class UserNotification extends ActiveRecord {

    const TYPE_APPOINTMENT='appointment';
    const TYPE_REMINDER='reminder';
    /**
     * @inheritdoc
     */
    public static function tableName() {
        return '{{%user_notification}}';
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
    public function rules() {
        return [
            [['sender_id', 'receiver_id', 'message', 'type'], 'required'],
            [['read_status'],'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => Yii::t('common', 'ID'),
            'type' => Yii::t('common', 'Type'),
            'sender_id' => Yii::t('common', 'Sender'),
            'receiver_id' => Yii::t('common', 'Receiver'),
            'message' => Yii::t('common', 'Message'),
            'created_at' => Yii::t('common', 'Created at'),
        ];
    }

    public static function getUserNotification($userID) {
        return self::find()->where(['sender_id' => $userID])->orWhere(['receiver_id' => $userID])->all();
    }

}
