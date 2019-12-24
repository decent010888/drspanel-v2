<?php
$baseUrl = Yii::getAlias('@frontendUrl');

use common\components\DrsPanel;
use common\models\User;
use common\models\UserAppointment;
use branchonline\lightbox\Lightbox;

$this->title = Yii::t('frontend', 'DrsPanel :: Apointment Detail');

$getTransactionDetail = \common\models\Transaction::find()->where(['appointment_id' => $appointments['id'], 'type' => 'refund'])->one();
//$paymetResponse = json_decode($getTransactionDetail['paytm_response']);

$appointment_cancel_url = "'" . $baseUrl . "/patient/ajax-cancel-appointment'";
$appointment_refund_url = "'" . $baseUrl . "/patient/get-refund-status'";

$js = "
    $('.cancel_appointment').on('click',function(){
        appointment_id = $(this).attr('data-id');
        var txt_show = '<p>Are you sure want to Cancel this appointment?</p>';
        $('#ConfirmModalHeading').html('<span>Appointment Delete?</span>');        $('#ConfirmModalContent').html(txt_show);
        $('#ConfirmModalShow').modal({backdrop:'static',keyword:false})
        .one('click', '#confirm_ok' , function(e){
        $.ajax({
            url: $appointment_cancel_url,
            dataType:   'html',
            method:     'POST',
            data: { appointment_id: appointment_id},
            success: function(response){
               location.reload();
            }
        });
        });
    });
    $('.refund_status').on('click', function(){
        appointment_id = $(this).attr('data-id');
        $.ajax({
            url: $appointment_refund_url,
            dataType:   'json',
            method:     'POST',
            data: { appointment_id: appointment_id},
            success: function(response){
               location.reload();
            }
        });
    });
    
    
    ";
$this->registerJs($js, \yii\web\VIEW::POS_END);
// pr($appointments);die;
//echo "<pre>";print_r($appointment_doctorData);die
// pr($appointment_hospitalData);die;
?>
<div class="inner-banner"> </div>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-7 mx-auto">
                    <div class="appointment_part">
                        <div class="pace-part main-tow">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pace-left mb-3">
                                        <?php $image = DrsPanel::getUserAvator($appointments['doctor_id']); ?>

                                        <?php
                                        echo Lightbox::widget([
                                            'files' => [
                                                [
                                                    'thumb' => $image,
                                                    'original' => $image,
                                                    'title' => $appointments['doctor_name'],
                                                ],
                                            ]
                                        ]);
                                        ?>
                                    </div>
                                    <div class="pace-right">
                                        <h4><?php echo isset($appointments['doctor_name']) ? $appointments['doctor_name'] : '' ?></h4>
                                        <p> <?php echo isset($appointment_doctorData['speciality']) ? $appointment_doctorData['speciality'] : '' ?> </p>
                                        <p> <i class="fa fa-phone" aria-hidden="true"></i> <?php echo $appointments['doctor_phone'] ?></p>
                                    </div>
                                    <div class="doc-listboxes">
                                        <div class="pull-left">
                                            <p> Doctor Details </p>
                                            <p><strong> <?php echo DrsPanel::getHospitalName($appointment_hospitalData['id']); ?></strong> </p>
                                            <p><?php echo DrsPanel::getAddressShow($appointment_hospitalData['id']); ?></p>
                                        </div>

                                        <div class="pull-right text-right">
                                            <p> Date and Time </p>

                                            <p><strong> <?php
                                                    if (isset($appointments['date'])) {
                                                        echo date('d M Y', strtotime($appointments['date']));
                                                    }
                                                    ?>,  <?php echo isset($appointments['shift_name']) ? $appointments['shift_name'] : '' ?> </strong> </p>
                                            <p><?php
                                                echo
                                                DrsPanel::getnextDaysCount($appointments['date']);
                                                ?></p>
                                        </div>
                                    </div>

                                    <div class="doc-listboxes">
                                        <div class="pull-left">
                                            <p> Booking for </p>
                                            <p><strong> <?php echo isset($appointments['user_name']) ? ucfirst($appointments['user_name']) : '' ?></strong> </p>
                                        </div>
                                        <div class="pull-right text-right">
                                            <p> Contact Number </p>
                                            <p><strong><?php echo isset($appointments['user_phone']) ? $appointments['user_phone'] : '' ?></strong> </p>
                                        </div>
                                    </div>
                                    <div class="doc-listboxes">
                                        <div class="pull-left">
                                            <p> Consultation charge </p>
                                            <p class="price_text"><strong> <i class="fa fa-rupee" aria-hidden="true"></i> <?php echo isset($appointments['doctor_fees']) ? $appointments['doctor_fees'] : ''; ?> </strong> </p>
                                        </div>

                                        <div class="pull-right text-right">
                                            <p> Booking ID </p>
                                            <p><strong> <?php echo isset($appointments['booking_id']) ? $appointments['booking_id'] : '' ?> </strong> </p>
                                        </div>

                                    </div>

                                    <div class="doc-listboxes">
                                        <div class="pull-left">
                                            <p> Service charge </p>
                                            <p class="price_text"><strong> <i class="fa fa-rupee" aria-hidden="true"></i>
                                                    <?php echo isset($appointments['service_charge']) ? $appointments['service_charge'] : ''; ?>
                                                </strong> </p>
                                        </div>

                                        <div class="pull-right text-right">
                                            <p> Transaction ID </p>
                                            <p><strong> <?php echo DrsPanel::getTransactionId($appointments['id']); ?> </strong> </p>
                                        </div>

                                    </div>

                                    <div class="doc-listboxes token_appointment clearfix">
                                        <div class="pull-left token-left">
                                            <p> Token Counter </p>
                                        </div>
                                        <div class="pull-right token-right">
                                            <p class="price_text"><strong> <?php echo isset($appointments['token']) ? $appointments['token'] : '' ?> </strong> </p>
                                        </div>
                                    </div>
                                    <div class="row appointment-bookbtn">


                                        <?php
                                        if ($appointments['status'] == UserAppointment::STATUS_CANCELLED || $appointments['status'] == UserAppointment::STATUS_COMPLETED) {
                                            
                                        } else {
                                            $doctor_link = $baseUrl . '/patient/live-status/' . $appointments['id'];
                                            ?>
                                            <div class="col-lg-4 col-sm-12">
                                                <div class="bookappoiment-btn">
                                                    <input type="button" value="Live Status" class="bookinput" onclick="location.href = '<?php echo $doctor_link ?>'">
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <?php if ($appointments['status'] == UserAppointment::STATUS_CANCELLED || $appointments['status'] == UserAppointment::STATUS_COMPLETED || $appointments['status'] == UserAppointment::STATUS_ACTIVE) { ?>
                                            <div class = "col-lg-4 col-sm-12">
                                                <?php
                                                $slug = isset($appointment_doctorData['slug']) ? $appointment_doctorData['slug'] : '';
                                                $doctor_link = $baseUrl . '/doctor/' . $slug;
                                                ?>
                                                <div class="bookappoiment-btn">
                                                    <input type="button" value="Rebook" class="bookinput green-btn" onclick="location.href = '<?php echo $doctor_link ?>'">
                                                </div>

                                            </div>
                                        <?php } ?>

                                        <?php
                                        $timestamp = $appointments['appointment_time'] - 60 * 60 * 2; // appointment_time - 2 hours==  && $timestamp <= time()
                                        if ($appointments['status'] == UserAppointment::STATUS_CANCELLED) {
                                            ?>
                                            <div class="col-lg-4 col-sm-12">
                                                <div class="bookappoiment-btn">
                                                    <?php if ($getTransactionDetail['refund_by'] != '') { ?>
                                                        <input type="button" value="Refund Status" class="bookinput green-btn refund_status" data-id="<?php echo $appointments['id'] ?>">
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } else { ?>
                                            <div class="col-lg-4 col-sm-12"></div>
                                        <?php } ?>

                                        <?php
                                        if ($appointments['status'] == UserAppointment::STATUS_CANCELLED || $appointments['status'] == UserAppointment::STATUS_COMPLETED || $appointments['status'] == UserAppointment::STATUS_ACTIVE) {
                                            
                                        } else {
                                            ?>
                                            <div class="col-lg-4 col-sm-12">
                                                <div class="bookappoiment-btn">
                                                    <input type="button" value="Cancel" class="bookinput cancel_appointment" data-id="<?php echo $appointments['id']; ?>">
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <div class="col-lg-4 col-sm-12">
                                            <div class="bookappoiment-btn">
                                                <input type="button" value="Back" class="bookinput" onclick="location.href = '<?php echo $baseUrl . '/patient/appointments' ?>'">
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo $this->render('@frontend/views/layouts/rightside'); ?>
            </div>
        </div>
    </div>
</section>