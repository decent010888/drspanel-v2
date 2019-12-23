<?php

use backend\assets\BackendAsset;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/* @var $this \yii\web\View */
/* @var $content string */

$bundle = BackendAsset::register($this);
$googlekey=Yii::$app->params['googleApiKey'];
$this->params['body-class'] = array_key_exists('body-class', $this->params) ?
        $this->params['body-class'] : null;
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?php echo Yii::$app->language ?>">
    <head>
        <meta charset="<?php echo Yii::$app->charset ?>">
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

        <?php echo Html::csrfMetaTags() ?>
        <title><?php echo Html::encode($this->title) ?></title>
        <?php $this->head() ?>
        <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?= $googlekey; ?>&libraries=places"></script>
    </head>
    <?php
    echo Html::beginTag('body', [
        'class' => implode(' ', [
            ArrayHelper::getValue($this->params, 'body-class'),
            Yii::$app->keyStorage->get('backend.theme-skin', 'skin-red'),
            Yii::$app->keyStorage->get('backend.layout-fixed') ? 'fixed' : null,
            Yii::$app->keyStorage->get('backend.layout-boxed') ? 'layout-boxed' : null,
            Yii::$app->keyStorage->get('backend.layout-collapsed-sidebar') ? 'sidebar-collapse' : null,
        ])
    ])
    ?>
    <?php $this->beginBody() ?>
    <div id="main-js-preloader" style="display: none;"></div>

    <?php echo $content ?>

    <?php $this->endBody() ?>
    <?php
    $getAllAppoint = common\models\UserAppointmentLogs::find()->where(['payment_status' => 'completed'])->andWhere('`date` >= "' . date('Y-01-01') . '" AND `date` <= "' . date('Y-m-d') . '" ')->all();

    //echo '<pre>';print_r($getAllAppoint);die;
    ?>
    <script>
        $(document).ready(function () {
            var dateobj = new Date();
            var month = dateobj.getMonth() + 1;
            var year = dateobj.getFullYear();
            $('.responsive-calendar').responsiveCalendar({
                time: year + '-' + month,
                events: {
                    <?php foreach ($getAllAppoint as $ApptDatas) {
                            $countAppointment = common\models\UserAppointmentLogs::find()->where(['payment_status' => 'completed'])->andWhere('date_format(`date`, "%Y-%m-%d") = "' . date('Y-m-d',strtotime($ApptDatas['date'])) . '" ')->count();
                    ?>
                    "<?php echo $ApptDatas['date'] ?>":  {"number": <?php echo $countAppointment ?>, "url": ""},
                    <?php } ?>
                }
            });
        });
    </script>
    <?php echo Html::endTag('body') ?>
</html>
<?php $this->endPage() ?>