<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>


<div class="login-section-record">
    <div class="modal fade model_opacity show" id="EditRecord" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <div class="modal-header text-center">
                    <h4 class="modal-title w-100 ">Share Record</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body mx-3">
                    <?php
                     $form = ActiveForm::begin(['id' => 'patient-member-share', 'enableAjaxValidation'=>true,'options'=>['enctype'=>'multipart/form-data']]);?>
                    <?php echo $form->field($modelShare, 'member_id')->hiddenInput()->label(false); ?>
                    <div class="file_row clearfix">
                        <?php echo $form->field($modelShare, 'phone')->textInput(['placeholder'=>'Mobile Number','maxlength' => 10])->label(false); ?>
                    </div>
                    <div class="file_row clearfix">
                        <div class="submitbtn text-center">
                            <?php echo Html::submitButton(Yii::t('frontend', 'Share Record'), ['class' => 'add_record_btn', 'name' => 'signup-button']) ?>
                        </div>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>