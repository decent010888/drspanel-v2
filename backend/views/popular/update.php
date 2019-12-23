<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

use kartik\select2\Select2;



/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $roles yii\rbac\Role[] */


$hospital_list = array();$speciality_list=$treatment_list=$city_list=array();
$hospital_list_sel = array();$speciality_list_sel=$treatment_list_sel=array();



foreach ($hospitalData as $h_key=>$hospital) {
    foreach ($popularHospital as $key => $value) {

        $dataValue = explode(',', $value['value']);

        foreach ($dataValue as  $valueData) {

            if($valueData == $hospital->user_id)
            {
                $hospital_list_sel[$hospital->user_id]=$hospital->user_id;
                // unset($hospital->user_id);
            }

        }
    }
    $hospital_list[$hospital->user_id] = $hospital->name;
}
$model->hospital=$hospital_list_sel;


foreach ($specialities as $h_key=>$speciality) {
// pr($speciality);

    foreach ($popularSpeciality as $key => $value) {

        $dataValue = explode(',', $value['value']);

        foreach ($dataValue as  $valueData) {

            if($valueData == $speciality->value) {
                // unset($speciality->value);
                // unset($speciality->label);
                $speciality_list_sel[$speciality->value]=$speciality->value;
            }

        }
    }
    $speciality_list[$speciality->value] = $speciality->label;
}
$model->speciality=$speciality_list_sel;


foreach ($treatments as $h_key=>$treatment) {

    foreach ($popularTreatment as $key => $value) {

        $dataValue = explode(',', $value['value']);

        foreach ($dataValue as  $valueData) {

            if($valueData == $treatment->value)
            {
                $treatment_list_sel[$treatment->value]=$treatment->value;
                // unset($treatment->value);
                // unset($treatment->label);
            }

        }
    }
    $treatment_list[$treatment->value] = $treatment->label;
}
$model->treatment=$treatment_list_sel;

$city_list[$cities->id] = $cities->name;
$model->city=$cities->id;

?>


<div class="row" id="userdetails">
    <div class="col-md-6">
        <div class="nav-tabs-custom">
            <div class="panel-heading">
                <h3 class="panel-title">Popular Cities Data</h3>
            </div>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(['id' => 'profile-form','options' => ['enctype'=> 'multipart/form-data']]); ?>
                    <?php echo  $form->field($model, 'city')->widget(Select2::classname(),
                        [
                            'data' => $city_list,
                            'size' => Select2::SMALL,
                            'options' => ['placeholder' => 'Select city ...', 'multiple' => false],

                        ]) ?>

                        <?php echo  $form->field($model, 'hospital')->widget(Select2::classname(),
                        [
                            'data' => $hospital_list,
                            'size' => Select2::SMALL,
                            'options' => ['placeholder' => 'Select a hospital ...', 'multiple' => true],

                        ]); ?>

                <?php echo  $form->field($model, 'speciality')->widget(Select2::classname(),
                    [
                        'data' => $speciality_list,
                        'size' => Select2::SMALL,
                        'options' => ['placeholder' => 'Select a speciality ...', 'multiple' => true],

                    ]); ?>

                <?php echo  $form->field($model, 'treatment')->widget(Select2::classname(),
                    [
                        'data' => $treatment_list,
                        'size' => Select2::SMALL,
                        'options' => ['placeholder' => 'Select a treatments ...', 'multiple' => true],

                    ]); ?>

                <div class="form-group clearfix col-sm-12">
                    <?php echo Html::submitButton(Yii::t('backend', 'Update'), ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
                </div>

                <?php ActiveForm::end(); ?>





            </div>
        </div>
    </div>
</div>