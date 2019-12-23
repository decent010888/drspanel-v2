<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;


/* @var $this yii\web\View */
/* @var $model common\models\SliderImage */
/* @var $form yii\widgets\ActiveForm */

foreach ($cities as $h_key=>$city) {

    foreach ($popularCity as $key => $value) {

        $dataValue = explode(',', $value['value']);

        foreach ($dataValue as  $valueData) {

            if($valueData == $city->name)
            {
                // unset($treatment->value);
                // unset($treatment->label);
            }

        }
    }
    $city_list[$city->name] = $city->name;
}

?>

<div class="slider-image-form">

    <?php $form = ActiveForm::begin(); ?>

        <?php echo  $form->field($model, 'city')->widget(Select2::classname(),
            [
                'data' => $city_list,
                'size' => Select2::SMALL,
                'options' => ['placeholder' => 'Select cities ...', 'multiple' => true],

            ]); ?>

    <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'sub_title')->textInput() ?>

    <?= $form->field($model, 'link')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'pages')->dropDownList(['Home'=>'Home','Event'=>'Event']); ?>
    
   <?= $form->field($model, 'image')->fileInput([
              'options' => ['accept' => 'image/*'],
            'maxFileSize' => 5000000, // 5 MiB
               
          ]);   ?>
    <?php if($model->image){  ?>
    <div class="edit-image" style="margin-left: 200px;margin-top: -70px;">
    <img  src="<?php echo Yii::getAlias('@storageUrl/source/slider-images/').$model->image; ?>" width="75" height="75"/>
    </div>
    <?php } ?>

    <?= $form->field($model, 'app_image')->fileInput([
        'options' => ['accept' => 'image/*'],
        'maxFileSize' => 5000000, // 5 MiB

    ]);   ?>
    <?php if($model->app_image){  ?>
        <div class="edit-image" style="margin-left: 200px;margin-top: -70px;">
            <img  src="<?php echo Yii::getAlias('@storageUrl/source/slider-images/').$model->app_image; ?>" width="75" height="75"/>
        </div>
    <?php } ?>

    <div class="col-sm-12">
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <?= $form->field($model, 'start_date')->textInput()->widget(
                        DatePicker::className(), [
                        'convertFormat' => true,
                        'options' => ['placeholder' => 'Show From'],
                        'layout'=>'{input}{picker}',
                        'pluginOptions' => [
                            'autoclose'=>true,
                            'format' => 'yyyy-MM-dd',
                            'endDate' => date('Y-m-d'),
                            'todayHighlight' => true
                        ],])->label('Show From'); ?>

                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <?= $form->field($model, 'end_date')->textInput()->widget(
                        DatePicker::className(), [
                        'convertFormat' => true,
                        'options' => ['placeholder' => 'Show Till'],
                        'layout'=>'{input}{picker}',
                        'pluginOptions' => [
                            'autoclose'=>true,
                            'format' => 'yyyy-MM-dd',
                            'startDate' => date('Y-m-d'),
                            'todayHighlight' => true
                        ],])->label('Show Till'); ?>

                </div>
            </div>
        </div>
    </div>

    <?= $form->field($model, 'status')->dropDownList(['0'=>'Not Active','1'=>'Active']); ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
