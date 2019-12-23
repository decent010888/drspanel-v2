<?php
use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;
use common\models\UserAppointment;

$baseUrl=Yii::getAlias('@frontendUrl');

$this->title = Yii::t('frontend','Appointment  :: Live Status');

$js="
        function autoRefreshPage(){
            window.location = window.location.href;
        }
        setInterval('autoRefreshPage()', 30000);
    
    ";
$this->registerJs($js,\yii\web\VIEW::POS_END);

?>
<section class="mid-content-part live-patient-screen">
    <div class="signup-part">
        <div class="container">

            <div class="row">
                <div class="col-md-12 mx-auto">
                    <div class="appointment_part">
                        <div class="pace-part main-tow">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="pace-left mb-3">
                                        <?php $image = DrsPanel::getUserAvator($userAppointment->doctor_id);?>

                                        <?php
                                        echo Lightbox::widget([
                                            'files' => [
                                                [
                                                    'thumb' => $image,
                                                    'original' => $image,
                                                    'title' => $userAppointment->doctor_name,
                                                ],
                                            ]
                                        ]);
                                        ?>
                                    </div>
                                    <div class="pace-right">
                                        <h4><?php echo isset($userAppointment->doctor_name)?$userAppointment->doctor_name:''?></h4>
                                        <p> <i class="fa fa-phone" aria-hidden="true"></i> <?php echo $userAppointment->doctor_phone?></p>
                                        <div class="doc-listboxes">
                                            <div class="pull-left">
                                                <p> Date and Time </p>
                                                <p><strong> <?php if(isset($userAppointment->date)) { echo date('d M Y' , strtotime($userAppointment->date)); }?>,  <?php echo isset($userAppointment->shift_label)?$userAppointment->shift_label:'' ?> </strong> </p>
                                            </div>
                                            <div class="pull-right text-right">
                                                <p> Your Token </p>
                                                <p class="live_token"><strong><?php echo $userAppointment->token ?></strong></p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="col-md-12 mx-auto resp-tabs-container hor_1">

                <div>

                    <!-- Token Start -->

                    <div class="doc-boxespart-book">
                        <div class="row">
                            <?php foreach($appointments as $appointment) {?>
                                <div class="col-sm-12">
                                    <div class="token_allover latest_token">
                                        <div class="token avail">
                                            <h3><span class="color_token <?php echo $appointment['status_class']?>"> <?php echo $appointment['token'];?> </span></h3>
                                        </div>
                                        <div class="token-rightdoctor">
                                            <div class="token-timingdoc emergency">
                                                <div class="token_box">
                                                    <span class="call-no <?php echo $appointment['status_class']?>">
                                                        <?php echo strtoupper($appointment['status'])?>
                                                    </span>
                                                </div>
                                                <div class="token_box pl-3">
                                                    <h3 class="mt-2"> Appointment Time </h3>
                                                    <span class="call-no green-text">
                                                        <?php echo $appointment['appointment_time']; ?>
                                                    </span>
                                                </div>
                                                <div class="token_box bdr-none pl-3">
                                                    <h3 class="mt-2"> <?php echo $appointment['time_label']; ?> </h3>
                                                    <span class="call-no <?php echo $appointment['time_class'] ?>"> <?php echo $appointment['time']; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                    </div>

                    <!-- Token Start End -->

                </div>

            </div>
        </div>
    </div>
</section>