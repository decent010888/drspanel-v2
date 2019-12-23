<?php 
$baseUrl= Yii::getAlias('@frontendUrl'); 

//pr($doctorSpecialities);die;
?>


    <div class="doctor-timing-main">
        <?php if(!empty($doctorSpecialities['speciality'])) { ?>
            <h3>Specialization</h3>
         <?php $Servicedata = explode(',', $doctorSpecialities['speciality']);
         foreach ($Servicedata as $list) {
             $speciality=\common\models\MetaValues::find()->where(['value'=>$list,'key'=>5])->one();
             $img_url=$baseUrl."/images/doctor-profile-icon7.png";
             if(!empty($speciality)){
                 if($speciality->icon) {
                     $img_url=$speciality->base_path . $speciality->file_path.$speciality->icon;
                 }
             }

             ?>
             <div class="morning-parttiming">
              <div class="main-todbox">
                <div class="pull-left">
                  <div class="moon-cionimg">
                      <img src="<?php echo $img_url ?>" alt="image">
                    <span id="hospital-name" ><?php echo $list ?></span></div>
                  </div>
                </div>
              </div>
          <?php } 
        }
        else { ?>
        You have no speciality
        <?php } ?>
        <?php
        if(!empty($doctorSpecialities['treatments'])) {
          ?> <h3 style="margin-top:15px;">Treatments</h3> <?php
          $Treatmentdata = explode(',', $doctorSpecialities['treatments']);
          foreach ($Treatmentdata as $list) {
              $treatment=\common\models\MetaValues::find()->where(['value'=>$list,'key'=>9])->one();
              $img_url=$baseUrl."/images/doctor-profile-icon4.png";
              if(!empty($treatment)){
                  if($treatment->icon) {
                      $img_url=$treatment->base_path . $treatment->file_path.$treatment->icon;
                  }
              }

              ?>
            <div class="morning-parttiming">
              <div class="main-todbox">
                <div class="pull-left">
                  <div class="moon-cionimg"><img src="<?php echo $img_url ?>" alt="image">
                    <span id="hospital-name" ><?php echo $list ?></span></div>
                  </div>
                </div>
              </div>
              <?php 
              } 
            } 
            ?>
          </div>



