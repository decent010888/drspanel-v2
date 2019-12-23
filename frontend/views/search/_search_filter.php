<?php 
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\date\DatePicker;
use common\models\UserProfile;
use common\models\MetaValues;
use common\components\DrsPanel;
use common\models\Areas;

$js = "function myFunction(inputid,ulid) {
    var input, filter, ul, li, a, i, txtValue;
    input = document.getElementById(inputid);
    filter = input.value.toUpperCase();
    ul = document.getElementById(ulid);
    li = ul.getElementsByTagName('li');
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName('label')[0];
        txtValue = a.textContent || a.innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = '';
        } else {
            li[i].style.display = 'none';
        }
    }
}";
$this->registerJs($js,\yii\web\VIEW::POS_END);

if(isset($string['type'])){
    $filterarray=DrsPanel::getFilterArray(Yii::$app->controller->action->id,'',0,$string['type']);
}
else{
    $filterarray=DrsPanel::getFilterArray(Yii::$app->controller->action->id);

}
$labelarray=array();
foreach($filterarray as $keyf => $filter){
    $labelarray[$keyf]=$filter['label'];
}

if(Yii::$app->controller->action->id == 'doctor'){
    $sortarray=array('price_highttolow'=>'Price, High to Low','price_lowtohigh'=>'Price, Low to High','rating_highttolow'=>'Rating, High to Low','rating_lowtohigh'=>'Rating, Low to High',);
}
else{
    $sortarray=array('rating_highttolow'=>'Rating, High to Low','rating_lowtohigh'=>'Rating, Low to High',);
}




$this->title = Yii::t('frontend','DrsPanel::Doctor List');

$model = new UserProfile();


$checked_meta =array();


?>
  <div class="search-boxicon search-part appointment_part patient_profile">
 
  <button data-toggle="modal" data-target="#myfilter" class="filter-btn filter_btn_left"><i class="fa fa-filter"></i> Filter</button>
  <div id="myfilter" class="modal fade model_opacity filter-popup" role="dialog">
    <div class="modal-dialog"> 
      <div class="modal-content">
       <?php $form = ActiveForm::begin(); ?>
        <div class="modal-header">
          <h3>Filter</h3>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body clearfix">
            <div class="filter-part">
              <div class="resp-tabs-container hor_1">
                <div id="ChildVerticalTab_1">
                  <ul class="resp-tabs-list ver_1">
                      <?php foreach($filterarray as $keyf => $filter){ ?>
                          <li><?php echo $filter['label']; ?></li>
                      <?php } ?>
                        <li>Sort</li>
                  </ul>
                  <div class="resp-tabs-container ver_1">
                      <?php foreach($filterarray as $keyf => $filter){?>
                          <div>
                              <?php
                              if(!empty($string)){
                                  if(isset($string[$filter['type']])){
                                      $checked_meta=$string[$filter['type']];
                                  }
                                  else{
                                      $checked_meta=array();
                                  }
                              }
                              ?>
                              <?php if($filter['select_type'] == 'multiple') { ?>
                                  <div class="search-part">
                                      <div class="row">
                                          <div class="col-sm-12">
                                              <div class="search-inputbar">
                                                  <input type="text" class="form-control" placeholder="Search <?php echo $filter['label']; ?>" onkeyup="myFunction('<?php echo $filter['type']; ?>_input','filter_<?php echo $filter['type']; ?>')" id="<?php echo $filter['type']; ?>_input"  >
                                                  <div class="search-icon"> <i class="fa fa-search"></i> </div>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  <ul class="filter-menu" id="filter_<?php echo $filter['type']; ?>">
                                      <?php $lists=$filter['list'];
                                      if(!empty($lists)){
                                          foreach ($lists as $h_key=>$list){
                                              if(is_array($checked_meta) && in_array($list['value'],$checked_meta)){
                                                  $checked='checked';
                                              } else{
                                                  $checked='';
                                              } ?>
                                              <li>
                                                  <div class="form-check form-check-inline">
                                                      <input type="checkbox" class="form-check-input filter_<?php echo $filter['type']; ?>" value="<?php echo $list['value'] ?>" id="<?php echo $filter['type']; ?>_<?php echo $h_key?>" name="UserProfile[<?php echo $filter['type']; ?>][]" <?php echo $checked; ?>>
                                                      <label class="form-check-label" for="<?php echo $filter['type']; ?>_<?php echo $h_key?>"><?php echo $list['label'] ?></label>
                                                  </div>
                                              </li>
                                          <?php }
                                      } ?>
                                  </ul>
                              <?php }
                              else{ ?>
                                  <ul class="filter-menu no-scroll">
                                      <?php $lists=$filter['list'];
                                      if(!empty($lists)){
                                          foreach ($lists as $h_key=>$list){
                                              if(is_array($checked_meta) && in_array($list['value'],$checked_meta)){
                                                  $checked='checked';
                                              } else{
                                                  $checked='';
                                              } ?>
                                              <li>
                                                  <div class="form-check form-check-inline">
                                                      <input type="radio" class="form-check-input filter_<?php echo $filter['type']; ?>" value="<?php echo $list['value'] ?>" id="<?php echo $filter['type']; ?>_<?php echo $h_key?>" name="UserProfile[<?php echo $filter['type']; ?>]" <?php echo $checked; ?>>
                                                      <label class="form-check-label" for="<?php echo $filter['type']; ?>_<?php echo $h_key?>"><?php echo $list['label'] ?></label>
                                                  </div>
                                              </li>
                                          <?php }
                                      } ?>
                                  </ul>
                              <?php }?>
                          </div>
                      <?php } ?>

                      <div>
                          <?php
                          $checked_meta=array();
                          if(!empty($string)){
                              if(isset($string['sort'])){
                                  $checked_meta=$string['sort'];
                              }
                              else{
                                  $checked_meta=array();
                              }
                          }
                          ?>
                          <ul class="filter-menu no-scroll">
                              <?php
                              if(!empty($sortarray)){
                                  foreach ($sortarray as $h_key=>$list){
                                      if(is_array($checked_meta) && in_array($h_key,$checked_meta)){
                                          $checked='checked';
                                      } else{
                                          $checked='';
                                      } ?>
                                      <li>
                                          <div class="form-check form-check-inline">
                                              <input type="radio" class="form-check-input filter_sort" value="<?php echo $h_key ?>" id="sort_<?php echo $h_key?>" name="UserProfile[sort]" <?php echo $checked; ?>>
                                              <label class="form-check-label" for="sort_<?php echo $h_key?>"><?php echo $list ?></label>
                                          </div>
                                      </li>
                                  <?php }
                              } ?>
                          </ul>

                      </div>






                  </div>

                </div>
              </div>
            </div>
        </div> 
        <div class="modal-footer">
            <div class="pull-left">
            <button type="reset" class="btn filter-btn filter_btn_reset">Reset</button>
          </div>
          <div class="pull-right">
            <a href="javascript:void(0)" class="btn filter-btn" id="filter_apply_btn">Apply</a>
          </div>
        </div>
          <?php ActiveForm::end(); ?>
      </div>
    </div>
  </div>
  </div>


<div id="form_subcat_div" class="col-sm-12">
    <div class="seprator_box">
        
    </div>
</div>
