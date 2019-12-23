<?php

use common\models\UserAddress;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use common\components\DrsPanel;
$loginUser=Yii::$app->user->identity;
$baseUrl= Yii::getAlias('@frontendUrl');

$bookeing_confirm=$baseUrl."/appointment-time/".$doctorProfile->slug;

$js="
$('.booking_shifts').click(function() {    
    var checked = $(this).is(':checked');
    bookval = $(this).val();
    date = $(this).attr('data-next-date');
    $('#nextdate').val(date);
    $('#nextschedule_id').val(bookval);
    $('#formaddress_list').submit();
    //var urlLink= '".$bookeing_confirm."'"."+bookval+'?date='+date;
   // window.location.href = urlLink;
}); 
";
$this->registerJs($js,\yii\web\VIEW::POS_END); 
if(count($appointments)>0) {
    $form = ActiveForm::begin(['id' => 'formaddress_list','action'=>$bookeing_confirm]);
    foreach ($appointments as $key => $appointment) {

        if(!empty($appointment['address_id'])){ ?>
            <div class="all-listhospital <?php echo ($appointment['booking_closed'] == 1)?'disabled-block':''?>">
                <h5 style="font-size: 2.25rem;">
                    <?php echo $appointment['name'];?>
                    <span class="price_text pull-right">
                        <i class="fa fa-rupee"></i>
                        <?php if(isset($appointment['consultation_fees_discount']) && $appointment['consultation_fees_discount'] < $appointment['consultation_fees'] && $appointment['consultation_fees_discount'] > 0) { ?> <?= $appointment['consultation_fees_discount']?>/- <span class="cut-price"><?= $appointment['consultation_fees']?>/-</span> <?php } else { echo $appointment['consultation_fees'].'/-'; } ?>
                    </span>
                </h5>
                <span>
                    <?php
                    $address_length=strlen($appointment['address']);
                    if($address_length > 30){
                        $address_small = substr($appointment['address'], 0, 30);
                        $address_small=$address_small.' ...';
                    }
                    else{
                        $address_small=$appointment['address'];
                    }
                    ?>
                    <?php if($appointment['booking_closed'] == 0) { ?>
                    <input name="radio-group[]" id="test<?php echo $appointment['address_id']?><?=$appointment['schedule_id']?>" type="radio" class="booking_shifts" value="<?=$appointment['schedule_id']?>" data-next-date="<?= $appointment['next_date']; ?>">
                        <label for="test<?php echo $appointment['address_id']?><?=$appointment['schedule_id']?>"><?php echo $address_small?></label>
                <?php } else {  ?>
                        <input name="radio-group[]" id="test<?php echo $appointment['address_id']?><?=$appointment['schedule_id']?>" type="radio" class="booking_shifts" value="<?=$appointment['schedule_id']?>" data-next-date="<?= $appointment['next_date']; ?>" disabled="disabled">
                        <label for="test<?php echo $appointment['address_id']?><?=$appointment['schedule_id']?>"><?php echo $address_small?></label>
                    <?php } ?>

                </span>
                <span class="green-text <?php echo ($appointment['booking_closed'] == 1)?'disabled-class-label':''?>" style="float: right;"><?= $appointment['next_availablity']; ?></span>
                <input name="slug" id="doctor_slug" type="hidden" value="<?=$doctorProfile->slug?>">
                
                <div class="hos-lr-part">
                    <div class="pull-left">
                        <i class="fa fa-map-marker" aria-hidden="true"></i>
                        <?php
                        $getcurrent=DrsPanel::getCurrentLocationLatLong();
                        $address_detail=UserAddress::find()->where(['id'=>$appointment['address_id']])->asArray()->one();
                        $kms= DrsPanel::getKilometers($getcurrent['lat'],$getcurrent['lng'],$address_detail['lat'],$address_detail['lng']);
                        if($kms > 0){ ?>
                            <a href="javascript:void(0)"><?php echo $kms ?><i class="fa fa-location-arrow"></i></a>
                        <?php } ?>
                    </div>
                    <div class="pull-right"><i class="fa fa-clock"></i>
                    <?php echo $appointment['start_time'].' - '.$appointment['end_time']?></div>
                </div>

            </div>
        <?php }
    }?>
    <input type="hidden" name="nextdate" id="nextdate" value="" />
    <input type="hidden" name="schedule_id" id="nextschedule_id" value="" />
    <?php ActiveForm::end();
}else { ?>
        <p class="shift_not_available"> Doctor not available for online appointments.
            <br/>
            Please contact clinic.</p>
<?php } ?>