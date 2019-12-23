<?php
$baseUrl=Yii::getAlias('@frontendUrl');
use common\components\DrsPanel;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use yii\helpers\Html;

?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4 ><?php echo $type ?> Service Charge</span></h4>
        </div>
        <div class="modal-body" id="updatereminder">

                <?php $form = ActiveForm::begin(['id' => 'service-form','options' => ['enableAjaxValidation' => true]]); ?>

                <?= $form->field($service_log, 'id')->hiddenInput(['maxlength' => true])->label(false); ?>

                <?= $form->field($service_log, 'user_id')->hiddenInput(['maxlength' => true])->label(false); ?>
                <?= $form->field($service_log, 'address_id')->hiddenInput(['maxlength' => true])->label(false); ?>

                <?php echo $form->field($service_log, 'charge')->Input('number',['min'=>'0.00',
                    'placeholder' => Yii::t('db','Service Charge'),'readonly'=>false]); ?>

                <?php echo $form->field($service_log, 'charge_discount')->Input('number',['min'=>'0.00',
                    'placeholder' => Yii::t('db','Service Discount Charge'),'readonly'=>false]); ?>


                <div  style="padding-top: 15px"></div>
                <div class="btdetialpart form-group">
                        <div class="submitbtn pull-left reminder_add">
                            <?php echo Html::submitButton( $type, ['id'=>'add-update-reminder','name' => 'add-update-reminder','class' => 'confirm-theme']); ?>
                        </div>
                    <div class="pull-right reminder_cancel">
                        <a href="javascript:void(0)" class="confirm-theme" data-dismiss="modal">Cancel</a>

                    </div>
                </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
</div>
