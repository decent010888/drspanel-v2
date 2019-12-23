<?php
use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;
$baseUrl=Yii::getAlias('@frontendUrl');

$groupAlias= DrsPanel::getusergroupalias($doctor['id']);
$listAddress=DrsPanel::getBookingAddressShifts($doctor['id'],date('Y-m-d'));
$getcurrent=DrsPanel::getCurrentLocationLatLong();


$fees=$doctor['userProfile']['consultation_fees'];
$fees_discount=$doctor['userProfile']['consultation_fees_discount'];
$address_detail=\common\models\UserAddress::findOne($doctor['userProfile']['address_id']);
$address=DrsPanel::getAddressLine($doctor['userProfile']['address_id']);

?>
<div class="col-sm-6">
    <div class="doctoe_listing_one profile_detail_section" data-url="<?php echo $baseUrl.'/doctor/'.$doctor['userProfile']['slug']?>" data-slug="<?php echo $doctor['userProfile']['slug']?>">
        <div class="doctor_detail_left">
            <div class="image_doc">
                <?php $image = DrsPanel::getUserAvator($doctor['id']);?>
                <?php    echo Lightbox::widget([
                    'files' => [
                        [
                            'thumb' => DrsPanel::getUserThumbAvator($doctor['id']),
                            'original' => $image,
                            'title' => DrsPanel::getUserName($doctor['id']),
                        ],
                    ]
                ]); ?>
            </div>
            <div class="doc_specify">
                <h4>
                    <a href="<?php echo $baseUrl.'/doctor/'.$doctor['userProfile']['slug']?>">
                        <?php echo DrsPanel::getUserName($doctor['id']);?></a>
                    <div class="review_top_details pull-right">
                        <div class="rate-ex1-cnt yellow-star">
                            <?php
                            $total_rating = Drspanel::getRatingStatus($doctor['id']);
                            if(!empty($total_rating))
                            { ?>
                                <i class="fa fa-star" aria-hidden="true"></i> <?php echo isset($total_rating['rating'])?$total_rating['rating']:'0';?>
                                <?php
                            }
                            ?>
                        </div>
                    </div>


                </h4>
                <p><?= $doctor['userProfile']['speciality'] ?> </p>
                <p class="text"><?php
                    echo $address;
                    ?>
                </p>
            </div>
            <div class="doctor-feeandm">
                <ul>
                    <li> Exp. <?php echo isset($doctor['userProfile']['experience'])?$doctor['userProfile']['experience']:'0' ?> Years  </li>
                    <li>
                        <?php
                        $kms= DrsPanel::getKilometers($getcurrent['lat'],$getcurrent['lng'],$address_detail->lat,$address_detail->lng);
                        if($kms > 0){ ?>
                            <i class="fa fa-map-marker" aria-hidden="true"></i>
                            <a href="javascript:void(0)"><?php echo $kms ?></a>
                        <?php } ?>
                    </li>
                    <li>
                        <i class="fa fa-rupee" aria-hidden="true"></i>
                        <?php if(isset($fees_discount) &&
                            $fees_discount < $fees && $fees_discount > 0) { ?> <?= $fees_discount?>/- <span class="cut-price"><?= $fees?>/-</span> <?php } else { echo $fees.'/-'; } ?>
                    </li>
                </ul>
            </div>
        </div>
        <div class="button_bottom_c text-center hide">
            <a href="#" class="view_pro_appoint new_bookbtn"> Book Appointment </a>
        </div>
        <div class="button_bottom_c text-center patient_book_appointment">
                <a href="javascript:void(0)" data-slug="<?php echo $doctor['userProfile']['slug']; ?>" id="id_<?php echo $doctor['userProfile']['slug']?>" class="view_pro_appoint new_bookbtn doctor-addresss-list" > Book Appointment </a>
        </div>
    </div>
</div>