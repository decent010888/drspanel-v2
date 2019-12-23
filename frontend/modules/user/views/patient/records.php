<?php 
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use common\models\User;
use common\models\UserProfile;
use common\models\PatientMemberFiles;
use common\models\Groups;
use frontend\modules\user\models\PatientMemberForm;

$this->title = Yii::t('frontend', 'Patient::Records');

$memberData =  DrsPanel::membersList($id);
$PatientMembersData = new  PatientMemberFiles();
$PatientModel = new PatientMemberForm();
$baseUrl=Yii::getAlias('@frontendUrl');
$genderList=DrsPanel::getGenderList();

$this->registerJsFile($baseUrl.'/js/popper.min.js', ['depends' => [yii\web\JqueryAsset::className()]]);

$sharerecord="'".$baseUrl."/patient/share-record'";


$this->registerJs("
    
    function myFunction() {
        $('#filter_result_hide').css('display','none');
        var input, filter, ul, li, a,b, i, txtValue,show = 0;
        input = document.getElementById('myInput');
        filter = input.value.toUpperCase();
        ul = document.getElementById('record_list');
        li = ul.getElementsByTagName('li');
        
        for (i = 0; i < li.length; i++) {
            a = li[i].getElementsByTagName('span')[0];
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
    } 


  $('.OpenRecord').on('click', function () {
  var myMemberId = $(this).attr('data-id');
     $('.modal-body #memberId').val(myMemberId);
   });
   
   $('.share_user_record').on('click',function(){
      var myMemberId = $(this).attr('data-member_id');
        $.ajax({
            url: $sharerecord,
            dataType:   'html',
            method:     'POST',
            data: { member_id: myMemberId},
            success: function(response){
                $('#updaterecord').empty();
                $('#updaterecord').append(response);
                $('#updaterecord').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });
            }
        });
    });

",\yii\web\VIEW::POS_END);
?>
<div class="inner-banner"> </div>
<section class="mid-content-part">
  <div class="signup-part">
    <div class="container">
      <div class="row">
      <div class="col-md-9">
          <div class="today-appoimentpart">
              <h3 class="text-left mb-3"> My Records List </h3>
          </div>
        <div class="record_part_list">

            <div class="row">
                <div class="col-sm-12">
                    <div class="search-boxicon booking_icon">
                        <div class="search-iconmain"> <i class="fa fa-search"></i> </div>
                        <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search record ..." title="Type in a name" class="form-control">
                    </div>
                </div>
            </div>

          <ul class="record_list record-col" id="record_list">
            <?php 
            if(!empty($memberData)){
                foreach ($memberData as $member) { ?>
                <li class="dropdown">
				    <span class="pull-left"><?php echo $member['name']?> - <?php echo $member['phone']?></span>
                    <span class="pull-right">
                        <input onclick="location.href='<?php echo yii\helpers\Url::to(['patient-appointments','id'=>$member['id']]); ?>'" class="confirm-theme record-list-btn-app" value="Appointments " type="button">
                        <input onclick="location.href='<?php echo yii\helpers\Url::to(['patient-record-files','slug'=>$member['slug']]); ?>'" class="confirm-theme record-list-btn-record" value="Records " type="button">
							<a href="javascript:void(0)" id="share_user_record_<?php echo $member['id'];?>"  data-member_id="<?php echo $member['id'];?>"  aria-expanded="false" class="red-star share_user_record"><i class="fa fa-share-alt"></i></a>

                    </span>
                </li>
                <?php }
                } 
                else { ?> 
                    Records not found 
                <?php } ?>
            </ul>

            <div class="row text-center" id="filter_result_hide" style="display:none;">
                <?php echo 'No Records Found'; ?>
            </div>

        </div>
        </div>
        <?php echo $this->render('@frontend/views/layouts/rightside'); ?>
        </div>
        </div>
        </div>
        </section>

<div class="register-section">
<div class="modal fade model_opacity" id="updaterecord" tabindex="-1" role="dialog" aria-labelledby="addproduct" aria-hidden="true">
</div>
</div>