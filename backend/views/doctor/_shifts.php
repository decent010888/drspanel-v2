<?php

use common\models\UserSchedule;
use backend\models\AddScheduleForm;
use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;
use common\models\UserServiceCharge;

$shifts = DrsPanel::getShiftListByAddress($doctor_id, $list['id']);
$shifts2 = DrsPanel::getShiftListByAddress2($doctor_id, $list['id']);
$current_user_roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
?>
<style>
    .pace-icon i {
        font-size: 40px;
        color: #000;
    }
    .cut-price {
        padding-right: 10px;
        text-decoration: line-through;
        color: #ccc;
    }
</style>
<div class="pace-part patient-prodetials">
    <div class="row">
        <div class="col-sm-12">
            <div class="pace-icon">
                <i class="fa fa-map-marker" aria-hidden="true"></i>
            </div>
            <div class="pace-right main-second">
                <h4>
                    <?php echo $list['name'] ?>
                </h4>
                <p><?php echo $list['address_line'] ?> </p>
            </div>
            <div class="serviceCharge" style="position: absolute; right: 42%; top: 14px;">
                <?php
                foreach ($shifts2 as $key => $shift) {
                    $getSrviceCharge = UserServiceCharge::find()->where(['address_id' => $shift['id']])->one();
                    if ($getSrviceCharge) {
                        ?>
                        <span><?php echo 'Service Charge: ' . $getSrviceCharge['charge']; ?></span> /
                        <span><?php echo 'Discount: ' . $getSrviceCharge['charge_discount'] ?></span>
                        <?php
                    }
                }
                ?>
            </div>
            <div class="shift_edit_icon_main location pull-right ">
                <a class="modal-call check_service" title="Add/Update Service Charge" href="javascript:void(0)" data-id="<?php echo $list['id']; ?>">
                    <i class="fa fa-rupee"></i>
                </a>

                <a class="modal-call check_service" title="Edit Shift" href="<?php echo yii\helpers\Url::to(['doctor/edit-shift?id=' . $list['id'] . '&user_id=' . $doctor_id]); ?>" data-id="<?php echo $list['id']; ?>">
                    <i class="fa fa-pencil"></i>
                </a>
            </div>

            <?php
            foreach ($shifts as $shift) {
                ?>
                <div class="doc-listboxes">
                    <div class="pull-left">
                        <p> <?php echo $shift['shift_label']; ?>
                            &nbsp;&nbsp; Fee  <strong>
                                <i class="fa fa-rupee"></i>
                                <?php if (isset($shift['consultation_fees_discount']) && $shift['consultation_fees_discount'] < $shift['consultation_fees'] && $shift['consultation_fees_discount'] > 0) { ?> <?= $shift['consultation_fees_discount'] ?>/- <span class="cut-price"><?= $shift['consultation_fees'] ?>/-</span> <?php
                                } else {
                                    echo $shift['consultation_fees'] . '/-';
                                }
                                ?>
                            </strong>



                        </p>
                    </div>
                    <div class="pull-right text-right">
                        <p> <strong><?php echo str_replace(',', ', ', $shift['shifts_list']); ?> </strong> </p>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="col-sm-12">
            <div class="pace-rightpart-show">
                <ul class="pacemain-list">
                    <?php
                    if (!empty($list['images_list'])) {
                        foreach ($list['images_list'] as $key => $hospital_img) {
                            ?>
                            <li <?php if (isset($class) ? $class : '') ; ?>>
                                <div class="pace-list">
                                    <a class="fancybox" rel="gallery1" href="<?php echo $hospital_img['image']; ?>">
                                        <img src="<?php echo $hospital_img['image'] ?>" alt="" />
                                    </a>
                                </div>
                            </li>
                            <?php
                        }
                    }
                    ?>
                </ul>

            </div>
        </div>
    </div>
</div>

