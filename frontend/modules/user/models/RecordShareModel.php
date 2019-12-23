<?php
namespace frontend\modules\user\models;

use common\models\Groups;
use common\models\User;
use yii\base\Exception;
use yii\base\Model;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Create user form
 */
class RecordShareModel extends Model
{
    public $member_id;
    public $phone;


    private $model;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['member_id', 'required'],
            ['phone', 'required'],
            [['phone'], 'string', 'max' => 45],
            ['phone','is10NumbersOnly'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'member_id' => Yii::t('common', 'Member Id'),
            'phone' => Yii::t('common', 'Mobile Number'),
        ];
    }


    public function is10NumbersOnly($attribute)
    {
        if (!preg_match('/^[0-9]{10}$/', $this->$attribute)) {
            $this->addError($attribute, 'Phone number exactly 10 digits.');
        }else{
            $isExists=User::checkMobileNumber($this->$attribute,Groups::GROUP_PATIENT);
            if(!$isExists){
                $this->addError($attribute, 'This phone number is not registered with our system.');
            }
        }
    }

}
