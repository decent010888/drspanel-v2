<?php 
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use backend\models\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $roles yii\rbac\Role[] */

$this->title = Yii::t('backend', 'Shifts Timing');
$this->params['breadcrumbs'][] = $this->title;
?>
<?php
$js="
   // $('.fancybox').fancybox();
    
    $('.check_service').on('click',function(){
        address_id = $(this).attr('data-id');
        $.ajax({
            url: 'ajax-check-service',
            dataType:   'html',
            method:     'POST',
            data: { address_id: address_id,user_id:$doctor_id},
            success: function(response){
                $('#addupdateservice').empty();
                $('#addupdateservice').append(response);
                $('#addupdateservice').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });

                
            }
        });
    });
";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>
<section class="mid-content-part">
    <div class="container">
            <div class="row">
                <div class="col-md-10 mx-auto">

                    <div class="today-appoimentpart">
                        <div class="col-md-12 calendra_slider">
                            <h3> Shifts </h3>
                        </div>
                    </div>
                    <?php
                    if(!empty($address_list)) {
                        foreach($address_list as $key=>$list) {
                            echo $this->render('_shifts',['list' => $list,'doctor_id'=>$doctor_id]);
                        }
                    } else {  ?>
                    <div class="col-md-12 text-center">Shifts not available.</div>
                    <?php } ?>
                </div>
            </div>
    </div>
</section>

<div class="login-section">
    <div class="modal fade model_opacity" id="addupdateservice" tabindex="-1" role="dialog" aria-labelledby="addupdateservice" aria-hidden="true">
    </div>
</div>