<?php

namespace backend\controllers;

use Yii;
use backend\models\search\TimelineEventSearch;
use yii\web\Controller;

/**
 * Application timeline controller
 */
class TimelineEventController extends Controller {

    public $layout = 'common';

    /**
     * Lists all TimelineEvent models.
     * @return mixed
     */
    public function actionIndex() {
        $totalDoctor = \common\models\User::find()->where(['groupid' => 4,'status' => 2])->count();
        $totalHospital = \common\models\User::find()->where(['groupid' => 5,'status' => 2])->count();
        $totalPatient = \common\models\User::find()->where(['groupid' => 3,'status' => 2])->count();
        $getAllAppoint = \common\models\UserAppointmentLogs::getAppointment();
        foreach ($getAllAppoint as $appData) {
            $dateObj = \DateTime::createFromFormat('!m', $appData['month']);
            $monthName[] = "'" . $dateObj->format('F') . "'";
            $monthApp[] = "'" . $appData['app'] . "'";
        }
        $monthDataArr = implode(',', $monthName);
        $mothApp = implode(',', $monthApp);

        return $this->render('index', [
                    'mothApp' => $mothApp,
                    'monthDataArr' => $monthDataArr,
                    'totalDoctor' => $totalDoctor,
                    'totalHospital' => $totalHospital,
                    'totalPatient' => $totalPatient,
        ]);
    }

}
