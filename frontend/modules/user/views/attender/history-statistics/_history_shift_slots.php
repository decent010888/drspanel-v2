<div class="doc-timingslot">
    <ul>
        <?php echo $this->render('/common/_shifts', ['shifts' => $shifts, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => 'history', 'userType' => 'attender']); ?>
    </ul>
</div>
<div class="doc-boxespart-book" id="shift-tokens">
        <?php
        echo $this->render('/attender/history-statistics/_history-patient', ['appointments' => $appointments, 'doctor_id' => $doctor->id, 'userType' => 'attender']);
        ?>
</div>