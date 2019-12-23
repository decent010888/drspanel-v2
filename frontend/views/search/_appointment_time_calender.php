<?php
use common\models\UserScheduleDay;
use common\components\DrsPanel;

$baseUrl= Yii::getAlias('@frontendUrl');
$firstKey=strtotime($dates_range[0]);
$currentdate=strtotime(date('Y-m-d'));
$enddate=strtotime('+14 days', $currentdate);

$starttime=date('h:i a',$scheduleDay['start_time']);
$endtime=date('h:i a',$scheduleDay['end_time']);
$date_filter=date('Y-m-d',$date_filter);
?>

<style>
    .day_name h3{font-size: 25px;}
    .day_date{font-size:18px;}

</style>
<?php

?>


<div class="appointment_calendar clearfix">

    <?php if($firstKey > $currentdate) { ?>
        <div class="cal_prev prev_search_calender" id="prevdate_<?php echo $doctor_id; ?>_<?php echo $firstKey;?>" data-type="<?php echo $type; ?>" data-userType="<?php echo $userType; ?>" data-schedule_id="<?php echo $schedule_id; ?>" data-date_selected="<?php echo strtotime($date_filter);?>">
            <img src="<?php echo Yii::getAlias('@frontendUrl'); ?>/images/arrow-1.png" class="img-responsive" alt="img"/>
        </div>
    <?php } else { ?>
        <div class="cal_prev cal_prev_disabled">
            <img src="<?php echo Yii::getAlias('@frontendUrl'); ?>/images/arrow-1.png" class="img-responsive" alt="img"/>
        </div>
    <?php } ?>

    <div class="date_calender">
        <?php foreach($dates_range as $datekey){?>
            <div class="calender_date_list">

                <a id="<?php echo strtotime($datekey) ?>" class="fetch_date_schedule" href="javascript:void(0);" data-doctor_id="<?php echo $doctor_id; ?>"  data-nextdate="<?php echo $datekey?>">
                    <div class="date-col <?php if(isset($datekey) && isset($date_filter) && ($datekey == $date_filter)) { ?> active <?php }?>">
                        <h5><?php  echo \common\components\DrsPanel::getDateWeekDay($datekey) ?></h5>
                        <p><?php  echo date('d',strtotime($datekey)); ?></p>
                    </div>
                </a>

            </div>
        <?php }
        $lastKey=strtotime($datekey);
        ?>
    </div>

    <?php if($lastKey == $enddate) { ?>
        <div class="cal_next cal_next_disabled">
            <img src="<?php echo Yii::getAlias('@frontendUrl'); ?>/images/arrow-2.png" class="img-responsive" alt="img"/>
    <?php } else { ?>
        <div class="cal_next next_search_calender" id="date_<?php echo $doctor_id; ?>_<?php echo $firstKey;?>" data-type="<?php echo $type; ?>" data-userType="<?php echo $userType; ?>" data-schedule_id="<?php echo $schedule_id; ?>" data-date_selected="<?php echo strtotime($date_filter); ?>">
            <img src="<?php echo Yii::getAlias('@frontendUrl'); ?>/images/arrow-2.png" class="img-responsive" alt="img"/>
        </div>
    <?php } ?>
</div>