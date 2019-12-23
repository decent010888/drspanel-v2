<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\SliderImageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Slider Images';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-body">
        <div class="slider-image-index">

            <h1><?= Html::encode($this->title) ?></h1>
            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

            <p>
                <?= Html::a('Create Slider Image', ['create'], ['class' => 'btn btn-success']) ?>
            </p>

            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'options' => [
                    'class' => 'grid-view table-responsive '
                ],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'title',
                    'sub_title',
                    'pages',
                    'image',
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
                 //   'description:ntext',
                    //'status',
                    //'created_at',
                    //'updated_at',
                    //'deleted_at',
                    

                    ['class' => 'yii\grid\ActionColumn'],
                ],
            ]); ?>
        </div>
    </div>
</div>
