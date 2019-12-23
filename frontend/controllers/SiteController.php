<?php
namespace frontend\controllers;

use common\components\UserIp;
use Yii;
use frontend\models\ContactForm;
use yii\web\Controller;
use common\components\DrsPanel;
use common\models\Groups;
use common\models\UserProfile;
use common\models\MetaValues;
use yii\bootstrap\ActiveForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function actions(){
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction'
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null
            ],
            'set-locale'=>[
                'class'=>'common\actions\SetLocaleAction',
                'locales'=>array_keys(Yii::$app->params['availableLocales'])
            ]
        ];
    }

    public function actionIndex(){
        $currentcity= DrsPanel::getCitySelected();
        $city_id=DrsPanel::getCityId($currentcity,'Rajasthan');
        $drsdata = DrsPanel::homeScreenData($currentcity,$city_id);
         
        return $this->render('index',['drsdata'=>$drsdata]);
    } 

    public function actionGallery(){
        return $this->render('gallery');
    }

    public function actionContact(){
        $model = new ContactForm();
        $request = Yii::$app->request->queryParams;
        if(isset($request['type']) && $request['type'] == 'm'){
            $this->layout = "@frontend/views/layouts/webview";
        }
        if ($model->load(Yii::$app->request->post())) {
            if ($model->contact(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', "'Thank you for contacting us. We will respond to you as soon as possible.'");
                return $this->refresh();
            } else {
                Yii::$app->session->setFlash('error', "'There was an error sending email.'");
            }
        }

        return $this->render('contact', [
            'model' => $model
            ]);
    }

    public function actionPrefixTitle(){
        $result='<option value="">Select Title</option>';
        if(Yii::$app->request->post() && Yii::$app->request->isAjax){
            $post=Yii::$app->request->post();
            $group=Groups::allgroups();
            $rst=DrsPanel::prefixingList(strtolower($group[$post['type']]),'list');
            if(count($rst)>0){
                foreach ($rst as $key => $item) {
                    $result=$result.'<option value="'.$item.'">'.$item.'</option>';
                }
            }
        }
        return $result;
    }

    public function actionCityList(){
        $result='<option value="">Select City</option>';
        if(Yii::$app->request->post()){
            $post=Yii::$app->request->post();
            $rst=Drspanel::getCitiesList($post['state_id'],'name');
            foreach ($rst as $key => $item) {
                $result=$result.'<option value="'.$item->name.'">'.$item->name.'</option>';
            }
        }
        return $result;
    }

    public function actionAttenderList(){
        $result='<option value="">Select Attender</option>';
        if(Yii::$app->request->post()){
            $post=Yii::$app->request->post();
            $rst=Drspanel::attenderList(['parent_id'=>$post['doctor_id'],'address_id'=>$post['address_id']]);
            if(count($rst)>0){
                foreach ($rst as $key => $item) {
                    $result=$result.'<option value="'.$item->id.'">'.$item['userProfile']['name'].'</option>';
                }
            }
        }
        return $result;
    }

    public function getCurrentLatLong(){
        $ip  = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
        $url    =   'http://ip-api.com/json/' . $ip;
        $ch  = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $data = curl_exec($ch);
        curl_close($ch);

        if ($data) {
            $location = json_decode($data);
            $out['lat'] = isset($location->lat)?$location->lat:'';
            $out['lng'] = isset($location->lon)?$location->lon:'';
            $baseurl =$_SERVER['HTTP_HOST'];
            $json = json_encode($out, true);
            setcookie('current_location', $json, time()+60*60, '/',$baseurl , false);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action){
        if (!\Yii::$app->user->isGuest) {
            if(Yii::$app->controller->action->id == 'contact'){

            }
            else{
                $groupid=Yii::$app->user->identity->userProfile->groupid;
                if($groupid == Groups::GROUP_DOCTOR){ return $this->redirect(['doctor/appointments']); }
                elseif($groupid == Groups::GROUP_HOSPITAL){ return $this->redirect(['hospital/appointments']);}
                elseif($groupid == Groups::GROUP_ATTENDER){ return $this->redirect(['attender/appointments']);}
            }
        }
        $this->getCurrentLatLong();
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);

    }



}
