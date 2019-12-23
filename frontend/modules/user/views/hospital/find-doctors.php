<?php 
use common\components\DrsPanel;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use kartik\select2\Select2;

?>
<?php $baseUrl= Yii::getAlias('@frontendUrl'); ?>
<?php  $this->title = Yii::t('frontend', 'Hospital :: My Find Doctors', [
  'modelAddressClass' => 'Hospital',
  ]);

$loginUser=Yii::$app->user->identity; 
$updateStatus="'".$baseUrl."/hospital/update-status'";
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
    var input, filter, ul, li, a, i, txtValue, show = 0;
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
$categories=$speciality_list;
$cities=DrsPanel::getPopularCities();
$status_array=array('all'=>'All','requested'=>'Requested','confirm'=>'Confirmed');

$listcity=array();
foreach($cities as $city){
    $listcity[$city]=$city;
}
?>
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
            <h3 class="mb-3"> Find Doctors </h3>
          </div>

            <div class="slider hospitaldoctor-category">
                <?php
                if(!empty($categories)) {
                    foreach ($categories as $hslider) { ?>
                        <?php
                        $searchUrl=yii\helpers\Url::to(['/hospital/find-doctors?speciality='.$hslider['id']]);
                        ?>
                        <div onclick="location.href='<?php echo $searchUrl; ?>';">
                            <div class="detailmain-box <?php echo ($selected_speciality == $hslider['id'])?'detailmain_selected' : '' ?>">
                                <div class="detial-imgmain">
                                    <?php if($hslider['icon']=='') { ?>
                                        <img src="<?php echo $baseUrl?>/images/doctors1.png" alt="image">
                                    <?php } else { ?>
                                        <img src="<?php echo $hslider['icon']; ?>" alt="image">
                                    <?php  }?>
                                </div>
                            </div>
                            <div class="hos-discription"> <p><?php echo $hslider['value']?></p><span>(<?php echo isset($hslider['count'])?$hslider['count']:'0' ?>) <span></div>
                        </div>
                    <?php } }?>
            </div>

              <div class="mt-top25p hospital_find_dr">

                  <div class="select_box_div">
                      <?php echo Select2::widget([
                          'name' => 'city_filter',
                          'value' => '',
                          'data' => $listcity,
                          'options' => ['placeholder' => 'Select City ...','class'=>'search_filter_find','id'=>'city_filter']
                      ]);

                      ?>
                  </div>

                  <div class="select_box_div">
                      <?php echo Select2::widget([
                          'name' => 'status_filter',
                          'value' => '',
                          'data' => $status_array,
                          'options' => ['placeholder' => 'Select Status ...','class'=>'search_filter_find','id'=>'status_filter']
                      ]);

                      ?>
                  </div>

                  <div class="select_box_search search-boxicon">
                      <div class="search-iconmain"> <i class="fa fa-search"></i> </div>
                      <input placeholder="search doctors..." class="form-control" type="text"
                             id="doctor_filter_input" onkeyup="myFunction()">
                  </div>
              </div>
                <?php
                $doctorlists=array();
                if(isset($lists)){
                foreach ($lists as $list) {
                  $doctorlists[$list['user_id']] = $list['name'];
                }
              } ?>

            <div id="filter_doctor_list">
            <div class="row" id="filter_doctor">
               <?php 
               if(!empty($lists)){ 
                $i=0;
                foreach ($lists as $list) {  
                  $hospital_id = $user_id;
                  $doctor_id = $list['user_id'];
                  $checkRequest = DrsPanel::sendRequestCheck($hospital_id,$doctor_id);
                  ?>
                  <div class="col-sm-6 col-md-4" id="request_ids" >
                    <div class="pace-part profile_detail_section" data-url="<?php echo $baseUrl.'/hospital/doctor/'.$list['slug']?>" data-slug="<?php echo $list['slug']?>">
                     <span class="pace-left ">
                       <?php if(!empty($list['avatar'])) { ?>
                    <img src="<?php echo $list['avatar_base_url'].$list['avatar_path'].$list['avatar']?>" alt="image">
                    <?php } else { ?> 
                <img src="<?php echo $baseUrl?>/images/doctor-profile-image.jpg" alt="image">
                    <?php } ?>
                    </span>
                      <span class="pace-right">
                        <h4><?php echo isset($list['name'])?$list['name']:''?>
                          <span class="pull-right mydoctorpart hide">
                            <a class="modal-call" href="javascript:void(0)" title="Update Status" id="experiences-popup"><i class="fa fa-plus"></i></a></span>
                          </h4>
                          <p><?php echo isset($list['speciality'])?$list['speciality']:''?></p>
                          <?php if(!empty($checkRequest)){ ?>
                          <p class="status">Status: <?php echo $checkRequest?></p>
                          <?php } ?>
                          <?php if($checkRequest=='pending') {?>
                          <button id="id_<?php echo $list['slug']?>" type="button" dataid ="1" dataid2="<?php echo $doctor_id ?>" dataid3="<?php echo $hospital_id?>" dataid4="send" class="btn confirm-theme statusID">Send Request</button>
                          <?php } elseif($checkRequest=='requested') { ?>
                              <button id="id_<?php echo $list['slug']?>" type="button" dataid ="1" dataid2="<?php echo $doctor_id ?>" dataid3="<?php echo $hospital_id?>" dataid4="cancel" class="btn confirm-theme statusID">Cancel Request</button>
                              <?php } else{

                          }?>
                        </span>
                      </div>
                    </div>


                    <?php } $i++;
               }

             if(empty($lists))
                  {
                    echo 'No Doctors Found';
                  }
                    ?>
            </div>
            </div>

            <div class="row text-center" id="filter_result_hide" style="display:none;">
                <?php echo 'No Doctors Found'; ?>
            </div>

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
               <h4 class="modal-title" id="experiencesContact">Update Statis </h4>
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