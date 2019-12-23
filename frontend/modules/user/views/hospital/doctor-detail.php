<?php $baseUrl= Yii::getAlias('@frontendUrl');
use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;
use common\models\User;

$doctorProfile=$profile;

$this->title = Yii::t('frontend','DrsPanel :: '.$doctorProfile->prefix.$doctorProfile->name);
$this->registerJsFile($baseUrl.'/js/popper.min.js', ['depends' => [yii\web\JqueryAsset::className()]]);
$loginUser=$loginid;
$doctor_id=$doctorProfile->user_id;

$user_detail=User::findOne($doctor_id);

$slug="'".$doctorProfile->slug."'";

?>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-9 mx-auto">
                    <div class="pace-part main-tow">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="pace-left">
                                    <?php
                                    $image  = DrsPanel::getUserAvator($doctorProfile->user_id);
                                    echo Lightbox::widget([
                                        'files' => [
                                            [
                                                'thumb' => $image,
                                                'original' => $image,
                                                'title' => $doctorProfile->name,
                                            ],
                                        ]
                                    ]);
                                    ?>
                                </div>
                                <div class="pace-right">
                                    <h4><?= $doctorProfile->name ?></h4>
                                    <p><?= $doctorProfile->speciality ?></p>
                                    <div class="form-group">
                                        <p>Exp: <strong> <?php echo !empty($doctorProfile->experience)?$doctorProfile->experience:'0';?> + </strong> years</p>
                                    </div>
                                    <div class="review_top_details hide">
                                        <div class="rate-ex1-cnt yellow-star">
                                            <?php
                                            $total_rating = Drspanel::getRatingStatus($doctorProfile->user_id);
                                            if(!empty($total_rating)) { ?>
                                                <i class="fa fa-star" aria-hidden="true"></i> <?php echo isset($total_rating['rating'])?$total_rating['rating']:'0';?>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>


                                </div>
                                <div class="clearfix"></div>

                                <div class="likesicon-part">
                                    <div class="add-record record_part text-right">
                                        <ul>
                                            <li class="yellow-star star_rat">
                                                <?php
                                                $total_rating = Drspanel::getRatingStatus($doctorProfile->user_id);
                                                if(!empty($total_rating))
                                                { ?>
                                                    <i class="fa fa-star yellow-star" aria-hidden="true"></i> <?php echo isset($total_rating['rating'])?$total_rating['rating']:'0';?>
                                                    <?php
                                                }
                                                ?>
                                            </li>
                                        </ul>
                                        <a href="#" class="ssbp-btn" style="display:none;"></a>
                                    </div>


                                </div>
                            </div>

                        </div>
                    </div>
                    <?php
                    $date = date('Y-m-d');
                    $doctorHospital = DrsPanel::getBookingAddressShifts($doctorProfile->user_id, $date);
                    echo $this->render('doctor-details/_hospitals',['hospitals' => $doctorHospital]);
                    ?>
                    <?php echo $this->render('doctor-details/_description',['description' =>$doctorProfile->description,'name'=>$doctorProfile->name]); ?>
                    <?php echo $this->render('doctor-details/_treatments',['treatments' =>$doctorProfile->treatment]); ?>
                    <?php
                    $servicesList=$doctorProfile->services;
                    echo $this->render('doctor-details/_services',['doctor' =>$doctorProfile,'servicesList'=>$servicesList,'userType'=>'doctor']); ?>
                    <?php echo $this->render('doctor-details/_degrees',['degrees' =>$doctorProfile->degree]); ?>
                    <?php echo $this->render('doctor-details/_education',['user_id' => $doctorProfile->user_id]); ?>
                    <?php echo $this->render('doctor-details/_experience',['user_id' => $doctorProfile->user_id]); ?>
                    <?php // echo $this->render('feedback'); ?>
                </div>
            </div>
        </div>
    </div>
</section>