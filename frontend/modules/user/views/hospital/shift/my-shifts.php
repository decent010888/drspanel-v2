<?php

use common\components\DrsPanel;
$this->title = 'Drspanel :: My Shifts';
$base_url= Yii::getAlias('@frontendUrl');
?>

<div class="youare-text">Timing for Doctor: <?php echo DrsPanel::getUserName($doctor->id);?></div>
<section class="mid-content-part">

    <div class="container">
        <div class="row">
            <div class="col-md-10 mx-auto">

                <div class="today-appoimentpart">
                    <div class="col-md-12 calendra_slider">
                        <h3> My Shifts </h3>
                    </div>
                </div>


                <?php
                if(!empty($shifts)) { ?>
                    <div class="pace-part patient-prodetials">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="pace-icon">
                                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                                </div>
                                <div class="pace-right main-second">
                                    <h4>
                                        <?php echo $doctorProfile->name?>
                                    </h4>
                                    <p><?php echo DrsPanel::getDoctorSpeciality($doctorProfile->user_id) ?> </p>
                                </div>



                                <?php
                                foreach($shifts as $shift){ ?>
                                    <div class="doc-listboxes">
                                        <div class="pull-left">
                                            <p> <?php  echo $shift['shift_label']; ?></p>
                                        </div>
                                        <div class="pull-right text-right">
                                            <p> <strong><?php  echo str_replace(',', ', ', $shift['shifts_list']); ?> </strong> </p>
                                        </div>
                                    </div>
                                <?php }  ?>
                            </div>

                        </div>
                    </div>
                <?php } else {  ?>
                    <div class="col-md-12 text-center">Shifts not available.</div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

