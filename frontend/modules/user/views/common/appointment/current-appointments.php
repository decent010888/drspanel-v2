<?php
use kartik\date\DatePicker;
use common\components\DrsPanel;

$baseUrl= Yii::getAlias('@frontendUrl');
$loginUser=Yii::$app->user->identity;
$this->title = Yii::t('frontend','DrsPanel :: Doctor Appoinment');
?>
<?php if($userType == 'hospital') { ?>
    <div class="youare-text"> Appointment List for Doctor <?php echo DrsPanel::getUserName($doctor->id);?></div>
<?php } ?>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-12" id="appointments_section">
                    <div class="today-appoimentpart">
                        <div id="appointment_date_select" class="appointment_date_select mx-auto calendra_slider">
                            <?php
                            $dates_range=DrsPanel::getSliderDates();
                            echo $this->render('/common/_appointment_date_slider',['dates_range'=>$dates_range,'doctor_id'=>$doctor->id,'type'=>$type,'userType'=>$userType]);
                            ?>
                        </div>
                        <div class="calender_icon_main pull-right">
                            <?php echo DatePicker::widget([
                                'name' => 'appointment_date',
                                'type' => DatePicker::TYPE_BUTTON,
                                'value' => date('d M Y',$defaultCurrrentDay),
                                'id'=>  'appointment-date',
                                'buttonOptions'=>[
                                    'label' => '<img src="'.$baseUrl.'/images/celander_icon.png" alt="image"/>',
                                ],
                                'pluginOptions' => [
                                    'autoclose'=>true,
                                    'format' => 'dd M yyyy',
                                    'startDate' => date('d M Y',$defaultCurrrentDay),
                                ],
                                'pluginEvents' => [
                                    "change" => "function(){
                                            bookingsDate($('#appointment-date').val(),'$type','$userType',$doctor->id);
                                        }",
                                ],
                            ]);
                            ?>
                        </div>
                    </div>

                    <div class="hospitals-detailspt appointment_list">
                        <div class="docnew-tab">
                            <ul class="resp-tabs-list">

                                <li class="<?php echo ($type == 'book')?'resp-tab-active':'resp-tab-inactive'; ?>">
                                    <?php if($userType == 'hospital') { ?>
                                        <a href="<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'book']); ?>">
                                            <?= Yii::t('db','Book Appointment'); ?>
                                        </a>
                                    <?php } else{ ?>
                                        <a href="<?php echo yii\helpers\Url::to(['appointments','type'=>'book']); ?>">
                                            <?= Yii::t('db','Book Appointment'); ?>
                                        </a>
                                    <?php }?>
                                </li>
                                <li class="<?php echo ($type == 'current_shift')?'resp-tab-active':'resp-tab-inactive'; ?>">
                                    <?php if($userType == 'hospital') { ?>
                                        <a href="<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'current_shift']); ?>">
                                            <?= Yii::t('db','Current Status'); ?>
                                        </a>
                                    <?php } else{ ?>
                                        <a href="<?php echo yii\helpers\Url::to(['appointments','type'=>'current_shift']); ?>">
                                            <?= Yii::t('db','Current Status'); ?>
                                        </a>
                                    <?php }?>
                                </li>
                                <li class="<?php echo ($type == 'current_appointment')?'resp-tab-active':'resp-tab-inactive'; ?>">
                                    <?php if($userType == 'hospital') { ?>
                                        <a href="<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'current_appointment']); ?>">
                                            <?= Yii::t('db','Booked Appointment'); ?>
                                        </a>
                                    <?php } else{ ?>
                                        <a href="<?php echo yii\helpers\Url::to(['appointments','type'=>'current_appointment']); ?>">
                                            <?= Yii::t('db','Booked Appointment'); ?>
                                        </a>
                                    <?php }?>


                                </li>

                            </ul>
                        </div>

                        <div id="appointment_shift_slots">
                            <?php
                            echo $this->render('/common/_appointment_shift_slots',['appointments'=>$appointments,'current_shifts'=>$current_shifts,'doctor'=>$doctor,'type'=>$type,'bookings'=>$bookings,'userType'=>$userType]);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>