<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model backend\models\search\UserSearch */
/* @var $form yii\bootstrap\ActiveForm */
$baseurl = \Yii::$app->getUrlManager()->getBaseUrl();

$this->registerCssFile($baseurl . '/css/search.css');
$get = Yii::$app->request->get();
$fromdate = $todate = '';
if (isset($get['fromdate'])) {
    $fromdate = $get['fromdate'];
}
if (isset($get['todate'])) {
    $todate = $get['todate'];
}
?>
<div class="attender-search">

    <?php
    $form = ActiveForm::begin([
                'action' => ['index'],
                'method' => 'get',
                'options' => ['class' => 'form-horizontal searchlist',],
                'fieldConfig' =>
                [
                    'template' => "<div class=\"col-lg-4\">{label}</div>\n<div class=\"col-lg-8\">{input}</div>",
                ],
    ]);
    ?>


    <div class="form-group field-doctorsearch-name">
        <div class="col-lg-4"><label class="control-label" for="fromdate">From Date</label></div>
        <div class="col-lg-8"><?php
            echo DatePicker::widget([
                'name' => 'fromdate',
                'type' => DatePicker::TYPE_INPUT,
                'value' => $fromdate,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'dd-m-yyyy'
                ]
            ]);
            ?></div>
    </div>

    <div class="form-group field-doctorsearch-name">
        <div class="col-lg-4"><label class="control-label" for="fromdate">To Date</label></div>
        <div class="col-lg-8"><?php
            echo DatePicker::widget([
                'name' => 'todate',
                'type' => DatePicker::TYPE_INPUT,
                'value' => $todate,
                'pluginOptions' => [
                    'autoclose' => true,
                    'format' => 'dd-m-yyyy'
                ]
            ]);
            ?></div>
    </div>
    <div class="form-group bsubmit">
        <?php echo Html::submitButton(Yii::t('backend', 'Search'), ['class' => 'btn btn-primary submit']) ?>
        <?php //echo Html::submitButton(Yii::t('backend', 'Delete All'), ['class' => 'btn btn-danger delete', 'name' => 'delete', 'value' => 'yes']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<div class="clear"></div>
