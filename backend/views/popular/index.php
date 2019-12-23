<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use backend\models\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;
use dosamigos\multiselect\MultiSelect;
use common\models\UserProfile;


/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $roles yii\rbac\Role[] */


$hospital_list = array();$speciality_list=$treatment_list=$city_list=array();





foreach ($cities as $h_key=>$city) {


    $city_list[$city->name] = $city->name;
}

?>


<div class="row" id="userdetails">
    <div class="col-md-6">
        <div class="nav-tabs-custom">
            <div class="panel-heading">
                <h3 class="panel-title">Default Cities</h3>
            </div>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(['id' => 'profile-form','options' => ['enctype'=> 'multipart/form-data']]); ?>
                <div class="col-sm-12">
                    <?php echo  $form->field($model, 'city')->widget(Select2::classname(),
                        [
                            'data' => $city_list,
                            'size' => Select2::SMALL,
                            'options' => ['placeholder' => 'Select cities ...', 'multiple' => false],

                        ])->label(false); ?>
                </div>



                <div class="form-group clearfix col-sm-12">
                    <?php echo Html::submitButton(Yii::t('backend', 'Update'), ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>

