<?php 
use common\components\DrsPanel;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use kartik\select2\Select2;
use branchonline\lightbox\Lightbox;

$js = "function myFunction() {
    $('#filter_result_hide').css('display','none');
    var input, filter, ul, li, a, i, txtValue,show=0;
    input = document.getElementById('hospital_filter_input');
    filter = input.value.toUpperCase();
    ul = document.getElementById('filter_hospital');
    li = ul.getElementsByTagName('div');
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName('h4')[0];
        txtValue = a.textContent || a.innerText;
        console.log(txtValue);
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = '';
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
<?php $baseUrl= Yii::getAlias('@frontendUrl'); ?>
<?php  $this->title = Yii::t('frontend', 'Doctor :: 
Hospital Requests');

$loginUser=Yii::$app->user->identity; 
$updateStatus="'".$baseUrl."/doctor/update-status'";
$js="

$(document).on('click','.profile_detail_section',function(evt){
        url=$(this).attr('data-url');
        slug=$(this).attr('data-slug');
        if(evt.target.id == 'id_'+slug)
            return; 
            
        if(evt.target.id == 'login-popup')
            return; 
            
        url_return(url);

    });
    
   function url_return(url){
        window.location.href = url;
   } 


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
";
$this->registerJs($js,\yii\web\VIEW::POS_END); ?>

<style>
    .profile_detail_section:hover{
        box-shadow: 0 0 10px #FFA6A6;
        margin-top: 0px;
    }
</style>

<div class="inner-banner"> </div>
<section class="mid-content-part">
  <div class="signup-part">
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="today-appoimentpart">
            <h3 class="mb-3">Hospital Requests </h3>
          </div>
          <?php $form = ActiveForm::begin(); ?>
          <div class="search-boxicon">
            <div class="search-iconmain"> <i class="fa fa-search"></i> </div>
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
              <?php if(!empty($lists)){ 
                $i=0;
                foreach ($lists as $list) {  
                  $hospital_id = $list->request_from;
                  $hospital=\common\models\UserProfile::findOne($hospital_id);
                  $checkRequest = DrsPanel::sendRequestCheck($hospital_id,$doctor_id);
                  ?>
                  <div class="col-lg-4 col-md-6 col-sm-12 " id="request_ids">
                    <div class="pace-part profile_detail_section" data-url="<?php echo $baseUrl.'/doctor/hospital/'.$hospital->slug?>" data-slug="<?php echo $hospital->slug ?>">
                     
                      <div class="accept-hospital-request">
                      <span class="pace-left">
                        <?php $image = DrsPanel::getUserAvator($hospital_id); ?>
                       <?php    echo Lightbox::widget([
                           'files' => [
                               [
                                   'thumb' => DrsPanel::getUserThumbAvator($hospital_id),
                                   'original' => $image,
                                   'title' => $hospital->prefix.' '.$hospital->name,

                               ],
                           ]
                       ]); ?>
                       </span>
                       <span class="pace-right">
                        <h4><?php echo $hospital->name; ?></h4>
                        <?php if(!empty($checkRequest)){ ?>
                          <p class="status">Status: <?php echo $checkRequest?></p>
                          <?php } ?>
                          <?php if($checkRequest == 'requested') {?>
                          <button id="id_<?php echo $hospital->slug ?>"  type="button" dataid ="2" dataid2="<?php echo $doctor_id ?>" dataid3="<?php echo $hospital_id?>" class="btn confirm-theme statusID text-center">Accept Request</button>
                          <?php } ?>
                        </span>
                        
                          
                        </div>
                      </div>
                    </div>

                    <?php } $i++; 
                    } else{
                      echo "No request found";
                      }?>
                  </div>

                <div class="row text-center" id="filter_result_hide" style="display:none;">
                    <?php echo 'No request found'; ?>
                </div>

                <?php ActiveForm::end(); ?>

              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="register-section">
        <div id="experiences-modal" class="modal fade model_opacity"  role="dialog">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
               <h4 class="modal-title" id="experiencesContact">Update Status </h4>
               <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
             </div>
             <div class="modal-body">
              <?php $form = ActiveForm::begin(['enableAjaxValidation'=>true]); ?>
              <?php //echo $this->render('update_status_form',['form' => $form,'model' => $model]) ?>

              <?php ActiveForm::end(); ?>
            </div>
          </div><!-- /.modal-content -->
        </div>
      </div>
    </div>

    