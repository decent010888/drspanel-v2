<?php
use common\components\DrsPanel;
$experiences = DrsPanel::getDoctorExperience($user_id);
$baseUrl= Yii::getAlias('@frontendUrl');

?>
<?php if(!empty($experiences)){ ?>
<div class="pace-part patient-prodetials">
  <div class="row">
    <div class="col-sm-12">
      <div class="pace-icon"> <img src="<?php echo $baseUrl?>/images/doctor-profile-icon5.png"> <!-- <i class="fa fa-pencil-square" aria-hidden="true"></i> --> </div>
      <div class="pace-right main-second" id="experience_filter">
        <h4> Experience </h4>
          <ul class="list_profile_li">
           <?php
           $r=1;
           foreach ($experiences as $experience) {
               if($r > 4){ echo '<li id="experiencehide">';}
               else{ echo '<li>'; }
               ?>
               <?php  echo date("Y",$experience['start']); echo '-'; echo (date('Y',$experience['end']) > date("Y"))?'Till Now':date('Y',$experience['end']); ?>, <?=$experience['hospital_name']?>
               </li>
            <?php 
             $r++; } ?>
          </ul>

            <?php if($r > 4){ ?>
            <a href="javascript:void(0)" id="experience" class="seemore">See more...</a>
            <a href="javascript:void(0)" id="experience" class="seeless">See less...</a>
            <?php }  ?>
        </div>
      </div>
    </div>
  </div>
  <?php } ?>