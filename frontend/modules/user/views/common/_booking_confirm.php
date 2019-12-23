<?php

use common\models\UserAddress;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use  common\components\DrsPanel ;
$baseUrl= Yii::getAlias('@frontendUrl');
$slot_date=$slot->date;

$getlist="'".$baseUrl."/$userType/booking-confirm-step2'";

$js="
$('.booking_confirm_step1').on('click',function(){
  $('#user_name_div').removeClass('error');
  $('#user_phone_div').removeClass('error'); 
  $('.btdetialpart_error_msg').css('display','none'); 
  var ne=0; var pe=0;var ge=0;
  var name=$('#user_name').val();
  var user_name = $.trim(name);



  if(user_name == ''){
    $('#user_name_div').addClass('error');
    $('#user_name_div_msg').text('Name can not be blank');
    $('#user_name_div_msg').css('display','block'); 
    var ne=1;
  }else
  {
    if(/^[a-zA-Z0-9- ]*$/.test(user_name) == false) {
     $('#user_name_div').addClass('error');
        $('#user_name_div_msg').text('Name contains illegal characters');
        $('#user_name_div_msg').css('display','block');        
         var pe=1;
    }
  }
 
  
  var phone=$('#user_phone').val();
  if(phone == ''){
    $('#user_phone_div').addClass('error');
    $('#user_phone_div_msg').text('Phone number can not be blank');
    $('#user_phone_div_msg').css('display','block'); 
    var pe=1;
  }
  else{
    var ph=/^[+]?([\d]{0,3})?[\(\.\-\s]?([\d]{3})[\)\.\-\s]*([\d]{3})[\.\-\s]?([\d]{4})$/; 
    if(ph.test(phone)==false){
        $('#user_phone_div').addClass('error');
        $('#user_phone_div_msg').text('Invalid Phone Number');
        $('#user_phone_div_msg').css('display','block');        
         var pe=1;
    } 
  }
  
  var gender=$('input[name=gender]:checked').val();
  if(gender == '' || typeof gender == 'undefined'){    
    $('#user_gender_div_msg').text('Gender can not be blank');
    $('#user_gender_div_msg').css('display','block'); 
    var ge=1;
  }
  
  var slot_id=$('#slot_id').val();
  
  if(ne == 1 || pe == 1 || ge == 1){
    return false;
  }
  else{
    $.ajax({
        method:'POST',
        url: $getlist,
        data: {slot_id:slot_id,name:name,phone:phone,gender:gender}
    })
   .done(function( msg ) { 
    if(msg){
      $('#pslotTokenContent').html('');
      $('#pslotTokenContent').html(msg); 
      //$('#patientbookedShowModal').modal({backdrop: 'static',keyboard: false});
    }
    });
  }
  

});

 $('#user_phone').keypress(function (e) {
   var phone=$('#user_phone').val();
    if(phone == ''){
    }
    else{
        $('#user_phone_div').removeClass('error');
        $('#user_phone_div_msg').text('');
        $('#user_phone_div_msg').css('display','none'); 
    }    
  });

$('#user_name').keypress(function (e) {
       // if (e.which === 32 && !this.value.length)
      var regex = new RegExp('^[a-zA-Z0-9- ]+$');
      var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
      if (regex.test(str)) {
        $('#user_name_div').removeClass('error');
        $('#user_name_div_msg').text('');
         $('#user_name_div_msg').css('display','none'); 
        return true;
      }
      else{
          e.preventDefault();
          $('#user_name_div').addClass('error');
          $('#user_name_div_msg').text('Name can not be blank');
          $('#user_name_div_msg').css('display','block'); 
          var ne=1;
          return false;
      }
    });
    
    $('input:radio[name=gender]').on('focus',function(){
        var id=$(this).attr('data-id');       
        $('#divfocus_'+id+' input').prop('checked', true);
    });
    
";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>
<style>
    button:focus{
        outline: 1px dotted #000 !important;
    }
</style>
<div class="col-md-12 mx-auto">
    <div class="youare-text"> You are Booking an appointment with </div>
    <?php if($userType == 'hospital' || $userType == 'attender') { ?>
        <div class="pace-part main-tow mb-0">
            <div class="row">
                <div class="col-sm-12">
                    <div class="pace-left">
                        <?php $image = DrsPanel::getUserAvator($doctor->id);?>
                        <img src="<?php echo $image; ?>" alt="image"/>
                    </div>
                    <div class="pace-right">
                        <h4><?=$doctor['userProfile']['name']?></h4>
                        <p> <?= $doctor['userProfile']['speciality']; ?></p>
                        <p> <i class="fa fa-calendar"></i> <?php echo date('d M Y',strtotime($slot_date)); ?>
                            <?php if(isset($slot->fees_discount) && $slot->fees_discount < $slot->fees && $slot->fees_discount > 0) { ?>
                                <span class="pull-right cut-price">
                                        <strong><i class="fa fa-rupee"></i> <?=$slot->fees?>/-</strong>
                                    </span>
                                <span class="pull-right fees_discount ">
                                        <strong><i class="fa fa-rupee"></i><?php echo $slot->fees_discount?>/-</strong>
                                    </span>
                            <?php } else{ ?>
                                <span class="pull-right">
                                        <strong><i class="fa fa-rupee"></i> <?=$slot->fees?>/-</strong>
                                    </span>
                            <?php }?>
                        </p>
                        <p><i class="fa fa-clock-o" aria-hidden="true"></i> <?php echo $slot->shift_label; ?> </p>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="workingpart book-confirm cls-1">
        <input id="slot_date" type="hidden" value="<?php echo $slot->date; ?>">
        <div class="form-group">
            <div class="pull-left">
                <h5> <?php echo DrsPanel::getHospitalName($address->id); ?> </h5>
                <p><?php echo DrsPanel::getAddressShow($address->id); ?> </p>
            </div>
            <div class="pull-right hide">
                <?php
                $getcurrent=DrsPanel::getCurrentLocationLatLong();
                $kms= DrsPanel::getKilometers($getcurrent['lat'],$getcurrent['lng'],$address->lat,$address->lng);
                if($kms > 0){ ?>
                    <a tabindex="-1" href="javascript:void(0)"><?php echo $kms ?><i class="fa fa-location-arrow"></i></a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="workingpart book-confirm cls-2">
        <div class="form-group">
            <div class="pull-left">
                <p> Token <span> <a tabindex="-1" href="#" class="roundone"> <?php echo $slot->token; ?> </a> </span> </p>
            </div>
            <div class="pull-right">
                <a tabindex="-1" href="#" class="time-bg">
                    <?php echo date('h:i a',$slot->start_time); ?> - <?php echo date('h:i a',$slot->end_time); ?>
                </a>
            </div>
        </div>
    </div>


    <form class="appoiment-form-part mt-0 mt-nill">
        <div tabindex="0" class="btdetialpart mt-0" id="user_name_div">
            <input type="text" id="user_name" name="name" placeholder="Patient Name" style="text-transform: capitalize;">
        </div>
        <div class="btdetialpart_error_msg" id="user_name_div_msg" style="display: none;"></div>

        <div tabindex="1" class="btdetialpart" id="user_phone_div">
            <input type="text" id="user_phone" name="phone" placeholder="Contact Number" maxlength="10">
        </div>

        <div class="btdetialpart_error_msg" id="user_phone_div_msg" style="display: none;"></div>

        <div class="form-group">
            <label> Gender </label>
            <div class="row radiorow">
                <?php $genderList=DrsPanel::getGenderList();
                foreach($genderList as $key=>$gender){ ?>
                    <div class="col-sm-3" id="divfocus_<?= $key; ?>">
                        <span>
                            <input tabIndex="0" data-id="<?= $key; ?>" name="gender" id="label_<?= $key; ?>" type="radio" value="<?= $key; ?>">
                            <label for="label_<?= $key; ?>"><?= $gender; ?></label>
                        </span>
                    </div>
                <?php  }
                ?>
            </div>
        </div>

        <div class="btdetialpart_error_msg" id="user_gender_div_msg" style="display: none;"></div>

        <div class="btdetialpart">
            <div class="pull-left">
                <button type="button" class="confirm-theme" data-dismiss="modal">Cancel</button>                        </div>

            <div class="pull-right text-right">
                <input type="hidden" name="slot_id" id="slot_id" value="<?= $slot->id; ?>"/>
                <button type="button" class="confirm-theme booking_confirm_step1">Confirm Now</button>
            </div>
        </div>
    </form>

</div>