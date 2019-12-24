<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\UserAppointment;
use common\models\Transaction;
use backend\models\search\RefundSearch;

/**
 * RefundController implements the CRUD actions for Page model.
 */
class RefundController extends Controller {

    public function actionIndex() {
        $searchModel = new RefundSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

}
