<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use  common\components\DrsPanel ;
$baseUrl= Yii::getAlias('@frontendUrl');
$getAppointment="'".$baseUrl."/$userType/get-appointment-detail'";
$js="    
    $(document).on('click','.booking_appointment_btn', function(event){    
        event.preventDefault();
        var a = $(this);
        if(a.hasClass('disabled')) {
            return false;
        }
    
        a.html(\"Processing...\").addClass('disabled'); 
            
        var str=$(this).attr('id');
        var ret = str.split('_');
        var id= ret[1]; 
        var form = $('form')[0];
        $.ajax({
            url: $('#bookeing-confirm_'+id).attr('action'),
            type: 'POST',
            dataType: 'JSON',
            data: new FormData(form),
            processData: false,
            contentType: false,
            success: function (data, status){
                $('li.active a.get-shift-token').click();
                if(data.status == 1){
                    $.ajax({
                      method:'POST',
                      url: $getAppointment,
                      data: {appointment_id:data.appointment_id}
                    })
                   .done(function( json_result ) { 
                        if(json_result){
                          $('#pslotTokenContent').html('');
                          $('#pslotTokenContent').html(json_result); 
                          $('.modal-header').html('<h3 >Booking <span>Detail</span></h3>');
                          $('#patientbookedShowModal').modal({backdrop: 'static',keyboard: false});
                        } 
                    }); 
                } 
                else{
                    $('#patientbookedShowModal').modal('hide');                
                    setTimeout(function () {
                        swal({            
                        type:'error',
                        title:'Error!',
                        text:data.message,            
                        timer:5000,
                        confirmButtonColor:'#a42127'
                    })},300);            
                }           
                
            },
            error: function (xhr, desc, err){
                 $('#patientbookedShowModal').modal('hide');
                 setTimeout(function () {
                    swal({            
                    type:'error',
                    title:'Error!',
                    text:'Please try again!',            
                    timer:3000,
                    confirmButtonColor:'#a42127'
                })},300);
            }
        });        
});
";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>
<div class="col-md-12 mx-auto">
    <div class="youare-text"> You are Booking an appointment with </div>
    <?php if($userType == 'hospital' || $userType == 'attender') { ?>
        <div class="pace-part main-tow">
            <div class="row">
                <div class="col-sm-12">
                    <div class="pace-left">
                        <?php $image = DrsPanel::getUserAvator($doctor->id);?>
                        <img src="<?php echo $image; ?>" alt="image"/>
                    </div>
                    <div class="pace-right">
                        <h4><?=$doctor['userProfile']['prefix']?> <?=$doctor['userProfile']['name']?></h4>
                        <p> <?= $doctor['userProfile']['speciality']; ?></p>
                        <p> <i class="fa fa-calendar"></i> <?php echo date('d M Y',strtotime($slot->date)); ?>
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
    <div class="workingpart">
        <input id="slot_date" type="hidden" value="<?php echo $slot->date; ?>">
        <div class="form-group">
            <div class="pull-left">
                <h5> <?php echo DrsPanel::getHospitalName($address->id); ?> </h5>
                <p><?php echo DrsPanel::getAddressShow($address->id); ?> </p>
            </div>
            <div class="pull-right">

            </div>
        </div>
    </div>
    <div class="workingpart">
        <div class="form-group">
            <div class="pull-left">
                <p> Token <span> <a href="#" class="roundone"> <?php echo $slot->token; ?> </a> </span> </p>
            </div>
            <div class="pull-right">
                <a href="#" class="time-bg">
                    <?php echo date('h:i a',$slot->start_time); ?> - <?php echo date('h:i a',$slot->end_time); ?>
                </a>
            </div>
        </div>
    </div>

    <?php $form = ActiveForm::begin(
        ['action'=>"$baseUrl/$userType/appointment-booked",
            'id'=>'bookeing-confirm_'.$model->slot_id,'enableClientValidation'=>true,
            'options' => ['class' => 'appoiment-form-part mt-0 mt-nill']
        ]);
    echo $form->field($model,'doctor_id')->hiddenInput()->label(false);
    echo $form->field($model,'slot_id')->hiddenInput()->label(false);
    echo $form->field($model,'schedule_id')->hiddenInput()->label(false);
    echo $form->field($model,'user_name')->hiddenInput()->label(false);
    echo $form->field($model,'user_phone')->hiddenInput()->label(false);
    echo $form->field($model,'user_gender')->hiddenInput()->label(false);?>

    <div class="btdetialpart">
        <div class="pull-left"><?= $model->user_name; ?></div>
        <div class="pull-right"><?= $model->user_phone; ?> </div>
    </div>
    <div class="btdetialpart">
        <div class="pull-left">Fees</div>
        <div class="pull-right">
            <strong><i class="fa fa-rupee"></i>
                <?php if(isset($slot->fees_discount) && $slot->fees_discount < $slot->fees && $slot->fees_discount > 0) { ?>            <?= $slot->fees_discount?>/- <span class="cut-price"><?= $slot->fees?>/-</span>

                <?php } else { echo $slot->fees.'/-'; } ?>
            </strong> </div>
    </div>

    <div class="form-group">
        <div class="new_confirmbtn m-0">
            <button id="<?php echo 'btn_'.$model->slot_id; ?>" type="button" class="confirm-theme booking_appointment_btn">Confirm Now</button>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>
