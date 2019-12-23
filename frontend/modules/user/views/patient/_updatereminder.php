<?php
$baseUrl=Yii::getAlias('@frontendUrl');
use common\components\DrsPanel;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use yii\helpers\Html;
/*echo '<pre>';
print_r($doctorData);die;*/
?>

<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h3 ><?php echo $type ?> Reminder</span></h3>
        </div>
        <div class="modal-body" id="updatereminder">
            <div class="col-md-12 mx-auto">
                <div class="pace-part main-tow mb-0 reminder_div_patient">
                    <div class="row">
                        <div class="col-sm-12">
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
                               <?php /* ?> <p> <i class="fa fa-calendar"></i> <?php echo $doctorData['reminder_date']?> <span class="pull-right"> <strong>$<?php echo $doctorData['doctor_fees'] ?></strong></span></p>
                                <p><i class="fa fa-clock-o" aria-hidden="true"></i>  <?php echo $reminder->reminder_date?> </p> */?>
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
                <?php $form = ActiveForm::begin(['id' => 'reminder-form','options' => ['enableAjaxValidation' => true]]); ?>

                <?= $form->field($reminder, 'id')->hiddenInput(['maxlength' => true])->label(false); ?>

                <?= $form->field($reminder, 'user_id')->hiddenInput(['maxlength' => true])->label(false); ?>
                <?= $form->field($reminder, 'appointment_id')->hiddenInput(['maxlength' => true,['class' => 'appointment_id_hidden'] ])->label(false); ?>
                <?php
                $selectedTime= explode('-',$doctorData['appointment_time']);
                $endTime = strtotime("- 10 minutes", strtotime($selectedTime[0]));
                $approxTime =  date('h:i a', $endTime);
                ?>
                <input type="hidden" name="errorcheck" value="0" id="errorhiddencheck"/>
                <input type="hidden" name="hiddenEndTime" value="<?php echo $approxTime; ?>" id="hiddenEndTime"/>
                <input type="hidden" name="hiddenEndDate" value="<?php echo $doctorData['appointment_date']; ?>" id="hiddenEndDate"/>


                    <div class="workingpart cls-2">
                        <div class="form-group clearfix">
                            <div class="pull-left">
                                <p> Booking Id </p>
                            </div>
                            <div class="pull-right">
                                <p><span> <a href="#" >  <?php echo isset($doctorData['booking_id'])?$doctorData['booking_id']:''; ?> </a> </span> </p>
                            </div>
                        </div>
                    </div>
                    <div class="btdetialpart" id="user_phone_div">
                       <?= $form->field($reminder, 'reminder_date')->textInput([])->widget(
                        DatePicker::className(), [
                        'convertFormat' => true,
                        'type' => DatePicker::TYPE_INPUT,
                        'options' => ['placeholder' => 'Date*','class'=>'form-group '],
                        'layout'=>'{input}',
                        'pluginOptions' => [
                        'autoclose'=>true,
                        'format' => 'yyyy-MM-dd',
                        'startDate' => date('Y-m-d'),
                        'endDate' => $doctorData['appointment_date'],
                        'todayHighlight' => true
                        ],])->label(false); ?>
                    </div>
                    <div class="btdetialpart">
                    <?= $form->field($reminder, 'reminder_time')
                    ->textInput(['autocomplete'=>'off','placeholder' => Yii::t('db','Time'),
                    'readonly'=> false,'class'=>'reminder_time_check form-control',
                    'onchange' => 'shiftReminderValue("reminder-form");'])->label(false); ?>
                    </div>
                    <div  style="padding-top: 15px"></div>
                    <div class="btdetialpart">
                        <div class="submitbtn pull-left reminder_add">
                            <?php echo Html::submitButton( $type, 
                            ['id'=>'add-update-reminder','name' => 'add-update-reminder','class' => 'confirm-theme',]) ?>
                        </div>
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
