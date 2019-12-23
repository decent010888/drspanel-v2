<?php

use common\components\DrsPanel;
$this->title = 'Drspanel :: My Shifts';
$base_url= Yii::getAlias('@frontendUrl');
?>
<section class="mid-content-part">

    <div class="container">
        <div class="row">
            <div class="col-md-10 mx-auto">

                <div class="today-appoimentpart">
                    <div class="col-md-12 calendra_slider">
                        <h3> My Shifts </h3>
                    </div>
                </div>


                <?php
                if(!empty($address_list)) {
                    foreach($address_list as $key=>$list) {
                        echo $this->render('_shift-block',['list' => $list,'doctor_id'=>$doctor_id,'allshifts'=>$allshifts]);
                    }
                } else {  ?>
                    <div class="col-md-12 text-center">Shifts not available.</div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

