<?php

use common\models\UserExperience;
use yii\helpers\Html;
use kartik\date\DatePicker;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model backend\models\UserForm */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $roles yii\rbac\Role[] */
/* @var $permissions yii\rbac\Permission[] */
$js="
$('#userexperience-hospital_name').keypress(function (e) {
  var regex = new RegExp('^[a-zA-Z0-9- ]+$');
  var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
  if (regex.test(str)) {
    return true;
  }
  else
  {
    e.preventDefault();
    var ne=1;
    return false;
  }
});
";
$this->registerJs($js,\yii\web\VIEW::POS_END);


?>

<div class="edu-form">
 
    <?php echo $form->field($model, 'hospital_name')->textInput(['placeholder' => 'Hospital Name'])->label(false) ?>
    <?php $years = array_combine(range(date("Y"), 1910), range(date("Y"), 1910));
    $startyears=$years;
    $next_year=date('Y', strtotime('+1 year'));
    $tillnow=array($next_year=>"Till Now");
    $endyears=  $tillnow + $startyears;
    ?>

    <div class="row">
        <div class="col-sm-12" >
            <?php echo  $form->field($model, 'start')->widget(Select2::classname(),
                ['data' => $startyears,
                    'size' => Select2::SMALL,
                    'options' => ['multiple' => false,'placeholder'=> 'Select Start Year'],
                    'pluginOptions' => [
                        'allowClear' => false,
                        'closeOnSelect' => true,
                    ],
                ])->label(false);
            ?>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12" >
            <?php echo  $form->field($model, 'end')->widget(Select2::classname(),
                ['data' => $endyears,
                    'size' => Select2::SMALL,
                    'options' => ['multiple' => false,'placeholder'=> 'Select End Year'],
                    'pluginOptions' => [
                        'allowClear' => false,
                        'closeOnSelect' => true,
                    ],
                ])->label(false);
            ?>
        </div>
    </div>

        <?php echo Html::submitButton(Yii::t('frontend', 'Save'), ['class' => 'login-sumbit', 'id'=>"edu_form_btn", 'name' => 'signup-button']) ?>


    

    </div>
