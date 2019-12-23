<?php

namespace backend\controllers;

use backend\models\search\MetaValuesSearch;
use common\models\MetaKeys;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\MetaValues;

class ServicesController extends Controller {

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all MetaValue models.
     * @return mixed
     */
    public function actionIndex() {
        $metakey = MetaKeys::findOne(['key' => 'Services']);
        if (!empty($metakey)) {
            $searchModel = new MetaValuesSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $metakey->id);

            return $this->render('index', [
                        'searchModel' => $searchModel,
                        'dataProvider' => $dataProvider,
            ]);
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Creates a new MetaValue model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new MetaValues();
        $metakey = MetaKeys::findOne(['key' => 'Services']);
        if (!empty($metakey)) {
            $model->key = $metakey->id;
            if ($model->load(Yii::$app->request->post())) {
                $model->label = $model->label;
                if ($model->save()) {
                    return $this->redirect(['index']);
                } else {
                    return $this->render('create', [
                                'model' => $model,
                    ]);
                }
            } else {
                return $this->render('create', [
                            'model' => $model,
                ]);
            }
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Updates an existing MetaValue model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = MetaValues::findOne($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        } else {
            return $this->render('update', [
                        'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing MetaValues model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id) {
        //$this->findModel($id)->delete();
        $model = $this->findModel($id);
        $model->is_deleted = '1';
        $model->save();

        return $this->redirect(['index']);
    }

    /**
     * Finds the MetaValues model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return MetaValues the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = MetaValues::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
