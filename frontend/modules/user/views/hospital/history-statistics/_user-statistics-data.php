<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$baseUrl = Yii::getAlias('@frontendUrl');
$loginUser = Yii::$app->user->identity;
$this->title = Yii::t('frontend', 'DrsPanel :: User Statistics Data');

$getTabContent = "'" . $baseUrl . "/hospital/ajax-statistics-data'";
$js = "    
    $(document).on('click', '.get-appointments',function () {        
        datakey = $('li.active').attr('id');
        doctorid=$(this).attr('data-doctorid');
        var splitid=datakey.split(\"_\");
        var schedule_id=splitid[1];
        if(schedule_id=='undefined'){
          schedule_id=0;
        }
        date=$('#appointment-date').val();
        type=$(this).attr('data-type');
        $.ajax({
          method:'POST',
          url: $getTabContent,
          data: {user_id:doctorid,type:type,date:date,shift_id:schedule_id}
        })
      .done(function( json_result ) { 
         var obj = jQuery.parseJSON(json_result);
            if(obj.status){ 
            $('#statistics-appointments').html('');
            $('#statistics-appointments').html(obj.appointments);  
          }

      });
    });
";
$this->registerJs($js, \yii\web\VIEW::POS_END);
?>
<div class="doc-timingslot">
    <ul>
<?php echo $this->render('/common/_shifts', ['shifts' => $shifts, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => 'user_history', 'userType' => $userType]); ?>
    </ul>
</div>
    <?php if (count($shifts) > 0) { ?>
    <div id="statistics-appointments">
    <?php echo $this->render('/common/_appointment-token', ['appointments' => $appointments, 'doctor' => $doctor, 'typeselected' => $typeselected, 'typeCount' => $typeCount, 'userType' => $userType, 'doctor_id' => $doctor->id]) ?>
    </div>
<?php } ?>
