<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Transaction;

/**
 * TransactionSearch represents the model behind the search form about `common\models\Transaction`.
 */
class TransactionSearch extends Transaction
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'appointment_id', 'temp_appointment_id', 'created_at', 'updated_at'], 'integer'],
            [['type', 'txn_type', 'payment_type', 'originate_date', 'txn_date', 'paytm_response', 'status'], 'safe'],
            [['base_price', 'cancellation_charge', 'txn_amount'], 'number'],
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
        $query = Transaction::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'appointment_id' => $this->appointment_id,
            'temp_appointment_id' => $this->temp_appointment_id,
            'base_price' => $this->base_price,
            'cancellation_charge' => $this->cancellation_charge,
            'txn_amount' => $this->txn_amount,
            'originate_date' => $this->originate_date,
            'txn_date' => $this->txn_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'txn_type', $this->txn_type])
            ->andFilterWhere(['like', 'payment_type', $this->payment_type])
            ->andFilterWhere(['like', 'paytm_response', $this->paytm_response])
            ->andFilterWhere(['like', 'status', $this->status]);

        return $dataProvider;
    }
}
