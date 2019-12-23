<?php

namespace backend\models\search;

use common\models\Groups;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Transaction;

/**
 * UserSearch represents the model behind the search form about `common\models\User`.
 */
class RefundSearch extends Transaction {

    public $name;
    public $speciality;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['id', 'status'], 'integer'],
            [['txn_date','shift_name'], 'safe'],
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
     * @return ActiveDataProvider
     */
    public function search($params) {
        
        $query = Transaction::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            
        ]);
       
        $query->joinWith(['userAppointment']);
        $query->Where(['user_appointment.id' => $this->appointment_id]);
        $query->Where(['transaction.type' => 'refund']);
        
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'transaction.id' => $this->id,
            'transaction.status' => $this->status
        ]);

        $query->andFilterWhere(['like', 'transaction.refund_by', trim($this->refund_by)])
                ->andFilterWhere(['like', 'transaction.txn_date', trim($this->txn_date)]);
        
        return $dataProvider;
    }

}
