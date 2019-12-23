<?php
use yii\widgets\ActiveForm;


$this->title = Yii::t('backend', 'Change Password {modelClass}: ', ['modelClass' => 'User']) . ' ' . $profile->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $profile->name, 'url' => ['view', 'id' => $profile->name]];
$this->params['breadcrumbs'][] = ['label'=>Yii::t('backend', 'Change Password')];
$model->user_id=$profile->user_id;
?>
<div class="box">
    <div class="box-body">
        <div class="user-update">
            <div class="row">
                <?php $form = ActiveForm::begin([
                    'id' => 'account-setting--form',
                    'fieldConfig'=>['template' =>"{label}\n{input}\n {error}"],
                    'options' => [
                        'enctype' => 'multipart/form-data',
                        'class' => 'login_body clear',
                    ],
                ]); ?>



                <?php echo $form->field($model,'user_id')->hiddenInput()->label(false);?>
                <div class="form-group clearfix">
                    <?= $form->field($model, 'password_old',['options'=>['class'=>
                        'col-md-6 col-sm-12']])->passwordInput(['placeholder'=> Yii::t('db', 'Current Password')]);?>
                </div>

                <div class="form-group clearfix">
                    <?= $form->field($model, 'password',['options'=>['class'=>
                        'col-md-6 col-sm-12']])->passwordInput(['placeholder'=> Yii::t('db', 'Password')]);?>
                </div>

                <div class="form-group clearfix">
                    <?= $form->field($model, 'password_confirm',['options'=>['class'=>
                        'col-md-6 col-sm-12']])->passwordInput(['placeholder'=>Yii::t('db', 'Confirm Password')]);?>
                </div>

                <div class="form-group clearfix">
                    <div class="col-md-3">
                        <input type="submit" class="form-control btn btn-success" value="<?php echo Yii::t('db','Save');?>" class="">
                    </div>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>