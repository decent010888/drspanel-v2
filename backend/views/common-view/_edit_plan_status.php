<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

$status=\common\components\DrsPanel::getAllPlanStatus();
?>
<?php $form = ActiveForm::begin([
    'id' => 'update-status',
    'fieldConfig'=>['template' =>"{label}{input}\n {error}"],
    'options' => [
        'enctype' => 'multipart/form-data',
    ],
]); ?>

    <div class="col-sm-12">

        <div class="form-group clearfix">

            <div class="col-sm-8">
                <label class="control-label">Plan</label>
                <select name="PlanStatus[status]" class="form-control">
                    <?php foreach($status as $value=>$status){ ?>
                        <?php if($user->user_plan == $value) { ?>
                            <option selected value="<?php echo $value; ?>"><?php echo $status; ?></option>
                        <?php } else{ ?>
                            <option value="<?php echo $value; ?>"><?php echo $status; ?></option>
                        <?php }?>
                    <?php }?>
                </select>

                <div class="form-group">
                    <?= $form->field($user_plan, 'from_date')->textInput()->widget(
                        DatePicker::className(), [
                        'convertFormat' => true,
                        'options' => ['placeholder' => 'From Date'],
                        'layout'=>'{input}{picker}',
                        'pluginOptions' => [
                            'autoclose'=>true,
                            'format' => 'yyyy-MM-dd',
                            //'startDate' => date('Y-m-d'),
                            'todayHighlight' => true
                        ],])->label('From Date'); ?>

                </div>

                <div class="form-group">
                    <?= $form->field($user_plan, 'to_date')->textInput()->widget(
                        DatePicker::className(), [
                        'convertFormat' => true,
                        'options' => ['placeholder' => 'To Date'],
                        'layout'=>'{input}{picker}',
                        'pluginOptions' => [
                            'autoclose'=>true,
                            'format' => 'yyyy-MM-dd',
                            'startDate' => $user_plan->from_date,
                            'todayHighlight' => true
                        ],])->label('To Date'); ?>

                </div>
            </div>
        </div>
    </div>

    <div class="form-group clearfix">
        <div class="col-md-12 text-right">
            <input type="hidden" name="userid" value="<?php echo $userProfile->user_id?>"/>
            <input type="submit" class="show-profile" value="<?php echo Yii::t('db','Update'); ?>">
        </div>
    </div>
<?php ActiveForm::end(); ?>