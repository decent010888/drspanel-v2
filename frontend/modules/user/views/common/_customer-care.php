<?php
$this->title = Yii::t('frontend', 'DrsPanel :: Customer Care');

$base_url = Yii::getAlias('@frontendUrl');
$userId = Yii::$app->user->id;
$getGroup = common\models\User::find()->where(['id'=>$userId])->one();
?>
<div class="inner-banner"> </div>
<section class="mid-content-part customer_care">
    <div class="signup-part customer_care">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="row">
                        <?php
                        if (isset($customer['email']) && !empty($customer['email'])) {
                            if (isset($customer['phone']) && empty($customer['phone'])) {
                                ?>
                                <div class="col-md-12" onclick="location.href = '<?php echo 'mailto:' . $customer['email']['value'] ?>'">
                                <?php } else { ?>
                                    <div class="col-md-6" onclick="location.href = '<?php echo 'mailto:' . $customer['email']['value'] ?>'">
                                    <?php } ?>
                                    <div class="allover-reminderpart">
                                        <div class="success-reminderpart">
                                            <div class="reminderic-success1"> <img src="<?php echo $base_url ?>/images/mail-icon.png"> </div>
                                            <div class="reminder-ctpart">
                                                <h4> <?php echo $customer['email']['value'] ?> </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (isset($customer['phone']) && !empty($customer['phone'])) { 
                                if($getGroup['groupid'] != 3){
                                ?>
                                <div class="col-md-6">
                                    <div class="allover-reminderpart">
                                        <div class="success-reminderpart">
                                            <div class="reminderic-success1"> <img src="<?php echo $base_url ?>/images/mail-call.png"> </div>
                                            <div class="reminder-ctpart">
                                                <h4>91+  <?php echo $customer['phone']['value'] ?> </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } }?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>