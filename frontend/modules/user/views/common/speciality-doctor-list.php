<?php
$baseUrl= Yii::getAlias('@frontendUrl');

if(isset($page_heading)){
    $page_heading=$page_heading;
}
else{
    $page_heading='';
}

if($actionType == 'appointment') {
    $this->title = Yii::t('frontend','DrsPanel :: Appointment');
}
else{
    $this->title = Yii::t('frontend','DrsPanel :: '.($page_heading)?$page_heading:'Patient History');
}
?>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="today-appoimentpart" style="margin-bottom: 15px;">
                        <?php if($actionType == 'appointment') { ?>
                            <h3> Appointment</h3>
                        <?php }else {  ?>
                            <h3> <?php echo isset($page_heading)?$page_heading:'Patient History'?> </h3>
                        <?php } ?>
                    </div>
                    <div class="hospitals-detailspt appointment_list">
                        <?php if(isset($data_array['data']) && !empty($data_array['data'])) { ?>
                            <div id="booked-appointment">
                                <?php echo $this->render('/common/_doctors_list',['data_array'=>$data_array,'hospital'=>$hospital,'selected_speciality'=>$selected_speciality,'type'=>$type,'actionType'=>$actionType,'userType'=>$userType]);?>
                            </div>
                        <?php } else { ?>
                            <div> No Doctors Found </div>
                        <?php } ?>

                        <div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
