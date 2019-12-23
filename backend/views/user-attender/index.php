<?php

use common\grid\EnumColumn;
use common\models\User;
use common\models\Groups;
use yii\helpers\Html;
use yii\grid\GridView;
use common\components\DrsPanel;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('backend', 'Attender');
$this->params['breadcrumbs'][] = ['label' => Yii::t('backend', 'Doctors'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$baseUrl=Yii::getAlias('@backendUrl');
$model_name="'User'";
$url="'".$baseUrl."/site/export-import-model'";
$import="'".$baseUrl."/site/import-model'";
$export="'".$baseUrl."/site/export-model'";
$importData="'".$baseUrl."/site/import-model-data'";
$exportData="'".$baseUrl."/site/export-model-data'";
$type=Groups::GROUP_DOCTOR;

$this->registerJs("
    $('select').change(function(){
       $('.form-group button.submit').click();
    });

     $('.user-search form').submit(function(){
        $.pjax({container:'#users-grid',push:false,replace:false,data:$(this).serialize()});
        return false;
    }); 
            
",View::POS_END);

$js="
     $('.file-model-show').on('click', function () {
        id=$(this).attr('id');
        if(id=='import'){
            url=$import;
        }
        else{
            url=$export;
        }
        $.ajax({
              method: 'POST',
              url: url,
             data: { groupid:$type,model:$model_name,type:id}
        })
          .done(function( msg ) { 
            if(msg){
                $('#FileModalContent').html('');
                $('#FileModalContent').html(msg);
                $('#FileModalShow').modal({backdrop: 'static',keyboard: false})

            }
          });

    });


";
$this->registerJs($js,\yii\web\VIEW::POS_END); 
?>
<div class="box">
    <div class="box-body">
        <div class="user-index">

            <?php  echo $this->render('_search', ['model' => $searchModel,'id'=>$id]); ?>

            <p>
                <?php echo Html::a(Yii::t('backend', 'Add new Attender', [
            'modelClass' => 'Doctor',
        ]), ['attender-create?id='.$id], ['class' => 'btn btn-success']) ?>
        </p>

       <?php /* <?php echo Html::a(Yii::t('backend', 'Export ', []), 'javascript:void(0)', ['id'=>'export', 'class' => 'file-model-show btn btn-primary']) ?>
        <?php echo Html::a(Yii::t('backend', 'Import ', []), 'javascript:void(0)', ['id'=>'import' , 'class' => 'file-model-show btn btn-default']) ?>
            </p> */ ?>



            <?php echo GridView::widget([
                'dataProvider' => $dataProvider,
                //'filterModel' => $searchModel,
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
                    /*[
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
                        'enum' => User::statuses(),
                        'filter' => User::statuses()
                    ],*/

                    [
                        'content' => function ($model, $key, $index, $column) {
                            $link = Html::a('<span class="glyphicon glyphicon-eye-open"></span>', ['attender-detail', 'id' => $model->id], ['aria-label'=>'View', 'title'=>'View']);
                            $link1 = Html::a('<span class="glyphicon glyphicon-trash"></span>', ['attender-delete', 'id' => $model->id], ['aria-label'=>'Delete', 'title'=>'Delete','method'=>'post','data' => [
        'confirm' => 'Are you sure you want to delete this item?',
        'method' => 'post',
    ]]);
                            return $link.' '.$link1;
                        }
                    ],
                ],
            ]); ?>

        </div>
    </div>
</div>
