<?php 
$baseUrl= Yii::getAlias('@frontendUrl');
if(!empty($treatments)){ ?>
<div class="pace-part patient-prodetials">
  <div class="row">
    <div class="col-sm-12">
      <div class="pace-icon"> <img src="<?php echo $baseUrl?>/images/doctor-profile-icon4.png"> <!-- <i class="fa fa-heartbeat" aria-hidden="true"></i>  --></div>
      <div class="pace-right main-second" id="treatment_filter">
        <h4> Treatment  </h4>
              <?php
              $string = $treatments;
              $parts = explode(",", $string);
              echo '<ul class="list_profile_li">';
              $r=1;
              foreach($parts as $part){
                  if($r > 4){ echo '<li id="treatmenthide">'.$part.'</li>'; }
                  else{ echo '<li>'.$part.'</li>'; }
                  $r++;
              }
              echo '</ul>';

               if($r > 4){ ?>
                  <a href="javascript:void(0)" id="treatment" class="seemore">See more...</a>
                  <a href="javascript:void(0)" id="treatment" class="seeless">See less...</a>
              <?php }  ?>

        </div>
      </div>
    </div>
  </div>
  <?php } ?>