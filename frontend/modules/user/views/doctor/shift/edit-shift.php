<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\date\DatePicker;
use common\components\DrsPanel;
use common\models\UserProfile;
use kartik\select2\Select2;
use yii\helpers\Url;

/*$this->registerJsFile('http://maps.google.com/maps/api/js?key=AIzaSyD68G6UYDDxDthxDtQCjidVP5dgth3P-o0&sensor=false&libraries=places',['depends' => [\yii\web\JqueryAsset::className()]]);*/

$citiesList=[];
$base_url= Yii::getAlias('@frontendUrl');
$this->title = 'Drspanel :: Edit Shift';


$statesList=ArrayHelper::map(DrsPanel::getStateList(),'name','name');


if($modelAddress->state)
    $citiesList=ArrayHelper::map(DrsPanel::getCitiesList($modelAddress->state,'name'),'id','name');

if($modelAddress->city_id)
    $area_list=ArrayHelper::map(DrsPanel::getCityAreasList($modelAddress->city_id),'name','name');

if(!in_array($modelAddress->area,$area_list)){
    $area_list[$modelAddress->area]=$modelAddress->area;
}

$frontend=Yii::getAlias('@frontendUrl');

$cityUrl="'".$frontend."/doctor/city-list'";
$cityAreaUrl="'".$frontend."/doctor/city-area-list'";
$addmoreshift="'".$frontend."/doctor/add-more-shift'";
$mapAreaUrl="'".$frontend."/doctor/map-area-list'";


$js="
$(document).on('change', '#estate_list',function () { 
  $.ajax({
    method:'POST',
    url: $cityUrl,
    data: {state_id:$(this).val()}
  })
  .done(function( msg ) { 
    $('#ecity_list').html('');
    $('#ecity_list').html(msg);

  });
}); 

$(document).on('change', '#ecity_list',function () {
  $.ajax({
    method: 'POST',
    url: $cityAreaUrl,
    data: { id: $('#ecity_list').val()}
  })
  .done(function( msg ) { 
    if(msg){
      $('#arealist_update').show();
      $('#arealist_update').html('');
      $('#arealist_update').html(msg);
    }
  });
});

$('.maplocation_attachment').on('click', function () {
  $.ajax({
    method: 'POST',
    url: $mapAreaUrl,
    data: { city: $('#ecity_list').val(),state: $('#estate_list').val(),address:$('#useraddress-address').val(),
            area:$('#useraddress-area').val(),lat:$('#useraddress-lat').val(),lng:$('#useraddress-lng').val()}
  })
  .done(function( json_result ) { 
    $('#mapTokenContent').html('');
    $('#mapTokenContent').html(json_result); 
    $('#mapbookedShowModal').modal({backdrop: 'static',keyboard: false});
    
    
  });
});

$('.modal').on('shown.bs.modal', function (e) {
   initialize();
});

$('.add_siftbox').click(function(){
    var numItems = $('.shift_time_section').length;  
    $.ajax({
        type: 'POST',
        url: $addmoreshift,
        data:{shiftcount:numItems},
        success: function(data) {
            $('.add_more_shift').append(data);
            addValidationRules('shiftform',numItems,'edit');
        }
    });
   
});

";
$this->registerJs($js,\yii\web\VIEW::POS_END);


?>

<div class="inner-banner"> </div>

<section class="mid-content-part">

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <?php $form = ActiveForm::begin(['id' => 'shiftform','options' => ['enctype'=> 'multipart/form-data','enableAjaxValidation' => true]]); ?>
                <div class="col-md-12 mx-auto">
                    <div class="main_usrimgbox">
                        <h2 class="track_headline">Edit Shift</h2>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($modelAddress, 'name')->textInput(
                                    ['placeholder' => 'Hospital/Clinic Name','readOnly'=>($disable_field == 0)?false:true])->label(false) ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo  $form->field($modelAddress, 'state')->widget(Select2::classname(),
                                [
                                    'data' => $statesList,
                                    'size' => Select2::SMALL,
                                    'options' => ['placeholder' => 'Select State ...', 'multiple' => false,'id'=>'estate_list'],
                                    'pluginOptions' => [
                                        'tags' => false,
                                        'allowClear' => true,
                                        'multiple' => false,
                                        'disabled'=>($disable_field == 0)?false:true
                                    ],
                                ])->label(false); ?>
                        </div>

                        <div class="col-md-6" id="citylist_update">
                            <?php echo  $form->field($modelAddress, 'city_id')->widget(Select2::classname(),
                                [
                                    'data' => $citiesList,
                                    'size' => Select2::SMALL,
                                    'options' => ['placeholder' => 'Select City ...', 'multiple' => false,'id'=>'ecity_list'],
                                    'pluginOptions' => [
                                        'tags' => false,
                                        'allowClear' => true,
                                        'multiple' => false,
                                        'disabled'=>($disable_field == 0)?false:true
                                    ],
                                ])->label(false); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($modelAddress, 'address')->textInput(['placeholder' => 'Address','readOnly'=>($disable_field == 0)?false:true])->label(false) ?>
                        </div>

                        <div class="col-md-6" id="arealist_update">
                            <?php echo  $form->field($modelAddress, 'area')->widget(Select2::classname(),
                                [
                                    'data' => $area_list,
                                    'size' => Select2::SMALL,
                                    'options' => ['placeholder' => 'Select Area/Colony ...', 'multiple' => false,'disabled'=>($disable_field == 0)?false:true],
                                    'pluginOptions' => [
                                        'tags' => true,
                                        'allowClear' => true,
                                        'multiple' => false,
                                        'disabled'=>($disable_field == 0)?false:true
                                    ],
                                ])->label(false); ?>
                        </div>

                        <div class="col-md-3">
                            <?= $form->field($modelAddress, 'phone')->textInput(['placeholder' => 'Phone','maxlength'=> 10,'readOnly'=>($disable_field == 0)?false:true])->label(false) ?>
                        </div> 
                        <div class="col-md-3">
                            <?= $form->field($modelAddress, 'landline')->textInput(['placeholder' => 'Landline','maxlength'=> 12,'readOnly'=>($disable_field == 0)?false:true])->label(false) ?>
                        </div>

                        <div class="col-md-12 map_attachment">
                            <span class="maplocation maplocation_attachment">
                                <i aria-hidden="true" class="fa fa-map-marker"></i> Set Location
                            </span>
                            <span class="pin_address"></span>
                            <?= $form->field($modelAddress, 'lat')->hiddenInput()->label(false) ?>
                            <?= $form->field($modelAddress, 'lng')->hiddenInput()->label(false) ?>
                        </div>

                        <?= $form->field($modelAddress, 'id')->hiddenInput()->label(false) ?>

                        <div class="col-md-12" id="data_image_reload">
                            <?php echo $this->render('_address_images',['form'=>$form,'addressImages'=>$addressImages,'userAdddressImages'=>$userAdddressImages,'disable_field'=>$disable_field])?>
                        </div>

                        <div class="clearfix"></div>
                    </div>
                </div>

                <div class="ct_newpart">
                    <div class="col-md-12 add_more_shift">
                        <?php 
                        if(count($shifts) > 0) {
                            $s=0;
                            foreach($shifts as $keys=> $shift) { ?>
                                <div class="bt_formpartmait shift_time_section" id="shift_count_<?php echo $s + 1 ?>">
                                    <div class="edit-delete" style="<?php echo ($s == 0)?'display:block;':'' ?>" id="edit_shift_<?php echo $s + 1 ?>" >
                                        <a class="remove_shiftbox_div" href="javascript:void(0)"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 mx-auto calendra_slider">
                                            <div class="week_sectionmain">
                                                <ul>
                                                    <?php
                                                    $model->weekday[$s]=$shift['shifts'];
                                                    echo $form->field($model, 'weekday['.$s.'][]')
                                                        ->checkboxList($weeks, [
                                                            'item' => function ($index, $label, $name, $checked, $value) {
                                                                $return = '<li><div class="weekDays-selector"><span>';
                                                                $return .= Html::checkbox($name, $checked, ['value' => $value, 'autocomplete' => 'off', 'id' => 'week_' .$name.'_'.$value,'class'=> 'weekday']);
                                                                $return .= '<label for="week_' .$name.'_'. $value . '" >' . Yii::t('db',ucwords($label)) . '</label>';
                                                                $return .= '</span></div></li>';
                                                                return $return;
                                                            }
                                                        ])->label(false) ?>
                                                </ul>

                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <?= $form->field($model, 'start_time['.$s.']')->textInput(['value'=>$shift['start_time'],'autocomplete'=>'off','placeholder' => Yii::t('db','Start'),'readonly'=> false,'class'=>'shift-time-check addscheduleform-start_time form-control','onchange' => 'shiftOneValue("shiftform",'.$s.',"edit");'])->label('From'); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?= $form->field($model, 'end_time['.$s.']')->textInput(['value'=>$shift['end_time'],'autocomplete'=>'off','placeholder' => Yii::t('db','To'), 'readonly'=>false,'class'=>'shift-time-check addscheduleform-end_time form-control','onchange' => 'shiftOneValue("shiftform",'.$s.',"edit");'])->label('To'); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php echo $form->field($model, 'appointment_time_duration['.$s.']')->Input('number',['min'=>'1.00','max'=>'10.00','value'=>$shift['appointment_time_duration'],'placeholder' => Yii::t('db','Duration (Minutes)'),'onchange' => 'maxvalidation("shiftform","appointment_time_duration",'.$s.',"edit");', 'readonly'=>false])->label('Duration'); ?>
                                        </div>

                                        <div class="col-md-6">
                                            <?php echo $form->field($model, 'patient_limit['.$s.']')->Input('number',['min'=>'1.00','max'=>'10.00','value'=>$shift['patient_limit'],'placeholder' => Yii::t('db','Patient Limit'),'onchange' => 'patientcount("shiftform","patient_limit",'.$s.',"edit");', 'readonly'=>true])->label('Patient Limit'); ?>
                                        </div>

                                        <div class="col-md-6">
                                            <?php echo $form->field($model, 'consultation_fees['.$s.']')->Input('number',['min'=>'0.00','value'=>$shift['consultation_fees'],'placeholder' => Yii::t('db','Consultancy Fee'), 'onchange' => 'feesvalidation("shiftform","consultation_fees",'.$s.',this.value,"edit");','readonly'=>false]); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php echo $form->field($model, 'emergency_fees['.$s.']')->Input('number',['min'=>'0.00','value'=>$shift['emergency_fees'],'placeholder' => Yii::t('db','Emergency Fee'), 'onchange' => 'feesvalidation("shiftform","emergency_fees",'.$s.',this.value,"edit");','readonly'=>false]); ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php echo $form->field($model, 'consultation_fees_discount['.$s.']')->Input('number',['min'=>'0.00','max'=>($shift['consultation_fees'] > 0)?($shift['consultation_fees'] - 1):0,'value'=>$shift['consultation_fees_discount'],'placeholder' => Yii::t('db','Discounted Consultancy Fee'), 'onchange' => 'maxvalidation("shiftform","consultation_fees_discount",'.$s.',"edit");', 'readonly'=>false]); ?>

                                        </div>
                                        <div class="col-md-6">
                                            <?php echo $form->field($model, 'emergency_fees_discount['.$s.']')->Input('number',['min'=>'0.00','max'=>($shift['emergency_fees'] > 0)?($shift['emergency_fees'] - 1):0,'value'=>$shift['emergency_fees_discount'],'placeholder' => Yii::t('db','Discounted Emergency Fee'), 'onchange' => 'maxvalidation("shiftform","emergency_fees_discount",'.$s.',"edit");', 'readonly'=>false]); ?>
                                        </div>
                                        <div class="clearfix"></div>
                                        <input type="hidden" name="AddScheduleForm[id]" value="<?php echo $shift['shift_id']?>"/>
                                           
                                    </div>
                                </div>
                                <?php
                                if(!empty($shift['shifts_days_id']))
                                {
                                    foreach ($shift['shifts_days_id'] as $key => $shiftidValue) {

                                        ?>
                                        <input type="hidden" name="shift_ids[<?php echo $keys; ?>][<?php echo $key?>]" value="<?php echo $shiftidValue?>"/>
                                        <?php
                                    }
                                }
                                ?>
                            <?php $s++; }
                        }
                        else{ ?>
                            <div class="bt_formpartmait shift_time_section" id="shift_count_1">
                                <div class="edit-delete" id="edit_shift_1" style="display:block;">
                                    <a class="remove_shiftbox_div" href="javascript:void(0)"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mx-auto calendra_slider">
                                        <div class="week_sectionmain">
                                            <ul>
                                                <?php
                                                echo $form->field($model, 'weekday[0][]')
                                                    ->checkboxList($weeks, [
                                                        'item' => function ($index, $label, $name, $checked, $value) {
                                                            $return = '<li><div class="weekDays-selector"><span>';
                                                            $return .= Html::checkbox($name, $checked, ['value' => $value, 'autocomplete' => 'off', 'id' => 'week_' .$name.'_'.$value,'class'=> 'weekday']);
                                                            $return .= '<label for="week_' .$name.'_'. $value . '" >' . Yii::t('db',ucwords($label)) . '</label>';
                                                            $return .= '</span></div></li>';
                                                            return $return;
                                                        }
                                                    ])->label(false) ?>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <?= $form->field($model, 'start_time[]')->textInput(['autocomplete'=>'off','placeholder' => Yii::t('db','Start'),'readonly'=> false,'class'=>'shift-time-check addscheduleform-start_time form-control','onchange' => 'shiftOneValue("shiftform",0,"add");'])->label('From'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= $form->field($model, 'end_time[]')->textInput(['autocomplete'=>'off','placeholder' => Yii::t('db','To'), 'readonly'=>false,'class'=>'shift-time-check addscheduleform-end_time form-control','onchange' => 'shiftOneValue("shiftform",0,"add");'])->label('To'); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php echo $form->field($model, 'appointment_time_duration[]')->Input('number',['min'=>'1.00','max'=>'240','placeholder' => Yii::t('db','Duration (Minutes)'), 'onchange' => 'maxvalidation("shiftform","appointment_time_duration",0,"add");','readonly'=>false])->label('Duration'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo $form->field($model, 'patient_limit[]')->Input('number',['min'=>'1.00','max'=>'240','placeholder' => Yii::t('db','Patient Limit'),'onchange' => 'patientcount("shiftform","patient_limit",0,"add");', 'readonly'=>true])->label('Patient Limit'); ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php echo $form->field($model, 'consultation_fees[]')->Input('number',['min'=>'0.00','placeholder' => Yii::t('db','Consultancy Fee'), 'onchange' => 'feesvalidation("shiftform","consultation_fees",0,this.value,"add");', 'readonly'=>false]); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php echo $form->field($model, 'emergency_fees[]')->Input('number',['min'=>'0.00','placeholder' => Yii::t('db','Emergency Fee'), 'onchange' => 'feesvalidation("shiftform","emergency_fees",0,this.value,"add");', 'readonly'=>false]); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php echo $form->field($model, 'consultation_fees_discount[]')->Input('number',['min'=>'0.00','max'=>'10.00','placeholder' => Yii::t('db','Discounted Consultancy Fee'), 'onchange' => 'maxvalidation("shiftform","consultation_fees_discount",0,"add");','readonly'=>false]); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?php echo $form->field($model, 'emergency_fees_discount[]')->Input('number',['min'=>'0.00','max'=>'10.00','placeholder' => Yii::t('db','Discounted Emergency Fee'),'onchange' => 'maxvalidation("shiftform","emergency_fees_discount",0,"add");','readonly'=>false]); ?>
                                    </div>
                                
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        <?php }?>
                    </div>
                    <div class="col-md-12">
                        <div class="add_siftbox">
                            <a href="javascript:void(0)"><i class="fa fa-plus"></i> Add More Shifts</a>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="bookappoiment-btn" style="margin:0px;">
                            <input type="hidden" id="deletedImages" name="deletedImages" type="text" value=""/>
                            <?php echo Html::submitButton(Yii::t('frontend', 'Update Shift Detail'), ['id'=>'profile_from','class' => 'login-sumbit schedule_form_edit', 'name' => 'profile-button']) ?>
                        </div>
                    </div>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</section>

<div class="login-section ">
    <div class="modal fade model_opacity" id="mapbookedShowModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"  style="display: none;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 >Pick <span>Address</span></h3>
                </div>
                <div class="modal-body" id="mapTokenContent">

                </div>
                <div class="modal-footer text-center">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>



