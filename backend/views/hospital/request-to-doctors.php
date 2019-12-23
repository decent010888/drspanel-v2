<?php
use yii\bootstrap\ActiveForm;
use kartik\date\DatePicker;
use common\grid\EnumColumn;
use common\models\UserRequest;
use yii\helpers\Html;
use yii\grid\GridView;
use kartik\select2\Select2;
use common\components\DrsPanel;
use backend\models\RequestForm;



/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$model = new RequestForm();

$this->title = Yii::t('backend', 'Hospital Doctors List');
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Hospitals'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$updateStatus="'update-status'";


$js="

$('.statusID').on('click', function () {
  status_id=$(this).attr('dataid');
  requested_to =$(this).attr('dataid2');
  requested_from =$(this).attr('dataid3');
  type=$(this).attr('dataid4');
  $.ajax({
    method:'POST',
    url: $updateStatus,
    data: {status:status_id,request_to:requested_to,request_from:requested_from,type:type}
  })
  .done(function( msg ) { 

  });
}); 

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
    <div class="user-index">
      <?php $form = ActiveForm::begin(['id' => 'profile-form']); ?>
      <div  class="col-sm-12">
        <div class="seprator_box">
          <h4>Send Request To Doctors:</h4>

          <?php echo  $form->field($model, 'id')->widget(Select2::classname(), 
            [
            'data' => $doctorList,
            'size' => Select2::SMALL,
            'options' => ['placeholder' => 'Select a doctor ...', 'multiple' => true],
            'pluginOptions' => [
            'allowClear' => true
            ],
            ])->label(false); ?>
          </div>
        </div>
        <div class="form-group clearfix col-sm-12">
          <?php echo Html::submitButton(Yii::t('backend', 'Send Request'), ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
        </div>
        <?php ActiveForm::end(); ?>
        
      </div>

    </div>
  </div>


<div class="box">
    <div class="box-body">

        <h4>Requested Doctors List</h4>

        <div class="search-boxicon" style="margin-bottom: 20px;">
            <div class="search-iconmain"></div>
            <input placeholder="search doctors..." class="form-control" type="text" id="doctor_filter_input" onkeyup="myFunction()">
        </div>
        <div class="row" id="filter_doctor">
            <?php
            if(isset($mydoctors)){
                foreach ($mydoctors as $list) {
                    $detail=\common\models\User::findOne($list);
                    $profile=\common\models\UserProfile::find()->where(['user_id'=>$list])->one();?>
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

                                  <?php $checkRequest = DrsPanel::sendRequestCheck($id,$profile->user_id); ?>


                                <?php if(!empty($checkRequest)){ ?>
                                    <p class="status">Status: <?php echo $checkRequest?></p>
                                <?php } ?>
                                <?php if($checkRequest=='pending') {?>
                                    <button id="id_<?php echo $profile->slug?>" type="button" dataid ="1"
                                            dataid2="<?php echo $profile->user_id ?>" dataid3="<?php echo $id?>"
                                            dataid4="send" class="btn confirm-theme statusID">Send Request</button>
                                <?php } elseif($checkRequest=='requested') { ?>
                                    <button id="id_<?php echo $profile->slug?>" type="button" dataid ="1"
                                            dataid2="<?php echo $profile->user_id ?>" dataid3="<?php echo $id?>"
                                            dataid4="cancel" class="btn confirm-theme statusID">Cancel Request</button>
                                <?php } else{

                                }?>
                                </span>
                        </div>
                    </div>
                <?php }
            }
            if(empty($mydoctors)) {
                echo 'No Doctors Found';
            }?>
        </div>

        <div class="row text-center" id="filter_result_hide" style="display:none;">
            <?php echo 'No Doctors Found'; ?>
        </div>
    </div>
</div>