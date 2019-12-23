<?php 
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use backend\models\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;
use common\models\UserRequest;

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $roles yii\rbac\Role[] */

$this->title = Yii::t('backend', 'My Doctors');
$this->params['breadcrumbs'][] = $this->title;


$js="
function myFunction() {
    $('#filter_result_hide').css('display','none');
    var input, filter, ul, li, a, i, txtValue,show=0;
    input = document.getElementById('doctor_filter_input');
    filter = input.value.toUpperCase();
    ul = document.getElementById('filter_doctor');
    li = ul.getElementsByTagName('div');
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName('h4')[0];
        txtValue = a.textContent || a.innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = '';
             show=1;
        } else {
            li[i].style.display = 'none';
        }
    }
    if(show == 0){
        $('#filter_result_hide').css('display','block');
    }
}";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>


<div class="box">
    <div class="box-body">

        <div class="search-boxicon" style="margin-bottom: 20px;">
            <div class="search-iconmain"></div>
            <input placeholder="search doctors..." class="form-control" type="text" id="doctor_filter_input" onkeyup="myFunction()">
        </div>
        <div class="row" id="filter_doctor">
            <?php
            if(isset($lists)){
                foreach ($lists as $list) {
                    $detail=\common\models\User::findOne($list['user_id']);
                    $profile=\common\models\UserProfile::find()->where(['user_id'=>$list['user_id']])->one();?>
                    <div class="col-sm-4">
                        <div class="pace-part">
                                  <span class="pace-left img_div_left">
                                   <?php if(!empty($profile->avatar)) { ?>
                                       <img src="<?php echo $profile->avatar_base_url.$profile->avatar_path.$profile->avatar?>" alt="image">
                                   <?php } else { ?>
                                       <img src="<?php echo Yii::getAlias('@frontendUrl/')?>/images/doctor-profile-image.jpg" alt="image">
                                   <?php } ?>
                                </span>
                            <span class="pace-right">
                                  <h4><?php echo isset($profile->name)?$profile->name:''?>
                                      <span class="pull-right mydoctorpart hide"> <i class="fa fa-pencil" aria-hidden="true"></i></span>
                                  </h4>
                                  <p><?php echo isset($profile->speciality)?$profile->speciality:''?></p>
                                </span>
                        </div>
                    </div>
                <?php }
            }
            if(empty($lists)) {
                echo 'No Doctors Found';
            }?>
        </div>

        <div class="row text-center" id="filter_result_hide" style="display:none;">
            <?php echo 'No Doctors Found'; ?>
        </div>
    </div>
</div>



