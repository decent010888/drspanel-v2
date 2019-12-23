<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\ContactUs */

$this->title = 'Update Contact Us: ' . ' ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Contact uses', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="contact-us-update">

    <?php echo $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
