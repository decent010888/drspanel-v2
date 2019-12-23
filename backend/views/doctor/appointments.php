<?php

use common\grid\EnumColumn;
use common\models\UserAppointment;
use yii\helpers\Html;
use yii\grid\GridView;
use common\components\DrsPanel;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\UserAppointmentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Total Appointments for Doctor "'.$model->getPublicIdentity().'"';
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Doctors'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->getPublicIdentity(), 'url' => ['detail', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Appointments';
?>
<div class="box">
    <div class="box-body">
        <div class="user-appointment-index">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'options' => [
                    'class' => 'grid-view table-responsive'
                ],
                'columns' => [
                    'id',
                    'token',
                    'user_name',
                    'user_phone',
                    'book_for',

                    //'user_age',

                    //'user_address',
                    //'user_gender',
                    'payment_type',
                    [
                        'attribute'=>'transaction',
                        'label'=>'Transaction Id',
                        'value'=>function($data){
                            $getId=DrsPanel::getTransactionId($data->id);
                            return $getId;
                        }

                    ],
                    //'doctor_address',
                    'doctor_fees',
                    'status',
                    [
                        'attribute'=>'deleted_by',
                        'label'=>'Cancelled By',
                        'value'=>function($data){
                            if($data->status == UserAppointment::STATUS_CANCELLED){
                                if($data->deleted_by != '' || !empty($data->deleted_by)){
                                    return $data->deleted_by;
                                }
                                else{
                                    return '---';
                                }
                            }
                            else{
                                return '---';
                            }
                        }
                    ],

                    'updated_at:datetime',
                    //'updated_at',


                ],
            ]); ?>
        </div>
    </div>
</div>
