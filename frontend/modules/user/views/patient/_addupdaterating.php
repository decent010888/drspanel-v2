<?php
$baseUrl=Yii::getAlias('@frontendUrl');
use common\components\DrsPanel;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use yii\helpers\Html;
use kartik\rating\StarRating;

?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h3 ><?php echo $type ?> Rating</span></h3>
        </div>
        <div class="modal-body rating_div_update" id="updatereminder">
            <div class="col-md-12 mx-auto">
                <div class="pace-part main-tow mb-0">
                    <div class="row">
                        <div class="col-sm-12 clearfix">
                            <div class="pace-left">
                                <?php $image = DrsPanel::getUserAvator(isset($doctorData['doctor_id'])?$doctorData['doctor_id']:'');?>
                                <img src="<?php  echo $image; ?>" alt="image"/>
                            </div>
                            <div class="pace-right">
                                <h4><?php echo $doctorData['doctor_name']?></h4>
                                <p> <?php echo $doctorData['doctor_speciality'] ?></p>
                                <ul class="doctor_reminder doctor_reminder_edit">
                                    <li style="width:100%;">Appointment Date: <?= $doctorData['appointment_date'];?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="workingpart cls-1">
                    <div class="form-group clearfix">
                        <div class="pull-left">
                            <p> Token <span> <a href="#" class="roundone">  <?php echo $doctorData['token'];?> </a> </span> </p>
                        </div>
                        <div class="pull-right"> <a href="#" class="time-bg"><?php echo $doctorData['appointment_time']?> </a> </div>
                    </div>
                </div>
                <?php $form = ActiveForm::begin(['id' => 'raring-form','options' => ['enableAjaxValidation' => true]]); ?>

                <?= $form->field($rating_logs, 'id')->hiddenInput(['maxlength' => true])->label(false); ?>

                <?= $form->field($rating_logs, 'user_id')->hiddenInput(['maxlength' => true])->label(false); ?>
                <?= $form->field($rating_logs, 'doctor_id')->hiddenInput(['maxlength' => true])->label(false); ?>
                <?= $form->field($rating_logs, 'appointment_id')->hiddenInput(['maxlength' => true,['class' => 'appointment_id_hidden'] ])->label(false); ?>

                <?php if($type == "Add") { ?>
                    <span>Doctor Rating</span>
                    <div class="">
                        <?= $form->field($rating_logs, 'rating')->widget(
                            StarRating::className(), ['pluginOptions' => [
                            'filledStar' => '&#x2605;',
                            'emptyStar' => '&#x2606;',
                            'min'=>0,
                            'max'=>5,
                            'step'=>0.5,
                            'showClear'=>false,
                            'showCaption'=>false
                        ]])->label(false); ?>

                    </div>
                    <div class="btdetialpart">
                        <?= $form->field($rating_logs, 'review')->textarea(['placeholder' => Yii::t('db','Review'),'readonly'=> false])->label(false); ?>
                    </div>

                    <?php if($hospital_rate == 1){ ?>
                        <span>Hospital Rating</span>
                        <div class="">
                            <?= $form->field($rating_logs, 'hospital_rating')->widget(
                                StarRating::className(), ['pluginOptions' => [
                                'filledStar' => '&#x2605;',
                                'emptyStar' => '&#x2606;',
                                'min'=>0,
                                'max'=>5,
                                'step'=>0.5,
                                'showClear'=>false,
                                'showCaption'=>false
                            ]])->label(false); ?>

                        </div>
                        <div class="btdetialpart">
                            <?= $form->field($rating_logs, 'hospital_review')->textarea(['placeholder' => Yii::t('db','Hospital Review'),'readonly'=> false])->label(false); ?>
                        </div>
                    <?php }?>

                <?php } else { ?>

                    <div class="">

                        <?= $form->field($rating_logs, 'rating')->widget(
                            StarRating::className(), ['pluginOptions' => [
                            'filledStar' => '&#x2605;',
                            'emptyStar' => '&#x2606;',
                            'min'=>0,
                            'max'=>5,
                            'step'=>0.5,
                            'showClear'=>false,
                            'showCaption'=>false,
                            'disabled' => true
                        ]])->label(false); ?>

                    </div>
                    <div class="btdetialpart">
                        <?= $form->field($rating_logs, 'review')->textarea(['placeholder' => Yii::t('db','Review'),'readonly'=> true])->label(false); ?>
                    </div>

                    <?php if($hospital_rate == 1){ ?>
                        <span>Hospital Rating</span>
                        <div class="">
                            <?= $form->field($rating_logs, 'hospital_rating')->widget(
                                StarRating::className(), ['pluginOptions' => [
                                'filledStar' => '&#x2605;',
                                'emptyStar' => '&#x2606;',
                                'min'=>0,
                                'max'=>5,
                                'step'=>0.5,
                                'showClear'=>false,
                                'showCaption'=>false,
                                'disabled' => true
                            ]])->label(false); ?>

                        </div>
                        <div class="btdetialpart">
                            <?= $form->field($rating_logs, 'hospital_review')->textarea(['placeholder' => Yii::t('db','Hospital Review'),'readonly'=> true])->label(false); ?>
                        </div>
                    <?php }?>

                <?php } ?>



                <div  style="padding-top: 15px"></div>
                <div class="btdetialpart">
                    <?php if($type == 'Add'){ ?>
                    <div class="submitbtn pull-left reminder_add">
                         <?php echo Html::submitButton( $type, ['id'=>'add-update-reminder','name' => 'add-update-reminder','class' => 'confirm-theme']); ?>
                    </div>
                    <?php } ?>
                    <div class="pull-right reminder_cancel">
                        <a href="javascript:void(0)" class="confirm-theme" data-dismiss="modal">Cancel</a>

                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
</div>
