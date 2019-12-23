<?php

use common\components\DrsPanel;
use common\models\UserProfile;

$base_url = Yii::getAlias('@frontendUrl');

if (!empty($data_array)) {
    ?>
    <?php
    $categories = $data_array['speciality'];
    $doctorList = $data_array['data'];
    ?>
    <div class="slider hospitaldoctor-category">
        <?php
        if (!empty($categories)) {
            foreach ($categories as $hslider) {
                ?>
                <?php
                if ($actionType == 'appointment') {
                    $searchUrl = yii\helpers\Url::to(['/' . $userType . '/appointments?type=' . $type . '&speciality=' . $hslider['id']]);
                } elseif ($actionType == 'user_history') {
                    $searchUrl = yii\helpers\Url::to(['/' . $userType . '/user-statistics-data?speciality=' . $hslider['id']]);
                } elseif ($actionType == 'day-shifts') {
                    $searchUrl = yii\helpers\Url::to(['/' . $userType . '/day-shifts?speciality=' . $hslider['id']]);
                } elseif ($actionType == 'my-shifts') {
                    $searchUrl = yii\helpers\Url::to(['/' . $userType . '/my-shifts?speciality=' . $hslider['id']]);
                } else {
                    $searchUrl = yii\helpers\Url::to(['/' . $userType . '/patient-history?speciality=' . $hslider['id']]);
                }
                ?>
                <div onclick="location.href = '<?php echo $searchUrl; ?>';">
                    <div class="detailmain-box <?php echo ($selected_speciality == $hslider['id']) ? 'detailmain_selected' : '' ?>">
                        <div class="detial-imgmain">
                            <?php if ($hslider['icon'] == '') { ?>
                                <img src="<?php echo $base_url ?>/images/doctors1.png" alt="image">
                            <?php } else { ?>
                                <img src="<?php echo $hslider['icon']; ?>" alt="image">
                            <?php } ?>
                        </div>
                    </div>
                    <div class="hos-discription"> <p><?php echo $hslider['value'] ?></p><span>(<?php echo isset($hslider['count']) ? $hslider['count'] : '0' ?>) <span></div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                        </div>
                        <div class="mt-top25p" id="doctors_list_div">
                            <div class="row">
                                <?php
                                if (!empty($doctorList)) {
                                    foreach ($doctorList as $doctor) {
                                        ?>
                                        <div class="col-sm-6 col-md-4">
                                            <div class="pace-part main-tow doc-list-new">
                                                <div class="row">
                                                    <div class="col-sm-12">
                                                        <div class="pace-left">

                                                            <?php
                                                            $image = DrsPanel::getUserAvator($doctor['user_id']);
                                                            echo \branchonline\lightbox\Lightbox::widget([
                                                                'files' => [
                                                                    [
                                                                        'thumb' => $image,
                                                                        'original' => $image,
                                                                        'title' => $doctor['name'],
                                                                    ],
                                                                ]
                                                            ]);
                                                            ?>
                                                        </div>
                                                        <div class="pace-right">
                                                            <h4><?php echo $doctor['name'] ?></h4>
                                                            <p> <?php echo $doctor['speciality'] ?> </p>
                                                            <div class="rate-ex1-cnt yellow-star pull-right starRate">
                                                                <?php
                                                                $total_rating = Drspanel::getRatingStatus($doctor['user_id']);
                                                                if (!empty($total_rating)) {
                                                                    ?>
                                                                    <i class="fa fa-star yellow-star" aria-hidden="true"></i> <?php echo isset($total_rating['rating']) ? $total_rating['rating'] : '0'; ?>
                                                                    <?php
                                                                }
                                                                ?>
                                                            </div>
                                                            <div class="doc-listboxes">
                                                                <div class="pull-left">
                                                                    <?php if ($doctor['experience'] > 0) { ?>
                                                                        <p> <?php echo $doctor['experience'] ?> + years </p>
                                                                    <?php } ?>
                                                                </div>
                                                                <div class="pull-right text-right">

                                                                    <?php
                                                                    if ($userType == 'attender') {
                                                                        $login_id = Yii::$app->user->id;
                                                                        $getParentDetails = DrsPanel::getParentDetails($login_id);
                                                                        $parentGroup = $getParentDetails['parentGroup'];
                                                                        if ($parentGroup == \common\models\Groups::GROUP_HOSPITAL) {
                                                                            $parent_id = $getParentDetails['parent_id'];
                                                                        } else {
                                                                            $parent_id = $getParentDetails['parent_id'];
                                                                        }
                                                                    } else {
                                                                        $parent_id = Yii::$app->user->id;
                                                                    }
                                                                    $firstAddress = DrsPanel::hospitalDoctorFees($parent_id, $doctor['user_id']);
                                                                    $fees = $firstAddress['consultation_fees'];
                                                                    $fees_discount = $firstAddress['consultation_fees_discount'];
                                                                    ?>

                                                                    <p> Fee:
                                                                        <i class="fa fa-rupee" aria-hidden="true"></i>
                                                                        <?php
                                                                        if (isset($fees_discount) &&
                                                                                $fees_discount < $fees && $fees_discount > 0) {
                                                                            ?> <?= $fees_discount ?>/- <span class="cut-price"><?= $fees ?>/-</span> <?php
                                                                        } else {
                                                                            echo $fees . '/-';
                                                                        }
                                                                        ?>

                                                                    </p>
                                                                </div>

                                                            </div>
                                                        </div>
                                                        <div class="bookappoiment-btn">
                                                            <?php
                                                            if ($actionType == 'appointment') {
                                                                $doctorUrl = $base_url . '/' . $userType . '/appointments/' . $doctor['slug'];
                                                                $label = 'Book Appointment';
                                                            } elseif ($actionType == 'user_history') {
                                                                $doctorUrl = $base_url . '/' . $userType . '/user-statistics-data/' . $doctor['slug'];
                                                                $label = 'View History';
                                                            } elseif ($actionType == 'day-shifts') {
                                                                $doctorUrl = $base_url . '/' . $userType . '/day-shifts/' . $doctor['slug'];
                                                                $label = 'View Timing';
                                                            } elseif ($actionType == 'my-shifts') {
                                                                $doctorUrl = $base_url . '/' . $userType . '/my-shifts/' . $doctor['slug'];
                                                                $label = 'View Shifts';
                                                            } else {
                                                                $doctorUrl = $base_url . '/' . $userType . '/patient-history/' . $doctor['slug'];
                                                                $label = 'View';
                                                            }
                                                            ?>
                                                            <?php $groupAlias = DrsPanel::getusergroupalias($doctor['user_id']) ?>
                                                            <a href="<?php echo $doctorUrl; ?>" class="bookinput bookinput_color" ><?php echo $label; ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    } else {
                        
                    }
                    ?>