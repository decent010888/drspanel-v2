<?php
/**
 * @author Eugene Terentev <eugene@terentev.net>
 * @var $model common\models\TimelineEvent
 */

use common\models\User;
use common\models\Groups;

$user_id=$model->data['user_id'];
$user=User::findOne($user_id);
?>
<div class="timeline-item">
    <span class="time">
        <i class="fa fa-clock-o"></i>
        <?php echo Yii::$app->formatter->asRelativeTime($model->created_at) ?>
    </span>

    <h3 class="timeline-header">
        <?php echo Yii::t('backend', 'You have new user!') ?>
    </h3>

    <div class="timeline-body">
        <?php echo Yii::t('backend', 'New user ({identity}) was registered at {created_at}', [
            'identity' => $model->data['public_identity'],
            'created_at' => Yii::$app->formatter->asDatetime($model->data['created_at'])
        ]) ?>
    </div>

    <div class="timeline-footer">

        <?php
        if(!empty($user)){
            if($user->groupid == Groups::GROUP_DOCTOR){
                echo \yii\helpers\Html::a(
                    Yii::t('backend', 'View doctor'),
                    ['/doctor/view', 'id' => $model->data['user_id']],
                    ['class' => 'btn btn-success btn-sm']
                );
            }
            elseif($user->groupid == Groups::GROUP_PATIENT){
                echo \yii\helpers\Html::a(
                    Yii::t('backend', 'View patient'),
                    ['/patient/view', 'id' => $model->data['user_id']],
                    ['class' => 'btn btn-success btn-sm']
                );
            }
            elseif($user->groupid == Groups::GROUP_HOSPITAL){
                echo \yii\helpers\Html::a(
                    Yii::t('backend', 'View hospital'),
                    ['/hospital/view', 'id' => $model->data['user_id']],
                    ['class' => 'btn btn-success btn-sm']
                );
            }
        }


        ?>


    </div>
</div>