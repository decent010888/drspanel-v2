<?php

namespace backend\controllers;

use backend\models\search\MetaValuesSearch;
use common\components\DrsPanel;
use common\models\Category;
use common\models\MetaKeys;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use common\models\MetaValues;
use yii\web\UploadedFile;


class SpecialityController extends Controller
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
     * Lists all MetaValue models.
     * @return mixed
     */
    public function actionIndex()
    {
        $metakey=MetaKeys::findOne(['key'=>'speciality']);
        if(!empty($metakey)){
            $searchModel = new MetaValuesSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$metakey->id);

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
    public function actionCreate(){
        $model = new MetaValues();
        $metakey=MetaKeys::findOne(['key'=>'speciality']);
        if(!empty($metakey)){
            $model->key=$metakey->id;
            if ($model->load(Yii::$app->request->post())){
                if($model->key == 9 || $model->key == 5){
                    $model->slug=DrsPanel::metavalues_slugify($model->label);
                }
                if($model->save()) {

                    if($_FILES) {
                        $model->base_path=Yii::getAlias('@frontendUrl');
                        $model->file_path='/storage/web/source/'.strtolower(MetaValues::getKeyName($model->key)).'/';
                        $image = UploadedFile::getInstance($model, 'image');
                        if($image){
                            $uploadDir = Yii::getAlias('@storage/web/source/'.strtolower(MetaValues::getKeyName($model->key)).'/');
                            $image_name=time().rand().'.'.$image->extension;
                            $image->saveAs($uploadDir .$image_name );
                            $model->image=$image_name;

                            $imagine = new Image();
                            $imagine->thumbnail($uploadPath . $newimage, 300, 168)
                                ->save(Yii::getAlias($uploadPath . $thumbImage), ['quality' => 70]);

                        }

                        $icon = UploadedFile::getInstance($model, 'icon');
                        if($icon){
                            $uploadDir = Yii::getAlias('@storage/web/source/'.strtolower(MetaValues::getKeyName($model->key)).'/');
                            $image_name=time().rand().'_icon.'.$icon->extension;
                            $icon->saveAs($uploadDir .$image_name );
                            $model->icon=$image_name;
                        }
                        $model->save();
                    }

                    return $this->redirect(['index']);
                }
                else{
                    return $this->render('create', [
                        'model' => $model,
                    ]);
                }
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }
        else{
            throw new NotFoundHttpException('The requested page does not exist.');
        }


    }

    /**
     * Updates an existing MetaValue model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id){
        $model = MetaValues::findOne($id);
        $metakeys=MetaKeys::find()->select(['id','label'])->asArray()->all();
        if($_FILES) { 
            $model->base_path=Yii::getAlias('@frontendUrl');
            $model->file_path='/storage/web/source/'.strtolower(MetaValues::getKeyName($model->key)).'/';
            $image = UploadedFile::getInstance($model, 'image');
            if($image){
                $uploadDir = Yii::getAlias('@storage/web/source/'.strtolower(MetaValues::getKeyName($model->key)).'/');  
                $image_name=time().rand().'.'.$image->extension;
                $image->saveAs($uploadDir .$image_name );
                $model->image=$image_name;
            }

            $icon = UploadedFile::getInstance($model, 'icon');
            if($icon){
                $uploadDir = Yii::getAlias('@storage/web/source/'.strtolower(MetaValues::getKeyName($model->key)).'/');  
                $image_name=time().rand().'_icon.'.$icon->extension;
                $icon->saveAs($uploadDir .$image_name );
                $model->icon=$image_name;
            }
        }
        if ($model->load(Yii::$app->request->post())){
            if($model->save()) {
                return $this->redirect(['index']);
            } else {
                return $this->render('update', [
                    'model' => $model,'metakeys'=>$metakeys
                ]);
            }
        }
        return $this->render('update', [
            'model' => $model,'metakeys'=>$metakeys
        ]);

    }

    public function actionFeaturedUpdate() {

        if(Yii::$app->request->isPost && Yii::$app->request->isAjax){
            $id=Yii::$app->request->post('id');
            $is_featured=Yii::$app->request->post('is_featured');
            $model = MetaValues::find()->andWhere(['key'=>5,'id'=>$id])->one();
            $model->popular=($is_featured)?0:1;
            $model->save();
            return $this->renderAjax('featured', ['is_featured' => $is_featured]);
        }
       return false; 
    }

    public function actionImport(){
        $modelImport = new \yii\base\DynamicModel([
            'fileImport'=>'File Import',
            'type'=>'Type'
        ]);
        $modelImport->addRule(['fileImport'],'required');
        $modelImport->addRule(['fileImport'],'file',['skipOnEmpty' => false,'extensions' => 'xlsx']);
        $modelImport->addRule(['type'],'required');


        $categories = MetaValues::find()->orderBy('id asc')
            ->where(['status'=>1,'key' =>5])->all();

        $logs=array();

        if(Yii::$app->request->post()){
            $post=Yii::$app->request->post();
            $modelImport->fileImport = \yii\web\UploadedFile::getInstance($modelImport,'fileImport');
            if($modelImport->fileImport){
                $inputFileType = \PHPExcel_IOFactory::identify($modelImport->fileImport->tempName);
                $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
                $objPHPExcel = $objReader->load($modelImport->fileImport->tempName);
                $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);


                $baseRow = 2;
                while(!empty($sheetData[$baseRow]['A'])){
                    $model = new MetaValues();
                    $model->parent_key=$post['DynamicModel']['type'];
                    $model->key=9;
                    $model->label=$sheetData[$baseRow]['A'];
                    $model->value=$sheetData[$baseRow]['A'];
                    $model->slug=DrsPanel::metavalues_slugify($model->value);
                    $model->popular=0;
                    $model->status=1;

                    if($model->save()){
                    }
                    else{
                        $logs[$baseRow]=$model->getErrors();
                    }
                    $baseRow++;
                }
                Yii::$app->getSession()->setFlash('success','Sheet Data Inserted');
            }else{
                Yii::$app->getSession()->setFlash('error','File not found');
            }
        }
        return $this->render('import',[
            'modelImport' => $modelImport,'logs'=>$logs,'categories'=>$categories
        ]);
    }

    public function getSheetKeyCheck($row){
        $newrow=array();
        $row=array_filter($row);
        foreach($row as $key=>$val){
            $newrow[$key]=trim($val);
        }
        $array=array(
            'A'=>'Noms et prenom',
            'B' => 'Adresse',
            'C' => 'Zip Code',
            'D' => 'Ville',
            'E' => 'Etage',
            'F' => 'Adresse2',
            'G' => 'Département',
            'H' => 'Région',
            'I' => 'TELEPHONE',
            'J' => 'Type',
            'K' => 'Country'
        );
        if($newrow === $array){
            return true;
        }
        else{
            return false;
        }
    }


}
