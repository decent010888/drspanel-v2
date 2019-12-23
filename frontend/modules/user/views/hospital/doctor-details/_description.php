<?php
use common\components\DrsPanel;
$base_url= Yii::getAlias('@frontendUrl');
if(!empty($description))
{ ?>
    <div class="pace-part patient-prodetials">
        <div class="row">
            <div class="col-sm-12">
                <h4> About</h4>
                <p><?php echo $description ?></p>

            </div>
        </div>
    </div>
    <?php
}
?>