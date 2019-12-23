<?php
namespace backend\models;

use yii\base\Model;
use common\models\User;
use Yii;

/**
 * Account form
 */
class ChangePasswordForm extends Model
{
    public $password_old;
    public $password;
    public $password_confirm;
    public $user_id;

    private $user;

    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['user_id','safe'],
            [['password_old','password','password_confirm'], 'required'],
            [['password_old','password','password_confirm'], 'string', 'min' => 6],
            [['password_old'],'findPasswords'],
            ['password', 'string'],
            [['password_confirm'], 'compare', 'compareAttribute' => 'password']
        ];
    }

    public function findPasswords($attribute, $params){
        $user = User::find()->where([
            'id'=>$this->user_id
        ])->one();

        if(!$user->validatePassword($this->password_old))
            $this->addError($attribute,'Old password is incorrect');

    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'password_old' => Yii::t('backend', 'Old Password'),
            'password' => Yii::t('backend', 'New Password'),
            'password_confirm' => Yii::t('backend', 'Password Confirm')
        ];
    }

    public function save()
    {
        if ($this->password) {
            $this->user->setPassword($this->password);
        }
        return $this->user->save();
    }
}
