<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\captcha\Captcha;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $model \frontend\models\ContactForm */

$this->title = 'Contact Us';
?>
<div class="inner-banner"> </div>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="site-contact">
                        <h2 class="addnew2"><?php echo Html::encode($this->title) ?></h2>
                                <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>
                                    <?php echo $form->field($model, 'name') ?>
                                    <?php echo $form->field($model, 'email') ?>
                                    <?php echo $form->field($model, 'subject') ?>
                                    <?php echo $form->field($model, 'body')->textArea(['rows' => 6]) ?>

                        <div class="bookappoiment-btn" style="margin:0px;">
                                        <?php echo Html::submitButton(Yii::t('frontend', 'Submit'), ['class' => 'login-sumbit', 'name' => 'contact-button']) ?>
                                    </div>
                                <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
