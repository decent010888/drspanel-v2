<?php 
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use yii\helpers\ArrayHelper;
use backend\modelAddresss\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;


$this->title = Yii::t('frontend', 'Attender::Profile Update', [
  'modelAddressClass' => 'Attender',
  ]);
$base_url= Yii::getAlias('@frontendUrl');
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
              
              <h2 class="addnew2">Edit Profile</h2>

                <?php $form = ActiveForm::begin(['id' => 'profile-form','options' => ['enctype'=> 'multipart/form-data']]); ?>
                <div class="row">
              <div class="col-md-12">
                <div class="user_profile_img">
                  <div class="doc_profile_img">
                    <img src="<?= DrsPanel::getUserDefaultAvator($userProfile->user_id,'thumb'); ?>" />
                  </div>

                  <input style="display:none" id="uploadfile" onchange="readImageURL(this);" type="file" name="UserProfile[avatar]" class="form-control" placeholder="uploadfile">
                  <i class="fa fa-camera profileimageupload" style="cursor:pointer"></i>
                </div>
              </div>
              <div class="col-lg-6 col-sm-12">
                <?php echo $form->field($userProfile, 'name')->textInput(['class'=>'input_field','placeholder' =>'Name'])->label(false); ?>
              </div>

                    <div class="col-md-6">
                        <?php echo $form->field($userModel, 'email',['template' => '{input}<a href="javascript:void(0)" class="profile_edit_input" id="email_'.$userProfile->user_id.'" data-userType="attender" data-keyid="'.$userProfile->user_id.'"><i class="fa fa-edit" aria-hidden="true" data-id="88"></i></a>{error}'])->textInput(['class'=>'input_field input_email_edit','placeholder' =>'Email','readOnly'=>true])->label(false); ?>
                    </div>

                    <div class="col-md-6">

                        <?php echo $form->field($userModel, 'phone',['template' => '{input}<a href="javascript:void(0)" class="profile_edit_input" id="phone_'.$userProfile->user_id.'" data-userType="attender" data-keyid="'.$userProfile->user_id.'"><i class="fa fa-edit" aria-hidden="true" data-id="88"></i></a>{error}'])->textInput(['class'=>'input_field input_phone_edit','placeholder' =>'Phone','readOnly'=>true])->label(false); ?>

                    </div>



                  <div class="col-lg-6 col-sm-12">
            <div class="row">
                      <?php
                      echo $form->field($userProfile, 'gender', ['options' => ['class' =>
                          'col-md-12 selectpicker']])->radioList($genderList, [
                          'item' => function ($index, $label, $name, $checked, $value) {

                              $return = '<span>';
                              $return .= Html::radio($name, $checked, ['value' => $value, 'autocomplete' => 'off', 'id' => 'gender_' . $label]);
                              $return .= '<label for="gender_' . $label . '" >' . ucwords($label) . '</label>';
                              $return .= '</span>';

                              return $return;
                          }
                      ])->label('Gender');
                      ?>
          </div>
                  </div>
                  <div class="clearfix"></div>

              <div class="col-md-6 dob_icon dob_icon_check">
                <?= $form->field($userProfile, 'dob')->textInput([])->widget(
                  DatePicker::className(), [
                  'convertFormat' => true,
                  'type' => DatePicker::TYPE_INPUT,
                  'options' => ['placeholder' => 'Date of Birth*','class'=>'form-group '],
                  'layout'=>'{input}',
                  'pluginOptions' => [
                  'autoclose'=>true,
                  'format' => 'yyyy-MM-dd',
                  'endDate' => date('Y-m-d'),
                  'todayHighlight' => true
                  ],])->label(false); ?>
                </div>

                
                










                  <div class="col-md-12 text-center lg_pt_20">
                    <div class="bookappoiment-btn" style="margin:0px;">
                      <?php echo Html::submitButton(Yii::t('frontend', 'Profile Update'), ['id'=>'profile_from','class' => 'login-sumbit', 'name' => 'profile-button']) ?>
                    </div>
                  </div>
                </div>
                  <?php ActiveForm::end(); ?>
              </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

<div class="register-section">
    <div id="edit-input-modal" class="modal fade model_opacity"  role="dialog">
    </div>
</div>