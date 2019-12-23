<?php
$baseUrl= Yii::getAlias('@frontendUrl');
?>



<div class="row">
    <div class="col-sm-12">
        <div class="search-boxicon">
            <div class="search-iconmain"> <i class="fa fa-search"></i> </div>
            <input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search patient, token ..." title="Type in a name" class="form-control">
        </div>
    </div>
</div>




<div id="appointment_shift_slots">
    <?php
    echo $this->render('/doctor/history-statistics/_history_shift_slots',['appointments'=>$appointments,'current_shifts'=>$current_selected,'doctor'=>$doctor,'shifts'=>$shifts,'history_count'=>$history_count,'typeCount'=>$typeCount,'userType'=>'doctor','type'=>'history']);
    ?>
</div>
<!--<div class="bookappoiment-btn">
    <input value="Statement" class="bookinput" type="button">
</div>-->