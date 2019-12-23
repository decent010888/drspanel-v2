<?php
use yii\helpers\Html;
use common\models\MetaKeys;
use common\models\MetaValues;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use kartik\select2\Select2;
 $base_url= Yii::getAlias('@frontendUrl'); ?>
<?php  $this->title = Yii::t('frontend', 'Hospital::Profile', [
  'modelAddressClass' => 'Hospital',
  ]);

  ?>
  <div class="inner-banner"> </div>
  <section class="mid-content-part">
    <div class="signup-part">
      <div class="container">
        <div class="row">
          <div class="col-md-8 mx-auto">
            <div class="appointment_part">
              <div class="appointment_details">
                <div class="pace-part main-tow">
                  <div class="row">
                    <div class="col-sm-12">
                        <div class="pace-left">
                            <?php $getuserImage=DrsPanel::getUserAvator($userProfile->user_id,'thumb');?>
                            <img src="<?php echo$getuserImage; ?>" alt="image">
                        </div>
                       <div class="pace-right">
                         <h4><?php echo $userProfile->name; ?></h4>
                           <div class="doctor-educaation-details">
                          <p>Specialization : <span> <?php echo $userProfile->speciality; ?> </span> </p>
                          <?php if($userProfile->gender==1) { ?> <p> Gender :<span>Male</span> </p> <?php } 
                          else if($userProfile->gender==2){ ?> <p> Gender :<span>Female</span> </p> <?php }
                          else if($userProfile->gender==3){ ?> <p> Gender :<span>Other</span> </p> <?php } ?>
                        </div>
                        <?php
                        $profilepercentage=DrsPanel::calculatePercentage($userProfile->user_id);
                        ?>
                          <div class="progress">
                            <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="<?php echo $profilepercentage?>" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo $profilepercentage; ?>%"> Complete Profile <?php echo $profilepercentage; ?>% </div>
                          </div>
                      
                      </div>
                      <div class="repl-foracpart">
                        <ul>
                          <li> <a href="<?php echo $base_url.'/hospital/edit-profile'?>">
                            <div class="list-mainboxe"> <img src="<?php echo $base_url ?>/images/doctor-profile-icon7.png" alt="image"> </div>
                            <div class="datacontent-et">
                              <p>Edit Profile</p>
                            </div>
                          </a> </li>
                            <li> <a href="<?php echo $base_url.'/hospital/address'?>">
                                    <div class="list-mainboxe"> <img src="<?php echo $base_url ?>/images/doctor-profile-icon3.png" alt="image"> </div>
                                    <div class="datacontent-et">
                                        <p>Address</p>
                                    </div>
                                </a> </li>
                            <li> <a href="<?php echo $base_url.'/hospital/aboutus'?>">
                            <div class="list-mainboxe"> <img src="<?php echo $base_url ?>/images/doctor-profile-icon2.png" alt="image"> </div>
                            <div class="datacontent-et">
                              <p>My About Us</p>
                            </div>
                          </a> </li>


                          <li> 
                            <a class="modal-call" href="javascript:void(0)" title="Add More " id="speciality-popup">
                              <div class="list-mainboxe"> <img src="<?php echo $base_url?>/images/doctor-profile-icon4.png" alt="image">  </div>
                              <div class="datacontent-et">
                                <p>Speciality</p>
                              </div>
                            </a> 
                          </li>
                          <li><a href="<?php echo $base_url.'/hospital/services'?>" class="hide">
                                        <div class="list-mainboxe"> <img src="<?php echo $base_url?>/images/profile-icon/service-icon.png" alt="image"> </div>
                                        <div class="datacontent-et">
                                            <p>Facilities/Services</p>
                                        </div>
                                    </a>
                                <?php
                                if(!empty($servicesList[0]['services'])){ ?>
                                <a class="modal-call" href="javascript:void(0)" title="Edit Services" id="experiences-popup">
                                  <div class="list-mainboxe"> <img src="<?php echo $base_url?>/images/profile-icon/service-icon.png" alt="image"> </div>
                                  <div class="datacontent-et">
                                      <p>Facilities/Services</p>
                                  </div>
                                </a>
                                <?php } else { ?>
                                <a class="modal-call" href="javascript:void(0)" title="Add Services" id="experiences-popup">
                                   <div class="list-mainboxe"> <img src="<?php echo $base_url?>/images/profile-icon/service-icon.png" alt="image"> </div>
                                  <div class="datacontent-et">
                                      <p>Facilities/Services</p>
                                  </div>
                                </a>
                                <?php }
                                ?>
                                </li>
                      
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

<!-- Add/Edit/View Hospital Specialities  -->
  <div class="register-section">
    <div id="speciality-modal" class="modal fade model_opacity"  role="dialog">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="specialityContact">View Specialities </h4>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          </div>
          <div class="modal-body">
              <div class="doctor-timing-main">
            <?php
            $getspecialities = Drspanel::getMyHospitalSpeciality($userProfile->user_id);
            ?>

              <?php if(!empty($getspecialities['speciality'])) { ?>
                  <h3>Specialities</h3>
                  <?php
                  $Servicedata = explode(',', $getspecialities['speciality']);
                  foreach ($Servicedata as $list) {
                      ?>
                      <div class="morning-parttiming">
                          <div class="main-todbox">
                              <div class="pull-left">
                                  <div class="moon-cionimg"><img src="<?php echo $base_url?>/images/doctor-bag-icon.png" alt="image">
                                      <span id="hospital-name" ><?php echo $list ?></span></div>
                              </div>
                          </div>
                      </div>
                  <?php }

                  if(!empty($getspecialities['treatments'])) {
                      ?> <h3>Treatments</h3> <?php
                      $Treatmentdata = explode(',', $getspecialities['treatments']);
                      foreach ($Treatmentdata as $list) {
                          ?>
                          <div class="morning-parttiming">
                              <div class="main-todbox">
                                  <div class="pull-left">
                                      <div class="moon-cionimg"><img src="<?php echo $base_url?>/images/doctor-bag-icon.png" alt="image">
                                          <span id="hospital-name" ><?php echo $list ?></span></div>
                                  </div>
                              </div>
                          </div>
                          <?php
                      }
                  }
              } else{
                  echo "You'r Hospital Doctors not added any speciality";
              } ?>

            </div>
          </div>
          </div><!-- /.modal-content -->
        </div>
      </div>
    </div>

  <!-- Add/Edit/View Hospital Services  -->
    <div class="register-section">
      <div id="experiences-modal" class="modal fade model_opacity"  role="dialog">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <?php if(!empty($servicesList)) {?>
              <h4 class="modal-title" id="experiencesContact">Update Services </h4>
              <?php } else { ?>
              <h4 class="modal-title" id="experiencesContact">Add Services </h4>
              <?php }?>
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
              <?php $form = ActiveForm::begin(['enableAjaxValidation'=>true]); ?>
              <?= $this->render('services_form', [
                'model' => $userProfile,
                'form'=>$form,
                'services' => $services,
                'servicesList' =>$servicesList
                ]) ?>
                <?php ActiveForm::end(); ?>
              </div>
            </div><!-- /.modal-content -->
          </div>
        </div>
      </div>