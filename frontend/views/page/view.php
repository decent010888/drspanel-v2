<?php
/**
 * @var $this \yii\web\View
 * @var $model \common\models\Page
 */
$this->title = Yii::t('db', $model->title);
?>
<!--- inner banner part start --->
<section class="inner_bannerspart" style="background:url(<?php echo \yii\helpers\Url::to('@frontendUrl')?>/images/terms-banner.jpg) no-repeat center;">
    <div class="container">
        <div class="inner_pagescontent">
            <h1><?php echo Yii::t('db', $model->title); ?></h1>
        </div>
    </div>
</section>

<section class="terms_mainpart">
    <div class="container">
        <div class="terms_innercontent">
            <?php echo $model->body ?>
        </div>
    </div>
</section>