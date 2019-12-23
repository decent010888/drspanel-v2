<?php

namespace common\models;

use Yii;
use common\models\User;
use yii\behaviors\TimestampBehavior;
/**
 * This is the model class for table "user_address".
 *
 * @property int $id
 * @property string $type
 * @property int $user_id
 * @property string $name
 * @property string $address
 * @property string $city
 * @property string $state
 * @property string $country
 * @property string $lat
 * @property string $lng
 * @property int $created_at
 * @property int $updated_at
 * @property string $image
 */
class UserAddress extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_address';
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

    

    public function rules()
    {
        return [
            [['type'], 'string'],
            [['user_id','name', 'address', 'city_id', 'state','area'], 'required'],
            ['phone', 'filter', 'filter' => 'trim'],
            //['phone','is10NumbersOnly'],
            [['phone'], 'integer'],
            [['phone'], 'string', 'min' => 10],
           // ['landline','is12NumbersOnly'],
            [['landline'], 'integer'],
            [['landline'], 'string','min'=>7],
            [['user_id','is_register'], 'integer'],
            [['city'],'safe'],
            [['address', 'city', 'state', 'country','area','image_base_url','image_path','name'], 'string', 'max' => 255],
            [['image'], 'file', 'extensions' => ['png', 'jpg', 'gif','jpeg'],'maxFiles' => 8],
            [[ 'lat', 'lng'], 'string', 'max' => 45],
            [['phone', 'landline'], 'compositeRequired', 'skipOnEmpty' => false,],
        ];
    }

    /**
     * @inheritdoc
     */
     public function is10NumbersOnly($attribute){
        if (!preg_match('/^[0-9]{10}$/', $this->$attribute)) {
            $this->addError($attribute, 'Phone number should be exactly 10 digits.');
        }
    }

    /**
     * @inheritdoc
     */
    public function compositeRequired($attribute_name, $params)
    {
        if (empty($this->phone) && empty($this->landline)) {
            $this->addError('phone', Yii::t('error', 'At least 1 of the field phone or landline number is required'));
            return false;
        }
        elseif(!empty($this->phone)){
            if (!preg_match('/^[0-9]{10}$/', $this->phone)) {
                $this->addError('phone', 'Phone number should be exactly 10 digits.');
            }
        }
        elseif(!empty($this->landline)){
            if (!preg_match('/^[0-9]{10}$|^[0-9]{11}$|^[0-9]{12}$/', $this->landline)) {
                $this->addError('landline', 'Landline number should be between 10-12 digits.');
            }
        }
        else{

        }

        return true;
    }


    /**
     * @inheritdoc
     */
     public function is12NumbersOnly($attribute){
        if (!preg_match('/^[0-9]{10,12}$/', $this->$attribute)) {
            $this->addError($attribute, 'Landline number should be not be greater than 12 digits.');
        }
    }


    /**
     * @inheritdoc
     */

    public function groupUniqueNumber($post){

        $phone=User::find()
            ->andWhere(['phone'=> $post['phone']])
            ->andWhere(['groupid'=> $post['groupid']])
            ->andWhere(['!=','id',$post['id']])
            ->one();
        return ($phone)?true:false;
    }
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'user_id' => 'User ID',
            'name' => 'Name',
            'address' => 'Address',
            'area' => 'Area',
            'city' => 'City',
            'city_id' => 'City',
            'state' => 'State',
            'state_id' => 'State',
            'country' => 'Country',
            'lat' => 'Lat',
            'lng' => 'Lng',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function linkedDoctors($user_id){
        return UserAddress::find()->andWhere(['type'=>'RegHospital'])->andWhere(['status'=>1])->andWhere(['user_id'=>$user_id])->all();
    }

    public function requestToDoctors($user_id=null){
       return  User::find()
                ->andWhere(['groupid'=>4])
                ->joinWith('user_address as b')
                //->andWhere(['b.'])
                ->all();
    }
}
