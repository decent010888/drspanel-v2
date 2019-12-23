<?php 
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use common\models\User;
use kartik\select2\Select2;

$this->title = Yii::t('frontend', 'Hospital::Profile Update', [
  'modelAddressClass' => 'Doctor',
  ]);
?>
<div class="inner-banner"> </div>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="appointment_part">
                        <div class="hosptionhos-profileedit">
                            <h2 class="addnew2">Edit Profile</h2>
                            <?php $form = ActiveForm::begin(['id' => 'profile-form',
                                'options' => ['enctype'=> 'multipart/form-data',
                                    'action' => 'userProfile']]); ?>
                                <div class="col-md-12">
                                    <div class="user_profile_img">
                                        <div class="doc_profile_img">
                                            <img src="<?= DrsPanel::getUserDefaultAvator($userProfile->user_id,'thumb'); ?>" />
                                        </div>
                                        <input style="display:none" id="uploadfile" onchange="readImageURL(this);" type="file" name="UserProfile[avatar]" class="form-control" placeholder="uploadfile">
                                        <i class="fa fa-camera profileimageupload" style="cursor:pointer"></i>
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                                <div class="row discri_edithost">
                                    <p class="col-sm-3"> Profile Name :</p>
                                    <span class="col-sm-7"> <?php echo $form->field($userProfile, 'name')->textInput(['class'=>'input_field'])->label(false); ?></span>
                                </div>
                                <div class="row discri_edithost">
                                    <p class="col-sm-3"> E-mail ID :</p>
                                    <span class="col-sm-7">
                                        <?php echo $form->field($userModel, 'email',['template' => '{input}<a href="javascript:void(0)" class="profile_edit_input" id="email_'.$userProfile->user_id.'" data-userType="hospital" data-keyid="'.$userProfile->user_id.'"><i class="fa fa-edit" aria-hidden="true" data-id="88"></i></a>{error}'])->textInput(['class'=>'input_field input_email_edit','placeholder' =>'Email','readOnly'=>true])->label(false); ?>
                                    </span>
                                </div>
                                <div class="row discri_edithost">
                                    <p class="col-sm-3"> Mobile Number :</p>
                                    <span class="col-sm-7">
                                         <?php echo $form->field($userModel, 'phone',['template' => '{input}<a href="javascript:void(0)" class="profile_edit_input" id="phone_'.$userProfile->user_id.'" data-userType="hospital" data-keyid="'.$userProfile->user_id.'"><i class="fa fa-edit" aria-hidden="true" data-id="88"></i></a>{error}'])->textInput(['class'=>'input_field input_phone_edit','placeholder' =>'Phone','readOnly'=>true])->label(false); ?>
                                    </span>
                                </div>

                                <div class="row discri_edithost">
                                    <p class="col-sm-3"> Establishment Year :</p>
                                    <?php
                                    $startyears = array_combine(range(date("Y"), 1910), range(date("Y"), 1910));
                                    ?>
                                    <span class="col-sm-7">

                                         <div class="row">
                                             <div class="col-sm-12" >
                                            <?php echo  $form->field($userProfile, 'dob')->widget(Select2::classname(),
                                                ['data' => $startyears,
                                                    'size' => Select2::SMALL,
                                                    'options' => ['multiple' => false,'placeholder'=> 'Select Year'],
                                                    'pluginOptions' => [
                                                        'allowClear' => false,
                                                        'closeOnSelect' => true,
                                                    ],
                                                ])->label(false);
                                            ?>                                  </div>
                                        </div>

                                    </span>
                                </div>
                                <div class="bookappoiment-btn" style="margin:0px;">
                                    <?php if($model->admin_status == User::STATUS_ADMIN_PENDING){
                                        $btn_text='Request for live';
                                    } elseif($model->admin_status == User::STATUS_ADMIN_REQUESTED){
                                        $btn_text='Requested for live';

                                    } elseif($model->admin_status == User::STATUS_ADMIN_APPROVED){
                                        $btn_text='Profile Approved';
                                    } else{
                                        $btn_text='Profile Update';
                                    }?>
                                    <?php echo Html::submitButton(Yii::t('frontend', $btn_text), ['id'=>'profile_from','class' => 'login-sumbit', 'name' => 'profile-button']) ?>
                                </div>
                            <?php ActiveForm::end(); ?>
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