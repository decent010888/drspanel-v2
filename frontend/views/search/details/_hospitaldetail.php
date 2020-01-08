<?php

use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;
use common\models\UserAddress;

$base_url = Yii::getAlias('@frontendUrl');
$doctorHospital = $profile;

$js = "
    $('.fancybox').fancybox();
";
$this->registerJs($js, \yii\web\VIEW::POS_END);
?>
<?php $this->title = Yii::t('frontend', 'DrsPanel ::' . $doctorHospital->name); ?>
<section class="mid-content-part">
    <div class="signup-part doctor_detail_div">
        <div class="container">
            <div class="row">
                <div class="col-md-9">
                    <?php //echo $this->render('_search');  ?>
                    <div class="pace-part">
                        <div class="row">

                            <div class="col-sm-12">
                                <div class="pace-left">
                                    <?php $image = DrsPanel::getUserAvator($doctorHospital->user_id);
                                    ?>
                                    <?php
                                    echo Lightbox::widget([
                                        'files' => [
                                            [
                                                'thumb' => DrsPanel::getUserThumbAvator($doctorHospital->user_id),
                                                'original' => $image,
                                                'title' => $doctorHospital->name,
                                            ],
                                        ]
                                    ]);
                                    ?>
                                </div>
                                <div class="pace-right">
                                    <h4> <?php echo $doctorHospital->name ?>
                                        <span class="ratingpart pull-right">
                                            <i class="fa fa-star"></i>  <?php
                                            $rating = DrsPanel::getRatingStatus($doctorHospital->user_id);
                                            echo $rating['rating'];
                                            ?>  </span> </h4>
                                    <p>
                                        <?php
                                        $speciality_list = DrsPanel::getMyHospitalSpeciality($doctorHospital->user_id);
                                        echo DrsPanel::commaSeperatedWithSpace($speciality_list['speciality']);
                                        ?>
                                    </p>
                                    <p>
                                        <span>
                                            <?php echo DrsPanel::getAddressShow($doctorHospital->address_id); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="pace-rightpart-show">
                                    <ul class="pacemain-list">
                                        <?php
                                        if (!empty($addressImages)) {
                                            foreach ($addressImages as $key => $addressImage) {
                                                ?>
                                                <?php $image_url = $addressImage->image_base_url . $addressImage->image_path . $addressImage->image; ?>
                                                <li <?php if (isset($class) ? $class : '') ; ?>>
                                                    <div class="pace-list">
                                                        <a class="fancybox" rel="gallery1" href="<?php echo $image_url; ?>">
                                                            <img src="<?php echo $image_url ?>" alt=""  title="<?= $addressImage->image; ?>"/>
                                                        </a>
                                                    </div>
                                                </li>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </ul>
                                    <div class="away-part pull-right hide">
                                        <?php
                                        $getcurrent = DrsPanel::getCurrentLocationLatLong();
                                        $address_detail = UserAddress::findOne($doctorHospital->address_id);
                                        $kms = 0;
                                        if ($address_detail) {
                                            $kms = DrsPanel::getKilometers($getcurrent['lat'], $getcurrent['lng'], $address_detail->lat, $address_detail->lng);
                                        }
                                        if ($kms > 0) {
                                            ?>
                                            <a tabindex="-1" href="javascript:void(0)"><?php echo $kms ?><i class="fa fa-location-arrow"></i></a>
                                        <?php } ?>
                                        <a href="#"> Get Direction <i class="fa fa-location-arrow"></i> </a>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="hospitals-detailspt">
                        <div id="parentHorizontalTab">
                            <div class="doctforstab">
                                <ul class="resp-tabs-list hor_1">
                                    <li>Doctors</li>
                                    <li>Treatments</li>
                                    <li>Services </li>
                                    <li>About Us</li>
                                </ul>
                            </div>
                            <div class="resp-tabs-container hor_1">
                                <div id="hospital-doctors">
                                    <?php echo $this->render('_doctor-slider', ['hospital' => $doctorHospital, 'selected_speciality' => $selected_speciality, 'loginid' => $loginID]) ?>
                                </div>
                                <div id="hospital-treatment">
                                    <?php echo $this->render('_hospital-treatment', ['doctorSpecialities' => $getspecialities]) ?>
                                </div>
                                <div id="hospital-services">
                                    <div class="checkservices-list">

                                        <?php
                                        $servicesList = $doctorHospital->services;
                                        echo $this->render('_services', ['hospital' => $doctorHospital, 'servicesList' => $servicesList, 'userType' => 'hospital'])
                                        ?>
                                    </div>
                                </div>
                                <div id="hospital-about-us">
                                    <?php echo $this->render('_aboutus', ['user_id' => $doctorHospital->user_id, 'name' => $doctorHospital->name]) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php echo $this->render('/layouts/rightside'); ?>
            </div>
        </div>
</section>

