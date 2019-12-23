<?php
namespace backend\models;

use common\models\User;
use common\models\Groups;
use common\models\UserProfile;
use yii\base\Exception;
use yii\base\Model;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Create user form
 */
class UserForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $status;
    public $name;
    public $phone;
    public $type;
    public $groupid;
    private $model;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'filter', 'filter' => 'trim'],
            [['name','groupid'], 'required'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            ['email', 'filter', 'filter' => 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'unique', 'targetClass'=> User::className(), 'filter' => function ($query) {
                if (!$this->getModel()->isNewRecord) {
                    $query->andWhere(['not', ['id'=>$this->getModel()->id]]);
                }
            }],

            ['password', 'required', 'on' => 'create'],
            ['password', 'string', 'min' => 6],

            [['phone'], 'string', 'max' => 45],
            ['phone','is10NumbersOnly'],



            [['status','groupid'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => Yii::t('common', 'Username'),
            'email' => Yii::t('common', 'Email'),
            'status' => Yii::t('common', 'Status'),
            'password' => Yii::t('common', 'Password'),
            'groupid' => Yii::t('common', 'Roles')
        ];
    }

    public function is10NumbersOnly($attribute)
    {
        if (!preg_match('/^[0-9]{10}$/', $this->$attribute)) {
            $this->addError($attribute, 'Phone number exactly 10 digits.');
        }else{
            $id=$this->getModel()->id;
            $isExists=User::checkMobileNumber($this->$attribute,[Groups::GROUP_ADMIN,Groups::GROUP_MANAGER,
                Groups::GROUP_PATIENT,Groups::GROUP_DOCTOR,
                Groups::GROUP_HOSPITAL,Groups::GROUP_ATTENDER],$id);
            if($isExists){
                $this->addError($attribute, 'This phone number has already been taken.');
            }
        }
    }

    /**
     * @param User $model
     * @return mixed 
     */
    public function setModel($model)
    {
        $this->username = $model->username;
        $this->email = $model->email;
        $this->status = $model->status;
        $this->name = $model['userProfile']['name'];
        $this->phone = $model->phone;
        $this->password = $model->password;
        $this->status = $model->status;
        $this->groupid = $model->groupid;
        $this->model = $model;

        return $this->model;
    }

    /**
     * @return User
     */
    public function getModel()
    {
        if (!$this->model) {
            $this->model = new User();
        }
        return $this->model;
    }

    /**
     * Signs user up.
     * @return User|null the saved model or null if saving fails
     * @throws Exception
     */
    public function save(){
        if ($this->validate()) {
            $model = $this->getModel();
            $isNewRecord = $model->getIsNewRecord();
            if ($isNewRecord) {
                $model->username = $this->email;
            }
            $model->email = $this->email;
            $model->status = $this->status;
            $model->phone = $this->phone;
            $model->groupid=$this->groupid;
            if($this->groupid == Groups::GROUP_ADMIN){
                $model->role = 'Admin';
            }
            elseif($this->groupid == Groups::GROUP_MANAGER){
                $model->role = 'SubAdmin';
            }
            else{
                $model->role = 'SubAdmin';
            }

            if ($this->password) {
                $model->setPassword($this->password);
                $model->password=$this->password;
            }
            if (!$model->save()) {
                throw new Exception('Model not saved');
            }

            $profileData=array();
            $profileData['name'] = $this->name;
            $profileData['groupid'] = $this->groupid;
            if(isset($this->gender)){
                $profileData['gender'] = $this->gender;
            }
            else{
                $profileData['gender'] = 3;
            }
            $profileData['email'] = $this->email;
            if(isset($this->dob)){
                $profileData['dob'] = $this->dob;
            }
            else{
                $profileData['dob'] = '';
            }

            if(isset($this->blood_group)){
                $profileData['blood_group'] = $this->blood_group;
            }
            else{
                $profileData['blood_group'] = '';
            }

            if ($isNewRecord) {
                $model->afterSignup($profileData);

                $auth = Yii::$app->authManager;
                $auth->revokeAll($model->getId());

                // Default role
                if($profileData['groupid'] == Groups::GROUP_ADMIN){
                    $auth = Yii::$app->authManager;
                    $auth->assign($auth->getRole(User::ROLE_ADMINISTRATOR), $model->getId());
                }
                elseif($profileData['groupid'] == Groups::GROUP_MANAGER){
                    $auth = Yii::$app->authManager;
                    $auth->assign($auth->getRole(User::ROLE_MANAGER), $model->getId());
                }
            }
            else{

                $profile = UserProfile::findOne(['user_id'=> $model->getId()]);
                $profile->email=$this->email;
                $profile->groupid=$this->groupid;
                $profile->name=$this->name;
                $profile->save();

                $auth = Yii::$app->authManager;
                $auth->revokeAll($model->getId());

                // Default role
                if($profileData['groupid'] == Groups::GROUP_ADMIN){
                    $auth = Yii::$app->authManager;
                    $auth->assign($auth->getRole(User::ROLE_ADMINISTRATOR), $model->getId());
                }
                elseif($profileData['groupid'] == Groups::GROUP_MANAGER){
                    $auth = Yii::$app->authManager;
                    $auth->assign($auth->getRole(User::ROLE_MANAGER), $model->getId());
                }
            }


            return !$model->hasErrors();
        }
        return null;
    }

    public function setUserName($email){

        $username=explode('@',$email); 
        $username=$username[0]; 
        $count=User::find()->andWhere(['username'=>$username])->count();
        if($count>0){
            $username=$username.'_'.$count+1;
        }
        return $username;
    }
}
