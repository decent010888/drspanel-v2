<?php

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use common\models\PatientMemberFiles;

$this->title = Yii::t('frontend', 'DrsPanel :: My Payments');
?>
<div class="inner-banner"> </div>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="today-appoimentpart">
                        <h3 class="text-left mb-3"> My Payments </h3>
                    </div>
                    <?php if ($userAppointment) { ?>
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th scope="col">Patient Detail</th>
                                    <th scope="col">Token</th>
                                    <th scope="col">Doctor</th>
                                    <th scope="col">Hospital / Clinic Name</th>
                                    <th scope="col">Shift Name / Shift Time</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($userAppointment as $paymentData) {
                                    $txnId = json_decode($paymentData['paytm_response']);
                                    $doctorAddress = common\models\UserAddress::findOne(['id' => $paymentData['doctor_address_id']]);
                                    ?>
                                    <tr>
                                        <td><?php echo $paymentData['user_name'] . '<br>' . $paymentData['user_phone'] ?></td>
                                        <td><?php echo $paymentData['token'] ?></td>
                                        <td><?php echo $paymentData['doctor_name'] ?></td>
                                        <td style="width: 220px;"><?php echo '<strong>' . $doctorAddress['type'] . ':</strong> ' . $doctorAddress['name'] . '<br>' . $doctorAddress['address'] ?></td>
                                        <td><?php echo $paymentData['shift_label'] . '<br>' . $paymentData['shift_name'] ?></td>
                                        <td><?php echo '<i class="fa fa-rupee"></i>' . $paymentData['txn_amount'] ?></td>
                                        <td><?php echo $paymentData['refund_by'] != '' ? 'Refund' : 'Completed' ?></td>
                                        <td><?php echo date('d M, Y', strtotime($paymentData['originate_date'])) ?></td>
                                        <td><a href="javascript:;" class="btn btn-default printReceipt" id="printReceipt" data-id="<?php echo $paymentData['appointment_id'] ?>">Receipt</a></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                    <?php } else {
                        ?>
                        No any payments history
                    <?php } ?>
                </div>
                <?php echo $this->render('@frontend/views/layouts/rightside'); ?>
            </div>
        </div>
    </div>
</section>
<?php
$baseUrl = Yii::getAlias('@frontendUrl');
$statementFileLink = $baseUrl . '/receipt.pdf';
$printReceipt = "'" . $baseUrl . "/patient/print-receipt'";
$this->registerJs("
    $(document).on('click', '#printReceipt', function () {
        appointmentId =$(this).attr('data-id');
        $('#main-js-preloader').show();
        $.ajax({
            dataType:'JSON',
            method:'POST',
            url: $printReceipt,
            data: {appointmentId:appointmentId}
        })
        .done(function( responce_data ) { 
        $('#main-js-preloader').hide();
            if (responce_data.status == 'success') {
                window.open('$statementFileLink', '_blank');
            }
        })// ajax close		

    }); //close addresss List
    ", \yii\web\VIEW::POS_END);
?>
