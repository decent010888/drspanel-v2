<?php 
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use common\components\DrsPanel;

$baseUrl= Yii::getAlias('@frontendUrl'); 
$loginUser=Yii::$app->user->identity;
$this->title = Yii::t('frontend','DrsPanel :: Current Status');

$updateAppointmentStatus="'".$baseUrl."/attender/appointment-status-update'";
$updateShift="'".$baseUrl."/attender/current-appointment-shift-update'";
$js="

$(document).on('click','.start_shift', function () {
    var schedule_id=$(this).attr('data-value');
    doctorid=$(this).attr('data-doctor_id');
    var status='start';
    $('#main-js-preloader').show();
    $.ajax({
      method:'POST',
      url: $updateShift,
      data: {schedule_id:schedule_id,status:status,doctor_id:doctorid,user_id:doctorid}
    })
    .done(function( json_result ) { 
      if(json_result){
        var obj = jQuery.parseJSON(json_result);
        if(obj.status){
            $('#shiftslot_'+schedule_id+' .get-shift-token').trigger('click');
            $('#main-js-preloader').hide();
          //$('#current-affairs').html('');
          //$('#current-affairs').html(obj.data);
        }
      }
    });
}); // next button close close

$(document).on('click','.cancel_shift', function () {
    var schedule_id=$(this).attr('data-value');
    doctorid=$(this).attr('data-doctor_id');
    var status='cancel';
    $('#main-js-preloader').show();
    $.ajax({
      method:'POST',
      url: $updateShift,
      data: {schedule_id:schedule_id,status:status,doctor_id:doctorid,user_id:doctorid}
    })
    .done(function( json_result ) { 
      if(json_result){
        var obj = jQuery.parseJSON(json_result);
        if(obj.status){
            $('#shiftslot_'+schedule_id+' .get-shift-token').trigger('click');
            $('#main-js-preloader').hide();
          //$('#current-affairs').html('');
          //$('#current-affairs').html(obj.data);
        }
      }
    });
});

$(document).on('click','#skip-btn', function () {
    var schedule_id=$(this).attr('data-value');
   doctorid=$(this).attr('data-doctor_id');
   var status='skip';
   $('#main-js-preloader').show();
    $.ajax({
      method:'POST',
      url: $updateAppointmentStatus,
      data: {schedule_id:schedule_id,status:status,doctor_id:doctorid,user_id:doctorid}
    })
    .done(function( json_result ) { 
      if(json_result){
        var obj = jQuery.parseJSON(json_result);

        if(obj.status){
          $('#shiftslot_'+schedule_id+' .get-shift-token').trigger('click');
           
        }
        $('#main-js-preloader').hide();
      }
    });
}); // next button close close


$(document).on('click','#next-btn', function () {
   var schedule_id=$(this).attr('data-value');
   doctorid=$(this).attr('data-doctor_id');
   var status='next';
    $('#main-js-preloader').show();
   $.ajax({
     method:'POST',
     url: $updateAppointmentStatus,
     data: {schedule_id:schedule_id,status:status,doctor_id:doctorid,user_id:doctorid}
   })
   .done(function( json_result ) { 
     if(json_result){
      var obj = jQuery.parseJSON(json_result);

      if(obj.status){
       $('#shiftslot_'+schedule_id+' .get-shift-token').trigger('click');
     }
      $('#main-js-preloader').hide();
   }

 });
}); // next button close close

$(document).on('click', '#end-btn',function () {
   var schedule_id=$(this).attr('data-value');
   doctorid=$(this).attr('data-doctor_id');
   var status='completed';
   $('#main-js-preloader').show();
   $.ajax({
     method:'POST',
     url: $updateShift,
     data: {schedule_id:schedule_id,status:status,doctor_id:doctorid,user_id:doctorid}
   })
   .done(function( json_result ) { 
     if(json_result){
      var obj = jQuery.parseJSON(json_result);

      if(obj.status){
       $('#shiftslot_'+schedule_id+' .get-shift-token').trigger('click');
     }
     $('#main-js-preloader').hide();
   }

 });
}); // next button close close



";
$this->registerJs($js,\yii\web\VIEW::POS_END);

?>
<div class="youare-text">Booking Appointment for Doctor: <?php echo DrsPanel::getUserName($doctor->id);?></div>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-12" id="appointments_section">
                    <div class="today-appoimentpart">
                        <div id="appointment_date_select" class="appointment_date_select mx-auto calendra_slider">

                            <div class="appointment_calendar clearfix">
                                <ul>
                                    <li>
                                        <div class="day_blk">
                                            <div class="day_name"><h3>Today Appointments</h3></div>
                                            <div class="day_date">
                                                <?php  echo date('d M Y',strtotime($date)); ?>
                                            </div>
                                        </div>
                                    </li>
                                </ul>

                            </div>
                        </div>
                    </div>

                    <div class="hospitals-detailspt appointment_list">
                        <div class="docnew-tab">
                            <ul class="resp-tabs-list">

                                <li onclick="location.href='<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'book']); ?>'" class="<?php echo ($type == 'book')?'resp-tab-active':'resp-tab-inactive'; ?>">
                                    <a href="<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'book']); ?>">
                                        <?= Yii::t('db','Book Appointment'); ?>
                                    </a>
                                </li>
                                <li onclick="location.href='<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'current_shift']); ?>'" class="<?php echo ($type == 'current_shift')?'resp-tab-active':'resp-tab-inactive'; ?>">
                                    <a href="<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'current_shift']); ?>">
                                        <?= Yii::t('db','Current Status'); ?>
                                    </a>
                                </li>
                                <li onclick="location.href='<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'current_appointment']); ?>'" class="<?php echo ($type == 'current_appointment')?'resp-tab-active':'resp-tab-inactive'; ?>">
                                    <a href="<?php echo yii\helpers\Url::to(['/'.$userType.'/appointments/'.$doctorProfile->slug,'type'=>'current_appointment']); ?>">
                                        <?= Yii::t('db','Booked Appointment'); ?>
                                    </a>
                                </li>

                            </ul>
                        </div>

                        <div class="doc-timingslot">
                            <ul>
                                <?php echo $this->render('/common/_shifts',['shifts'=>$Shifts,'current_shifts'=>$current_shifts,'doctor'=>$doctor,'type'=>$type,'userType'=>'attender']);?>
                            </ul>
                        </div>

                        <?php if(!empty($Shifts)) { ?>

                            <div id="shift-current-appointment-load">
                                <div class="doc-boxespart-book" id="shift-current-appointment">
                                    <?php echo $this->render('/common/_current_bookings',['bookings'=>$appointments,'type'=>$type,'userType'=>'attender','is_started'=>$is_started,'is_completed'=>$is_completed,'is_cancelled'=>$is_cancelled,'schedule_id'=>$schedule_id,'shift_id'=>$shift_id,'doctor'=>$doctor]); ?>

                                </div>
                            </div>

                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

