<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use kartik\select2\Select2;
/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $roles yii\rbac\Role[] */

$this->title = Yii::t('backend', '{modelClass}: ', ['modelClass' => 'Attender']) . ' ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Doctors'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Attender'), 'url' => ['attender-list','id' => $model->parent_id]];

$this->params['breadcrumbs'][] = ['label'=>$model->email];


$this->registerJs("
   

",\yii\web\VIEW::POS_END); 


?>



<div class="row" id="userdetails">
    <div class="col-md-6">
        <div class="nav-tabs-custom">
            <div class="panel-heading">
                <h3 class="panel-title">Personal Information</h3>
            </div>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(['id' => 'profile-form']); ?>
                <div class="col-sm-12">
                    <?php echo $form->field($userProfile, 'name') ?>
                </div>
                <div class="col-sm-12">
                    <?php echo $form->field($model, 'email') ?>
                </div>



                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-sm-3">
                            <?php echo $form->field($model, 'countrycode')->dropDownList(\common\components\DrsPanel::getCountryCode(91)) ?>
                        </div>
                        <div class="col-sm-9">
                            <?php echo $form->field($model, 'phone') ?>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12">
                    <div class="form-group">
                        <?= $form->field($userProfile, 'dob')->textInput()->widget(
                            DatePicker::className(), [
                            'convertFormat' => true,
                            'options' => ['placeholder' => 'Date of Birth*'],
                            'layout'=>'{input}{picker}',
                            'pluginOptions' => [
                                'autoclose'=>true,
                                'format' => 'yyyy-MM-dd',
                                'endDate' => date('Y-m-d'),
                                'todayHighlight' => true
                            ],]); ?>
                    </div>
                </div>


                <div class="col-sm-12">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-sm-1"><label>Gender</label></div>

                                    <?php
                                    echo $form->field($userProfile, 'gender', ['options' => ['class' =>
                                        'col-sm-11']])->radioList(['1' => 'Male', '2' => 'Female','3' => 'Other'], [
                                        'item' => function ($index, $label, $name, $checked, $value) {

                                            $return = '<span>';
                                            $return .= Html::radio($name, $checked, ['value' => $value, 'autocomplete' => 'off', 'id' => 'gender_' . $label]);
                                            $return .= '<label for="gender_' . $label . '" >' . ucwords($label) . '</label>';
                                            $return .= '</span>';

                                            return $return;
                                        }
                                    ])->label(false)
                                    ?>
                                </div>

                            </div>
                        </div>
                        <?php /*
                        <div class="col-sm-6">
                            <?php echo $form->field($userProfile, 'blood_group')->dropDownList(\common\components\DrsPanel::getBloodGroups()) ?>
                        </div> */ ?>
                    </div>
                </div>
                 <div class="col-sm-12" id="shift_time_list">
                       
                    <?php echo  $form->field($shiftModels, 'shift_id')->widget(Select2::classname(), 
                        [
                        'data' => $shifts,
                        'size' => Select2::MEDIUM,
                        'options' => ['placeholder' => 'Select a Shifts ...', 'multiple' => true],
                        'pluginOptions' => [
                        'allowClear' => true
                        ],
                        ])->label(false); ?>
                </div>
                <div  class="col-sm-12">
                    <?php echo $form->field($userProfile, 'description')->textarea(); ?>
                </div>

                <div class="col-sm-12">
                    <?= $form->field($userProfile, 'avatar')->fileInput([
                        'options' => ['accept' => 'image/*'],
                        'maxFileSize' => 5000000, // 5 MiB

                    ]);   ?>
                    <?php if($userProfile->avatar){  ?>
                        <div class="edit-image" style="margin-left: 200px;margin-top: -70px;">
                            <img  src="<?php echo Yii::getAlias('@storageUrl/source/attenders/').$userProfile->avatar; ?>" width="75" height="75"/>
                        </div>
                    <?php } ?>
                </div>

                <div class="form-group clearfix col-sm-12">
                    <?php echo Html::submitButton(Yii::t('backend', 'Update'), ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                </div>
                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>













