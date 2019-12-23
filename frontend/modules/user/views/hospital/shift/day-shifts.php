<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use common\components\DrsPanel;

$this->title = Yii::t('frontend','DrsPanel :: Today Timing');

$baseUrl= Yii::getAlias('@frontendUrl');
$updateStatus="'".$baseUrl."/hospital/update-shift-status'";
$js="
    $(document).on('click', '.shift-toggle',function () {
    $('#main-js-preloader').show();   
       // alert('dsf');
      shift_id=$(this).attr('data-shift');
      dates=$('#appointment-date').val();  
      date=new Date(dates).getTime() / 1000    
      status=$('#toggle-'+shift_id).is(':checked');      
      if(status=='true'){
        $('#toggle-'+shift_id).attr('checked',false);
        $('#back-color_'+shift_id).css('background-color', '#ccc');
        statusValue=1;
      }else{
         $('#toggle-'+shift_id).attr('checked',true);
         $('#back-color_'+shift_id).css('background-color', '#4cd964');
         statusValue=0;
      }      
       $.ajax({
          method:'POST',
          url: $updateStatus,
          data: {id:shift_id,status:statusValue,date:date,user_id:$doctor->id}
        })
      .done(function( msg ) { 
            getShiftSlots($doctor->id,'hospital','shifts',date,0,'+');
            setTimeout(function() {
                $('#main-js-preloader').fadeOut('fast');
            }, 1000);
      });
    }); 
";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>

<div class="youare-text">Timing for Doctor: <?php echo DrsPanel::getUserName($doctor->id);?></div>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-8 mx-auto" id="appointments_section">
                    <div class="today-appoimentpart">
                        <div id="appointment_date_select" class="appointment_date_select mx-auto calendra_slider">
                            <?php
                            $dates_range=DrsPanel::getSliderDates($date);
                            echo $this->render('/common/_appointment_date_slider',['dates_range'=>$dates_range,'doctor_id'=>$doctor->id,'type'=>'shifts','userType'=>'hospital']);
                            ?>
                        </div>

                        <div class="calender_icon_main pull-right">
                            <?php echo DatePicker::widget([
                                'name' => 'appointment_date',
                                'type' => DatePicker::TYPE_BUTTON,
                                'value' => date('d M Y',$defaultCurrrentDay),
                                'id'=>  'appointment-date',
                                'buttonOptions'=>[
                                    'label' => '<img src="'.$baseUrl.'/images/celander_icon.png" alt="image"/>',
                                ],
                                'pluginOptions' => [
                                    'autoclose'=>true,
                                    'format' => 'dd M yyyy',
                                    'startDate' => date('d M Y',$defaultCurrrentDay),
                                ],
                                'pluginEvents' => [
                                    "change" => "function(){
                                            bookingsDate($('#appointment-date').val(),'shifts','hospital',$doctor->id);
                                        }",
                                ],
                            ]);
                            ?>
                        </div>
                    </div>
                    <div class="doctor-timing-main" id="appointment_shift_slots">
                        <?php echo $this->render('_address-with-shift',['appointments'=>$shifts])?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>