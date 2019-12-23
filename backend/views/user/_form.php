<?php

use backend\models\UserForm;
use common\models\User;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UserForm */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $roles yii\rbac\Role[] */
/* @var $permissions yii\rbac\Permission[] */
?>
<style>
    [type="checkbox"]:checked, [type="checkbox"]:not(:checked){
        left:20px;
    }
</style>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>
        <?php echo $form->field($model, 'name') ?>
        <?php echo $form->field($model, 'email') ?>
        <?php echo $form->field($model, 'phone') ?>

        <?php if(!isset($hidepass)) { ?>
        <?php echo $form->field($model, 'password')->passwordInput() ?>
        <?php } else { ?>
            <div style="display:none;">
            <?php echo $form->field($model, 'password')->passwordInput() ?>
            </div>
        <?php } ?>
        <?php echo $form->field($model, 'status')->dropDownList(User::statuses()) ?>
        <?php echo $form->field($model, 'groupid')->radioList($roles) ?>
        <div class="form-group">
            <?php echo Html::submitButton(Yii::t('backend', 'Save'), ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
        </div>
    <?php ActiveForm::end(); ?>

</div>
