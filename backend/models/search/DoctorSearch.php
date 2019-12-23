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
class DoctorSearch extends User
{
    public $name;
    public $speciality;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['name', 'auth_key', 'password_hash', 'email','phone','admin_status','created_at', 'updated_at', 'logged_at','user_plan','speciality'], 'safe'],
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
        $query->Where(['user_profile.groupid' => Groups::GROUP_DOCTOR]);
        /*if($logined->role=='SubAdmin'){
            $query->andWhere(['user.admin_user_id' => $logined->id]);
        }*/

        $dataProvider->setSort([
            'attributes' => [
                'id',
                'name',
                'email',
                'phone',
                'speciality',
                'status',
                'admin_status',
                'user_plan',
                'created_at'
            ]
        ]);
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status
        ]);

        if(!empty($this->admin_status) && $this->admin_status != '0'){
            $query->andWhere(['admin_status'=>$this->admin_status]);
        }

        $query->andFilterWhere(['like', 'user.phone', trim($this->phone)])
            ->andFilterWhere(['like', 'name', trim($this->name)])
            ->andFilterWhere(['like', 'user_profile.speciality', trim($this->speciality)])
            ->andFilterWhere(['like', 'user.email', trim($this->email)]);

        return $dataProvider;
    }

    public function linkedDoctors($params,$ids=[],$groupid){
        $query = User::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        $query->andWhere(['user.groupid'=>$groupid]);
        $query->joinWith(['userProfile']);
        $query->andWhere(['user_profile.groupid' => $groupid]);

        $dataProvider->setSort([
            'attributes' => [
                'id' => [
                    'desc' => ['id' => SORT_DESC],
                    'label' => 'ID',
                    'default' => SORT_DESC
                ],
                'name',
                'status',
                'email',
                'phone',
            ]
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }


        if(count($ids)>0){
            $query->andWhere(['user.id'=>$ids]);
        }


        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'user.phone', trim($this->phone)])
            ->andFilterWhere(['like', 'name', trim($this->name)])
            ->andFilterWhere(['like', 'user.email', trim($this->email)]);


           


         return $dataProvider;
    }
}
