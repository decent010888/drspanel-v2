<?php
use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;

$base_url= Yii::getAlias('@frontendUrl');
$doctorHospital=$profile;
$js="
    $('.fancybox').fancybox();
";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>
<?php $this->title = Yii::t('frontend','DrsPanel ::'.$doctorHospital->name); ?>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-9 mx-auto">
                    <div class="pace-part main-tow">
                        <div class="row">

                            <div class="col-sm-12">
                                <div class="pace-left">
                                    <?php $image = DrsPanel::getUserAvator($doctorHospital->user_id);
                                    ?>
                                    <?php    echo Lightbox::widget([
                                        'files' => [
                                            [
                                                'thumb' => $image,
                                                'original' => $image,
                                                'title' => $doctorHospital->name,
                                            ],
                                        ]
                                    ]); ?>
                                </div>
                                <div class="pace-right">
                                    <h4> <?php echo $doctorHospital->name?>
                                        <span class="ratingpart pull-right">

                                    <i class="fa fa-star"></i>  <?php $rating=DrsPanel::getRatingStatus($doctorHospital->user_id); echo $rating['rating'];?>  </span> </h4>
                                    <p>
                                        <?php $speciality_list = DrsPanel::getMyHospitalSpeciality($doctorHospital->user_id);

                                        echo DrsPanel::commaSeperatedWithSpace($speciality_list['speciality']);
                                        ?>
                                    </p>
                                    <p><?php  echo $doctorHospital->address2 ?></p>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="pace-rightpart-show">
                                    <ul class="pacemain-list">
                                        <?php
                                        if(!empty($addressImages)) {
                                            foreach ($addressImages as $key => $addressImage) {  ?>
                                                <?php $image_url=$addressImage->image_base_url.$addressImage->image_path.$addressImage->image; ?>
                                                <li <?php if(isset($class)?$class:''); ?>>
                                                    <div class="pace-list">
                                                        <a class="fancybox" rel="gallery1" href="<?php echo $image_url;?>">
                                                            <img src="<?php echo $image_url?>" alt=""  title="<?= $addressImage->image; ?>"/>
                                                        </a>
                                                    </div>
                                                </li>
                                            <?php } }  ?>
                                    </ul>

                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="hospitals-detailspt">
                        <div id="parentHorizontalTab">
                            <div class="doctforstab">
                                <ul class="resp-tabs-list hor_1">
                                    <li>Doctors</li>
                                    <li>Speciality/Treatment</li>
                                    <li>Services </li>
                                    <li>About Us</li>
                                </ul>
                            </div>
                            <div class="resp-tabs-container hor_1">
                                <div id="hospital-doctors">
                                    <?php echo $this->render('hospital-details/_doctor-slider', ['hospital' => $doctorHospital,'selected_speciality'=>$selected_speciality,'loginid' => $loginID])?>
                                </div>
                                <div id="hospital-treatment">
                                    <?php echo $this->render('hospital-details/_hospital-treatment',['doctorSpecialities' => $getspecialities])?>
                                </div>
                                <div id="hospital-services">
                                    <div class="checkservices-list">

                                        <?php
                                        $servicesList=$doctorHospital->services;
                                        echo $this->render('hospital-details/_services',['hospital' => $doctorHospital,'servicesList' => $servicesList,'userType'=>'hospital'])?>
                                    </div>
                                </div>
                                <div id="hospital-about-us">
                                    <?php echo $this->render('hospital-details/_aboutus' , ['user_id' => $doctorHospital->user_id,'name'=>$doctorHospital->name])?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
</section>