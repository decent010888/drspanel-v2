<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\MetaValues;

/**
 * MetaValuesSearch represents the model behind the search form of `common\models\MetaValues`.
 */
class MetaValuesSearch extends MetaValues {

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'key', 'status', 'created_at', 'updated_at'], 'integer'],
            [['label', 'value', 'parent_key'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios() {
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
    public function search($params, $key = 0) {
        if ($key > 0) {
            $query = MetaValues::find()->where(['key' => $key, 'is_deleted' => 0]);
        } else {
            $query = MetaValues::find();
        }
        
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'key' => $this->key,
            'parent_key' => $this->parent_key,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'label', trim($this->label)])
                ->andFilterWhere(['like', 'value', trim($this->value)]);

        return $dataProvider;
    }

}
