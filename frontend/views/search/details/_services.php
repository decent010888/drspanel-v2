<?php
$baseUrl= Yii::getAlias('@frontendUrl');
?>
<?php if($userType == 'doctor'){
    if(!empty($servicesList)){ ?>
    <div class="pace-part patient-prodetials">
        <div class="row">
            <div class="col-sm-12">
                <div class="pace-icon">
                    <img src="<?php echo $baseUrl?>/images/service_icon.png"> <!-- <i class="fa fa-heartbeat" aria-hidden="true"></i>  --></div>
                <div class="pace-right main-second" id="service_filter">
                    <h4> Services  </h4>
                        <?php
                        $string = $servicesList;
                        $parts = explode(",", $string);
                        echo '<ul class="list_profile_li">';
                        $r=1;
                        foreach($parts as $part){
                            if($r > 4){ echo '<li id="servicehide">'.$part.'</li>'; }
                            else{ echo '<li>'.$part.'</li>'; }
                            $r++;
                        }
                        echo '</ul>';

                        if($r > 4){ ?>
                            <a href="javascript:void(0)" id="service" class="seemore">See more...</a>
                            <a href="javascript:void(0)" id="service" class="seeless">See less...</a>
                        <?php }  ?>


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