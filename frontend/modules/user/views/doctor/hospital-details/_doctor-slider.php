<?php 
use common\components\DrsPanel;
use common\models\UserProfile;
use branchonline\lightbox\Lightbox;

$base_url = Yii::getAlias('@frontendUrl');

$params['user_id']=$hospital->user_id;
$params['filter']=json_encode(array(['type'=>'speciality','list'=>[$selected_speciality]]));
$data_array =  DrsPanel::getMyDoctorList($params);
if(!empty($data_array)){ ?>
    <?php
    $categories=$data_array['speciality'];
    $doctorList=$data_array['data'];
    ?>
    <div class="slider hospitaldoctor-category">
        <?php
        if(!empty($categories)) {
            foreach ($categories as $hslider) { ?>
                <div onclick="location.href='<?php echo yii\helpers\Url::to(['/doctor/hospital/'.$hospital->slug.'?speciality='.$hslider['id']]); ?>';">
                    <div class="detailmain-box <?php echo ($selected_speciality == $hslider['id'])?'detailmain_selected' : '' ?>">
                        <div class="detial-imgmain">
                            <?php if($hslider['icon']=='') { ?>
                                <img src="<?php echo $base_url?>/images/doctors1.png" alt="image">
                            <?php } else { ?>
                                <img src="<?php echo $hslider['icon']; ?>" alt="image">
                            <?php  }?>
                        </div>
                    </div>
                    <div class="hos-discription"> <p><?php echo $hslider['value']?></p><span>(<?php echo isset($hslider['count'])?$hslider['count']:'0' ?>) <span></div>
                </div>
            <?php } }?>
    </div>
    <div class="mt-top25p" id="doctors_list_div">
        <div class="row">
            <?php
            if(!empty($doctorList)){
                foreach ($doctorList as $doctor) {
                    ?>
                    <div class="col-sm-6">
                        <div class="pace-part main-tow doc-list-new">
                            <div class="row ">
                                <div class="col-sm-12">
                                    <div class="pace-left">
                                            <?php
                                            $image = DrsPanel::getUserAvator($doctor['user_id']);
                                                echo Lightbox::widget([
                                            'files' => [
                                            [
                                            'thumb' => $image,
                                            'original' => $image,
                                            'title' => $doctor['name'],
                                            ],
                                            ]
                                            ]); ?>
                                      
                                    </div>
                                    <div class="pace-right">
                                        <h4><?php echo $doctor['name']?></h4>
                                        
                                        <p> <?php echo $doctor['speciality']?> </p>
                                        <div class="rate-ex1-cnt yellow-star pull-right starRate">
                                            <?php
                                            $total_rating = Drspanel::getRatingStatus($doctor['user_id']);
                                            if(!empty($total_rating))
                                            { ?>
                                                <i class="fa fa-star yellow-star" aria-hidden="true"></i> <?php echo isset($total_rating['rating'])?$total_rating['rating']:'0';?>
                                            <?php 
                                            }
                                            ?>
                                        </div>
                                        <div class="doc-listboxes">
                                            <div class="pull-left"><p><strong> <?php echo $doctor['experience']?> </strong> + years </p></div>
                                            <div class="pull-right text-right">

                                                <?php
                                                $firstAddress=DrsPanel::hospitalDoctorFees($hospital->user_id,$doctor['user_id']);
                                                $fees=$firstAddress['consultation_fees'];
                                                $fees_discount=$firstAddress['consultation_fees_discount'];
                                                ?>
                                                <p> Fee:
                                                    <i class="fa fa-rupee" aria-hidden="true"></i>
                                                    <?php if(isset($fees_discount) &&
                                                        $fees_discount < $fees && $fees_discount > 0) { ?> <?= $fees_discount?>/- <span class="cut-price"><?= $fees?>/-</span> <?php } else { echo $fees.'/-'; } ?>

                                                </p>
                                            </div>

                                        </div>
                                    </div>

                                   
                                   

                                </div>
                            </div>
                        </div>
                    </div>

                <?php } } ?>
        </div>
    </div>
<?php }
else{

}
?>



