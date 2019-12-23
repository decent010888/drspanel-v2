<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\SliderImage */

$this->title = 'Update Slider Image: ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => 'Slider Images', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
	<div class="box">
    	<div class="box-body">
			<div class="slider-image-update">

			    <h1><?= Html::encode($this->title) ?></h1>

			    <?= $this->render('_form', [
			        'model' => $model,'popularCity'=>$popularCity,'cities'=>$cities
			    ]) ?>

			</div>
		</div>
	</div>
