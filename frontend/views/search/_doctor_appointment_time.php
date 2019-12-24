<?php
use common\components\DrsPanel;

$this->title = Yii::t('frontend','DrsPanel :: Doctor Appointment');

$baseUrl= Yii::getAlias('@frontendUrl');
$loginUser=Yii::$app->user->identity;
$slug="'$doctorProfile->slug'";
$getlist="'".$baseUrl."/search/booking-confirm'";
$gettokenDetails="'".$baseUrl."/search/get-date-tokens'";
$js="

$(document).on('click','.fetch_date_schedule',function(){
    var id= this.id;
    date = $('#'+id).attr('data-nextdate');
    doctor_id = $('#'+id).attr('data-doctor_id');
    $.ajax({
        method:'POST',
        url: $gettokenDetails,
        data: {doctor_id:doctor_id,nextdate:date}
    })
  .done(function( responce_data ) { 
        $('#address-list-modal-content').html('');
		$('#address-list-modal-content').html(responce_data);
		$('#address-list-modal').modal({backdrop: 'static',keyboard: false,show: true})
  });
});
$('.get-slot').on('click',function(){
  id=$(this).attr('id');
  $.ajax({
    method:'POST',
    url: $getlist,
    data: {slug:$slug,slot_id:id,date:'$date'}
  })
  .done(function( msg ) { 
    if(msg){
      $('#pslotTokenContent').html('');
      $('#pslotTokenContent').html(msg); 
      $('#patientbookedShowModal').modal({backdrop: 'static',keyboard: false});
    }

  });

});

";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>

<section class="mid-content-part">
    <div class="signup-part">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-9 mx-auto">
<!--                    <div class="refresh_notice">Note: Please do not reload or refresh the page.</div>-->
                    <div class="youare-text"> You are Booking an appointment with </div>
                    <div class="hospitals-detailspt">

                        <div id="ajaxLoadDetailDiv">
                            <?php
                            echo $this->render('_booking_detail_list',['date'=>$date,'doctor'=>$doctor,'scheduleDay'=>$scheduleDay,'schedule'=>$schedule,'slots'=>$slots,'doctorProfile'=>$doctorProfile]);
                            ?>
                        </div>

                    </div>
                </div>
                <div class="col-md-2 mx-auto">
                    <div class="Ads_part">
                        <div class="ads_box">
                            <img src="<?php echo $baseUrl?>/images/ads_img1.jpg">
                        </div>
                        <div class="ads_box">
                            <img src="<?php echo $baseUrl?>/images/ads_img1.jpg">
                        </div>
                        <div class="ads_box">
                            <img src="<?php echo $baseUrl?>/images/ads_img1.jpg">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<div class="signup-part">
    <div class="modal fade model_opacity" id="patientbookedShowModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"  style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 >Confirm <span>Booking</span></h3>
                </div>
                <div class="modal-body" id="pslotTokenContent">

                </div>
                <div class="modal-footer ">

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Model confirm message Sow -->
<div class="login-section ">
    <div id="address-list-modal" class="modal model_opacity" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="addressHeading">Doctor <span> Address list </span></h3>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body addressListHospital" id="address-list-modal-content">

                </div>
            </div>
        </div>
    </div>
</div>