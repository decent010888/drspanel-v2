<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

use yii\web\View;

$this->registerJs(" 
     function readImageURL(input) {
      var fileTypes = ['jpg', 'jpeg', 'png'];
      if (input.files && input.files[0]) {
    
        var extension = input.files[0].name.split('.').pop().toLowerCase(),  //file extension from input file
          isSuccess = fileTypes.indexOf(extension) > -1;  //is extension in acceptable types
    
        if (isSuccess) { //yes
          var reader = new FileReader();
          reader.onload = function (e) {
            $('.profile-file-preview img')
              .attr('src', e.target.result);
            $('.profile-file-preview').css('display','block');
          }
    
          reader.readAsDataURL(input.files[0]);
        }
        else { //no
          alert('Please upload correct file');
        }
      }
}
", View::POS_END);
?>

<div class="advertisement-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="col-sm-12">
        <?php echo $form->field($model, 'type')->dropDownList($types) ?>
    </div>

    <div class="col-sm-12">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
    </div>
    <div class="col-sm-12">
        <?= $form->field($model, 'link')->textInput(['maxlength' => true]) ?>
    </div>

    <div class="col-sm-12">
        <?= $form->field($model, 'image')->fileInput([
            'options' => ['accept' => 'image/*'],
            'maxFileSize' => 5000000, // 5 MiB
            'onchange'=> "readImageURL(this);"
        ]);   ?>
        <?php if($model->image){  ?>
            <div class="edit-image" style="margin-left: 200px;margin-top: -50px;">
                <img  src="<?php echo $model->image; ?>" width="200" height="75"/>
            </div>
        <?php } else { ?>
            <div class="profile-file-preview" style="display:none; margin-left: 200px;margin-top: -50px; width: 200px;"><img class="file-preview-image" src="#" alt="your image" style="width: 100%"/></div>
        <?php }?>
    </div>

    <div class="col-sm-12">
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <?= $form->field($model, 'start_date')->textInput()->widget(
                        DatePicker::className(), [
                        'convertFormat' => true,
                        'options' => ['placeholder' => 'Show From'],
                        'layout'=>'{input}{picker}',
                        'pluginOptions' => [
                            'autoclose'=>true,
                            'format' => 'yyyy-MM-dd',
                            //'endDate' => date('Y-m-d'),
                            'todayHighlight' => true
                        ],])->label('Show From'); ?>

                </div>
            </div>
            <div class="col-sm-4">
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
            <div class="col-sm-4">
                <?= $form->field($model, 'show_for_seconds')->Input('number'); ?>
            </div>

            </div>
    </div>


    <div class="col-sm-12">
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'sequence')->Input('number') ?>

            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'status')->dropDownList([ 'active' => 'Active', 'inactive' => 'Inactive', ]) ?>

            </div>
        </div>
    </div>



    <div class="form-group clearfix col-sm-12">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
