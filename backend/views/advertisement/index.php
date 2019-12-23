<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel common\models\AdvertisementSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Advertisements';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-body">
        <div class="advertisement-index">
            <p>
                <?= Html::a('Create Advertisement', ['create'], ['class' => 'btn btn-success']) ?>
            </p>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],

                    //'id',
                    'title',
                    'link',
                    [
                        'attribute' => 'start_date',
                        'filter' => \yii\jui\DatePicker::widget(['dateFormat' => 'yyyy-MM-dd','name'=>'AdvertisementSearch[start_date]']),
                        'value' => function($data) {
                            if($data->created_at !==NULL){
                                return Yii::$app->formatter->asDate($data->start_date, 'php:Y M d');
                            }else{
                                return $data->start_date;
                            }
                        },
                    ],

                    [
                        'attribute' => 'end_date',
                        'filter' => \yii\jui\DatePicker::widget(['dateFormat' => 'yyyy-MM-dd','name'=>'AdvertisementSearch[end_date]']),
                        'value' => function($data) {
                            if($data->created_at !==NULL){
                                return Yii::$app->formatter->asDate($data->end_date, 'php:Y M');
                            }else{
                                return $data->end_date;
                            }
                        },
                    ],
                    //'show_for_seconds',
                    //'image_path',
                    //'image_base_url:url',
                    //'status',
                    //'sequence',
                    //'created_at',
                    //'updated_at',

                    ['class' => 'yii\grid\ActionColumn'],
                ],
            ]); ?>
        </div>
    </div>
</div>