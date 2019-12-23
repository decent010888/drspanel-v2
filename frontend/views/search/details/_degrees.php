<?php
$baseUrl= Yii::getAlias('@frontendUrl');
if(!empty($degrees)){ ?>
    <div class="pace-part patient-prodetials">
        <div class="row">
            <div class="col-sm-12">
                <div class="pace-icon"> <img src="<?php echo $baseUrl?>/images/doctor-profile-icon7.png"> <!-- <i class="fa fa-heartbeat" aria-hidden="true"></i>  --></div>
                <div class="pace-right main-second" id="degree_filter">
                    <h4> Degree  </h4>
                        <?php
                        $string = $degrees;
                        $parts = explode(",", $string);
                        echo '<ul class="list_profile_li">';
                        $r=1;
                        foreach($parts as $part){
                            if($r > 4){ echo '<li id="degreehide">'.$part.'</li>'; }
                            else{ echo '<li>'.$part.'</li>'; }
                            $r++;
                        }
                        echo '</ul>';
                        if($r > 4){ ?>
                            <a href="javascript:void(0)" id="degree" class="seemore">See more...</a>
                            <a href="javascript:void(0)" id="degree" class="seeless">See less...</a>
                        <?php }  ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>