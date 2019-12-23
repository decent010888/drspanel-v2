<?php

namespace backend\models\search;

use common\models\Groups;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\User;


/**
 * UserSearch represents the model behind the search form about `common\models\User`.
 */
class PatientSearch extends User
{
    
    public $name;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['name', 'auth_key', 'password_hash', 'email','created_at', 'updated_at', 'logged_at','phone'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => 'Name'
        ];
    }

    /**
     * Creates data provider instance with search query applied
     * @return ActiveDataProvider
     */
    public function search($params,$logined){
        $query = User::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        $query->joinWith(['userProfile']);
        $query->Where(['user_profile.groupid' => Groups::GROUP_PATIENT]);

        if($logined->role=='SubAdmin'){
            $query->andWhere(['user.admin_user_id' => $logined->id]);
        }

        $dataProvider->setSort([
            'attributes' => [
                'id' ,
                'name',
                'email',
                'phone',
                'status',
                'created_at',
                'logged_at'
            ]
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }


        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);
            
        $query->andFilterWhere(['like', 'user.phone', trim($this->phone)])
            ->andFilterWhere(['like', 'name', trim($this->name)])
            ->andFilterWhere(['like', 'user.email', trim($this->email)]);

        if (!empty($this->created_at)) {
            $start_date = strtotime($this->created_at . " +00 hour +00 minutes +00 seconds") . "<br>";
            $end_date = strtotime($this->created_at . " +23 hour +59 minutes +00 seconds");
            $query->andFilterWhere(['between', 'user.created_at', $start_date, $end_date]);
            $this->created_at = null;
        }
        if (!empty($this->logged_at)) {
            $start_date = strtotime($this->logged_at . " +00 hour +00 minutes +00 seconds") . "<br>";
            $end_date = strtotime($this->logged_at . " +23 hour +59 minutes +00 seconds");
            $query->andFilterWhere(['between', 'user.logged_at', $start_date, $end_date]);
            $this->logged_at = null;
        }

        

        return $dataProvider;
    }
}
