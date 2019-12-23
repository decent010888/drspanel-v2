<?php 
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use yii\helpers\ArrayHelper;
use backend\modelAddresss\AddScheduleForm;
use common\components\DrsPanel;
use kartik\select2\Select2;
$base_url= Yii::getAlias('@frontendUrl'); ?>
<?php  $this->title = Yii::t('frontend', 'Hospital :: MyDoctors', [
  'modelAddressClass' => 'Hospital',
  ]);?>
<?php

$updateStatus="'".$base_url."/hospital/update-status'";


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

$categories=$speciality_list;
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
          <h3 class="mb-3"> My Doctors </h3>
        </div>

          <div class="slider hospitaldoctor-category">
              <?php
              if(!empty($categories)) {
                  foreach ($categories as $hslider) { ?>
                      <?php
                      $searchUrl=yii\helpers\Url::to(['/hospital/my-doctors?speciality='.$hslider['id']]);
                      ?>
                      <div onclick="location.href='<?php echo $searchUrl; ?>';">
                          <div class="detailmain-box <?php echo ($selected_speciality == $hslider['id'])?'detailmain_selected' : '' ?>">
                              <div class="detial-imgmain">
                                  <?php if($hslider['icon']=='') { ?>
                                      <img src="<?php echo $base_url?>/images/doctors1.png" alt="image">
                                  <?php } else { ?>
                                      <img src="<?php echo $hslider['icon']; ?>" alt="image">
                                  <?php  }?>
                              </div>
                          </div>
                          <div class="hos-discription"> <p><?php echo $hslider['value']?></p><span>(<?php echo isset($hslider['count'])?$hslider['count']:'0' ?>) <span></div>
                      </div>
                  <?php } }?>
          </div>


          <div class="mt-top25p search-boxicon">
          <div class="search-iconmain"> <i class="fa fa-search"></i> </div>
            <input placeholder="search my doctors..." class="form-control" type="text" id="doctor_filter_input" onkeyup="myFunction()">
        </div>

          <div class="row" id="filter_doctor">
              <?php
              if(isset($lists)){
                foreach ($lists as $list) {
                    $hospital_id = $user_id;
                    $doctor_id = $list['user_id'];
                    ?>
                    <div class="col-sm-6 col-md-4">
                      <div class="pace-part profile_detail_section" data-url="<?php echo $base_url.'/hospital/doctor/'.$list['slug']?>" data-slug="<?php echo $list['slug']?>">
                          <span class="pace-left ">
                           <?php if(!empty($list['avatar'])) { ?>
                               <img src="<?php echo $list['avatar_base_url'].$list['avatar_path'].$list['avatar']?>" alt="image">
                           <?php } else { ?>
                               <img src="<?php echo $base_url?>/images/doctor-profile-image.jpg" alt="image">
                           <?php } ?>
                        </span>
                        <span class="pace-right">
                          <h4><?php echo isset($list['name'])?$list['name']:''?>
                            <span class="pull-right mydoctorpart hide"> <i class="fa fa-pencil" aria-hidden="true"></i></span>
                          </h4>
                          <p><?php echo isset($list['speciality'])?$list['speciality']:''?></p>
                            <button type="button" dataid ="1" dataid2="<?php echo $doctor_id ?>" dataid3="<?php echo $hospital_id?>" dataid4="remove" class="btn confirm-theme statusID">Remove</button>
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
  </div>
</section>

