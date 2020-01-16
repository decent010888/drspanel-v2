<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;
use common\components\DrsPanel;

$baseUrl = Yii::getAlias('@frontendUrl');
$loginUser = Yii::$app->user->identity;
$this->title = Yii::t('frontend', 'DrsPanel :: Patient History');

$js = "function myFunction() {
    var input, filter, ul, li, a,b, i, txtValue,b,txtValue2;
    input = document.getElementById('myInput');
    filter = input.value.toUpperCase();
    ul = document.getElementsByClassName('shift-tokens');
    
    $( 'div.shift-tokens' ).each(function( i ) {        
        li = $(this).children('div')
        for (i = 0; i < li.length; i++) {
            a = li[i].getElementsByTagName('h3')[0];
            b = li[i].getElementsByTagName('h4')[0];
            txtValue = a.textContent || a.innerText;
            txtValue2= b.textContent || b.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                li[i].style.display = '';
            }  
            else if(txtValue2.indexOf(filter) > -1) {
                li[i].style.display = '';
            }
            else {
                li[i].style.display = 'none';
            }
        }
    });   
    
}";
$this->registerJs($js, \yii\web\VIEW::POS_END);
?>
<section class="mid-content-part">
    <div class="signup-part">
        <div class="container">
            <div class="row">
                <div class="col-md-12" id="appointments_section">
                    <div class="today-appoimentpart">
                        <div id="appointment_date_select" class="appointment_date_select mx-auto calendra_slider">
                            <?php
                            $dates_range = DrsPanel::getSliderDates();
                            echo $this->render('/common/_appointment_date_slider', ['dates_range' => $dates_range, 'doctor_id' => $doctor->id, 'type' => 'history', 'userType' => 'attender']);
                            ?>
                        </div>
                        <div class="calender_icon_main pull-right">
                            <?php
                            echo DatePicker::widget([
                                'name' => 'appointment_date',
                                'type' => DatePicker::TYPE_BUTTON,
                                'options' => ['placeholder' => 'Enter birth date ...'],
                                'value' => date('d M Y', $defaultCurrrentDay),
                                'id' => 'appointment-date',
                                'buttonOptions' => [
                                    'label' => '<img src="' . $baseUrl . '/images/celander_icon.png" alt="image"/>',
                                ],
                                'pluginOptions' => [
                                    'autoclose' => true,
                                    'format' => 'dd M yyyy',
                                    'startView' => 'days'
                                ],
                                'pluginEvents' => [
                                    "change" => "function(){
                                    historyDate($('#appointment-date').val(),'history','attender',$doctor->id);
                            }",
                                ],
                            ]);
                            ?>
                        </div>
                    </div>
                    <div class="doctormain-detailspt">
                        <div class="doc-boxespart-book" id="history-content">
<?php echo $this->render('/attender/history-statistics/_history-content', ['typeCount' => $typeCount, 'history_count' => $history_count, 'appointments' => $appointments, 'shifts' => $shifts, 'doctor' => $doctor, 'current_selected' => $current_selected, 'type' => 'history', 'userType' => 'attender']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>