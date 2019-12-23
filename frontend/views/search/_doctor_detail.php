<?php
use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;
use common\models\UserAddress;
use kartik\date\DatePicker;
$baseUrl= Yii::getAlias('@frontendUrl');

?>
<div class="row">
    <div class="col-sm-12 patient_appointment_detail">
        <div class="pace-left">
            <?php $image = DrsPanel::getUserAvator($doctor->id);?>
            <!--  <img src="<?php //echo $image; ?>" alt="image"/> -->
            <?php
            echo Lightbox::widget([
                'files' => [
                    [
                        'thumb' => $image,
                        'original' => $image,
                        'title' => 'optional title',
                    ],
                ]
            ]);
            ?>
        </div>
        <div class="pace-right">
            <h4>
                <div><?= $doctor['userProfile']['name'] ?></div>
                <div class="calender_div_patient">
                    <?php
                    $defaultCurrrentDay=strtotime(date('Y-m-d'));
                    echo DatePicker::widget([
                        'name' => 'appointment_date',
                        'type' => DatePicker::TYPE_BUTTON,
                        'value' => date('d M Y',$defaultCurrrentDay),
                        'id'=>  'appointment-date',
                        'buttonOptions'=>[
                            'label' => '<img src="'.$baseUrl.'/images/celander_icon.png" alt="image"/>',
                        ],
                        'pluginOptions' => [
                            'autoclose'=>true,
                            'format' => 'dd M yyyy',
                            'startDate' => date('d M Y',$defaultCurrrentDay),
                        ],
                        'pluginEvents' => [
                            "change" => "function(){
                                            patientBookingDate($('#appointment-date').val(),'shifts','patient',$doctor->id);
                                        }",
                        ],
                    ]);
                    ?>
                </div>
            </h4>
            <p> <?= $doctor['userProfile']['speciality']; ?> </p>
            <p>
                <i class="fa fa-calendar"></i>
                <span id="doctor-date"> <?php  echo isset($date)?$date:''; ?> </span>
                <span class="pull-right">
                                                <strong><i class="fa fa-rupee"></i><?php if(isset($scheduleDay['consultation_fees_discount']) && $scheduleDay['consultation_fees_discount'] < $scheduleDay['consultation_fees'] && $scheduleDay['consultation_fees_discount'] > 0) { ?> <?= $scheduleDay['consultation_fees_discount']?>/- <span class="cut-price"><?= $scheduleDay['consultation_fees']?>/-</span> <?php } else { echo $scheduleDay['consultation_fees'].'/-'; } ?></strong>
                                            </span>
            </p>
            <p>
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                <?php echo date('h:i a',$scheduleDay['start_time']); ?> - <?php echo date('h:i a',$scheduleDay['end_time']); ?>
            </p>
            <div class="pull-left">
                <p>
                    <strong><?php echo DrsPanel::getHospitalName($scheduleDay['address_id'])?></strong>
                    <br><?php echo DrsPanel::getAddressShow($scheduleDay['address_id']); ?>
                </p>
            </div>
            <div class="pull-right">
                <?php
                $getcurrent=DrsPanel::getCurrentLocationLatLong();
                $address_detail=UserAddress::findOne($scheduleDay['address_id']);
                $kms= DrsPanel::getKilometers($getcurrent['lat'],$getcurrent['lng'],$address_detail->lat,$address_detail->lng);
                if($kms > 0){ ?>
<!--                    <i class="fa fa-map-marker" aria-hidden="true"></i>
-->                    <a href="javascript:void(0)"><?php echo $kms ?><i class="fa fa-location-arrow"></i></a>
                <?php } ?>

                <!--<a href="#" data-toggle="modal" data-target="#myModal">0.8 km<i class="fa fa-location-arrow"></i>
                </a>-->
            </div>
        </div>
    </div>
</div>