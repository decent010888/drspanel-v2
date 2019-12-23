<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use backend\models\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;

if($modelAddress->city_id)
    $modelAddress->city_id= $modelAddress->city_id;
//else
//$userProfile->treatment='';
echo  $form->field($modelAddress, 'city_id')->widget(Select2::classname(),
    [
        'data' => $city_list,
        'size' => Select2::SMALL,
        'options' => ['placeholder' => 'Select City ...', 'multiple' => false,'id'=>'ecity_list'],
        'pluginOptions' => [
            'tags' => false,
            'allowClear' => true,
            'multiple' => false,
        ],
    ])->label(false);
?>