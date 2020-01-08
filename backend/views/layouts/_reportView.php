<table style="border:1px solid #ccc">
    <tr>
        <td style="border: 1px solid #ccc; font-weight: bold">Patient Detail</td>
        <td style="border:1px solid #ccc; font-weight: bold">Appointment Date & Time</td>
        <td style="border:1px solid #ccc; font-weight: bold">Hospital/Clinic</td>
        <td style="border:1px solid #ccc; font-weight: bold">Token No.</td>
        <td style="border:1px solid #ccc; font-weight: bold">Fees</td>
        <td style="border:1px solid #ccc; font-weight: bold">Booking Id.</td>
        <td style="border:1px solid #ccc; font-weight: bold">Appointment Status</td>
    </tr>
    <tbody>
        <?php
        if ($appointments) {
            foreach ($appointments as $appdata) {
                $getUserAddress = common\models\UserAddress::find()->where(['id' => $appdata['doctor_address_id']])->one();
                ?>
                <tr>
                    <td style="border: 1px solid #ccc"><?php echo $appdata['patient_name'] . '<br> ' . $appdata['patient_mobile'] ?></td>
                    <td style="border: 1px solid #ccc"><?php echo date('d M, Y', strtotime($appdata['appointment_date'])) . '<br> ' . $appdata['appointment_time'] ?></td>
                    <td style="border: 1px solid #ccc"><?php echo ucfirst($getUserAddress['name']) ?></td>
                    <td style="border: 1px solid #ccc"><?php echo $appdata['token'] ?></td>
                    <td style="border: 1px solid #ccc"><?php echo $appdata['fees'] ?></td>
                    <td style="border: 1px solid #ccc"><?php echo $appdata['booking_id'] ?></td>
                    <td style="border: 1px solid #ccc"><?php echo $appdata['status_label'] ?></td>
                </tr>
                <?php
            }
        }
        ?>
    </tbody>
</table>