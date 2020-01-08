<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use kartik\date\DatePicker;

$baseUrl = Yii::getAlias('@backendUrl');

$getAppointmentReport = "'" . $baseUrl . "/doctor/get-appointment-report'";
$deleteAppointment = "'" . $baseUrl . "/doctor/delete-appointment'";
$statementFileLink = $baseUrl . '/statement.pdf';
$js = "
    $(document).on('click', '.download_statement', function () {
        doctorid = $doctor_id;
        hospitalid = '';
        shiftid = 0;
        $.ajax({
            dataType:'JSON',
            method: 'POST',
            url: $getAppointmentReport,
            data: {user_id: doctorid,hospitalid:hospitalid, dateFrom: $('.date_from').val(), dateTo: $('.date_to').val(), shiftid : shiftid}
        })
        .done(function (json_result) {
            if (json_result.status == 'success') {
                window.open('$statementFileLink', '_blank');
            }
        });
    });
    
    $(document).on('click', '.delete_statement', function () {
        doctorid = $doctor_id;
        hospitalid = '';
        shiftid = 0;
        $.ajax({
            method: 'POST',
            url: $deleteAppointment,
            data: {user_id: doctorid, hospitalid:hospitalid, dateFrom: $('.date_from').val(), dateTo: $('.date_to').val(), shiftid : shiftid}
        })
        .done(function (json_result) {});
    });
";
$this->registerJs($js, \yii\web\VIEW::POS_END);
?>
<div class="row ">
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
                <label class="control-label" style="display: block">&nbsp;</label>
                <button class="btn btn-danger delete_statement" type="button">Delete</button>
                <button class="btn btn-success download_statement" type="button">Download</button>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
</div>