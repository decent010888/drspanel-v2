<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\components\DrsPanel;
use kartik\select2\Select2;

$this->title = Yii::t('frontend', 'Hospital::Address Update', [
    'modelAddressClass' => 'Doctor',
]);

$citiesList=[];$area_list=[];

$statesList=ArrayHelper::map(DrsPanel::getStateList(),'name','name');

if($userAddress->state) {
    $citiesList = ArrayHelper::map(DrsPanel::getCitiesList($userAddress->state, 'name'), 'id', 'name');
}
if($userAddress->city_id){
    $area_list=ArrayHelper::map(DrsPanel::getCityAreasList($userAddress->city_id),'name','name');
}

$base_url= Yii::getAlias('@frontendUrl');
$base_urls= "'".$base_url."'";
$statesList=ArrayHelper::map(DrsPanel::getStateList(),'name','name');

$frontend=Yii::getAlias('@frontendUrl');
$cityUrl="'".$frontend."/hospital/city-list'";
$cityAreaUrl="'".$frontend."/hospital/city-area-list'";
$mapAreaUrl="'".$frontend."/hospital/map-area-list'";


$js="
          $('#estate_list').on('change', function () {
            if($(this).val())
            {
             $.ajax({
              method:'POST',
              url: $cityUrl,
              data: {state_id:$(this).val()}
            })
            .done(function( msg ) { 

              $('#ecity_list').html('');
              $('#ecity_list').html(msg);

            });
          }
        }); 
        
        $('#ecity_list').on('change', function () {
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
                    area:$('#useraddress-area').val()}
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

        ";
$this->registerJs($js,\yii\web\VIEW::POS_END);
?>
<style>
 /*   span.select2-container{
        z-index:9999999;
    }*/
</style>
    <div class="inner-banner"> </div>
    <section class="mid-content-part address_page">
        <div class="signup-part">
            <div class="container">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="appointment_part">
                            <div class="hosptionhos-profileedit">
                                <h2 class="addnew2">Address</h2>

                                <?php $form = ActiveForm::begin(['id' => 'profile-form','options' => ['enctype'=> 'multipart/form-data','action' => 'userProfile']]); ?>
                                <?php
                                echo $form->field($userAddress,'id')->hiddenInput()->label(false);
                                echo $form->field($userAddress,'user_id')->hiddenInput()->label(false);
                                echo $form->field($userAddress,'type')->hiddenInput()->label(false);
                                ?>
                                <div class="row discri_edithost">
                                    <p class="col-sm-3"> Hospital Name :</p>
                                    <span class="col-sm-7 marginbottom_edit"> <?php echo $form->field($userAddress, 'name')->textInput(['class'=>'input_field form-control','placeholder' => 'Hospital Name','readonly'=>true])->label(false); ?></span>
                                </div>
                                <div class="row discri_edithost" id="statelist_update">
                                    <p class="col-sm-3">State:</p>
                                    <span class="col-sm-7 marginbottom_edit">
                                        <?php echo  $form->field($userAddress, 'state')->widget(Select2::classname(),
                                            [
                                                'data' => $statesList,
                                                'size' => Select2::SMALL,
                                                'options' => ['placeholder' => 'Select State ...', 'multiple' => false,'id'=>'estate_list'],
                                                'pluginOptions' => [
                                                    'tags' => false,
                                                    'allowClear' => true,
                                                    'multiple' => false,
                                                ],
                                            ])->label(false); ?>

                                    </span>
                                </div>

                                <div class="row discri_edithost" id="citylist_update">
                                    <p class="col-sm-3">City:</p>
                                    <span class="col-sm-7 marginbottom_edit">
                                        <?php echo  $form->field($userAddress, 'city_id')->widget(Select2::classname(),
                                            [
                                                'data' => $citiesList,
                                                'size' => Select2::SMALL,
                                                'options' => ['placeholder' => 'Select City ...', 'multiple' => false,'id'=>'ecity_list'],
                                                'pluginOptions' => [
                                                    'tags' => false,
                                                    'allowClear' => true,
                                                    'multiple' => false,
                                                ],
                                            ])->label(false); ?>

                                    </span>
                                </div>

                                <div class="row discri_edithost">
                                    <p class="col-sm-3">Address:</p>
                                    <span class="col-sm-7 marginbottom_edit"> <?= $form->field($userAddress, 'address')->textInput(['class'=>'input_field','placeholder' => 'Address'])->label(false)?> </span>
                                </div>

                                <div class="row discri_edithost" id="arealist_update">
                                    <p class="col-sm-3">Area:</p>
                                    <span class="col-sm-7 marginbottom_edit">
                                        <?php echo  $form->field($userAddress, 'area')->widget(Select2::classname(),
                                            [
                                                'data' => $area_list,
                                                'size' => Select2::SMALL,
                                                'options' => ['placeholder' => 'Select Area/Colony ...', 'multiple' => false],
                                                'pluginOptions' => [
                                                    'tags' => true,
                                                    'allowClear' => true,
                                                    'multiple' => false,
                                                ],
                                            ])->label(false); ?>

                                    </span>
                                </div>

                                <div class="row discri_edithost">
                                    <p class="col-sm-3">Phone:</p>
                                    <span class="col-sm-7 marginbottom_edit"> <?= $form->field($userAddress, 'phone')->textInput(['class'=>'input_field','placeholder' => 'Phone','maxlength'=> 10])->label(false)?> </span>
                                </div>

                                <div class="row discri_edithost">
                                    <p class="col-sm-3">Landline:</p>
                                    <span class="col-sm-7 marginbottom_edit"> <?= $form->field($userAddress, 'landline')->textInput(['class'=>'input_field','placeholder' => 'Landline','maxlength'=> 12])->label(false)?> </span>
                                </div>

                                <div class="row discri_edithost map_attachment">
                <span class="maplocation maplocation_attachment">
                    <i aria-hidden="true" class="fa fa-map-marker"></i> Set Location
                </span>
                                    <span class="pin_address"></span>
                                    <?= $form->field($userAddress, 'lat')->hiddenInput()->label(false) ?>
                                    <?= $form->field($userAddress, 'lng')->hiddenInput()->label(false) ?>
                                </div>

                                <div class="row" id="data_image_reload">
                                    <?php echo $this->render('_address_images',['form'=>$form,'addressImages'=>$addressImages,'userAdddressImages'=>$userAdddressImages])?>
                                </div>




                                <div class="bookappoiment-btn" style="margin:0px;">
                                    <input type="hidden" id="deletedImages" name="deletedImages" type="text" value=""/>
                                    <?php echo Html::submitButton(Yii::t('frontend', 'Save'), ['id'=>'profile_from','class' => 'login-sumbit', 'name' => 'profile-button']) ?>
                                </div>
                                <?php ActiveForm::end(); ?>
                            </div>
                        </div>
                    </div>
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
