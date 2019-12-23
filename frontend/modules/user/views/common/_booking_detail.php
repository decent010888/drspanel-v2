<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use  common\components\DrsPanel ;
$baseUrl= Yii::getAlias('@frontendUrl');
$getAppointmentConfirm="'".$baseUrl."/$userType/appointment-payment-confirm'";
$getAppointmentConsult="'".$baseUrl."/$userType/appointment-consulting-confirm'";
$getAppointmentCancel="'".$baseUrl."/$userType/ajax-cancel-appointment'";

$js="

    function PrintDiv() {    
       var divToPrint = document.getElementById('patientbookedShowModal');
       var popupWin = window.open('', '_blank', 'width=300,height=300');
       popupWin.document.open();
       popupWin.document.write('<html><body onload=\"window.print()\">' + divToPrint.innerHTML + '</html>');
        popupWin.document.close();
            }
            
    $(document).on('click','.appointment_confirm_payment', function () {
        doctorid=$(this).attr('data-doctorid');
        datastatus=$(this).attr('data-status');
        $.ajax({
          method:'POST',
          url: $getAppointmentConfirm,
          data: {user_id:doctorid,appointment_id:$(this).attr('data-appointmentid'),booking_type:datastatus}
        })
       .done(function( json_result ) { 
        if(json_result){
             var json_result = jQuery.parseJSON(json_result);                    
            $('.modal').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('li.active a.get-shift-token').click();
            $('#patientbookedShowModal').on('hidden.bs.modal', function (e) {
              setTimeout(function () {
                swal({            
                type:'success',
                title:'Success!',
                text:'Booking Updated',            
                timer:1000,
                confirmButtonColor:'#a42127'
            })},100);
            });
         
        } 
        });   
    });
    
    $(document).on('click','.appointment_consulting_mark', function () {
        doctorid=$(this).attr('data-doctorid');
        datastatus=$(this).attr('data-status');
        $.ajax({
          method:'POST',
          url: $getAppointmentConsult,
          data: {user_id:doctorid,appointment_id:$(this).attr('data-appointmentid'),booking_type:datastatus}
        })
       .done(function( json_result ) { 
        if(json_result){
             var json_result = jQuery.parseJSON(json_result);                    
            $('.modal').modal('hide');
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('li.active a.get-shift-token').click();
            $('#patientbookedShowModal').on('hidden.bs.modal', function (e) {
              setTimeout(function () {
                swal({            
                type:'success',
                title:'Success!',
                text:'Booking Updated',            
                timer:1000,
                confirmButtonColor:'#a42127'
            })},100);
            });
         
        } 
        });   
    });
    
    $('.cancel_appointment').on('click',function(){
        appointment_id = $(this).attr('data-id');
        var txt_show = '<p>Are you sure want to Cancel this appointment?</p>';
        $('#ConfirmModalHeading').html('<span>Appointment Delete?</span>');       
        $('#ConfirmModalContent').html(txt_show);
        $('#ConfirmModalShow').modal({backdrop:'static',keyword:false})
        .one('click', '#confirm_ok' , function(e){
            $.ajax({
                url: $getAppointmentCancel,
                dataType:   'html',
                method:     'POST',
                data: { appointment_id: appointment_id},
                success: function(response){
                    var json_result = jQuery.parseJSON(response);                    
                    $('.modal').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $('li.active a.get-shift-token').click();
                    if(json_result.status == 'error'){
                        $('#patientbookedShowModal').on('hidden.bs.modal', function (e) {
                          setTimeout(function () {
                            swal({            
                            type:'error',
                            title:'Error!',
                            text:'Please try again',            
                            timer:1000,
                            confirmButtonColor:'#a42127'
                        })},100);
                        });
                    
                    }
                    else{
                        $('#patientbookedShowModal').on('hidden.bs.modal', function (e) {
                          setTimeout(function () {
                            swal({            
                            type:'success',
                            title:'Success!',
                            text:'Appointment Cancelled!',            
                            timer:1000,
                            confirmButtonColor:'#a42127'
                        })},100);
                        });
                        
                    }
                     
                }
            });
        });
    });
    
";
$this->registerJs($js,\yii\web\VIEW::POS_END);

if(isset($current)){
    $current=$current;
}
else{
    $current=0;
}
?>

<div class="col-md-12 mx-auto booking_detail_div">
    <div class="pace-part main-tow mb-2">
        <div class="row">
            <div class="col-sm-12">
                <div class="reminder-left">
                    <p class="text-reminder">To </p>
                    <h4><?= $booking['doctor_name']?></h4>
                    <p> <?= $booking['doctor_speciality']?></p>
                    <p> <?= $booking['hospital_name']?></p>
                    <p> <?= $booking['doctor_address']?></p>
                </div>
                <div class="reminder-right text-right"> <img src="<?= $booking['doctor_image']?>" alt="image"></div>
            </div>
        </div>
    </div>
    <form class="appoiment-form-part">
        <div class="btdetialpart">
            <div class="pull-left"><?= ucfirst($booking['patient_name']);?></div>
            <div class="pull-right"> <?= $booking['patient_mobile']?> </div>
        </div>
        <div class="btdetialpart">
            <div class="pull-left">
                <p>Date</p>
                <p><strong><?= date('d M, Y', strtotime($booking['appointment_date']));?></strong></p>
            </div>
            <div class="pull-right text-right">
                <p>Appointment Time</p>
                <p><strong><?= $booking['appointment_time']?></strong></p>
            </div>
        </div>
        <div class="btdetialpart">
            <div class="pull-left">
                <p>Token Number</p>
                <p><strong><?= $booking['token']?></strong></p>
            </div>
            <div class="pull-right text-right">
                <p>Approx consultation Time</p>
                <p><strong><?= $booking['appointment_approx_time']?></strong></p>
            </div>
        </div>
        <div class="btdetialpart">
            <div class="pull-left">
                <p>Fees</p>
                <p><strong><i class="fa fa-rupee" aria-hidden="true"></i> <?= $booking['fees']?>/Session</strong></p>
            </div>
            <div class="pull-right text-right">
                <p>Booking id</p>
                <p><strong><?= $booking['booking_id']?></strong></p>
            </div>
        </div>
        <div class="btdetialpart">
        <div class="row booking_detail_btn">
        <?php
              if($current == 0){
                if($booking['status']=='pending') { ?>
                    <div class="pull-left col-sm-4">
                        <input type="button" onClick="modalClose()"  class="confirm-theme" value="OK">
                    </div>
                    <div class="text-center col-sm-4">
                        <input type="button" class="confirm-theme cancel_appointment" data-id="<?php echo $booking['id']?>" value="CANCEL">
                    </div>
                    <div class="pull-right text-right col-sm-4">
                        <input type="button" class="confirm-theme appointment_confirm_payment" data-status="<?php echo $booking['status']?>" data-doctorid="<?php echo $booking['doctor_id']?>" data-appointmentid ="<?php echo $booking['id']?>" id="appointment_confirm_payment_<?php echo $booking['id']?>" value="PAID">
                    </div>
                <?php }
                elseif($booking['status']=='completed'){ ?>
                    <div class="pull-left col-sm-6">
                        <input type="button" class="confirm-theme" value="OK" data-dismiss="modal">
                    </div>
                    <div class="pull-right text-right col-sm-6">
                        <input type="button" class="confirm-theme" value="PRINT" onclick="PrintDiv();">
                    </div>
                <?php }
                elseif($booking['status']=='cancelled'){ ?>
                    <div class="pull-left col-sm-4">
                        <input type="button" onClick="modalClose()"  class="confirm-theme" value="OK">
                    </div>
                    <div class="pull-right text-right col-sm-6">
                        <input type="button" class="confirm-theme" value="PRINT" onclick="PrintDiv();">
                    </div>
                <?php }
                else{ ?>
                    <div class="pull-left col-sm-4">
                        <input type="button" onClick="modalClose()"  class="confirm-theme" value="OK">
                    </div>
                    <div class="text-center col-sm-4">
                        <input type="button" class="confirm-theme cancel_appointment" data-id="<?php echo $booking['id']?>" value="CANCEL">
                    </div>
                    <div class="pull-right text-right col-sm-4">
                        <input type="button" class="confirm-theme" value="PRINT" onclick="PrintDiv();">
                    </div>
                <?php }

            } else{
                  if($booking['status']=='available' || $booking['status']=='skip') { ?>
                      <div class="pull-left col-sm-4">
                          <input type="button" class="confirm-theme appointment_consulting_mark" data-status="<?php echo $booking['status']?>" data-doctorid="<?php echo $booking['doctor_id']?>" data-appointmentid ="<?php echo $booking['id']?>" id="appointment_consulting_mark<?php echo $booking['id']?>" value="Mark Consulting">
                      </div>
                    <div class="text-center col-sm-4">
                    </div>
                      <div class="pull-right text-right col-sm-4">
                          <input type="button" onClick="modalClose()"  class="confirm-theme" value="OK">
                      </div>

                  <?php } else{ ?>
            <div class="pull-left col-sm-4">
            </div>
                      <div class="text-center col-sm-4">
                          <input type="button" onClick="modalClose()"  class="confirm-theme" value="OK">
                      </div>
            <div class="pull-right text-right col-sm-4">
            </div>
                  <?php }
            } ?>
            </div>
        </div>
    </form>
</div>
