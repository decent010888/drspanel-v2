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

<div class="tablist-diserp">
    <ul class="resp-tabs-list">

        <li type="online" class="get-appointments onlineselect <?php echo ($typeselected == 'online') ? 'resp-tab-active' : 'resp-tab-inactive'; ?>" data-type="online" data-doctorid="<?php echo isset($doctor->id) ? $doctor->id : ''; ?>">
            <a href="javascript:void(0)" class="" data-type="online" data-doctorid="<?php echo isset($doctor->id) ? $doctor->id : ''; ?>">
                <?= Yii::t('db', 'Online Appointment'); ?> <span id="online-count" ><?php echo ($typeCount) ? '(' . $typeCount['online'] . ')' : '' ?></span>
            </a>
        </li>
        <li type="offline" class="get-appointments offlineselect <?php echo ($typeselected == 'offline') ? 'resp-tab-active' : 'resp-tab-inactive'; ?>" data-type="offline" data-doctorid="<?php echo isset($doctor->id) ? $doctor->id : ''; ?>">
            <a href="javascript:void(0)" class="" data-type="offline" data-doctorid="<?php echo isset($doctor->id) ? $doctor->id : ''; ?>">
                <?= Yii::t('db', 'Offline Appointment'); ?> <span id="offline-count" ><?php echo ($typeCount) ? '(' . $typeCount['offline'] . ')' : '' ?></span>
            </a>
        </li>

    </ul>
</div>

<div class="doc-boxespart-book">
    <div class="col-md-12 col-sm-12">
        <div class="row">
            <div class="col-md-3 col-sm-6">
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
            <div class="col-md-3 col-sm-6">
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
            <div class="col-md-5 col-sm-6 report_button">
                <label class="control-label">&nbsp;</label>
                <button class="btn btn-danger delete_statement" type="button">Delete</button>
                <button class="btn btn-success download_statement" type="button">Download</button>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="row">
        <?php if (!empty($appointments)) { ?>
            <?php
            foreach ($appointments as $key => $appointment) {
                $offline_class = '';
                if ($appointment['booking_type'] == 'offline') {
                    $offline_class = "green_bg";
                }
                ?>
                <div class="col-sm-12">
                    <div class="token_allover get-booking-detail" data-appointment-id="<?php echo $appointment['id']; ?>" data-type="<?php echo $userType; ?>" data-doctorid="<?php echo $appointment['doctor_id']; ?>">
                        <div class="token1 avail <?php echo $offline_class; ?>">
                            <label class="token_text">Token</label>
                            <h4><?php echo $appointment['token']; ?> </h4>
                        </div>
                        <div class="token-rightdoctor1">

                            <div class="token-timingdoc1 avail <?php echo $offline_class; ?>">
                                <h3> <?php echo $appointment['name']; ?>  </h3>
                                <span class="number-partdoc"> <?php echo $appointment['phone']; ?> </span>
                                <p><strong>Booking ID:</strong> <?php echo $appointment['booking_id']; ?> </p>
                                <?php if ($userType == 'patient') { ?>
                                    <p><strong>Status: </strong><?php echo \common\components\DrsPanel::statusLabel($appointment['status']); ?></p>
                                <?php } else { ?>
                                    <p><strong>Status: </strong><?php echo \common\components\DrsPanel::statusLabelDoctor($appointment['status']); ?></p>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
        } else { ?>
            <div class="col-sm-12">
                <p>You have no appointment.</p>
            </div>
        <?php } ?>
    </div>
</div>