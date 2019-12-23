<?php
use common\components\DrsPanel;

$baseUrl= Yii::getAlias('@frontendUrl');

$getAppointment="'".$baseUrl."/$userType/get-appointment-detail'";
$js="
    $(document).on('click','.get-current-booking-detail', function () {
        doctorid=$(this).attr('data-doctorid');
        datastatus=$(this).attr('data-status');
        $.ajax({
          method:'POST',
          url: $getAppointment,
          data: {user_id:doctorid,appointment_id:$(this).attr('data-appointment-id'),booking_type:datastatus,current:1}
        })
       .done(function( json_result ) { 
        if(json_result){
          $('#pslotTokenContent').html('');
          $('#pslotTokenContent').html(json_result); 
          $('#patientbookedShowModal').modal({backdrop: 'static',keyboard: false});        
        } 
        });   
    });
";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>

    <div class="row">
        <?php if(count($bookings)>0 && ($shift_id == $schedule_id)){
            foreach ($bookings as $key => $booking) { ?>
                <?php
                if($booking['booking_type'] == 'offline'){
                    $token_class='avail';
                } else{
                    $token_class='';
                }

                if($booking['status'] == 'active'){
                    $bg_class='active_app';
                }
                else{
                    $bg_class='';
                }
                ?>
                <div class="col-sm-4">
                    <div class="get-current-booking-detail token_allover <?php echo $bg_class; ?>" data-appointment-id="<?php echo $booking['id']; ?>" data-type="<?php echo $userType; ?>" data-doctorid="<?php echo $doctor->id; ?>" data-status="<?php echo $booking['booking_type']?>">
                        <div class="token <?php echo $token_class; ?>">
                            <h4> <?php echo $booking['token']; ?> </h4>
                        </div>
                        <div class="token-rightdoctor">

                            <div class="token-timingdoc">
                                <h3> <?php echo $booking['name']; ?> </h3>
                                <span class="number-partdoc"> <?php echo $booking['phone']; ?> </span>
                                <p><strong>Booking ID:</strong> <?php echo $booking['booking_id']; ?> </p>
                                <p><strong>Status:</strong> <?php echo DrsPanel::statusLabelDoctor($booking['status']); ?> </p>
                            </div>
                        </div>
                    </div>
                </div>


            <?php }
        }
        else{
            if($is_started){
                echo "No Appointments!";
            }
            elseif($is_completed == 1){

            }
            elseif($is_cancelled == 1){

            }
            else{
                if($shift_id == $schedule_id) {
                    echo "No Appointments Booked!";
                }
                else{
                    echo "Shift not started!";
                }
            }
        }?>
    </div>

<?php
//if($current_shifts == $schedule_id) { ?>
    <?php if($is_started){ ?>
        <?php if($is_completed == 1){ ?>
            <div class="bookappoiment-btn">
                <input data-value="<?php echo $schedule_id; ?>" value="Shift Completed" class="bookinput complete_shift" type="button"/>
            </div>
        <?php } else{ ?>
            <?php if(count($bookings)>0){ ?>
                <div class="bookappoiment-btn">
                    <input value="Skip" class="bookinput" type="button" id="skip-btn" data-doctor_id="<?php echo $doctor->id; ?>" data-value="<?php echo $schedule_id; ?>">
                    <input value="Next" class="bookinput" type="button" id="next-btn" data-doctor_id="<?php echo $doctor->id; ?>" data-value="<?php echo $schedule_id; ?>">
                </div>
            <?php } ?>
            <div class="text-center">
				<button type="button" class="login-sumbit btn btn-primary" id="end-btn" data-doctor_id="<?php echo $doctor->id; ?>" data-value="<?php echo $schedule_id; ?>">End Shift</button>
			</div>
        <?php } ?>
    <?php }
    elseif($is_completed == 1){ ?>
        <div class="bookappoiment-btn">
            <input data-value="<?php echo $schedule_id; ?>" value="Shift Completed" class="bookinput complete_shift" type="button"/>
        </div>
    <?php }
    elseif($is_cancelled == 1){ ?>
        <div class="bookappoiment-btn">
            <input data-value="<?php echo $schedule_id; ?>" value="Shift Cancelled" class="bookinput cancelled_shift" type="button"/>
        </div>
    <?php }
    else { ?>
        <?php //if(count($bookings)>0){  ?>
            <div class="bookappoiment-btn">
                <input data-doctor_id="<?php echo $doctor->id; ?>" data-value="<?php echo $schedule_id; ?>" id="start-shift" value="Start Shift" class="bookinput start_shift" type="button">
                <input data-doctor_id="<?php echo $doctor->id; ?>" data-value="<?php echo $schedule_id; ?>" id="cancel-shift" value="Cancel Shift" class="bookinput cancel_shift" type="button">
            </div>
    <?php //}
    }?>
<?php //} ?>
</div>

<div class="login-section ">
    <div class="modal fade model_opacity" id="patientbookedShowModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"  style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 >Booking <span>Detail</span></h3>
                </div>
                <div class="modal-body" id="pslotTokenContent">

                </div>
                <div class="modal-footer ">

                </div>
            </div>
        </div>
    </div>
</div>