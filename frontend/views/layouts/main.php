<?php
/* @var $this \yii\web\View */
use yii\helpers\ArrayHelper;
use yii\widgets\Breadcrumbs;

/* @var $content string */

$this->beginContent('@frontend/views/layouts/base.php');


if(Yii::$app->session->hasFlash('success')){
    $succesMsg=Yii::$app->session->getFlash('success');
    $this->registerJs("
        $('span.select2-container').css('z-index','999');
        setTimeout(function () {
            swal({            
            type:'success',
            title:'Success!',
            text:$succesMsg,            
            timer:2000,
            confirmButtonColor:'#a42127'
            })},100);
             
        ",\yii\web\VIEW::POS_END);
}

if(Yii::$app->session->hasFlash('titlesuccess')){
    $succesMsg=Yii::$app->session->getFlash('titlesuccess');
    $this->registerJs("
        $('span.select2-container').css('z-index','999');
        setTimeout(function () {
            swal({            
            type:'success',
            title:'',
            text:$succesMsg,            
            timer:2000,
            confirmButtonColor:'#a42127'
            })},100);
             
        ",\yii\web\VIEW::POS_END);
}

if(Yii::$app->session->hasFlash('error')){
    $succesMsg=Yii::$app->session->getFlash('error');
    $this->registerJs("
        $('span.select2-container').css('z-index','999');
        setTimeout(function () {
            swal({
            title:'Error!',
            text:$succesMsg,
            type:'error',
            timer:2000,
           confirmButtonColor:'#a42127'            
            })},100);
             
        ",\yii\web\VIEW::POS_END);
}

if(Yii::$app->session->hasFlash('shifterror')){
    $succesMsg=Yii::$app->session->getFlash('shifterror');
    $this->registerJs("
        $('span.select2-container').css('z-index','999');
        setTimeout(function () {
            swal({
            title:'Error!',
            text:'$succesMsg',
            type:'error',
            timer:10000,
           confirmButtonColor:'#a42127'            
            })},100);
             
        ",\yii\web\VIEW::POS_END);
}

if(Yii::$app->session->hasFlash('erroralert')){
    $succesMsg=Yii::$app->session->getFlash('erroralert');
    $this->registerJs("
         $('span.select2-container').css('z-index','999');
        setTimeout(function () {
            swal({
            title:'Profile Status Alert!',
            text:$succesMsg,
            type:'error',
            timer:20000,
           confirmButtonColor:'#a42127'            
            })},100);
            
        ",\yii\web\VIEW::POS_END);
}
?>

<?php echo Breadcrumbs::widget([
    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
    ]) ?>
    <!-- Example of your ads placing -->
<?php echo \common\widgets\DbText::widget([
    'key' => 'ads-example'
    ]) ?>

    <?php echo $content ?>

    <?php $this->endContent() ?>