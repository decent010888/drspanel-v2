<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "user_verification".
 *
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string $phone
 * @property string $countrycode
 * @property string $otp
 * @property int $mobile_verified
 * @property int $email_verified
 * @property int $created_at
 * @property int $updated_at
 */
class UserVerification extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_verification';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'otp'], 'required'],
            [['user_id', 'mobile_verified', 'email_verified'], 'integer'],
            [['email'], 'string', 'max' => 255],
            [['phone'], 'string', 'max' => 45],
            [['countrycode'], 'string', 'max' => 11],
            [['email','phone','countrycode'],'safe'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'email' => 'Email',
            'phone' => 'Phone',
            'countrycode' => 'Countrycode',
            'otp' => 'Otp',
            'mobile_verified' => 'Mobile Verified',
            'email_verified' => 'Email Verified',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     * @return UserVerificationQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserVerificationQuery(get_called_class());
    }
}
