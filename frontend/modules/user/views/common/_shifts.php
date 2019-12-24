<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use common\components\DrsPanel;

$baseUrl= Yii::getAlias('@frontendUrl');
$loginUser=Yii::$app->user->identity;

$getToken="'".$baseUrl."/$userType/ajax-token'";
$getCurrentAppointment="'".$baseUrl."/$userType/ajax-current-appointment'";
$getAppointment="'".$baseUrl."/$userType/ajax-appointment'";
$getHistory="'".$baseUrl."/$userType/ajax-history-appointment'";
$getUserHistory="'".$baseUrl."/$userType/ajax-statistics-data'";
$js="
    $(document).ready(function () {
        $('#shiftID').val($current_shifts);
    });
    $('li').find('a.get-shift-token').click(function() {
        shiftid=$(this).attr('data-shift');
        type=$(this).attr('data-type');
        doctorid=$(this).attr('data-doctorid');
        $('#shiftID').val(shiftid);
        if(type == 'current_appointment'){
            $.ajax({
              method:'POST',
              url: $getAppointment,
              data: {user_id:doctorid,shift_id:$(this).attr('data-shift'),date:$(this).attr('shift-date')}
            })
           .done(function( json_result ) { 
            if(json_result){
              $('.doc-timingslot li').removeClass('active');
              $('#shift-tokens').empty();
              $('#shift-tokens').append(json_result);  
              $('.doc-timingslot li#shiftslot_'+shiftid).addClass('active');         
            } 
            });
        }
        else if(type == 'current_shift'){
            $.ajax({
              method:'POST',
              url: $getCurrentAppointment,
              data: {user_id:doctorid,shift_id:$(this).attr('data-shift'),date:$(this).attr('shift-date')}
            })
           .done(function( json_result ) { 
            if(json_result){
              $('.doc-timingslot li').removeClass('active');
              $('#shift-current-appointment').empty();
              $('#shift-current-appointment').append(json_result);  
              $('.doc-timingslot li#shiftslot_'+shiftid).addClass('active');         
            } 
            });
        }
        else if(type == 'history'){            
            $.ajax({
              method:'POST',
              url: $getHistory,
              data: {user_id:doctorid,shift_id:$(this).attr('data-shift'),date:$(this).attr('shift-date')}
            })
           .done(function( json_result ) {             
                if(json_result){
                  $('.doc-timingslot li').removeClass('active');
                  $('#shift-tokens').empty();
                  $('#shift-tokens').append(json_result);  
                  $('.doc-timingslot li#shiftslot_'+shiftid).addClass('active');         
                } 
            }).fail(function (jqXHR, textStatus, errorThrown) { 
                alert('hello');
                    
            });
        }
        else if(type == 'user_history'){
            type='online';
            $.ajax({
              method:'POST',
              url: $getUserHistory,
              data: {user_id:doctorid,shift_id:$(this).attr('data-shift'),date:$(this).attr('shift-date'),}
            })
           .done(function( json_result ) {
                var obj = jQuery.parseJSON(json_result);
                if(obj.status){ 
                $('.doc-timingslot li').removeClass('active');
                $('#statistics-appointments').html('');
                $('#statistics-appointments').html(obj.appointments);
                $('.doc-timingslot li#shiftslot_'+shiftid).addClass('active');  
                }
            }); 
        }
        else{
            $.ajax({
              method:'POST',
              url: $getToken,
              data: {shift_id:$(this).attr('data-shift'),date:$(this).attr('shift-date'),doctorid:$(this).attr('data-doctorid')}
            })
           .done(function( json_result ) { 
            if(json_result){
              $('.doc-timingslot li').removeClass('active');
              $('#shift-tokens').empty();
              $('#shift-tokens').append(json_result);  
              $('.doc-timingslot li#shiftslot_'+shiftid).addClass('active');         
            } 
            });
        }
        
      
    });
";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>
<input type="hidden" id="shiftID" value="">
<div class="shift_slots slider">

    <?php

    if($shifts){
        foreach ($shifts as $key => $shift) {?>
            <li id="<?php echo 'shiftslot_'.$shift['schedule_id']; ?>" class="<?php echo ($shift['schedule_id'] == $current_shifts)?'active':''?>">
                <a href="javascript:void(0)" shift-date="<?php echo isset($shift['date'])?$shift['date']:''; ?>" class="get-shift-token" data-shift="<?php echo $shift['schedule_id']; ?>" data-type="<?php echo $type; ?>" data-doctorid="<?php echo $doctor->id; ?>">
                    <?php echo $shift['shift_name']; ?>
                    <br/>
                    <span><?php echo ucwords($shift['hospital_name']) ?></span>
                </a>
            </li>
        <?php }
    } else { ?>
        <li>Shifts not available for selected date, Please add shift first.</li>
        <?php
    }
    ?>
</div>
