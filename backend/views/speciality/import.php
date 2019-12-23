<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\web\View;



use backend\assets\ImportAsset;




$this->title = 'Import';
$this->params['breadcrumbs'][] = ['label' => 'Import', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$cat_array=array();
foreach($categories as $category){
    $cat_array[$category->id]=$category->label;
}
?>
<div class="user-import-create">
    <div class="user-import-form">
        <div class="col-sm-12">
            <?php if (Yii::$app->session->getFlash('error')) { ?>
                <p class="alert alert-error">
                    <?= Yii::$app->session->getFlash('error'); ?>
                </p>
            <?php } ?>
            <?php if (Yii::$app->session->getFlash('success')) { ?>
                <p class="alert alert-success">
                    <?= Yii::$app->session->getFlash('success'); ?>
                </p>
            <?php } ?>
        </div>
        <?php $form = ActiveForm::begin(['options'=>['enctype'=>'multipart/form-data']]);?>

        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($modelImport,'type')->dropDownList($cat_array,
                    ['prompt' => Yii::t('db', 'Type')]); ?>
            </div>
        </div>

        <?= $form->field($modelImport,'fileImport')->fileInput() ?>

        <?= Html::submitButton('Import',['class'=>'btn btn-primary']);?>
        <?php ActiveForm::end();?>

        <?php if(!empty($logs)){
            //echo "<pre>"; print_r($logs);
        }?>
    </div>
</div>
