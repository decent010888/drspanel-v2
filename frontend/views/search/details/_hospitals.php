<?php

use common\components\DrsPanel;
use yii\helpers\ArrayHelper;
use branchonline\lightbox\Lightbox;

$baseUrl = Yii::getAlias('@frontendUrl');
$getcurrent = DrsPanel::getCurrentLocationLatLong();
?>

<?php
if (!empty($hospitals)) {
    foreach ($hospitals as $hospital) {
        ?>
        <div class="pace-part patient-prodetials doctors_shift_blocks">
            <div class="row">
                <div class="col-sm-12">
                    <div class="pace-icon">
                        <img src="<?php echo $baseUrl ?>/images/doctor-profile-icon3.png">
        <!--                <i class="fa fa-map-marker" aria-hidden="true"></i>
                        -->                </div>
                    <div class="pace-right main-second">
                        <h4>
                            <?php echo $hospital['name'] ?>

                            <span class="ratingpart pull-right">
                                <p> Fee  <strong>
                                        <i class="fa fa-rupee"></i>
                                        <?php if (isset($hospital['consultation_fees_discount']) && $hospital['consultation_fees_discount'] < $hospital['consultation_fees'] && $hospital['consultation_fees_discount'] > 0) { ?> <?= $hospital['consultation_fees_discount'] ?>/- <span class="cut-price"><?= $hospital['consultation_fees'] ?>/-</span> <?php
                                        } else {
                                            echo $hospital['consultation_fees'] . '/-';
                                        }
                                        ?>
                                    </strong> </p>
                            </span>


                        </h4>
                        <p>
                            <span><?php echo $hospital['address_line'] ?></span>
                            <span class="green-text" style="float: right;"><?php echo $hospital['next_availablity'] ?></span>
                        </p>
                    </div> 
                    <div class="doc-listboxes">
                        <div class="pull-left">
                            <p> <?php echo $hospital['shift_label']; ?></p>
                        </div>
                        <div class="pull-right text-right">
                            <p> <strong><?php echo $hospital['shifts_list'] ?> </strong> </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="pace-rightpart-show">
                        <ul class="pacemain-list">
                            <?php
                            // pr($hospital['hospital_images']);die;
                            if (!empty($hospital['hospital_images'])) {

                                foreach ($hospital['hospital_images'] as $key => $hospital_img) {
                                    $hospitalId = $hospital['id'];
                                    $js = "
                                        $('.fancybox'+$hospitalId).fancybox({ loop:false });
                                        ";
                                    $this->registerJs($js, \yii\web\VIEW::POS_END);
                                    ?>

                                    <li <?php if (isset($class) ? $class : '') ; ?>>
                                        <div class="pace-list"> 

                                            <a class="fancybox<?php echo $hospitalId ?>" rel="gallery1" href="<?php echo $hospital_img['image']; ?>">
                                                <img src="<?php echo $hospital_img['image']; ?>" alt="" />
                                            </a>
                                        </div>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                        <div class="away-part pull-right">
                            <?php $kms = DrsPanel::getKilometers($getcurrent['lat'], $getcurrent['lng'], $hospital['lat'], $hospital['lng']); ?>
                            <a href="javascript:void(0)">
                                <i class="fa fa-map-marker" aria-hidden="true"></i>
                                <?php echo $kms . ' away'; ?>
                            </a>
                            <br>
                            <a target="_blank" href="https://www.google.co.in/maps/dir/<?php echo $getcurrent['lat'] . ',' . $getcurrent['lng'] . '/' . $hospital['lat'] . ',' . $hospital['lng']; ?>" class="show_on_map" data-lat="<?php echo $hospital['lat']; ?>" data-lng="<?php echo $hospital['lng']; ?>"> Show On Map
                                <i class="fa fa-location-arrow"></i> 
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
?>