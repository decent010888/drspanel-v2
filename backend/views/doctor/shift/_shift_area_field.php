<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use backend\models\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;

if($modelAddress->area)
    $modelAddress->area= $modelAddress->area;
//else
//$userProfile->treatment='';

 echo  $form->field($modelAddress, 'area')->widget(Select2::classname(),
    [
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