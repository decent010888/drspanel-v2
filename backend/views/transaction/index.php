<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\TransactionSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Transactions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-body">
<div class="transaction-index">

    <?php echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            //'id',
            'type',
            'txn_type',
            'user_id',
            'appointment_id',
            // 'temp_appointment_id',
            // 'payment_type',
            // 'base_price',
            // 'cancellation_charge',
            // 'txn_amount',
            // 'originate_date',
            // 'txn_date',
            // 'paytm_response:ntext',
            // 'status',
            // 'created_at',
            // 'updated_at',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
    </div>
</div>
