<?php
use common\components\DrsPanel;
$educations = DrsPanel::getDoctorEducation($user_id);
$baseUrl= Yii::getAlias('@frontendUrl');

?>
<?php
if(!empty($educations)){ ?>
  <div class="pace-part patient-prodetials">
    <div class="row">
      <div class="col-sm-12">
        <div class="pace-icon"> <img src="<?php echo $baseUrl?>/images/doctor-profile-icon6.png"><!--  <i class="fa fa-map-marker" aria-hidden="true"></i> --> </div>
        <div class="pace-right main-second" id="education_filter">
          <h4> Education <span class="ratingpart pull-right"></span></h4>
            <ul class="list_profile_li">
            <?php
            $r=1;
                foreach ($educations as $education) {
                if($r > 4){ echo '<li id="educationhide">';}
                    else{ echo '<li>'; }
                    ?>
                     <?=$education['education']?> - <?php echo $education['collage_name']?>, <?php  echo date("Y",$education['start']); echo '-'; echo (date('Y',$education['end']) > date("Y"))?'Till Now':date('Y',$education['end']); ?>
                    </li>
            <?php $r++; }  ?>
            </ul>

            <?php if($r > 4){ ?>
                <a href="javascript:void(0)" id="education" class="seemore">See more...</a>
                <a href="javascript:void(0)" id="education" class="seeless">See less...</a>
            <?php }  ?>
          </div>
        </div>
      </div>
    </div>
<?php } ?>