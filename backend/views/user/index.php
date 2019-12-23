<?php

use common\grid\EnumColumn;
use common\models\User;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use common\components\DrsPanel;
use common\models\Groups;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('backend', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box">
    <div class="box-body">
        <div class="user-index">

            <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

            <p>
                <?php echo Html::a(Yii::t('backend', 'Create {modelClass}', [
            'modelClass' => 'User',
        ]), ['create'], ['class' => 'btn btn-success']) ?>
            </p>

            <?php Pjax::begin([ 'id'=>'users-grid','enablePushState'=>false, ]); ?>

            <?php echo GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
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
                    [
                        'class' => EnumColumn::className(),
                        'attribute' => 'status',
                        'enum' => User::statuses(),
                        'filter' => User::statuses()
                    ],
                    [
                        'class' => EnumColumn::className(),
                        'attribute' => 'groupid',
                        'enum' => Groups::statusesAdmin(),
                        'filter' => Groups::statusesAdmin()
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{change-password} {view} {update} {delete}',
                        'buttons' => [
                            'change-password' => function ($url) {
                                return Html::a(
                                    '<i class="fa fa-sign-in" aria-hidden="true"></i>',
                                    $url,
                                    [
                                        'title' => Yii::t('backend', 'Change Password')
                                    ]
                                );
                            },
                        ],
                        'visibleButtons' => [
                            'login' => Yii::$app->user->can('administrator')
                        ]

                    ],
                ],
            ]); ?>

            <?php Pjax::end(); ?>

        </div>
    </div>
</div>