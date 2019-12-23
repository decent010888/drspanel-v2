<?php

use kartik\date\DatePicker;

$baseUrl = Yii::getAlias('@frontendUrl');
$statementFileLink = $baseUrl . '/statement.pdf';
$getAppointment = "'" . $baseUrl . "/$userType/get-appointment-detail'";
$getAppointmentReport = "'" . $baseUrl . "/$userType/get-appointment-report'";
$deleteAppointment = "'" . $baseUrl . "/$userType/delete-appointment'";
$js = "
    $(document).on('click','.get-booking-detail', function () {
        doctorid=$(this).attr('data-doctorid');
        $.ajax({
          method:'POST',
          url: $getAppointment,
          data: {user_id:doctorid,appointment_id:$(this).attr('data-appointment-id')}
        })
       .done(function( json_result ) { 
        if(json_result){
          $('#pslotTokenContent').html('');
          $('#pslotTokenContent').html(json_result); 
          $('#patientbookedShowModal').modal({backdrop: 'static',keyboard: false});        
        } 
        });   
    });
    $(document).on('click', '.download_statement', function () {
        doctorid = $doctor_id;
        shiftid = $('#shiftID').val();
        $.ajax({
            method: 'POST',
            url: $getAppointmentReport,
            data: {user_id: doctorid, dateFrom: $('.date_from').val(), dateTo: $('.date_to').val(), shiftid : shiftid}
        })
        .done(function (json_result) {
            if (json_result) {
                window.open('$statementFileLink', '_blank');
            }
        });
    });
    
    $(document).on('click', '.delete_statement', function () {
        doctorid = $doctor_id;
        shiftid = $('#shiftID').val();
        $.ajax({
            method: 'POST',
            url: $deleteAppointment,
            data: {user_id: doctorid, dateFrom: $('.date_from').val(), dateTo: $('.date_to').val(), shiftid : shiftid}
        })
        .done(function (json_result) {});
    });
";
$this->registerJs($js, \yii\web\VIEW::POS_END);
?>

<?php
if (!empty($history_count)) {
    $total_patient = $history_count['total_patient'];
    $total_cancelled = $history_count['total_cancelled'];
}
if (!empty($typeCount)) {
    $total_online = $typeCount['online'];
    $total_offline = $typeCount['offline'];
}
?>

<div>
    <div class="totalpatient-part">
        <ul>
            <li><p>Total Patient <span><?php echo (isset($total_patient)) ? '(' . $total_patient . ')' : 0 ?></span>  </p></li>
            <li><p>Online Patient <span><?php echo (isset($total_online)) ? '(' . $total_online . ')' : 0 ?> </span> </p></li>
            <li><p> Offline Patient <span><?php echo (isset($total_offline)) ? '(' . $total_offline . ')' : 0 ?> </span>  </p></li>
            <li><p> Cancelled <span><?php echo (isset($total_cancelled)) ? '(' . $total_cancelled . ')' : 0 ?> </span>  </p></li>
        </ul>
    </div>
</div>

<div class="row shift-tokens">
    <div class="col-md-12 col-sm-12">
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <?php
                echo '<label class="control-label">From Date</label>';
                echo DatePicker::widget([
                    'name' => 'date_from',
                    'type' => DatePicker::TYPE_INPUT,
                    'value' => '',
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'dd-M-yyyy'
                    ],
                    'options' => ['class' => 'date_from']
                ]);
                ?>
            </div>
            <div class="col-md-4 col-sm-6">
                <?php
                echo '<label class="control-label">To Date</label>';
                echo DatePicker::widget([
                    'name' => 'date_to',
                    'type' => DatePicker::TYPE_INPUT,
                    'value' => '',
                    'pluginOptions' => [
                        'autoclose' => true,
                        'format' => 'dd-M-yyyy',
                        'class' => 'to_date'
                    ],
                    'options' => ['class' => 'date_to']
                ]);
                ?>

            </div>
            <div class="col-md-4 col-sm-6 report_button">
                <label class="control-label">&nbsp;</label>
                <button class="btn btn-danger delete_statement" type="button">Delete</button>
                <button class="btn btn-success download_statement" type="button">Download</button>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <?php if (!empty($appointments)) { ?>
        <?php
        foreach ($appointments as $key => $appointment) {
            $online_class = '';
            if ($appointment['booking_type'] == 'offline') {
                $online_class = "avail";
            }
            ?>

            <div class="col-md-4 col-sm-6">
                <span class="token_allover get-booking-detail" data-appointment-id="<?php echo $appointment['id']; ?>" data-type="<?php echo $userType; ?>" data-doctorid="<?php echo $doctor_id; ?>">
                    <span class="token <?php echo $online_class; ?>">
                        <h4> <?php echo $appointment['token']; ?> </h4>
                    </span>
                    <span class="token-rightdoctor">

                        <div class="token-timingdoc">
                            <h3> <?php echo $appointment['name']; ?>  </h3>
                            <span class="number-partdoc"> <?php echo $appointment['phone']; ?> </span>
                            <p><strong>Booking ID:</strong> <?php echo $appointment['booking_id']; ?> </p>
                            <p><strong>Status:</strong> <?php echo \common\components\DrsPanel::statusLabelDoctor($appointment['status']); ?> </p>
                        </div>
                    </span>
                </span>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="col-sm-12">
            <p>You have no any appointment for selected date.</p>
        </div>
    <?php } ?>

</div>
<div class="login-section ">
    <div class="modal fade model_opacity" id="patientbookedShowModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"  style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 >Booking <span>Detail</span></h3>
                </div>
                <div class="modal-body" id="pslotTokenContent">

                </div>
                <div class="modal-footer ">

                </div>
            </div>
        </div>
    </div>
</div>

<script>

</script>
