<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Cities;

/**
 * CitiesSearch represents the model behind the search form of `common\models\Cities`.
 */
class CitiesSearch extends Cities
{
    public $state;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'state_id'], 'integer'],
            [['code', 'name', 'status', 'lat', 'lng','state'], 'safe'],
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
    public function search($params)
    {
        $query = Cities::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $query->joinWith(['states']);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions

        $query->andFilterWhere([
            'id' => $this->id,
            'cities.state_id' => $this->state_id,
            'cities.status'=>$this->status
        ]);


        $query->andFilterWhere(['like', 'cities.name', trim($this->name)])
            ->andFilterWhere(['like', 'cities.code', trim($this->code)])
            ->andFilterWhere(['like', 'states.name', trim($this->state)]);


        return $dataProvider;
    }
}
