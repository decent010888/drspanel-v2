<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use branchonline\lightbox\Lightbox;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;

$this->title = Yii::t('frontend', 'Patient::Profile');
$baseUrl = Yii::getAlias('@frontendUrl');

$citiesList = [];
$area_list = [];
$statesList = ArrayHelper::map(DrsPanel::getStateList(), 'name', 'name');

if ($userProfile->state) {
    $citiesList = ArrayHelper::map(DrsPanel::getCitiesList($userProfile->state, 'name'), 'id', 'name');
}
if ($userProfile->city_id) {
    $area_list = ArrayHelper::map(DrsPanel::getCityAreasList($userProfile->city_id), 'name', 'name');
}


$frontend = Yii::getAlias('@frontendUrl');
$cityUrl = "'" . $frontend . "/patient/city-list'";
$cityAreaUrl = "'" . $frontend . "/patient/city-area-list'";
$mapAreaUrl = "'" . $frontend . "/patient/map-area-list'";


$js = "
var ShiftCount = 0;
$(document).on('change', '#estate_list',function () { 
  $('#main-js-preloader').show();  
  $.ajax({
    method:'POST',
    url: $cityUrl,
    data: {state_id:$(this).val()}
  })
  .done(function( msg ) { 
     $('#citylist_update').show();
     $('#citylist_update').html('');
     $('#citylist_update').html(msg);
      $('#main-js-preloader').hide();  

  });
}); 

$(document).on('change','#ecity_list', function () {
$('#main-js-preloader').show();  
  $.ajax({
    method: 'POST',
    url: $cityAreaUrl,
    data: { id: $('#ecity_list').val()}
  })
  .done(function( msg ) { 
    if(msg){
      $('#arealist_update').show();
      $('#arealist_update').html('');
      $('#arealist_update').html(msg);
      $('#main-js-preloader').hide();  
    }
  });
});

$('.maplocation_attachment').on('click', function () {
  $.ajax({
    method: 'POST',
    url: $mapAreaUrl,
    data: { city: $('#ecity_list').val(),state: $('#estate_list').val(),address:$('#useraddress-address').val(),
            area:$('#useraddress-area').val(),lat:$('#useraddress-lat').val(),lng:$('#useraddress-lng').val()}
  })
  .done(function( json_result ) { 
    $('#mapTokenContent').html('');
    $('#mapTokenContent').html(json_result); 
    $('#mapbookedShowModal').modal({backdrop: 'static',keyboard: false});
    
    
  });
});

$('.modal').on('shown.bs.modal', function (e) {
   initialize();
});

";
$this->registerJs($js, \yii\web\VIEW::POS_END);
?>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-9">
                    <div class="appointment_part">
                        <div class="appointment_details">
                            <div class="pace-part main-tow">
                                <h3 class="addnew">Edit Profilesss <?php echo \yii\helpers\Url::to('@frontendUrl'); ?></h3>

                                <?php $form = ActiveForm::begin(['id' => 'patient-profile-form', 'options' => ['enctype' => 'multipart/form-data']]); ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="user_profile_img">
                                            <div class="doc_profile_img patient_profile_img">

                                                <?php
                                                $image = DrsPanel::getUserAvator($userProfile->user_id);
                                                echo Lightbox::widget([
                                                    'files' => [
                                                        [
                                                            'thumb' => $image,
                                                            'original' => $image,
                                                            'title' => $userProfile['name'],
                                                        ],
                                                    ]
                                                ]);
                                                ?>
                                            </div>

                                            <input style="display:none" id="uploadfile" onchange="readImageURL(this);" type="file" name="UserProfile[avatar]" class="form-control" placeholder="uploadfile">
                                            <i class="fa fa-camera profileimageupload" style="cursor:pointer"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <?php echo $form->field($userProfile, 'name')->textInput(['class' => ''])->label(false); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo $form->field($userModel, 'email', ['template' => '{input}<a href="javascript:void(0)" class="profile_edit_input" id="email_' . $userProfile->user_id . '" data-userType="patient" data-keyid="' . $userProfile->user_id . '"><i class="fa fa-edit" aria-hidden="true" data-id="88"></i></a>{error}'])->textInput(['class' => 'input_field input_email_edit', 'placeholder' => 'Email', 'readOnly' => true])->label(false); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php
                                        echo $form->field($userProfile, 'state')->widget(Select2::classname(), [
                                            'data' => $statesList,
                                            'size' => Select2::SMALL,
                                            'options' => ['placeholder' => 'Select State ...', 'multiple' => false, 'id' => 'estate_list'],
                                            'pluginOptions' => [
                                                'tags' => false,
                                                'allowClear' => true,
                                                'multiple' => false,
                                            ],
                                        ])->label(false);
                                        ?>
                                    </div>

                                    <div class="col-md-6" id="citylist_update">
                                        <?php
                                        echo $form->field($userProfile, 'city_id')->widget(Select2::classname(), [
                                            'data' => $citiesList,
                                            'size' => Select2::SMALL,
                                            'options' => ['placeholder' => 'Select City ...', 'multiple' => false, 'id' => 'ecity_list'],
                                            'pluginOptions' => [
                                                'tags' => false,
                                                'allowClear' => true,
                                                'multiple' => false,
                                            ],
                                        ])->label(false);
                                        ?>
                                    </div>

                                    <div class="col-md-6" id="arealist_update">
                                        <?php
                                        echo $form->field($userProfile, 'area')->widget(Select2::classname(), [
                                            'data' => $area_list,
                                            'size' => Select2::SMALL,
                                            'options' => ['placeholder' => 'Select Area/Colony ...', 'multiple' => false],
                                            'pluginOptions' => [
                                                'tags' => true,
                                                'allowClear' => true,
                                                'multiple' => false,
                                            ],
                                        ])->label(false);
                                        ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($userProfile, 'address1')->textInput(['placeholder' => 'Address'])->label(false) ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo $form->field($userModel, 'phone', ['template' => '{input}<a href="javascript:void(0)" class="profile_edit_input" id="phone_' . $userProfile->user_id . '" data-userType="patient" data-keyid="' . $userProfile->user_id . '"><i class="fa fa-edit" aria-hidden="true" data-id="88"></i></a>{error}'])->textInput(['class' => 'input_field input_phone_edit', 'placeholder' => 'Phone', 'readOnly' => true])->label(false); ?>

                                    </div>

                                    <div class="col-md-6 dob_icon_check">
                                        <?=
                                        $form->field($userProfile, 'dob')->textInput([])->widget(
                                                DatePicker::className(), [
                                            'convertFormat' => true,
                                            'type' => DatePicker::TYPE_INPUT,
                                            'options' => ['placeholder' => 'Date of Birth*', 'class' => 'form-group selectpicker '],
                                            'layout' => '{input}',
                                            'pluginOptions' => [
                                                'autoclose' => true,
                                                'format' => 'yyyy-MM-dd',
                                                'endDate' => date('Y-m-d'),
                                                'todayHighlight' => true
                                            ],])->label(false);
                                        ?>
                                    </div>

                                    <div class="">
                                        <?php
                                        echo $form->field($userProfile, 'gender', ['options' => ['class' =>
                                                'col-sm-12 selectpicker']])->radioList($genderList, [
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

                                    <div class="col-md-6 hide">
                                        <?php
                                        echo $form->field($userProfile, 'blood_group')
                                                ->dropDownList(DrsPanel::getBloodGroups(), ['class' => 'selectpicker', 'prompt' => 'Select Blood Group'])
                                                ->label(false);
                                        ?>
                                    </div>

                                    <div class="col-md-6 hide">
                                        <?php echo $form->field($userProfile, 'marital')->dropDownList(DrsPanel::getMaritalStatus(), ['class' => 'selectpicker', 'prompt' => 'marital', 'placeholder' => 'Marital'])->label(false); ?>
                                    </div>
                                    <div class="col-md-6 hide">
                                        <?php echo $form->field($userProfile, 'weight')->textInput(['class' => '', 'prompt' => 'Weight', 'placeholder' => 'Weight'])->label(false); ?>
                                    </div>

                                    <div class="col-md-6 hide">
                                        <?php
                                        echo $form->field($userProfile, 'location')
                                                ->textInput(['class' => '', 'prompt' => 'location', 'placeholder' => 'Location'])
                                                ->label(false);
                                        ?>
                                    </div>
                                    <div class="col-md-6 hide">
                                        <?php echo $form->field($userProfile, 'height')->dropDownList(DrsPanel::getPatientHeight(), ['class' => ' selectpicker', 'prompt' => 'Height in Feet', 'placeholder' => 'Height In Feet'])->label(false); ?>
                                    </div>
                                    <div class="col-md-6 hide">
                                        <?php echo $form->field($userProfile, 'inch')->dropDownList(DrsPanel::getInch(), ['class' => 'selectpicker', 'prompt' => 'Height in Inch', 'placeholder' => 'Height In Inch'])->label(false); ?>
                                    </div>
                                    <div class="col-sm-12 text-center">
                                        <?php echo Html::submitButton(Yii::t('backend', 'Profile Update'), ['class' => 'submit_btn btn btn-primary', 'name' => 'signup-button']) ?>
                                    </div>
                                </div>
                                <?php ActiveForm::end(); ?>

                            </div>
                        </div>
                    </div>
                </div>
                <?php echo $this->render('@frontend/views/layouts/rightside'); ?>
            </div>
        </div>
    </div>
</section>

<div class="register-section">
    <div id="edit-input-modal" class="modal fade model_opacity"  role="dialog">
    </div>
</div>
