<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use backend\models\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;

if($userAddress->area)
    $userAddress->area= $userAddress->area;
//else
//$userProfile->treatment='';
?>
    <p class="col-sm-3">Area:</p>
    <span class="col-sm-7 marginbottom_edit">
        <?php echo  $form->field($userAddress, 'area')->widget(Select2::classname(),
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
    </span>
