<?php

use common\grid\EnumColumn;
use common\models\User;
use yii\helpers\Html;
use yii\grid\GridView;
use common\components\DrsPanel;
use yii\web\View;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('backend', 'Hospitals');
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs("
    $('select#hospitalsearch-admin_status').change(function(){
       $('.form-group button.submit').click();
    });

     $('.user-search form').submit(function(){
        $.pjax({container:'#users-grid',push:false,replace:false,data:$(this).serialize()});
        return false;
    }); 
            
",View::POS_END);

$this->registerJs("      
    
    $(document).on('change','.status_update',function(){
        $('#users-grid').css('opacity','0.7');
        var id= $(this).attr('data-id');
        var val= $(this).val();
        $.ajax({
            url: 'update-livemodal',
            dataType:   'html',
            method:     'POST',
            data: { id: id,val:val},
            success: function(response){                             
                $.pjax({container:'#users-grid',push:false,replace:false,data:$(this).serialize()});
                $('#users-grid').css('opacity','1');   
                return false;
            }
        });
    });
    
    function modal_edit_actions(id){   
        $.ajax({
            url: 'get-edit-livemodal',
            dataType:   'html',
            method:     'POST',
            data: { id: id},
            success: function(response){                
                $('#editlivestatusmodal #open_model_live').empty();
                $('#editlivestatusmodal #open_model_live').append(response);
                $('#editlivestatusmodal').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });
                setTimeout(function(){
                    $('body').addClass('modal-open');
                }, 400);
            }
        });
    }
    
    function modal_plan_actions(id){   
        $.ajax({
            url: 'get-plan-livemodal',
            dataType:   'html',
            method:     'POST',
            data: { id: id},
            success: function(response){                
                $('#editliveplanmodal #open_model_live').empty();
                $('#editliveplanmodal #open_model_live').append(response);
                $('#editliveplanmodal').modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });
                setTimeout(function(){
                    $('body').addClass('modal-open');
                }, 400);
            }
        });
    }
    
    
",View::POS_END);
?>
<div class="box">
    <div class="box-body">
        <div class="user-index">

            <?php  echo $this->render('_search', ['model' => $searchModel]); ?>

            <p>
                <?php echo Html::a(Yii::t('backend', 'Add new {modelClass}', [
            'modelClass' => 'Hospital',
        ]), ['create'], ['class' => 'btn btn-success']) ?>
            </p>

            <?php Pjax::begin([ 'id'=>'users-grid','enablePushState'=>false, ]); ?>

            <?php echo GridView::widget([
                'dataProvider' => $dataProvider,
               // 'filterModel' => $searchModel,
                'options' => [
                    'class' => 'grid-view table-responsive'
                ],
                'columns' => [
                    'id',
                    [
                            'attribute'=>'name',
                        'value'=>function($data){
                                    return DrsPanel::getUserName($data->id);
                        }
                    ],
                    'email:email',
                    'phone',
                    [
                        'class' => EnumColumn::className(),
                        'attribute' => 'status',
                        'label'=>'Login Status',
                        'enum' => User::statuses(),
                        'filter' => User::statuses()
                    ],

                    [
                        'class' => EnumColumn::className(),
                        'attribute' => 'admin_status',
                        'label'=>'Profile Status',
                        'enum' => User::admin_statuses(),
                        'filter' => User::admin_statuses(),
                        'format'=>'raw',
                        'value' => function ($data) {
                            $current_user_roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
                            $adminStatus = User::admin_statuses();
                            if(isset($current_user_roles['manager'])){
                                $html = $data->admin_status;
                            }else{
                            $html = '';
                            $html .= '<select class="form-control status_update" data-id=' . $data->id . '>';
                            foreach ($adminStatus as $adkey => $adminst) {
                                if ($adkey == $data->admin_status) {
                                    $html .= '<option value=' . $adkey . ' selected>' . $adminst . '</option>';
                                } else {
                                    $html .= '<option value=' . $adkey . '>' . $adminst . '</option>';
                                }
                            }
                            $html .= '</select>';
                            }
                            return $html;
                        },
                    ],

                   /* [
                        'class' => EnumColumn::className(),
                        'attribute' => 'admin_status',
                        'label'=>'Profile Status',
                        'enum' => User::admin_statuses(),
                        'filter' => User::admin_statuses(),
                        'content' => function ($data) {
                            $adminStatus =  User::admin_statuses();

                            $link = Html::a($adminStatus[$data->admin_status], 'javascript:void(0)', ['onclick'=>"return modal_edit_actions($data->id);",'aria-label'=>'Edit', 'title'=>'Edit']);
                            return $link;
                        }
                    ],*/
                    [
                        'class' => EnumColumn::className(),
                        'attribute' => 'user_plan',
                        'label'=>'Profile Plan',
                        'enum' => User::plan_statuses(),
                        'filter' => User::plan_statuses(),
                        'content' => function ($data) {
                            $adminStatus =  User::plan_statuses();

                            $link = Html::a($adminStatus[$data->user_plan], 'javascript:void(0)', ['onclick'=>"return modal_plan_actions($data->id);",'aria-label'=>'Edit', 'title'=>'Edit']);
                            return $link;
                        }
                    ],
                    [
                        'attribute' => 'created_at',
                        'filter' => \yii\jui\DatePicker::widget(['dateFormat' => 'yyyy-MM-dd','name'=>'HospitalSearch[created_at]']),
                        'value' => function($data) {
                            if($data->created_at !==NULL){
                                return Yii::$app->formatter->asDate($data->created_at, 'php:Y M d H:i:s');
                            }else{
                                return $data->created_at;
                            }
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view}',  // the default buttons + your custom button

                    ],

                ],
            ]); ?>

            <?php Pjax::end(); ?>

        </div>
    </div>
</div>

<div class="modal fade" id="editlivestatusmodal" tabindex="-1" role="dialog" aria-labelledby="addproduct" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalContact">Update Profile/Live Status</h4>
            </div>
            <div class="modal-body" id="open_model_live">


            </div>
        </div><!-- /.modal-content -->
    </div>
</div>

<div class="modal fade" id="editliveplanmodal" tabindex="-1" role="dialog" aria-labelledby="addproduct" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalContact">Update Plan</h4>
            </div>
            <div class="modal-body" id="open_model_live">


            </div>
        </div><!-- /.modal-content -->
    </div>
</div>

