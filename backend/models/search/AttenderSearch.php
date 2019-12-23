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
class AttenderSearch extends User
{
    public $name;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'created_at', 'updated_at', 'logged_at'], 'integer'],
            [['name', 'auth_key', 'password_hash', 'email','phone','admin_status'], 'safe'],
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
    public function search($params,$id)
    {
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
        $query->andWhere(['user.parent_id' => $id]);
        $query->andWhere(['user_profile.groupid' => Groups::GROUP_ATTENDER]);
       // $query->Where(['user_attender.groupid' => Groups::GROUP_ATTENDER]);

        $dataProvider->setSort([
            'attributes' => [
                'id',
                'name',
                'email',
                'phone',
                'status',
                'admin_status'
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
            ->andFilterWhere(['like', 'user.email', trim($this->email)]);

        return $dataProvider;
    }
    
}
