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
            [['txn_date'], 'safe'],
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
        if(isset($params['delete']) && $params['delete'] == 'yes') {
            
        }
        $query = Transaction::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $query->joinWith(['userAppointment']);
        $query->Where(['user_appointment.id' => $this->appointment_id]);
        $query->Where(['transaction.type' => 'refund']);

        $fromdate = date('Y-m-d', strtotime($params['fromdate']));

        $todate = date('Y-m-d', strtotime($params['todate']));
        $query->andFilterWhere(['between', 'txn_date', $fromdate, $todate]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }


        return $dataProvider;
    }

}
