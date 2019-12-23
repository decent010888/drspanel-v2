<?php
use common\components\DrsPanel;
use common\models\UserProfile;
use yii\widgets\ActiveForm;
/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $roles yii\rbac\Role[] */

$this->title = Yii::t('backend', 'Requested Hospital List');
$this->params['breadcrumbs'][] = $this->title;

$updateStatus="'update-status'";


$js="

$('.statusID').on('click', function () {
  status_id=$(this).attr('dataid');
  requested_to =$(this).attr('dataid2');
  requested_from =$(this).attr('dataid3');
  $.ajax({
    method:'POST',
    url: $updateStatus,
    data: {status:status_id,request_to:requested_to,request_from:requested_from}
  })
  .done(function( msg ) { 

  });
}); 

function myFunction() {
    $('#filter_result_hide').css('display','none');
    var input, filter, ul, li, a, i, txtValue,show=0;
    input = document.getElementById('hospital_filter_input');
    filter = input.value.toUpperCase();
    ul = document.getElementById('filter_hospital');
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

        <?php $form = ActiveForm::begin(); ?>

        <div class="search-boxicon" style="margin-bottom: 20px;">
            <div class="search-iconmain"></div>
            <input placeholder="search hospitals..." class="form-control" type="text" id="hospital_filter_input" onkeyup="myFunction()">
            <?php
            $doctorlists=array();
            if(!empty($lists)){
                foreach ($lists as $list) {
                    $doctorlists[$list->request_from] = $list->request_from;
                }
            }
            ?>
        </div>
        <div class="row" id="filter_hospital">
            <?php
            if(isset($lists)){
                foreach ($lists as $list) {
                    $hospital_id = $list->request_from;
                    $hospital=UserProfile::findOne($hospital_id);
                    $checkRequest = DrsPanel::sendRequestCheck($hospital_id,$doctor_id);
                    ?>
                    <div class="col-sm-4">
                        <div class="pace-part">
                                  <span class="pace-left img_div_left">
                                       <img src="<?php echo DrsPanel::getUserThumbAvator($hospital_id) ?>" alt="image">
                                  </span>

                            <span class="pace-right">
                            <h4><?php echo $hospital->name; ?></h4>
                            <?php if(!empty($checkRequest)){ ?>
                                <p class="status">Status: <?php echo $checkRequest?></p>
                            <?php } ?>
                                    <?php if($checkRequest == 'requested') {?>
                                        <button id="id_<?php echo $hospital->slug ?>"  type="button" dataid ="2"
                                                dataid2="<?php echo $doctor_id ?>" dataid3="<?php echo $hospital_id?>"
                                                class="btn confirm-theme statusID text-center">Accept Request</button>
                                    <?php } ?>
                            </span>
                        </div>
                    </div>
                <?php }
            }
            if(empty($lists)) {
                echo 'No request Found';
            }?>
        </div>

        <div class="row text-center" id="filter_result_hide" style="display:none;">
            <?php echo 'No Hospitals Request Found'; ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>



