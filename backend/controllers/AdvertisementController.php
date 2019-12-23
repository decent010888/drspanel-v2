<?php

namespace backend\controllers;

use Yii;
use common\models\Advertisement;
use backend\models\search\AdvertisementSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\helpers\Url;

/**
 * AdvertisementController implements the CRUD actions for Advertisement model.
 */
class AdvertisementController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
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
     * Lists all Advertisement models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AdvertisementSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Advertisement model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Advertisement model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Advertisement();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if(isset($_FILES['Advertisement']['name']['image'])  && !empty($_FILES['Advertisement']['name']['image']) && !empty($_FILES['Advertisement']['tmp_name'])) {
                $upload = UploadedFile::getInstance($model, 'image');

                $path = Url::to('@frontendUrl');
                $dir = Url::to('@frontend');
                $dir=$dir.'/web/advertisement/';
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                $uploadDir = Yii::getAlias('@frontend/web/advertisement/');
                $image_name=time().'image.'.$upload->extension;
                $upload->saveAs($uploadDir .$image_name );

                $model->image_path = '/advertisement/' . $image_name;
                $model->image_base_url = $path;
                if ($model->save()) {

                }
            }
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'types' => Advertisement::types()
        ]);
    }

    /**
     * Updates an existing Advertisement model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->image=$model->image_base_url.$model->image_path;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if(isset($_FILES['Advertisement']['name']['image'])  && !empty($_FILES['Advertisement']['name']['image']) && !empty($_FILES['Advertisement']['tmp_name'])) {
                $upload = UploadedFile::getInstance($model, 'image');

                $path = Url::to('@frontendUrl');
                $dir = Url::to('@frontend');
                $dir=$dir.'/web/advertisement/';
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }

                $uploadDir = Yii::getAlias('@frontend/web/advertisement/');
                $image_name=time().'image.'.$upload->extension;
                $upload->saveAs($uploadDir .$image_name );

                $model->image_path = '/advertisement/' . $image_name;
                $model->image_base_url = $path;
                if ($model->save()) {

                }
            }
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'types' => Advertisement::types()
        ]);
    }

    /**
     * Deletes an existing Advertisement model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Advertisement model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Advertisement the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Advertisement::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
