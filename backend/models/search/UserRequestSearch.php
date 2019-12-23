<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\UserRequest;

/**
 * UserRequestSearch represents the model behind the search form about `common\models\UserRequest`.
 */
class UserRequestSearch extends UserRequest
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'groupid', 'request_from', 'request_to', 'status', 'created_at', 'updated_at'], 'integer'],
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
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params,$ids){
        $query = UserRequest::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $query->joinWith(['userProfile']);

        if(count($ids)>0){
            $query->andWhere(['request_to'=>$ids]);
        }

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }


        $query->andFilterWhere([
            'id' => $this->id,
            'groupid' => $this->groupid,
            'request_from' => $this->request_from,
            'status' => $this->status,
        ]);

        return $dataProvider;
    }
}
