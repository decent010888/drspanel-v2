<?php

namespace backend\controllers;

use common\models\Cities;
use common\models\PopularMeta;
use Yii;
use common\models\SliderImage;
use backend\models\search\SliderImageSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * SliderImageController implements the CRUD actions for SliderImage model.
 */
class SliderImageController extends Controller
{
    /**
     * {@inheritdoc}
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
     * Lists all SliderImage models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SliderImageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SliderImage model.
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
     * Creates a new SliderImage model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate(){
        $model = new SliderImage();
        $model->setScenario('create');
        $cities = Cities::find()->orderBy('id asc')
            ->where(['status'=>1])->all();
        $popularMetaCity = PopularMeta::find()->where(['key' => 'city'])->all();



        if (Yii::$app->request->isPost) {
            $params=Yii::$app->request->post();
            $model->load(Yii::$app->request->post());
            $upload = UploadedFile::getInstance($model, 'image');
            $upload_app = UploadedFile::getInstance($model, 'app_image');

            $uploadDir = Yii::getAlias('@storage/web/source/slider-images/');
            $model->base_path=Yii::getAlias('@frontendUrl');
            $model->file_path='/storage/web/source/slider-images/';
            $model->city = implode(',', $params['SliderImage']['city']);

            if(isset($_FILES['SliderImage']['name']['image'])  && !empty($_FILES['SliderImage']['name']['image']) && !empty($_FILES['SliderImage']['tmp_name'])) {
                $image_name=time().$model->pages.'.'.$upload->extension;
                $model->image=$image_name;
            }
            if(isset($_FILES['SliderImage']['name']['app_image'])  && !empty($_FILES['SliderImage']['name']['app_image']) && !empty($_FILES['SliderImage']['tmp_name'])) {
                $image_name1=time().$model->pages.'.'.$upload_app->extension;
                $model->app_image=$image_name1;
            }

            if ($model->validate()) {

                $model->sub_title=($model->sub_title)?$model->sub_title:$model->title;
                if($upload) {
                    $model->image=$image_name;
                    $upload->saveAs($uploadDir .$image_name );
                }
                if($upload_app) {
                    $model->app_image=$image_name1;
                    $upload_app->saveAs($uploadDir .$image_name1 );
                }
                $model->save();
                return $this->redirect(['index']);
            }
        }
      /*  if ($model->load(Yii::$app->request->post()) && $model->save()) {
            
        } */

        return $this->render('create', [
            'model' => $model,'popularCity'=>$popularMetaCity,'cities'=>$cities
        ]);
    }

    /**
     * Updates an existing SliderImage model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id){
        $model = $this->findModel($id);
        $image_name=($model->image)?$model->image:'';
        $image_name1=($model->app_image)?$model->app_image:'';

        $cities = Cities::find()->orderBy('id asc')
            ->where(['status'=>1])->all();
        $popularMetaCity = PopularMeta::find()->where(['key' => 'city'])->all();
        $city_model = $model->city;
        if(!empty($city_model)){
            $model->city = explode(',', $city_model);
        }

        if (Yii::$app->request->post()){
            $params=Yii::$app->request->post();
            $upload = UploadedFile::getInstance($model, 'image');
            $upload_app = UploadedFile::getInstance($model, 'app_image');
            $uploadDir = Yii::getAlias('@storage/web/source/slider-images/');
            $model->base_path=Yii::getAlias('@frontendUrl');
            $model->file_path='/storage/web/source/slider-images/';

            if(isset($_FILES['SliderImage']['name']['image']) && !empty($_FILES['SliderImage']['name']['image']) && !empty($_FILES['SliderImage']['tmp_name'])) {
                $image_name=time().$model->pages.'.'.$upload->extension;
                $model->image=$image_name;
            }

            if(isset($_FILES['SliderImage']['name']['app_image'])  && !empty($_FILES['SliderImage']['name']['app_image']) && !empty($_FILES['SliderImage']['tmp_name'])) {
                $image_name1=time().$model->pages.'.'.$upload_app->extension;
                $model->app_image=$image_name1;
            }
            $model->load(Yii::$app->request->post());
            $model->image=$image_name;
            $model->app_image=$image_name1;
            $model->city = implode(',', $params['SliderImage']['city']);
            if($upload) {
                $model->image=$image_name;
                $upload->saveAs($uploadDir .$image_name );
            }
            if($upload_app) {
                $model->app_image=$image_name1;
                $upload_app->saveAs($uploadDir .$image_name1 );
            }
            $model->save();
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,'popularCity'=>$popularMetaCity,'cities'=>$cities
            ]);
    }

    /**
     * Deletes an existing SliderImage model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id){
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the SliderImage model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SliderImage the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SliderImage::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
