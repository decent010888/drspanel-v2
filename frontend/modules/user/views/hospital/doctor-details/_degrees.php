<?php
$baseUrl= Yii::getAlias('@frontendUrl');
if(!empty($degrees)){ ?>
    <div class="pace-part patient-prodetials">
        <div class="row">
            <div class="col-sm-12">
                <div class="pace-icon"> <img src="<?php echo $baseUrl?>/images/doctor-profile-icon7.png"> <!-- <i class="fa fa-heartbeat" aria-hidden="true"></i>  --></div>
                <div class="pace-right main-second">
                    <h4> Degree  </h4>
                    <p>
                        <?php
                        $string = $degrees;
                        $parts = explode(",", $string);
                        $degree = implode(', ', $parts);
                        echo isset($degree)?$degree:''; // Return the value
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php } ?>