<?php
namespace backend\controllers;

use backend\models\PopularCityDataForm;
use common\models\Cities;
use Yii;
use common\models\MetaKeys;
use common\models\MetaValues;
use common\models\PopularMeta;
use backend\models\search\MetaValuesSearch;
use backend\models\PopularForm;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\components\DrsPanel;


/**
 * PopularController implements the CRUD actions for Page model.
 */
class PopularController extends Controller
{
    public function behaviors()
    {

        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Page models.
     * @return mixed
     */
    public function actionIndex(){


        $cities = Cities::find()->orderBy('id asc')
            ->where(['status'=>1])->all();


        $model = new PopularForm();

        $city_model = PopularMeta::find()->where(['key' => 'city'])->one();
        if(!empty($city_model)){
            $model->city = $city_model->value;
        }

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $postData = $post['PopularForm'];
            if(isset($postData['city'])) {
                $city_model = PopularMeta::find()->where(['key' => 'city','city_id'=> 0])->one();
                if(!empty($city_model))
                {
                    $city_model->delete();
                }
                if(!empty($postData['city']))
                {
                    $city_model = new PopularMeta();
                    $cities = $postData['city'];
                    $city_model->city_id=0;
                    $city_model->value = $cities;
                    $city_model->key = 'city';
                    $city_model->save();
                }
            }
   
        Yii::$app->session->setFlash('alert', [
            'options'=>['class'=>'alert-success'],
            'body'=>Yii::t('backend', 'City updated!')
            ]);
        return $this->redirect(['index']);
        } 

        else {
            return $this->render('index',['cities'=>$cities,'model' => $model]);
        }
  
    }

    /**
     * Updates an existing Page model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id){
        $cities_check = Cities::find()->orderBy('id asc')
            ->where(['status'=>1,'id'=>$id])->one();
        if(!empty($cities_check)){
            $specialities = MetaValues::find()->orderBy('id asc')
                ->where(['key'=>5])->all();

            $treatments = MetaValues::find()->orderBy('id asc')
                ->where(['key'=>9])->all();

            $hospital_data = DrsPanel::getallhospital();

            $model =new PopularCityDataForm();
            $popularMetaHospital = PopularMeta::find()->where(['key' => 'hospital','city_id'=>$id])->all();
            $popularMetaSpeciality = PopularMeta::find()->where(['key' => 'speciality','city_id'=>$id])->all();
            $popularMetaTreatment = PopularMeta::find()->where(['key' => 'treatment','city_id'=>$id])->all();


            if (Yii::$app->request->post()) {
                $post = Yii::$app->request->post();
                $postData = $post['PopularCityDataForm'];

                $city_id=$postData['city'];
                $city_name=DrsPanel::getCityName($city_id);

                if(isset($postData['hospital'])) {
                    $hospital_model = PopularMeta::find()->where(['key' => 'hospital','city_id'=>$city_id])->one();
                    if(!empty($hospital_model)) {
                        $hospital_model->delete();
                    }
                    if(!empty($postData['hospital'])){
                        $hospital_model = new PopularMeta();
                        $hospital = implode(',', $postData['hospital']);
                        $hospital_model->city_id=$city_id;
                        $hospital_model->city=$city_name;
                        $hospital_model->value = $hospital;
                        $hospital_model->key = 'hospital';
                        $hospital_model->save();
                    }

                }
                if(isset($postData['speciality'])) {
                    $speciality_model = PopularMeta::find()->where(['key' => 'speciality','city_id'=>$city_id])->one();
                    if(!empty($speciality_model))
                    {
                        $speciality_model->delete();
                    }
                    if(!empty($postData['speciality'])){
                        $speciality_model = new PopularMeta();
                        $speciality = implode(',', $postData['speciality']);
                        $speciality_model->city_id=$city_id;
                        $speciality_model->city=$city_name;
                        $speciality_model->value = $speciality;
                        $speciality_model->key = 'speciality';
                        $speciality_model->save();
                    }
                }
                if(isset($postData['treatment'])) {
                    $treatment_model = PopularMeta::find()->where(['key' => 'treatment','city_id'=>$city_id])->one();
                    if(!empty($treatment_model))
                    {
                        $treatment_model->delete();
                    }
                    if(!empty($postData['treatment']))
                    {
                        $treatment_model = new PopularMeta();
                        $treatment = implode(',', $postData['treatment']);
                        $treatment_model->city_id=$city_id;
                        $treatment_model->city=$city_name;
                        $treatment_model->value = $treatment;
                        $treatment_model->key = 'treatment';
                        $treatment_model->save();
                    }
                }

                Yii::$app->session->setFlash('alert', [
                    'options'=>['class'=>'alert-success'],
                    'body'=>Yii::t('backend', 'Data updated!')
                ]);
                return $this->redirect(Yii::$app->request->referrer);

            }

            return $this->render('update', [
                'model' => $model,'specialities' => $specialities, 'treatments' => $treatments ,'cities'=>$cities_check,'hospitalData' => $hospital_data,'popularHospital' => $popularMetaHospital,'popularSpeciality' => $popularMetaSpeciality, 'popularTreatment' => $popularMetaTreatment
            ]);
        }
        else{
            Yii::$app->session->setFlash('alert', [
                'options'=>['class'=>'alert-error'],
                'body'=>Yii::t('backend', 'Error!')
            ]);
            return $this->redirect(['index']);
        }
    }
}
