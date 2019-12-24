<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\grid\EnumColumn;
use common\models\MetaValues;
use common\models\UserAppointment;


/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\MetaValuesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Refund Status';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-body">
        <div class="meta-values-index">
            <?php echo $this->render('_search', ['model' => $searchModel]); ?>
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute' => 'appointment_id',
                        'label' => 'Appointment',
                        'value' => function($data) {
                            $appName = UserAppointment::findOne($data->appointment_id);
                            return 'Shift : ' . $appName['shift_label'] . '<br> Shift Time : ' . $appName['shift_name'];
                        },
                        'format' => 'html'
                    ],
                    [
                        'attribute' => 'appointment_id',
                        'label' => 'Doctor',
                        'value' => function($data) {
                            $userName = UserAppointment::findOne($data->appointment_id);
                            return $userName['doctor_name'];
                        }
                    ],
                    [
                        'attribute' => 'user_id',
                        'label' => 'Patient',
                        'value' => function($data) {
                            $userName = UserAppointment::findOne($data->appointment_id);
                            return $userName['user_name'];
                        }
                    ],
                    'originate_date',
                    [
                        'attribute' => 'refund_by',
                        'label' => 'Refund by',
                        'value' => function($data) {
                            return $data->refund_by;
                        }
                    ],
                ],
            ]);
            ?>
        </div>
    </div>
</div>