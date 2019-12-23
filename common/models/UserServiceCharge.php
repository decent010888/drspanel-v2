<?php

namespace common\models;

use common\models\query\UserServiceChargeQuery;
use yii\behaviors\TimestampBehavior;
use Yii;

/**
 * This is the model class for table "user_service_charge".
 *
 * @property int $id
 * @property int $user_id
 * @property int $address_id
 * @property double $charge
 * @property int $created_at
 * @property int $updated_at
 */
class UserServiceCharge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_service_charge';
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
    public function rules()
    {
        return [
            [['user_id', 'address_id','charge'], 'required'],
            [['user_id', 'address_id'], 'integer'],
            [['charge','charge_discount'], 'number'],
            /*[['charge'], 'compare', 'compareAttribute'=>'charge_discount',
                'operator'=>'>', 'skipOnEmpty'=>true,
                'message'=>'{attribute} must be greater than {compareValue}'],*/
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
            'address_id' => 'Address ID',
            'charge' => 'Service Charge',
            'charge_discount' => 'Discounted Service Charge',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     * @return UserServiceChargeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UserServiceChargeQuery(get_called_class());
    }
}
