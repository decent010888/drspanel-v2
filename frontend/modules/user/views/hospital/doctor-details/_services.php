<?php
$baseUrl= Yii::getAlias('@frontendUrl');
?>
<?php if($userType == 'doctor'){
    if(!empty($servicesList)){ ?>
    <div class="pace-part patient-prodetials">
        <div class="row">
            <div class="col-sm-12">
                <div class="pace-icon"> <img src="<?php echo $baseUrl?>/images/doctor-profile-icon3.png"> <!-- <i class="fa fa-heartbeat" aria-hidden="true"></i>  --></div>
                <div class="pace-right main-second">
                    <h4> Services/Facility  </h4>
                    <p>
                        <?php
                        $string = $servicesList;
                        $parts = explode(",", $string);
                        $services = implode(', ', $parts);
                        echo isset($services)?$services:''; // Return the value
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php }
} else{ ?>
    <ul class="mobile-text">
        <?php
        if(!empty($servicesList)) {
            $Servicedata = explode(',', $servicesList);
            foreach ($Servicedata as $list) { ?>
            <li><i><img src="<?php echo $baseUrl?>/images/check-icon.png"></i> <?php echo $list?></li>
            <?php }
        }?>
    </ul>
<?php } ?>