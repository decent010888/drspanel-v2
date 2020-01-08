<?php

namespace backend\controllers;

use backend\models\AddAppointmentForm;
use backend\models\DailyPatientLimitForm;
use backend\models\search\UserAppointmentSearch;
use common\components\DrsPanel;
use common\components\DrsImageUpload;
use common\models\Groups;
use common\models\MetaKeys;
use common\models\MetaValues;
use common\models\UserAddress;
use common\models\UserAppointment;
use common\models\UserFeesPercent;
use common\models\UserPlanDetail;
use common\models\UserProfile;
use common\models\UserRating;
use common\models\UserSchedule;
use common\models\UserEducations;
use common\models\UserExperience;
use common\models\UserRequest;
use common\models\UserAddressImages;
use common\models\UserServiceCharge;
use Yii;
use common\models\User;
use backend\models\DoctorForm;
use backend\models\AttenderForm;
use backend\models\search\DoctorSearch;
use backend\models\search\UserScheduleSearch;
use backend\models\search\AttenderSearch;
use backend\models\search\UserEducationsSearch;
use backend\models\search\HospitalSearch;
use backend\models\search\UserUserExperienceSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\AddScheduleForm;
use yii\bootstrap\ActiveForm;
use yii\web\UploadedFile;
use Intervention\Image\ImageManagerStatic;
use kartik\mpdf\Pdf;

/**
 * DoctorController implements the CRUD actions for User model.
 */
class DoctorController extends Controller {

    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /* public function beforeAction($action){
      $logined=Yii::$app->user->identity;
      if($logined->role=='SubAdmin'){
      $action=Yii::$app->controller->action->id;
      $id=Yii::$app->request->get('id');
      if(in_array($action,DrsPanel::adminAccessUrl($logined,'doctor')) && $id){
      $isAccess=User::find()->andWhere(['admin_user_id'=>$logined->id])->andWhere(['id'=>$id])->one();
      if(empty($isAccess)){
      $this->goHome();
      }
      }
      }
      return true;
      } */

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new DoctorSearch();
        $logined = Yii::$app->user->identity;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $logined);
        $dataProvider->sort->defaultOrder = ['id' => SORT_DESC];

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = new DoctorForm();
        if ($model->load(Yii::$app->request->post())) {
            $model->groupid = Groups::GROUP_DOCTOR;
            $model->admin_user_id = Yii::$app->user->id;
            if ($res = $model->signup($model)) {
                return $this->redirect(['view', 'id' => $res->id]);
            }
        }
        return $this->render('create', [
                    'model' => $model,
                    'roles' => ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name')
        ]);
    }

    /**
     * Deatils an existing User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        $model = $this->findModel($id);
        $userProfile = UserProfile::findOne(['user_id' => $id]);
        $old_image = $userProfile->avatar;
        $degrees = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 2])->all();
        $specialities = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 5])->all();
        $services = MetaValues::find()->where(['Key' => 11])->all();
        if ($userProfile['speciality'])
            $treatment = MetaValues::getValues(9, $userProfile['speciality']);
        else
            $treatment = [];
        $addressList = DrsPanel::doctorHospitalList($id);
        $listaddress = $addressList['listaddress'];
        $addressProvider = $addressList['addressProvider'];
        $userShift = UserSchedule::find()->where(['user_id' => $id])->all();
        $week_array = DrsPanel::getWeekArray();
        $availibility_days = array();
        foreach ($week_array as $week) {
            $availibility_days[] = $week;
        }
        if (empty($userShift)) {
            $shiftType = 'new';
        } else {
            $shiftType = 'old';
        }

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['AddScheduleForm'])) {
                $addUpdateShift = DrsPanel::addupdateShift($id, $post);
                return $this->redirect(['view', 'id' => $id]);
            } elseif (isset($post['LiveStatus'])) {
                $model->admin_status = $post['LiveStatus']['status'];
                if ($model->save()) {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-success'],
                        'body' => Yii::t('backend', 'Profile status updated!')
                    ]);
                    return $this->redirect(['view', 'id' => $id]);
                } else {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-danger'],
                        'body' => Yii::t('backend', 'Status not updated!')
                    ]);
                    return $this->redirect(['view', 'id' => $id]);
                }
            } elseif (isset($post['AdminRating'])) {
                $type = $post['AdminRating']['type'];
                $userRating = UserRating::find()->where(['user_id' => $id])->one();
                if (empty($userRating)) {
                    $userRating = new UserRating();
                    $userRating->user_id = $id;
                }
                $userRating->show_rating = $type;
                if ($type == 'Admin') {
                    $userRating->admin_rating = $post['AdminRating']['rating'];
                }
                if ($userRating->save()) {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-success'],
                        'body' => Yii::t('backend', 'Profile rating updated!')
                    ]);
                    return $this->redirect(['view', 'id' => $id]);
                } else {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-danger'],
                        'body' => Yii::t('backend', 'Rating not updated!')
                    ]);
                    return $this->redirect(['view', 'id' => $id]);
                }
            } elseif (isset($post['Fees'])) {
                foreach ($post['Fees'] as $key => $feetype) {
                    $getFees = UserFeesPercent::find()->where(['user_id' => $id, 'type' => $key])->one();
                    if (empty($getFees)) {
                        $getFees = new UserFeesPercent();
                        $getFees->user_id = $id;
                        $getFees->type = $key;
                    }
                    $getFees->admin = $feetype['admin'];
                    $getFees->user_provider = $feetype['user_provider'];
                    if ($key == 'cancel' || $key == 'reschedule') {
                        $getFees->user_patient = $feetype['user_patient'];
                    }
                    $getFees->save();
                }
                return $this->redirect(['view', 'id' => $id]);
            } elseif (isset($post['UserAddress'])) {
                $addAddress = new UserAddress();
                $addAddress->load($post);
                $addAddress->save();
                return $this->redirect(['view', 'id' => $id]);
            } else {

                if (isset($post['UserProfile']) || !empty($_FILES)) {
                    $model->load($post);
                    $userProfile->load($post);

                    if (isset($post['UserProfile']['degree'])) {
                        $udegrees = $post['UserProfile']['degree'];
                        if (!empty($udegrees)) {
                            $other_degree = false;
                            if (in_array("Other", $udegrees)) {
                                $other_degree = true;
                            }
                            $userProfile->degree = implode(',', $udegrees);
                            if (isset($post['other_degree']) && !empty($post['other_degree']) && $other_degree) {
                                $userProfile->other_degree = $post['other_degree'];
                            } else {
                                $userProfile->other_degree = NULL;
                            }
                        }
                    }

                    if (isset($post['UserProfile']['speciality']) && !empty($post['UserProfile']['speciality'])) {
                        $Userspecialities = $post['UserProfile']['speciality'];
                        $Usertreatments = $post['UserProfile']['treatment'];
                        if (!empty($Userspecialities)) {
                            $metakey_speciality = MetaKeys::findOne(['key' => 'speciality']);
                            $getSpecilaity = MetaValues::find()->where(['key' => $metakey_speciality->id, 'value' => $Userspecialities])->one();
                            if (!empty($Usertreatments)) {
                                $userProfile->treatment = implode(',', $Usertreatments);
                                foreach ($Usertreatments as $keyt => $valuet) {
                                    $treatmentModel = MetaValues::find()->where(['key' => 9, 'parent_key' => $getSpecilaity->id, 'value' => $valuet])->one();
                                    if (empty($treatmentModel)) {
                                        $treatmentModel = new MetaValues();
                                        if (!empty($getSpecilaity)) {
                                            $treatmentModel->parent_key = $getSpecilaity->id;
                                        }
                                        $treatmentModel->key = 9;
                                        $treatmentModel->value = $valuet;
                                        $treatmentModel->label = $valuet;
                                        $treatmentModel->slug = DrsPanel::metavalues_slugify($valuet);
                                        $treatmentModel->status = 1;
                                        $treatmentModel->save();
                                    }
                                }
                            } else {
                                $userProfile->treatment = '';
                            }
                        }
                    }

                    if (isset($post['UserProfile']['services'])) {
                        $userServices = $post['UserProfile']['services'];
                        if (!empty($userServices)) {
                            $userProfile->services = implode(',', $userServices);
                        }
                        if (!empty($userServices)) {
                            foreach ($userServices as $key => $value) {
                                $servicesModel = MetaValues::find()->where(['key' => 11, 'value' => $value])->one();
                                if (empty($servicesModel)) {
                                    $servicesModel = new MetaValues();
                                    $servicesModel->key = 11;
                                    $servicesModel->value = $value;
                                    $servicesModel->label = $value;
                                    $servicesModel->status = 1;
                                    $servicesModel->save();
                                }
                            }
                        }
                    }


                    if (isset($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['tmp_name'])) {
                        $upload = UploadedFile::getInstance($userProfile, 'avatar');
                        $uploadDir = Yii::getAlias('@storage/web/source/doctors/');
                        $image_name = time() . rand() . '.' . $upload->extension;
                        $userProfile->avatar = $image_name;
                        $userProfile->avatar_path = '/storage/web/source/doctors/';
                        $userProfile->avatar_base_url = Yii::getAlias('@frontendUrl');
                    } else {
                        $userProfile->avatar = $old_image;
                    }

                    if ($model->save()) {
                        if ($userProfile->save()) {
                            Yii::$app->session->setFlash('alert', [
                                'options' => ['class' => 'alert-success'],
                                'body' => Yii::t('backend', 'Profile updated!')
                            ]);
                            if (!empty($upload)) {
                                $waterMarkImgDir = Yii::getAlias('@frontend/web/images/watermark.png');
                                $upload->saveAs($uploadDir . $image_name);
                                $geturl = DrsPanel::getUserAvator($id);
                                $file_data = file_get_contents($geturl, false, stream_context_create([
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                    ],
                                ]));
                                $img = ImageManagerStatic::make($file_data)->insert($waterMarkImgDir, 'bottom-left', 10, 10);
                                $img->save($uploadDir . $image_name);
                            }
                            return $this->redirect(['view', 'id' => $id]);
                        } else {
                            
                        }
                    } else {
                        
                    }
                }
            }
        }
        // pr($degrees);die;

        return $this->render('detail', [
                    'model' => $model,
                    'userProfile' => $userProfile, 'degrees' => $degrees, 'treatment' => $treatment, 'specialities' => $specialities,
                    'addressProvider' => $addressProvider, 'shiftType' => $shiftType, 'listaddress' => $listaddress, 'week_array' => $week_array, 'availibility_days' => $availibility_days, 'services' => $services
        ]);
    }

    public function actionMyShifts($id) {
        $address_list = DrsPanel::doctorHospitalList($id);
        return $this->render('my-shifts', ['doctor_id' => $id, 'address_list' => $address_list['apiList']]);
    }

    public function actionAjaxCheckService() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();

            if (isset($post['UserServiceCharge'])) {
                $user_id = $post['UserServiceCharge']['user_id'];
                $address_id = $post['UserServiceCharge']['address_id'];
                $service_log = UserServiceCharge::find()->where(['user_id' => $user_id, 'address_id' => $address_id])->one();
                if (empty($service_log)) {
                    $service_log = new UserServiceCharge();
                }
                $service_log->user_id = $user_id;
                $service_log->address_id = $address_id;
                $service_log->charge = $post['UserServiceCharge']['charge'];
                $service_log->charge_discount = $post['UserServiceCharge']['charge_discount'];
                $service_log->save();
                return $this->redirect(Yii::$app->request->referrer);
            } else {
                $user_id = $post['user_id'];
                $address_id = $post['address_id'];
                $service_log = UserServiceCharge::find()->where(['user_id' => $user_id, 'address_id' => $address_id])->one();
                if (empty($service_log)) {
                    $service_log = new UserServiceCharge();
                    $service_log->user_id = $user_id;
                    $service_log->address_id = $address_id;
                    $type = 'Add';
                } else {
                    $type = 'Update';
                }

                echo $this->renderAjax('_addupdateservice', ['service_log' => $service_log, 'type' => $type]);
                exit;
            }
        }
    }

    public function actionHospitals($id) {
        $userShift = UserSchedule::find()->where(['user_id' => $id])->all();
        if (empty($userShift)) {
            $shiftType = 'new';
        } else {
            $shiftType = 'old';
        }
        $addressList = DrsPanel::doctorHospitalList($id);
        $listaddress = $addressList['listaddress'];
        $addressProvider = $addressList['addressProvider'];
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['UserAddress'])) {
                $addAddress = new UserAddress();
                $addAddress->load($post);
                $addAddress->save();
                return $this->redirect(['hospitals', 'id' => $id]);
            }
        }


        return $this->render('hospitals', ['addressProvider' => $addressProvider, 'userid' => $id]);
    }

    public function actionAjaxTreatmentList() {
        if (Yii::$app->request->isPost) {
            $form = ActiveForm::begin(['id' => 'profile-form']);
            $post = Yii::$app->request->post();

            if (isset($post['id']) && !empty($post['user_id'])) {
                $userProfile = UserProfile::findOne(['user_id' => $post['user_id']]);
                $metavalue = MetaValues::findOne(['value' => $post['id']]);
                if (!empty($metavalue)) {
                    $treatment = MetaValues::find()->andWhere(['status' => 1, 'key' => 9, 'parent_key' => $metavalue->id])->all();
                } else {
                    $checkid = 0;
                    $treatment = array();
                }

                $treatment_list = [];
                foreach ($treatment as $obj) {
                    $treatment_list[$obj->value] = $obj->label;
                }
                return $this->renderAjax('ajax-treatment-list', ['form' => $form, 'treatment_list' => $treatment_list, 'userProfile' => $userProfile]);
            }
        }
    }

    public function actionUpdateAddressModal() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['UserAddress'])) {
                $address = UserAddress::findOne($post['UserAddress']['id']);
                $address->load($post);
                $address->save();
                return $this->redirect(['view', 'id' => $post['UserAddress']['user_id']]);
            } else {
                $id = $post['id'];
                $address = UserAddress::findOne($id);
                echo $this->renderAjax('_editAddress', ['model' => $address]);
                exit;
            }
        }
        echo 'error';
        exit;
    }

    public function actionAttenderList($id) {
        $searchModel = new AttenderSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $id);

        return $this->render('/user-attender/index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                    'id' => $id
        ]);
    }

    public function actionAttenderCreate($id) {
        if (User::findOne($id)) {
            $model = new AttenderForm();

            $addressList = DrsPanel::doctorHospitalList($id);
            $listadd = $addressList['apiList'];
            $shift_array = array();
            $s = 0;
            $shift_value = array();
            $sv = 0;
            foreach ($listadd as $address) {
                $shifts = DrsPanel::getShiftListByAddress($id, $address['id']);
                foreach ($shifts as $key => $shift) {
                    if ($shift['hospital_id'] == 0) {
                        $shift_array[$s]['value'] = $shift['shifts_ids'];
                        $shift_array[$s]['label'] = $shift['name'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                        $shift_value[$sv] = $shift['name'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                        $s++;
                        $sv++;
                    }
                }
            }

            if (Yii::$app->request->post()) {
                $post = Yii::$app->request->post();
                $model->load($post);
                $model->groupid = Groups::GROUP_ATTENDER;
                $model->parent_id = $id;
                $model->created_by = 'Doctor';
                if ($res = $model->signup()) {

                    if (!empty($post['AttenderForm']['shift_id']) && count($post['AttenderForm']['shift_id']) > 0) {
                        $sel_shift = $post['AttenderForm']['shift_id'];
                        $shift_val = array();
                        foreach ($sel_shift as $s) {
                            $shift_selected_ids = $shift_array[$s];
                            $list = $shift_selected_ids['value'];
                            foreach ($list as $list) {
                                $shift_val[] = $list;
                            }
                        }
                        $addupdateAttender = DrsPanel::addUpdateAttenderToShifts($shift_val, $res['id']);
                    }

                    return $this->redirect(['attender-list', 'id' => $id]);
                }
            }

            return $this->render('/user-attender/create', [
                        'model' => $model,
                        'roles' => ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name'),
                        'id' => $id,
                        'hospitals' => $addressList['listaddress'],
                        'shifts' => $shift_value,
            ]);
        } else {
            return $this->redirect(['index']);
        }
    }

    public function actionAttenderDetail($id) {
        $model = $this->findModel($id);
        $userProfile = UserProfile::findOne(['user_id' => $id]);

        $selectedShifts = Drspanel::shiftList(['user_id' => $model->parent_id, 'attender_id' => $id], 'list');
        $addressList = DrsPanel::doctorHospitalList($model->parent_id);
        $listadd = $addressList['apiList'];

        $shift_array = array();
        $s = 0;
        $shift_value = array();
        $sv = 0;
        $selectedShiftsIds = array();
        foreach ($listadd as $address) {
            $shifts = DrsPanel::getShiftListByAddress($model->parent_id, $address['id']);
            foreach ($shifts as $key => $shift) {
                if ($shift['hospital_id'] == 0) {
                    $shift_array[$s]['value'] = $shift['shifts_ids'];
                    $shift_array[$s]['label'] = $shift['name'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                    $shift_value[$sv] = $shift['name'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';

                    $shift_id_list = $shift['shifts_ids'];
                    foreach ($selectedShifts as $select => $valuesel) {
                        if (in_array($select, $shift_id_list)) {
                            $selectedShiftsIds[$sv] = $sv;
                        }
                    }
                    $s++;
                    $sv++;
                }
            }
        }

        $shiftList = Drspanel::shiftList(['user_id' => $model->parent_id], 'list');
        $shiftModels = new AttenderForm();
        $shiftModels->shift_id = $selectedShiftsIds;
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();

            $model->load(Yii::$app->request->post());
            $userProfile->load(Yii::$app->request->post());

            $upload = UploadedFile::getInstance($userProfile, 'avatar');
            if (!empty($upload)) {
                $uploadDir = Yii::getAlias('@storage/web/source/attenders/');
                $image_name = time() . rand() . '.' . $upload->extension;
                $userProfile->avatar = $image_name;
                $userProfile->avatar_path = '/storage/web/source/attenders/';
                $userProfile->avatar_base_url = Yii::getAlias('@frontendUrl');
            }

            if ($model->save() && $userProfile->save()) {

                if (!empty($upload)) {
                    $upload->saveAs($uploadDir . $image_name);
                }

                if (isset($post['AttenderForm']['shift_id']) && count($post['AttenderForm']['shift_id']) > 0) {
                    $sel_shift = $post['AttenderForm']['shift_id'];
                    $shift_val = array();
                    foreach ($sel_shift as $s) {
                        $shift_selected_ids = $shift_array[$s];
                        $list = $shift_selected_ids['value'];
                        foreach ($list as $list) {
                            $shift_val[] = $list;
                        }
                    }
                    $addupdateAttender = DrsPanel::addUpdateAttenderToShifts($shift_val, $id);
                } else {
                    $addupdateAttender = DrsPanel::addUpdateAttenderToShifts(array(), $id);
                }
                Yii::$app->session->setFlash('alert', [
                    'options' => ['class' => 'alert-success'],
                    'body' => Yii::t('backend', 'Profile updated!')
                ]);
                return $this->redirect(['/doctor/attender-list', 'id' => $model->parent_id]);
            }
        }



        return $this->render('/user-attender/detail', [
                    'model' => $model,
                    'shiftModels' => $shiftModels,
                    'userProfile' => $userProfile,
                    'hospitals' => $addressList['listaddress'],
                    'shifts' => $shift_value,
        ]);
    }

    public function actionAttenderDelete($id) {
        if (Yii::$app->request->post()) {
            if ($user = User::find()->andWhere(['id' => $id])->andWhere(['groupid' => Groups::GROUP_ATTENDER])->one()) {
                if (DrsPanel::attenderDelete($user->id)) {
                    Yii::$app->session->setFlash('success', "'Attender Deleted!'");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionDailyPatientLimit($id) {
        $date = date('Y-m-d');
        $user_id = $id;
        $week = DrsPanel::getDateWeekDay($date);
        $shift_details = $this->setDateShiftData($user_id, $date);

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $postForm = $post['DailyPatientLimitForm'];
            $addUpdateShift = DrsPanel::updateDateShift($id, $postForm['date'], $postForm);
            return $this->redirect(['update', 'id' => $id]);
        }

        return $this->render('daily-patient-limit', [
                    'model' => $this->findModel($id), 'userShift' => $shift_details['userShift'], 'listaddress' => $shift_details['listaddress'], 'date' => $date, 'shifts_available' => $shift_details['shifts_available']
        ]);
    }

    public function actionAddShift($id = NULL) {
        $ids = $id;
        $date = date('Y-m-d');
        $current_shifts = 0;
        $week_array = DrsPanel::getWeekShortArray();
        $availibility_days = array();
        $addAddress = new UserAddress();
        $imgModel = new UserAddressImages();
        foreach ($week_array as $week) {
            $availibility_days[] = $week;
        }
        $newShift = new AddScheduleForm();
        $newShift->user_id = $id;
        if ($id) {
            $userShift = UserSchedule::findOne($id);
            if (!empty($userShift)) {
                $newShift->setShiftDataAdmin($userShift);
            }
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $newShift->setPostData($post);
            if (isset($post['UserAddress'])) {
                $shift = array();
                $shiftcount = $post['AddScheduleForm']['start_time'];
                $canAddEdit = true;
                $msg = ' invalid';
                $errorIndex = 0;
                $newInsertIndex = 0;
                $errorShift = array();
                $insertShift = array();
                $newshiftInsert = 0;
                $insertShift = array();
                $addAddress->load(Yii::$app->request->post());
                $addAddress->user_id = $ids;
                $upload = UploadedFile::getInstance($addAddress, 'image');
                $userAddressLastId = UserAddress::find()->orderBy(['id' => SORT_DESC])->one();
                $countshift = count($shiftcount);
                $newshiftcheck = array();
                $errormsgloop = array();
                $nsc = 0;
                $error_msg = 0;
                foreach ($post['AddScheduleForm']['weekday'] as $keyClnt => $day_shift) {
                    foreach ($day_shift as $keydata => $value) {
                        $dayShiftsFromDb = UserSchedule::find()->where(['user_id' => $ids])->andwhere(['weekday' => $value])->all();

                        if (!empty($dayShiftsFromDb)) {
                            foreach ($dayShiftsFromDb as $keydb => $dayshiftValuedb) {
                                $dbstart_time = date('Y-m-d', $dayshiftValuedb->start_time);
                                $dbend_time = date('Y-m-d', $dayshiftValuedb->end_time);
                                $nstart_time = $dbstart_time . ' ' . $post['AddScheduleForm']['start_time'][$keyClnt];
                                $nend_time = $dbend_time . ' ' . $post['AddScheduleForm']['end_time'][$keyClnt];
                                $startTimeClnt = strtotime($nstart_time);
                                $endTimeClnt = strtotime($nend_time);
                                $startTimeDb = $dayshiftValuedb->start_time;
                                $endTimeDb = $dayshiftValuedb->end_time;

                                if ($startTimeClnt > $endTimeClnt) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = '(end time should be greater than start time)';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' (end time should be greater than start time)';
                                }

                                //check values with local runtime form value
                                foreach ($newshiftcheck as $keyshift => $newshift) {
                                    $starttime_check = $newshift['start_time'];
                                    $endtime_check = $newshift['end_time'];
                                    $weekday_check = $newshift['weekday'];
                                    $keyClnt_check = $newshift['keyclnt'];
                                    if ($weekday_check == $value && $keyClnt != $keyClnt_check) {
                                        if ($startTimeClnt == $starttime_check && $endTimeClnt == $endtime_check) {
                                            $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                            $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                            $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                            $errormsgloop[$error_msg]['weekday'] = $value;
                                            $errormsgloop[$error_msg]['message'] = 'is already exists';
                                            $canAddEdit = false;
                                            $errorIndex++;
                                            $error_msg++;
                                            $msg = ' already exists';
                                        } elseif ($startTimeClnt >= $starttime_check && $startTimeClnt < $endtime_check) {
                                            $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                            $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                            $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                            $errormsgloop[$error_msg]['weekday'] = $value;
                                            $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                            $canAddEdit = false;
                                            $errorIndex++;
                                            $error_msg++;
                                            $msg = ' msg1';
                                        } elseif ($endTimeClnt >= $starttime_check && $endTimeClnt <= $endtime_check) {
                                            $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                            $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                            $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                            $errormsgloop[$error_msg]['weekday'] = $value;
                                            $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                            $canAddEdit = false;
                                            $errorIndex++;
                                            $error_msg++;
                                            $msg = ' msg2';
                                        } elseif ($starttime_check >= $startTimeClnt && $starttime_check <= $endTimeClnt) {
                                            $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                            $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                            $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                            $errormsgloop[$error_msg]['weekday'] = $value;
                                            $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                            $canAddEdit = false;
                                            $errorIndex++;
                                            $error_msg++;
                                            $msg = ' msg3';
                                        } elseif ($endtime_check > $startTimeClnt && $endtime_check <= $endTimeClnt) {
                                            $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                            $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                            $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                            $errormsgloop[$error_msg]['weekday'] = $value;
                                            $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                            $canAddEdit = false;
                                            $errorIndex++;
                                            $error_msg++;
                                            $msg = ' msg4';
                                        } elseif ($startTimeClnt >= $starttime_check && $startTimeClnt < $endtime_check) {
                                            $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                            $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                            $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                            $errormsgloop[$error_msg]['weekday'] = $value;
                                            $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                            $canAddEdit = false;
                                            $errorIndex++;
                                            $error_msg++;
                                            $msg = ' msg5';
                                        }
                                    }
                                }


                                //check values with database value
                                if ($startTimeClnt == $endTimeClnt) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = '(start time & end time should not be same)';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' (start time & end time should not be same)';
                                } elseif ($startTimeClnt == $startTimeDb && $endTimeClnt == $endTimeDb) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = 'is already exists';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' already exists';
                                } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' msg1';
                                } elseif ($endTimeClnt >= $startTimeDb && $endTimeClnt <= $endTimeDb) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' msg2';
                                } elseif ($startTimeDb >= $startTimeClnt && $startTimeDb <= $endTimeClnt) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' msg3';
                                } elseif ($endTimeDb > $startTimeClnt && $endTimeDb <= $endTimeClnt) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' msg4';
                                } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                    $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                    $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                    $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                    $errormsgloop[$error_msg]['weekday'] = $value;
                                    $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                    $canAddEdit = false;
                                    $errorIndex++;
                                    $error_msg++;
                                    $msg = ' msg5';
                                } else {
                                    if ($canAddEdit == true) {
                                        $nsc_add = $nsc++;
                                        $newshiftcheck[$nsc_add]['start_time'] = $startTimeClnt;
                                        $newshiftcheck[$nsc_add]['end_time'] = $endTimeClnt;
                                        $newshiftcheck[$nsc_add]['keyclnt'] = $keyClnt;
                                        $newshiftcheck[$nsc_add]['weekday'] = $value;
                                    }
                                }
                            }
                            if ($canAddEdit == true) {
                                $insertShift[$newInsertIndex] = $this->saveShiftData($ids, $keyClnt, $post, $value, $countshift = NULL);
                                $newInsertIndex++;
                            }
                        } else {
                            $dbstart_time = date('Y-m-d');
                            $nstart_time = $dbstart_time . ' ' . $post['AddScheduleForm']['start_time'][$keyClnt];
                            $nend_time = $dbstart_time . ' ' . $post['AddScheduleForm']['end_time'][$keyClnt];
                            $startTimeClnt = strtotime($nstart_time);
                            $endTimeClnt = strtotime($nend_time);
                            if ($startTimeClnt > $endTimeClnt) {
                                $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                $errormsgloop[$error_msg]['weekday'] = $value;
                                $errormsgloop[$error_msg]['message'] = '(end time should be greater than start time)';
                                $canAddEdit = false;
                                $errorIndex++;
                                $error_msg++;
                                $msg = ' (end time should be greater than start time)';
                            }

                            //check values with local runtime form value
                            foreach ($newshiftcheck as $keyshift => $newshift) {
                                $starttime_check = $newshift['start_time'];
                                $endtime_check = $newshift['end_time'];
                                $weekday_check = $newshift['weekday'];
                                $keyClnt_check = $newshift['keyclnt'];
                                if ($weekday_check == $value && $keyClnt != $keyClnt_check) {
                                    if ($startTimeClnt == $starttime_check && $endTimeClnt == $endtime_check) {
                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                        $errormsgloop[$error_msg]['message'] = 'is already exists';
                                        $canAddEdit = false;
                                        $errorIndex++;
                                        $error_msg++;
                                        $msg = ' already exists';
                                    } elseif ($startTimeClnt > $starttime_check && $startTimeClnt < $endtime_check) {
                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                        $canAddEdit = false;
                                        $errorIndex++;
                                        $error_msg++;
                                        $msg = ' msg1';
                                    } elseif ($endTimeClnt > $starttime_check && $endTimeClnt < $endtime_check) {
                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                        $canAddEdit = false;
                                        $errorIndex++;
                                        $error_msg++;
                                        $msg = ' msg2';
                                    }
                                }
                            }
                            if ($canAddEdit == true) {
                                $nsc_add = $nsc++;
                                $newshiftcheck[$nsc_add]['start_time'] = $startTimeClnt;
                                $newshiftcheck[$nsc_add]['end_time'] = $endTimeClnt;
                                $newshiftcheck[$nsc_add]['weekday'] = $value;
                                $newshiftcheck[$nsc_add]['keyclnt'] = $keyClnt;
                                $insertShift[$newInsertIndex] = $this->saveShiftData($ids, $keyClnt, $post, $value, $countshift);
                                $newInsertIndex++;
                            }
                        }
                    }
                }

                if ($canAddEdit == false) {
                    if (!empty($errormsgloop)) {

                        $html = array();
                        $weekdaysl = array();
                        foreach ($errormsgloop as $msgloop) {
                            $keyshifts = $msgloop['shift'];
                            $weekdaysl[$keyshifts][] = $msgloop['weekday'];
                            $html[$keyshifts] = 'Shift time ' . date('h:i a', $msgloop['start_time']) . ' - ' . date('h:i a', $msgloop['end_time']) . ' on ' . implode(',', $weekdaysl[$keyshifts]) . ' ' . $msgloop['message'];
                        }
                        Yii::$app->session->setFlash('shifterror', implode(" , ", $html));
                    } else {
                        Yii::$app->session->setFlash('shifterror', 'Shift time invalid');
                    }
                    return $this->render('shift/add-shift', ['defaultCurrrentDay' => strtotime($date), 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'modelAddress' => $addAddress, 'userAdddressImages' => $imgModel, 'postData' => $post]);
                } elseif ($canAddEdit == true) {
                    if ($addAddress->save()) {
                        $imageUpload = '';
                        if (isset($_FILES['image'])) {
                            $imageUpload = DrsImageUpload::updateAddressImageWeb($addAddress->id, $_FILES);
                        }
                        if (isset($_FILES)) {
                            $imageUpload = DrsImageUpload::updateAddressImageList($addAddress->id, $_FILES, 'UserAddressImages', 'web');
                        }
                        $addShifts = DrsPanel::addShiftWithAddress($insertShift, $addAddress->id, $id);
                        Yii::$app->session->setFlash('success', 'Shift Added SuccessFully');
                        return $this->redirect(['/doctor/my-shifts?id=' . $id]);
                    } else {
                        Yii::$app->session->setFlash('error', 'Please try again');
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Not added');
                }
            }
        }
        $scheduleslist = DrsPanel::weekSchedules($id);
        $hospitals = DrsPanel::doctorHospitalList($id);

        return $this->render('shift/_addShift', ['defaultCurrrentDay' => strtotime($date), 'hospitals' => $hospitals['apiList'], 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'listaddress' => $hospitals['listaddress'], 'scheduleslist' => $scheduleslist,
                    'modelAddress' => $addAddress, 'userAdddressImages' => $imgModel]);
    }

    public function saveShiftData($ids, $keyClnt, $post, $day_shift, $shiftcount) {
        $shift['AddScheduleForm']['user_id'] = $ids;
        $shift['AddScheduleForm']['start_time'] = $post['AddScheduleForm']['start_time'][$keyClnt];
        $shift['AddScheduleForm']['end_time'] = $post['AddScheduleForm']['end_time'][$keyClnt];
        $shift['AddScheduleForm']['appointment_time_duration'] = $post['AddScheduleForm']['appointment_time_duration'][$keyClnt];
        $shift['AddScheduleForm']['weekday'] = $day_shift;
        $time1 = strtotime($shift['AddScheduleForm']['start_time']);
        $time2 = strtotime($shift['AddScheduleForm']['end_time']);
        $difference = abs($time2 - $time1) / 60;
        $patient_limit = $difference / $shift['AddScheduleForm']['appointment_time_duration'];
        $shift['AddScheduleForm']['patient_limit'] = (int) $patient_limit;
        $shift['AddScheduleForm']['consultation_fees'] = (isset($post['AddScheduleForm']['consultation_fees'][$keyClnt]) && ($post['AddScheduleForm']['consultation_fees'][$keyClnt] > 0) ) ? $post['AddScheduleForm']['consultation_fees'][$keyClnt] : 0;
        $shift['AddScheduleForm']['emergency_fees'] = (!empty($post['AddScheduleForm']['emergency_fees'][$keyClnt]) && ($post['AddScheduleForm']['emergency_fees'][$keyClnt] > 0)) ? $post['AddScheduleForm']['emergency_fees'][$keyClnt] : 0;
        $shift['AddScheduleForm']['consultation_fees_discount'] = (isset($post['AddScheduleForm']['consultation_fees_discount'][$keyClnt])) ? $post['AddScheduleForm']['consultation_fees_discount'][$keyClnt] : 0;
        $shift['AddScheduleForm']['emergency_fees_discount'] = (isset($post['AddScheduleForm']['emergency_fees_discount'][$keyClnt])) ? $post['AddScheduleForm']['emergency_fees_discount'][$keyClnt] : 0;
        return $shift;
    }

    public function actionAddMoreShift() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $shift_count = $post['shiftcount'];
            $week_array = DrsPanel::getWeekShortArray();
            $newShift = new AddScheduleForm();
            $form = new ActiveForm();
            $result = $this->renderAjax('shift/add-more-shift', ['model' => $newShift, 'form' => $form, 'shift_count' => $shift_count, 'weeks' => $week_array]);
            return $result;
        }
    }

    public function actionUpdateShiftTime($id, $day) {
        $user_id = $id;
        $week_array = DrsPanel::getWeekArray();
        $searchModel = new UserScheduleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $day);
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $post['AddScheduleForm']['weekday'] = [$day];
            $post['AddScheduleForm']['user_id'] = $id;
            $shift_id = isset($post['AddScheduleForm']['id']) ? $post['AddScheduleForm']['id'] : '';
            if ($shift_id > 0) {
                $getSchedule = UserSchedule::find()->where(['id' => $shift_id, 'is_edit' => 1])->one();
                if (empty($getSchedule)) {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-danger'],
                        'body' => Yii::t('backend', 'You can not edit this shift')
                    ]);
                    return $this->redirect(['update-shift-time', 'id' => $id, 'day' => $day]);
                }
            }

            $post['AddScheduleForm']['consultation_fees_discount'] = (isset($post['AddScheduleForm']['consultation_fees_discount'])) ? $post['AddScheduleForm']['consultation_fees_discount'] : 0;
            $post['AddScheduleForm']['emergency_fees_discount'] = (isset($post['AddScheduleForm']['emergency_fees_discount'])) ? $post['AddScheduleForm']['emergency_fees_discount'] : 0;

            $addUpdateShift = DrsPanel::upsertShift($post, $shift_id);
            Yii::$app->session->setFlash('alert', [
                'options' => ['class' => 'alert-success'],
                'body' => Yii::t('backend', 'Shift Time updated successfully')
            ]);
            return $this->redirect(['update-shift-time', 'id' => $id, 'day' => $day]);
        }

        return $this->render('shift/update-shift-time', ['model' => $this->findModel($id), 'searchModel' => $searchModel, 'dataProvider' => $dataProvider,
        ]);
    }

    public function actionSingleShiftTime() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if ($shift = UserSchedule::findOne($post['id'])) {
                $serach_attender = ['parent_id' => $post['user_id'], 'address_id' => $shift->address_id];
                $addressList = DrsPanel::doctorHospitalList($post['user_id']);
                $attenderList = DrsPanel::attenderList($serach_attender, 'list');
                $singleShift = UserSchedule::setSingleShiftData($post);
                $week_array = DrsPanel::getWeekArray();
                $newShift = new AddScheduleForm();
                $newShift->setShiftData($singleShift);
                return $this->renderAjax('shift/_editShift', ['model' => $this->findModel($post['user_id']), 'userShift' => $newShift, 'listaddress' => $addressList['listaddress'], 'attenderList' => $attenderList, 'week' => $singleShift->weekday, 'week_array' => $week_array
                ]);
            }
        }
        return 'error';
    }

    public function actionEditShift($id, $user_id) {
        $loginID = $user_id;
        $address = UserAddress::findOne($id);
        $addressImages = UserAddressImages::find()->where(['address_id' => $id])->all();
        $imgModel = new UserAddressImages();

        if ($address->user_id == $loginID) {
            $disable_field = 0;
        } else {
            $disable_field = 1;
        }

        $date = date('Y-m-d');
        $week_array = DrsPanel::getWeekShortArray();
        $availibility_days = array();

        foreach ($week_array as $week) {
            $availibility_days[] = $week;
        }
        $newShift = new AddScheduleForm();
        $newShift->user_id = $loginID;
        $shifts = DrsPanel::getShiftListByAddress($loginID, $id);

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();

            $deletedImages = $post['deletedImages'];
            if (isset($post['UserAddress'])) {
                $user = User::findOne(['id' => $loginID]);
                if (!empty($user)) {
                    $addAddress = UserAddress::findOne($id);
                    if (empty($addAddress)) {
                        Yii::$app->session->setFlash('success', "'Address Not Valid'");
                        return $this->redirect(['/doctor/my-shifts']);
                    }
                    if ($disable_field == 0) {
                        $data['UserAddress']['user_id'] = $addAddress->user_id;
                        $data['UserAddress']['name'] = $post['UserAddress']['name'];
                        $data['UserAddress']['city_id'] = $post['UserAddress']['city_id'];
                        $data['UserAddress']['city'] = Drspanel::getCityName($post['UserAddress']['city_id']);
                        $data['UserAddress']['state'] = $post['UserAddress']['state'];
                        $data['UserAddress']['address'] = $post['UserAddress']['address'];
                        $data['UserAddress']['area'] = $post['UserAddress']['area'];
                        $data['UserAddress']['phone'] = $post['UserAddress']['phone'];
                        $data['UserAddress']['landline'] = $post['UserAddress']['landline'];
                        $data['UserAddress']['is_request'] = 0;

                        if (isset($post['UserAddress']['lat']) && !empty($post['UserAddress']['lat'])) {
                            $data['UserAddress']['lat'] = $post['UserAddress']['lat'];
                        } else {
                            $data['UserAddress']['lat'] = '26.943040';
                        }

                        if (isset($post['UserAddress']['lng']) && !empty($post['UserAddress']['lng'])) {
                            $data['UserAddress']['lng'] = $post['UserAddress']['lng'];
                        } else {
                            $data['UserAddress']['lng'] = '75.757060';
                        }

                        $addAddress->load($data);
                    }

                    if (isset($post['AddScheduleForm'])) {
                        if ((isset($post['AddScheduleForm']['weekday']) && !empty($post['AddScheduleForm']['weekday']))) {
                            $dayShifts = $post['AddScheduleForm']['weekday'];
                            $canAddEdit = true;
                            $msg = ' invalid';
                            $errorIndex = 0;
                            $newInsertIndex = 0;
                            $errorShift = array();
                            $insertShift = array();
                            $shiftcount = $post['AddScheduleForm']['start_time'];
                            $newshiftcheck = array();
                            $errormsgloop = array();
                            $nsc = 0;
                            $error_msg = 0;

                            foreach ($post['AddScheduleForm']['weekday'] as $keyClnt => $day_shift) {
                                $existing_shift = array();
                                if (!empty($day_shift)) {
                                    foreach ($day_shift as $keydata => $value) {
                                        if (isset($post['shift_ids'][$keyClnt]) && isset($post['shift_ids'][$keyClnt][$value])) {
                                            $existing_shift = UserSchedule::findOne($post['shift_ids'][$keyClnt][$value]);
                                        } else {
                                            $existing_shift = array();
                                        }
                                        $dayShiftsFromDb = UserSchedule::find()->where(['user_id' => $loginID])->andwhere(['weekday' => $value])->all();

                                        if (!empty($dayShiftsFromDb)) {
                                            foreach ($dayShiftsFromDb as $keydb => $dayshiftValuedb) {
                                                $dbstart_time = date('Y-m-d', $dayshiftValuedb->start_time);
                                                $dbend_time = date('Y-m-d', $dayshiftValuedb->end_time);
                                                $nstart_time = $dbstart_time . ' ' . $post['AddScheduleForm']['start_time'][$keyClnt];
                                                $nend_time = $dbend_time . ' ' . $post['AddScheduleForm']['end_time'][$keyClnt];
                                                $startTimeClnt = strtotime($nstart_time);
                                                $endTimeClnt = strtotime($nend_time);
                                                $startTimeDb = $dayshiftValuedb->start_time;
                                                $endTimeDb = $dayshiftValuedb->end_time;

                                                if (!empty($existing_shift) && $existing_shift->id == $dayshiftValuedb->id) {
                                                    if ($startTimeClnt == $startTimeDb && $endTimeClnt == $endTimeDb) {
                                                        $nsc_add = $nsc++;
                                                        $newshiftcheck[$nsc_add]['start_time'] = $startTimeClnt;
                                                        $newshiftcheck[$nsc_add]['end_time'] = $endTimeClnt;
                                                        $newshiftcheck[$nsc_add]['keyclnt'] = $keyClnt;
                                                        $newshiftcheck[$nsc_add]['weekday'] = $value;
                                                        $canAddEdit = true;
                                                        break;
                                                    } elseif ($startTimeClnt > $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = '(end time should be greater than start time)';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' (end time should be greater than start time)';
                                                        break;
                                                    } elseif ($startTimeClnt == $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = '(start time & end time should not be same)';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' (start time & end time should not be same)';
                                                        break;
                                                    }
                                                } else {

                                                    //check values with local runtime form value
                                                    foreach ($newshiftcheck as $keyshift => $newshift) {
                                                        $starttime_check = $newshift['start_time'];
                                                        $endtime_check = $newshift['end_time'];
                                                        $weekday_check = $newshift['weekday'];
                                                        $keyClnt_check = $newshift['keyclnt'];
                                                        if ($weekday_check == $value && $keyClnt != $keyClnt_check) {
                                                            if ($startTimeClnt == $starttime_check && $endTimeClnt == $endtime_check) {
                                                                $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                                $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                                $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                                $errormsgloop[$error_msg]['weekday'] = $value;
                                                                $errormsgloop[$error_msg]['message'] = 'is already exists';
                                                                $canAddEdit = false;
                                                                $errorIndex++;
                                                                $error_msg++;
                                                                $msg = ' already exists';
                                                                break;
                                                            } elseif ($startTimeClnt > $starttime_check && $startTimeClnt < $endtime_check) {
                                                                $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                                $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                                $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                                $errormsgloop[$error_msg]['weekday'] = $value;
                                                                $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                                $errormsgloop[$error_msg]['msg'] = 'msg1';
                                                                $canAddEdit = false;
                                                                $errorIndex++;
                                                                $error_msg++;
                                                                $msg = ' msg1';
                                                                break;
                                                            } elseif ($endTimeClnt > $starttime_check && $endTimeClnt < $endtime_check) {
                                                                $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                                $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                                $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                                $errormsgloop[$error_msg]['weekday'] = $value;
                                                                $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                                $errormsgloop[$error_msg]['msg'] = 'msg2';

                                                                $canAddEdit = false;
                                                                $errorIndex++;
                                                                $error_msg++;
                                                                $msg = ' msg2';
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    if ($startTimeClnt > $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = '(end time should be greater than start time)';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' (end time should be greater than start time)';
                                                        break;
                                                    } elseif ($startTimeClnt == $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = '(start time & end time should not be same)';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' (start time & end time should not be same)';
                                                        break;
                                                    } elseif ($startTimeClnt == $startTimeDb && $endTimeClnt == $endTimeDb) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is already exists';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' already exists';
                                                        break;
                                                    } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $errormsgloop[$error_msg]['msg'] = 'msg1';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg1';
                                                        break;
                                                    } elseif ($endTimeClnt > $startTimeDb && $endTimeClnt <= $endTimeDb) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $errormsgloop[$error_msg]['msg'] = 'msg2';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg2';
                                                        break;
                                                    } elseif ($startTimeDb >= $startTimeClnt && $startTimeDb < $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $errormsgloop[$error_msg]['msg'] = 'msg3';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg3';
                                                        break;
                                                    } elseif ($endTimeDb > $startTimeClnt && $endTimeDb <= $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $errormsgloop[$error_msg]['msg'] = 'msg4';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg4';
                                                        break;
                                                    } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $errormsgloop[$error_msg]['msg'] = 'msg5';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg5';
                                                        break;
                                                    } else {
                                                        if ($canAddEdit == true) {
                                                            $nsc_add = $nsc++;
                                                            $newshiftcheck[$nsc_add]['start_time'] = $startTimeClnt;
                                                            $newshiftcheck[$nsc_add]['end_time'] = $endTimeClnt;
                                                            $newshiftcheck[$nsc_add]['keyclnt'] = $keyClnt;
                                                            $newshiftcheck[$nsc_add]['weekday'] = $value;
                                                        }
                                                    }
                                                }
                                            }
                                            if ($canAddEdit == true) {
                                                $insertShift[$newInsertIndex] = $this->saveShiftData($loginID, $keyClnt, $post, $value, $countshift = NULL);
                                                if (!empty($existing_shift)) {
                                                    $insertShift[$newInsertIndex]['AddScheduleForm']['id'] = $existing_shift->id;
                                                }
                                                $newInsertIndex++;
                                            }
                                        } else {
                                            $dbstart_time = date('Y-m-d');
                                            $nstart_time = $dbstart_time . ' ' . $post['AddScheduleForm']['start_time'][$keyClnt];
                                            $nend_time = $dbstart_time . ' ' . $post['AddScheduleForm']['end_time'][$keyClnt];
                                            $startTimeClnt = strtotime($nstart_time);
                                            $endTimeClnt = strtotime($nend_time);
                                            if ($startTimeClnt > $endTimeClnt) {
                                                $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                $errormsgloop[$error_msg]['weekday'] = $value;
                                                $errormsgloop[$error_msg]['message'] = '(end time should be greater than start time)';
                                                $canAddEdit = false;
                                                $errorIndex++;
                                                $error_msg++;
                                                $msg = ' (end time should be greater than start time)';
                                            }
                                            foreach ($newshiftcheck as $keyshift => $newshift) {
                                                $starttime_check = $newshift['start_time'];
                                                $endtime_check = $newshift['end_time'];
                                                $weekday_check = $newshift['weekday'];
                                                $keyClnt_check = $newshift['keyclnt'];
                                                if ($weekday_check == $value && $keyClnt != $keyClnt_check) {
                                                    if ($startTimeClnt == $starttime_check && $endTimeClnt == $endtime_check) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is already exists';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' already exists';
                                                        break;
                                                    } elseif ($startTimeClnt >= $starttime_check && $startTimeClnt < $endtime_check) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg1';
                                                        break;
                                                    } elseif ($endTimeClnt >= $starttime_check && $endTimeClnt <= $endtime_check) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg2';
                                                        break;
                                                    } elseif ($starttime_check >= $startTimeClnt && $starttime_check <= $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg3';
                                                        break;
                                                    } elseif ($endtime_check > $startTimeClnt && $endtime_check <= $endTimeClnt) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg4';
                                                        break;
                                                    } elseif ($startTimeClnt >= $starttime_check && $startTimeClnt < $endtime_check) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg5';
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($canAddEdit == true) {
                                                $nsc_add = $nsc++;
                                                $newshiftcheck[$nsc_add]['start_time'] = $startTimeClnt;
                                                $newshiftcheck[$nsc_add]['end_time'] = $endTimeClnt;
                                                $newshiftcheck[$nsc_add]['weekday'] = $value;
                                                $newshiftcheck[$nsc_add]['keyclnt'] = $keyClnt;
                                                $insertShift[$newInsertIndex] = $this->saveShiftData($loginID, $keyClnt, $post, $value, $countshift = NULL);
                                                if (!empty($existing_shift)) {
                                                    $insertShift[$newInsertIndex]['AddScheduleForm']['id'] = $existing_shift->id;
                                                }
                                                $newInsertIndex++;
                                            }
                                        }
                                    }
                                }
                                if ($canAddEdit == false) {
                                    break;
                                }
                            }
                            if ($canAddEdit == false) {
                                if (!empty($errormsgloop)) {

                                    $html = array();
                                    $weekdaysl = array();
                                    foreach ($errormsgloop as $msgloop) {
                                        $keyshifts = $msgloop['shift'];
                                        $weekdaysl[$keyshifts][] = $msgloop['weekday'];
                                        $html[$keyshifts] = 'Shift time ' . date('h:i a', $msgloop['start_time']) . ' - ' . date('h:i a', $msgloop['end_time']) . ' on ' . implode(',', $weekdaysl[$keyshifts]) . ' ' . $msgloop['message'];
                                    }
                                    Yii::$app->session->setFlash('shifterror', implode(" , ", $html));
                                } else {
                                    Yii::$app->session->setFlash('shifterror', "'Shift time invalid'");
                                }
                                return $this->render('shift/edit-shift', ['defaultCurrrentDay' => strtotime($date), 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'modelAddress' => $addAddress, 'userAdddressImages' => $imgModel, 'postData' => $post, 'disable_field' => $disable_field, 'shifts' => $shifts, 'addressImages' => $addressImages,]);
                            } elseif ($canAddEdit == true) {
                                $errores = array();
                                if ($addAddress->save()) {

                                    if (isset($post['deletedImages']) & $post['deletedImages'] != '') {
                                        $deletedImages = explode(',', $post['deletedImages']);
                                        foreach ($deletedImages as $key_del => $value_del) {
                                            $deleteAddressimg = UserAddressImages::findOne($value_del);
                                            if (!empty($deleteAddressimg)) {
                                                $deleteAddressimg->delete();
                                            }
                                        }
                                    }

                                    $files = UploadedFile::getInstances($imgModel, 'image');
                                    if (!empty($files)) {
                                        $imageUpload = DrsImageUpload::updateAddressImageListWeb($addAddress->id, $files);
                                    }
                                    if (!empty($insertShift)) {
                                        $oldshift_ids = array();
                                        $currentshift_ids = array();


                                        if (isset($post['shift_ids'])) {
                                            foreach ($post['shift_ids'] as $keyids => $valueids) {
                                                foreach ($valueids as $valueid) {
                                                    $oldshift_ids[] = $valueid;
                                                }
                                            }
                                        }

                                        foreach ($insertShift as $key => $value) {
                                            if (isset($value['AddScheduleForm']['id'])) {
                                                $currentshift_ids[] = $value['AddScheduleForm']['id'];
                                                $saveScheduleData = UserSchedule::findOne($value['AddScheduleForm']['id']);
                                                $old_insert = 1;
                                                $olddata['id'] = $saveScheduleData->id;
                                                $olddata['start_time'] = $saveScheduleData->start_time;
                                                $olddata['end_time'] = $saveScheduleData->end_time;
                                                $olddata['appointment_time_duration'] = $saveScheduleData->appointment_time_duration;
                                                $olddata['consultation_fees'] = $saveScheduleData->consultation_fees;
                                                $olddata['emergency_fees'] = $saveScheduleData->emergency_fees;
                                                $olddata['consultation_fees_discount'] = $saveScheduleData->consultation_fees;
                                                $olddata['emergency_fees_discount'] = $saveScheduleData->emergency_fees;
                                            } else {
                                                $saveScheduleData = new UserSchedule();
                                                $old_insert = 0;
                                            }
                                            $saveScheduleData->load(['UserSchedule' => $value['AddScheduleForm']]);
                                            $saveScheduleData->address_id = $addAddress->id;
                                            $saveScheduleData->start_time = strtotime($value['AddScheduleForm']['start_time']);
                                            $saveScheduleData->end_time = strtotime($value['AddScheduleForm']['end_time']);
                                            if ($addAddress->user_id == $loginID) {
                                                
                                            } else {
                                                $saveScheduleData->shift_belongs_to = 'hospital';
                                                $saveScheduleData->hospital_id = $addAddress->user_id;
                                            }

                                            if ($saveScheduleData->save()) {
                                                if ($old_insert == 1) {
                                                    $checkandcleardata = Drspanel::oldShiftsDataUpdate($olddata, $value);
                                                }
                                            } else {
                                                $errores[$key] = $saveScheduleData->getErrors();
                                            }
                                        }
                                    }
                                    if (!empty($oldshift_ids)) {
                                        foreach ($oldshift_ids as $id_check) {
                                            if (in_array($id_check, $currentshift_ids)) {
                                                
                                            } else {
                                                //delete shift with all slots & its respective appointments to be cancelled
                                                $deleteShiftWithAppointment = DrsPanel::deleteShiftWithAppointments($id_check);
                                            }
                                        }
                                    }
                                }
                                $attender_shifts = DrsPanel::editShiftToAttender(Yii::$app->user->id);
                                $shifts_keys = Drspanel::addUpdateShiftKeys(Yii::$app->user->id);
                                $updateStatusShift = DrsPanel::userShiftsStatus(Yii::$app->user->id);
                                $loadtodayshifts = DrsPanel::getScheduleShifts(Yii::$app->user->id, date('Y-m-d'));
                                Yii::$app->session->setFlash('success', "'Shift Updated Successfully'");
                                return $this->redirect(['my-shifts?id=' . $user_id]);
                            } else {
                                
                            }
                        } else {
                            Yii::$app->session->setFlash('error', "'Please Select days'");
                            return $this->redirect(['my-shifts?id=' . $user_id]);
                        }
                    } else {
                        if ($addAddress->save()) {

                            $deleteschedules = UserSchedule::find()->where(['address_id' => $addAddress->id])->all();

                            foreach ($deleteschedules as $schedulesc) {
                                $id_check = $schedulesc->id;
                                $deleteShiftWithAppointment = DrsPanel::deleteShiftWithAppointments($id_check);
                            }

                            if (isset($post['deletedImages']) & $post['deletedImages'] != '') {
                                $deletedImages = explode(',', $post['deletedImages']);
                                foreach ($deletedImages as $key_del => $value_del) {
                                    $deleteAddressimg = UserAddressImages::findOne($value_del);
                                    if (!empty($deleteAddressimg)) {
                                        $deleteAddressimg->delete();
                                    }
                                }
                            }

                            $files = UploadedFile::getInstances($imgModel, 'image');
                            if (!empty($files)) {
                                $imageUpload = DrsImageUpload::updateAddressImageListWeb($addAddress->id, $files);
                            }

                            $attender_shifts = DrsPanel::editShiftToAttender(Yii::$app->user->id);
                            $shifts_keys = Drspanel::addUpdateShiftKeys(Yii::$app->user->id);
                            $updateStatusShift = DrsPanel::userShiftsStatus(Yii::$app->user->id);
                            $loadtodayshifts = DrsPanel::getScheduleShifts(Yii::$app->user->id, date('Y-m-d'));

                            Yii::$app->session->setFlash('success', "'Shift Updated'");
                            return $this->redirect(['my-shifts?id=' . $user_id]);
                        } else {
                            Yii::$app->session->setFlash('error', "'Please try again'");
                        }
                    }
                }
            }
        }
        return $this->render('shift/edit-shift', ['defaultCurrrentDay' => strtotime($date), 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'shifts' => $shifts, 'modelAddress' => $address, 'userAdddressImages' => $imgModel, 'addressImages' => $addressImages, 'disable_field' => $disable_field]);
    }

    public function actionCityList() {
        $city_list = [];
        $form = ActiveForm::begin(['id' => 'shiftform']);
        if (Yii::$app->request->post()) {
            $modelAddress = new UserAddress();
            $post = Yii::$app->request->post();
            $rst = DrsPanel::getCitiesList($post['state_id'], 'name');
            foreach ($rst as $key => $item) {
                $city_list[$item->id] = $item->name;
            }
            echo $this->renderAjax('/doctor/shift/_shift_city_field', ['form' => $form, 'city_list' => $city_list, 'modelAddress' => $modelAddress]);
            exit();
        }
    }

    public function actionCityAreaList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $form = ActiveForm::begin(['id' => 'shiftform']);
            $post = Yii::$app->request->post();
            $area_list = [];
            if (isset($post['id']) && !empty($post['id'])) {
                $modelAddress = new UserAddress();
                $areas = Drspanel::getCityAreasList($post['id']);
                $all_active_values = array();
                foreach ($areas as $area) {
                    $area_list[$area->name] = $area->name;
                }

                /* $area=$modelAddress->area;
                  if(!empty($area)){
                  if(!in_array($area,$all_active_values)){
                  $checkValue=Areas::find()->where(['city_id'=>$post['id'],'name'=>$area])->one();
                  if(!empty($checkValue)){
                  $area_list[$checkValue->id] = $checkValue->name;
                  }
                  }

                  } */

                echo $this->renderAjax('/doctor/shift/_shift_area_field', ['form' => $form, 'area_list' => $area_list, 'modelAddress' => $modelAddress]);
                exit();
            }
        }
    }

    public function actionMapAreaList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $lat = '26.900470';
            $lng = '75.828670';
            if (isset($post['lat']) && !empty($post['lat'])) {
                $lat = $post['lat'];
            }
            if (isset($post['lng']) && !empty($post['lng'])) {
                $lng = $post['lng'];
            }

            $area = $post['area'];
            $city = $post['city'];
            $checkValue = \common\models\Areas::find()->where(['city_id' => $city, 'name' => $area])->one();
            if (empty($lat) && empty($lng) && !empty($checkValue)) {
                $lat = $checkValue->lat;
                $lng = $checkValue->lng;
            }
            echo $this->renderAjax('/doctor/shift/_map_location', ['lat' => $lat, 'lng' => $lng]);
            exit();
        }
    }

    public function actionEditShift_old($id, $user_id) {
        $loginID = $user_id;
        $address_id = $id;
        $address = UserAddress::findOne($address_id);
        $addressImages = UserAddressImages::find()->where(['address_id' => $address_id])->all();
        $imgModel = new UserAddressImages();

        if ($address->user_id == $loginID) {
            $disable_field = 0;
        } else {
            $disable_field = 1;
        }

        $date = date('Y-m-d');
        $week_array = DrsPanel::getWeekShortArray();
        $availibility_days = array();

        foreach ($week_array as $week) {
            $availibility_days[] = $week;
        }
        $newShift = new AddScheduleForm();
        $newShift->user_id = $loginID;
        $shifts = DrsPanel::getShiftListByAddress($loginID, $address_id);

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            //$this->pr($post);die;
            if (isset($post['UserAddress'])) {
                $user = User::findOne(['id' => $loginID]);
                if (!empty($user)) {
                    $addAddress = UserAddress::findOne($address_id);
                    if (empty($addAddress)) {
                        Yii::$app->session->setFlash('success', 'Address Not Valid');
                        return $this->redirect(['/doctor/my-shifts']);
                    }
                    $data['UserAddress']['user_id'] = $loginID;
                    $data['UserAddress']['name'] = $post['UserAddress']['name'];
                    $data['UserAddress']['city'] = $post['UserAddress']['city'];
                    $data['UserAddress']['state'] = $post['UserAddress']['state'];
                    $data['UserAddress']['address'] = $post['UserAddress']['address'];
                    $data['UserAddress']['area'] = $post['UserAddress']['area'];
                    $data['UserAddress']['phone'] = $post['UserAddress']['phone'];
                    $data['UserAddress']['is_request'] = 0;
                    $addAddress->load($data);
                    if ((isset($post['AddScheduleForm']['weekday']) && !empty($post['AddScheduleForm']['weekday']))) {

                        $dayShifts = $post['AddScheduleForm']['weekday'];
                        $canAddEdit = true;
                        $msg = ' invalid';
                        $errorIndex = 0;
                        $newInsertIndex = 0;
                        $errorShift = array();
                        $insertShift = array();
                        $shiftcount = $post['AddScheduleForm']['start_time'];
                        foreach ($dayShifts as $key => $day_shift) {
                            if (!empty($day_shift)) {
                                $dayShiftsFromDb = UserSchedule::find()->where(['user_id' => $loginID])->andwhere(['weekday' => $day_shift[0]])->all();

                                if (!empty($dayShiftsFromDb)) {
                                    foreach ($shiftcount as $keyClnt => $shift_v) {
                                        foreach ($dayShiftsFromDb as $keydb => $dayshiftValuedb) {
                                            // print_r($post['AddScheduleForm']['id']);die;
                                            $newshiftInsertId = isset($id) ? $id : '';
                                            if (isset($newshiftInsertId)) {
                                                if ($newshiftInsertId == $dayshiftValuedb->address_id) {
                                                    continue;
                                                }
                                            }
                                            $dbstart_time = date('Y-m-d', $dayshiftValuedb->start_time);
                                            $dbend_time = date('Y-m-d', $dayshiftValuedb->end_time);
                                            $nstart_time = $dbstart_time . ' ' . $post['AddScheduleForm']['start_time'][$keyClnt];
                                            $nend_time = $dbend_time . ' ' . $post['AddScheduleForm']['end_time'][$keyClnt];
                                            $startTimeClnt = strtotime($nstart_time);
                                            $endTimeClnt = strtotime($nend_time);
                                            $startTimeDb = $dayshiftValuedb->start_time;
                                            $endTimeDb = $dayshiftValuedb->end_time;

                                            if ($startTimeClnt >= $startTimeDb && $startTimeClnt <= $endTimeDb) {
                                                $canAddEdit = false;
                                                $errorIndex++;
                                                $msg = ' already exists';
                                            } elseif ($endTimeClnt >= $startTimeDb && $endTimeClnt <= $endTimeDb) {
                                                $canAddEdit = false;
                                                $errorIndex++;
                                            } elseif ($startTimeDb >= $startTimeClnt && $startTimeDb <= $endTimeClnt) {
                                                $canAddEdit = false;
                                                $errorIndex++;
                                            } elseif ($endTimeDb >= $startTimeClnt && $endTimeDb <= $endTimeClnt) {
                                                $canAddEdit = false;
                                                $errorIndex++;
                                            } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                                $canAddEdit = false;
                                                $errorIndex++;
                                            }

                                            if ($canAddEdit == false) {
                                                Yii::$app->session->setFlash('shifterror', 'Shift ' . date('h:i a', $startTimeClnt) . ' - ' . date('h:i a', $endTimeClnt) . ' on ' . $day_shift[0] . $msg);
                                                return $this->render('shift/edit-shift', ['defaultCurrrentDay' => strtotime($date), 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'modelAddress' => $addAddress, 'userAdddressImages' => $imgModel, 'postData' => $post, 'disable_field' => $disable_field, 'shifts' => $shifts]);
                                            }
                                        }
                                        $shift['AddScheduleForm']['user_id'] = $loginID;
                                        $shift['AddScheduleForm']['id'] = $post['AddScheduleForm']['id'];
                                        $shift['AddScheduleForm']['start_time'] = $shift_v;
                                        $shift['AddScheduleForm']['end_time'] = $post['AddScheduleForm']['end_time'][$keyClnt];
                                        $shift['AddScheduleForm']['appointment_time_duration'] = $post['AddScheduleForm']['appointment_time_duration'][$keyClnt];
                                        $shift['AddScheduleForm']['weekday'] = $day_shift;
                                        $time1 = strtotime($shift['AddScheduleForm']['start_time']);
                                        $time2 = strtotime($shift['AddScheduleForm']['end_time']);
                                        $difference = abs($time2 - $time1) / 60;
                                        $patient_limit = $difference / $shift['AddScheduleForm']['appointment_time_duration'];
                                        $shift['AddScheduleForm']['patient_limit'] = (int) $patient_limit;
                                        $shift['AddScheduleForm']['consultation_fees'] = (isset($post['AddScheduleForm']['consultation_fees'][$keyClnt]) && ($post['AddScheduleForm']['consultation_fees'][$keyClnt] > 0) ) ? $post['AddScheduleForm']['consultation_fees'][$keyClnt] : 0;
                                        $shift['AddScheduleForm']['emergency_fees'] = (!empty($post['AddScheduleForm']['emergency_fees'][$keyClnt]) && ($post['AddScheduleForm']['emergency_fees'][$keyClnt] > 0)) ? $post['AddScheduleForm']['emergency_fees'][$keyClnt] : 0;
                                        $shift['AddScheduleForm']['consultation_fees_discount'] = (isset($post['AddScheduleForm']['consultation_fees_discount'][$keyClnt])) ? $post['AddScheduleForm']['consultation_fees_discount'][$keyClnt] : 0;
                                        $shift['AddScheduleForm']['emergency_fees_discount'] = (isset($post['AddScheduleForm']['emergency_fees_discount'][$keyClnt])) ? $post['AddScheduleForm']['emergency_fees_discount'][$keyClnt] : 0;
                                    }
                                } else {
                                    foreach ($shiftcount as $keyClnt => $shift_v) {
                                        $shift['AddScheduleForm']['user_id'] = $loginID;
                                        $shift['AddScheduleForm']['id'] = $post['AddScheduleForm']['id'];
                                        $shift['AddScheduleForm']['start_time'] = $shift_v;
                                        $shift['AddScheduleForm']['end_time'] = $post['AddScheduleForm']['end_time'][$keyClnt];
                                        $shift['AddScheduleForm']['appointment_time_duration'] = $post['AddScheduleForm']['appointment_time_duration'][$keyClnt];
                                        $shift['AddScheduleForm']['weekday'] = $day_shift;
                                        $time1 = strtotime($shift['AddScheduleForm']['start_time']);
                                        $time2 = strtotime($shift['AddScheduleForm']['end_time']);
                                        $difference = abs($time2 - $time1) / 60;
                                        $patient_limit = $difference / $shift['AddScheduleForm']['appointment_time_duration'];
                                        $shift['AddScheduleForm']['patient_limit'] = (int) $patient_limit;
                                        $shift['AddScheduleForm']['consultation_fees'] = (isset($post['AddScheduleForm']['consultation_fees'][$keyClnt]) && ($post['AddScheduleForm']['consultation_fees'][$keyClnt] > 0) ) ? $post['AddScheduleForm']['consultation_fees'][$keyClnt] : 0;
                                        $shift['AddScheduleForm']['emergency_fees'] = (!empty($post['AddScheduleForm']['emergency_fees'][$keyClnt]) && ($post['AddScheduleForm']['emergency_fees'][$keyClnt] > 0)) ? $post['AddScheduleForm']['emergency_fees'][$keyClnt] : 0;
                                        $shift['AddScheduleForm']['consultation_fees_discount'] = (isset($post['AddScheduleForm']['consultation_fees_discount'][$keyClnt])) ? $post['AddScheduleForm']['consultation_fees_discount'][$keyClnt] : 0;
                                        $shift['AddScheduleForm']['emergency_fees_discount'] = (isset($post['AddScheduleForm']['emergency_fees_discount'][$keyClnt])) ? $post['AddScheduleForm']['emergency_fees_discount'][$keyClnt] : 0;
                                    }
                                }
                            } else {
                                Yii::$app->session->setFlash('error', 'Please Select days');
                                return $this->redirect(['/doctor/my-shifts', 'id' => $loginID]);
                            }
                        }
                        if ($canAddEdit == true) {
                            if ($addAddress->save()) {
                                $imageUpload = '';
                                if (isset($_FILES['image'])) {
                                    $imageUpload = DrsImageUpload::updateAddressImage($addAddress->id, $_FILES);
                                }
                                if (isset($_FILES['images'])) {
                                    $imageUpload = DrsImageUpload::updateAddressImageList($addAddress->id, $_FILES);
                                }
                                $shiftDay = $shift['AddScheduleForm']['weekday'];
                                $output = array_diff($shiftDay, array_keys($post['shift_ids']));

                                foreach ($shiftDay as $key => $weekdayvalue) {

                                    foreach ($shift as $key => $value) {

                                        $saveScheduleData1 = UserSchedule::find()->where(['id' => $value['id']])->all();


                                        if (in_array($weekdayvalue, $saveScheduleData1)) {
                                            $appointment_index = 0;
                                            $appointmentCount = 0;
                                            $notEditedShift = array();
                                            if (isset($value['id'])) {
                                                $saveScheduleData = UserSchedule::findOne($value['id']);

                                                if (!empty($saveScheduleData)) {
                                                    $appointmentCount = UserAppointment::find()->where(['doctor_id' => $value['user_id'], 'schedule_id' => $value['id'], 'status' => 'available'])->count();
                                                    if ($appointmentCount > 0) {
                                                        $value['appointment_count'] = $appointmentCount;
                                                        $notEditedShift[$appointment_index] = $value;
                                                        $appointment_index++;
                                                    }
                                                    $value['weekday'] = $weekdayvalue;
                                                    $saveScheduleData->load(['UserSchedule' => $value]);
                                                    $saveScheduleData->address_id = $addAddress->id;
                                                    $saveScheduleData->start_time = strtotime($value['start_time']);
                                                    $saveScheduleData->end_time = strtotime($value['end_time']);
                                                    $saveScheduleData->weekday = $weekdayvalue;
                                                    if ($saveScheduleData->save()) {
                                                        
                                                    } else {
                                                        $this->pr($saveScheduleData->getErrors());
                                                        die;
                                                    }
                                                    Yii::$app->session->setFlash('success', 'Shift edited successfully');
                                                    return $this->redirect(['/doctor/my-shifts', 'id' => $loginID]);
                                                }
                                            }
                                        } else {
                                            $saveScheduleData = new UserSchedule();
                                            $value['weekday'] = $weekdayvalue;
                                            $saveScheduleData->load(['UserSchedule' => $value]);
                                            $saveScheduleData->address_id = $addAddress->id;
                                            $saveScheduleData->start_time = strtotime($value['start_time']);
                                            $saveScheduleData->end_time = strtotime($value['end_time']);
                                            $saveScheduleData->weekday = $weekdayvalue;
                                            if ($saveScheduleData->save()) {
                                                
                                            } else {
                                                $this->pr($saveScheduleData->getErrors());
                                                die;
                                            }
                                            Yii::$app->session->setFlash('success', 'Shift edited successfully');
                                            return $this->redirect(['/doctor/my-shifts', 'id' => $loginID]);
                                        }
                                    }
                                }die;
                            }
                        }
                    } else {
                        Yii::$app->session->setFlash('error', 'Please Select days');
                        return $this->redirect(['/doctor/my-shifts', '$loginID']);
                    }
                }
            }
        }
        return $this->render('shift/_editShift', ['defaultCurrrentDay' => strtotime($date), 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'shifts' => $shifts, 'modelAddress' => $address, 'userAdddressImages' => $imgModel, 'addressImages' => $addressImages, 'disable_field' => $disable_field]);
    }

    public function actionAjaxNewShift() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $id = $post['id'];
            $model = $this->findModel($id);
            $userProfile = UserProfile::findOne(['user_id' => $id]);
            $query = UserAddress::find()->where(['user_id' => $id]);
            $addressProvider = new ActiveDataProvider([
                'query' => $query,
            ]);
            $addressList = DrsPanel::doctorHospitalList($id);

            $userShift = UserSchedule::find()->where(['user_id' => $id])->all();
            $week_array = DrsPanel::getWeekArray();
            $availibility_days = array();
            foreach ($week_array as $week) {
                $availibility_days[] = $week;
            }
            $newShift = new AddScheduleForm();
            $newShift->user_id = $userProfile->user_id;
            $newShift->weekday = $availibility_days;
            $form = ActiveForm::begin();
            echo $this->renderAjax('_newShift', [
                'model' => $newShift, 'listaddress' => $addressList['listaddress'], 'form' => $form,
            ]);
            exit;
        }
        echo 'error';
        exit;
    }

    /* Today Timing */

    public function actionDayShifts($id) {
        $user_id = $id;
        $doctor = User::findOne($user_id);
        $date = date('Y-m-d');
        $getShists = DrsPanel::getBookingShifts($user_id, $date, $user_id);
        return $this->render('day-shifts', ['defaultCurrrentDay' => strtotime($date), 'shifts' => $getShists, 'doctor' => $doctor, 'userid' => $id]);
    }

    /* Today Timing Shift Status Acitve */

    public function actionUpdateShiftStatus() {
        $response["status"] = 0;
        $response["error"] = true;
        $response['message'] = 'You have do not permission.';
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $params['booking_closed'] = $post['status'];
            $params['doctor_id'] = $post['userid'];
            $params['date'] = date('Y-m-d', $post['date']);
            $params['schedule_id'] = $post['id'];
            $response = DrsPanel::updateShiftStatus($params);
            if (empty($response)) {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'You have do not permission.';
            }
        }
        return json_encode($response);
    }

    public function actionAjaxAddressList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $loginID = $post['user_id'];
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));
            $appointments = DrsPanel::getBookingShifts($loginID, $date, $loginID);
            echo $this->renderAjax('_address-with-shift', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'userid' => $loginID]);
            exit();
        }
    }

    public function actionAjaxDailyShift() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user_id = $post['id'];
            $date = $post['date'];
            $week = DrsPanel::getDateWeekDay($date);
            $shift_details = $this->setDateShiftData($user_id, $date);
            echo $this->renderAjax('_dailylimit', [
                'model' => $this->findModel($user_id), 'userShift' => $shift_details['userShift'], 'listaddress' => $shift_details['listaddress'], 'date' => $date, 'shifts_available' => $shift_details['shifts_available']
            ]);
            exit;
        }
        echo 'error';
        exit;
    }

    public function actionAddAppointments($id) {
        $date = date('Y-m-d');
        $user_id = $id;
        $week = DrsPanel::getDateWeekDay($date);
        $keys_avail = array();
        $addAppointment = new AddAppointmentForm();


        $totalshifts = DrsPanel::getDateShifts($user_id, $date);
        $shifts = array();
        if (!empty($totalshifts) && isset($totalshifts['shifts'])) {
            $shifts = $totalshifts['shifts'];
        }
        $shifts_available = 0;
        if (isset($shifts[UserSchedule::SHIFT_MORNING]) && !empty($shifts[UserSchedule::SHIFT_MORNING])) {
            $shifts_available = 1;
            $morningSchedule = $shifts[UserSchedule::SHIFT_MORNING];
            $keys_avail[UserSchedule::SHIFT_MORNING] = ucfirst(UserSchedule::SHIFT_MORNING) . ' (' . $morningSchedule['time'] . ')';
        }
        if (isset($shifts[UserSchedule::SHIFT_AFTERNOON]) && !empty($shifts[UserSchedule::SHIFT_AFTERNOON])) {
            $shifts_available = 1;
            $afternoonSchedule = $shifts[UserSchedule::SHIFT_AFTERNOON];
            $keys_avail[UserSchedule::SHIFT_AFTERNOON] = ucfirst(UserSchedule::SHIFT_AFTERNOON) . ' (' . $afternoonSchedule['time'] . ')';
        }
        if (isset($shifts[UserSchedule::SHIFT_EVENING]) && !empty($shifts[UserSchedule::SHIFT_EVENING])) {
            $shifts_available = 1;
            $eveningSchedule = $shifts[UserSchedule::SHIFT_EVENING];
            $keys_avail[UserSchedule::SHIFT_EVENING] = ucfirst(UserSchedule::SHIFT_EVENING) . ' (' . $eveningSchedule['time'] . ')';
        }

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctorProfile = UserProfile::findOne(['user_id' => $user_id]);
            $params = $post['AddAppointmentForm'];
            if (!empty($doctorProfile)) {
                $type = UserAppointment::TYPE_OFFLINE;
                $book_for = UserAppointment::BOOK_FOR_SELF;
                $appointment_date = $params['date'];
                $appointment_shift = $params['slot'];
                $appointment_type = UserAppointment::APPOINTMENT_TYPE_CONSULTATION;

                $dateDay = DrsPanel::getDateShifts($user_id, $appointment_date);
                if (!empty($dateDay)) {
                    if (isset($dateDay['shifts']) && isset($dateDay['shifts'][$appointment_shift]) && !empty($dateDay['shifts'][$appointment_shift])) {
                        $appointment_time = $dateDay['shifts'][$appointment_shift]['time'];
                        $doctor_fees = $dateDay['shifts'][$appointment_shift]['consultation_fees'];
                        $doctor_address = $dateDay['shifts'][$appointment_shift]['address'];
                        $user_name = $params['name'];
                        $user_age = $params['age'];
                        $user_phone = $params['phone'];
                        $user_gender = $params['gender'];
                        if (isset($params['address'])) {
                            $user_address = $params['address'];
                        } else {
                            $user_address = '';
                        }
                        $payment_type = $params['payment_type'];

                        $data['UserAppointment']['type'] = $type;
                        $data['UserAppointment']['appointment_type'] = $appointment_type;
                        $data['UserAppointment']['user_id'] = 0;
                        $data['UserAppointment']['doctor_id'] = $user_id;
                        $data['UserAppointment']['appointment_date'] = $appointment_date;
                        $data['UserAppointment']['appointment_shift'] = $appointment_shift;
                        $data['UserAppointment']['appointment_time'] = $appointment_time;
                        $data['UserAppointment']['book_for'] = $book_for;
                        $data['UserAppointment']['user_name'] = $user_name;
                        $data['UserAppointment']['user_age'] = $user_age;
                        $data['UserAppointment']['user_phone'] = $user_phone;
                        $data['UserAppointment']['user_gender'] = $user_gender;
                        $data['UserAppointment']['user_address'] = $user_address;
                        $data['UserAppointment']['payment_type'] = $payment_type;
                        $data['UserAppointment']['doctor_name'] = $doctorProfile->name;
                        $data['UserAppointment']['doctor_fees'] = $doctor_fees;
                        $data['UserAppointment']['doctor_address'] = $doctor_address;
                        $data['UserAppointment']['status'] = 'pending';
                        $addAppointment = DrsPanel::addAppointment($data, 'doctor');
                        if ($addAppointment['type'] == 'model_error') {
                            Yii::$app->session->setFlash('alert', [
                                'options' => ['class' => 'alert-danger'],
                                'body' => Yii::t('backend', 'Please try again!')
                            ]);
                        } else {
                            Yii::$app->session->setFlash('alert', [
                                'options' => ['class' => 'alert-success'],
                                'body' => Yii::t('backend', 'Appointment added successfully')
                            ]);
                            return $this->redirect(['view', 'id' => $user_id]);
                        }
                    } else {
                        Yii::$app->session->setFlash('alert', [
                            'options' => ['class' => 'alert-danger'],
                            'body' => Yii::t('backend', 'Can not add booking for this shift')
                        ]);
                    }
                } else {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-danger'],
                        'body' => Yii::t('backend', 'Can not add booking for this shift & date')
                    ]);
                }
            } else {
                Yii::$app->session->setFlash('alert', [
                    'options' => ['class' => 'alert-danger'],
                    'body' => Yii::t('backend', 'Doctor detail not found')
                ]);
            }
        }

        return $this->render('add_appointments', [
                    'model' => $this->findModel($id), 'userShift' => $shifts, 'date' => $date, 'shifts_available' => $shifts_available, 'addAppointment' => $addAppointment, 'keys_avail' => $keys_avail
        ]);
    }

    public function actionAjaxAppointments() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user_id = $post['id'];
            $date = $post['date'];
            $week = DrsPanel::getDateWeekDay($date);
            $keys_avail = array();

            $addAppointment = new AddAppointmentForm();


            $totalshifts = DrsPanel::getDateShifts($user_id, $date);
            $shifts = array();
            if (!empty($totalshifts) && isset($totalshifts['shifts'])) {
                $shifts = $totalshifts['shifts'];
            }
            $shifts_available = 0;
            if (isset($shifts[UserSchedule::SHIFT_MORNING]) && !empty($shifts[UserSchedule::SHIFT_MORNING])) {
                $shifts_available = 1;
                $morningSchedule = $shifts[UserSchedule::SHIFT_MORNING];
                $keys_avail[UserSchedule::SHIFT_MORNING] = ucfirst(UserSchedule::SHIFT_MORNING) . ' (' . $morningSchedule['time'] . ')';
            }
            if (isset($shifts[UserSchedule::SHIFT_AFTERNOON]) && !empty($shifts[UserSchedule::SHIFT_AFTERNOON])) {
                $shifts_available = 1;
                $afternoonSchedule = $shifts[UserSchedule::SHIFT_AFTERNOON];
                $keys_avail[UserSchedule::SHIFT_AFTERNOON] = ucfirst(UserSchedule::SHIFT_AFTERNOON) . ' (' . $afternoonSchedule['time'] . ')';
            }
            if (isset($shifts[UserSchedule::SHIFT_EVENING]) && !empty($shifts[UserSchedule::SHIFT_EVENING])) {
                $shifts_available = 1;
                $eveningSchedule = $shifts[UserSchedule::SHIFT_EVENING];
                $keys_avail[UserSchedule::SHIFT_EVENING] = ucfirst(UserSchedule::SHIFT_EVENING) . ' (' . $eveningSchedule['time'] . ')';
            }

            echo $this->renderAjax('_todayAppointment', [
                'model' => $this->findModel($user_id), 'userShift' => $shifts, 'date' => $date, 'shifts_available' => $shifts_available, 'addAppointment' => $addAppointment, 'keys_avail' => $keys_avail
            ]);
            exit;
        }
        echo 'error';
        exit;
    }

    public function actionAppointments($id) {
        $searchModel = new UserAppointmentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $id);
        return $this->render('appointments', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
                    'model' => $this->findModel($id)
        ]);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionEducationList($user_id) {

        /* $searchModel = new UserEducationsSearch();
          $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$user_id);

          return $this->renderAjax('education-list', [
          'searchModel' => $searchModel,
          'dataProvider' => $dataProvider,
          ]); */
        $edu_list = UserEducations::find()->where(['user_id' => $user_id])->all();
        return $this->renderAjax('education-list', ['edu_list' => $edu_list]);
    }

    public function actionEducationForm($user_id, $edu_id = NULL) {
        $newmodel = 0;
        $education = UserEducations::findOne($edu_id);
        $msg = 'Added';

        $degrees = MetaValues::find()->where(['key' => 2, 'status' => 1])->all();
        $degreelist = array();
        foreach ($degrees as $d_key => $degree) {
            $degreelist[$degree->value] = $degree->label;
        }

        if (empty($education)) {
            $model = new UserEducations();
            $newmodel = 1;
        } else {
            $listdegree = trim($education->education);
            if ($listdegree != '') {
                $checkValue = MetaValues::find()->where(['key' => 2, 'value' => $listdegree])->one();
                if (!empty($checkValue)) {
                    $degreelist[$checkValue->value] = $checkValue->label;
                }
            }
            $model = new UserEducations();
            $model->id = $education['id'];
            $model->user_id = $education['user_id'];
            $model->start = date('Y', $education['start']);
            $model->end = date('Y', $education['end']);
            $model->education = trim($education->education);
            $model->collage_name = trim($education->collage_name);
        }
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $post['UserEducations']['user_id'] = $user_id;
            if ($edu_id) {
                $post['UserEducations']['id'] = $edu_id;
                $msg = 'Updated';
            }
            $modelUpdate = UserEducations::upsert($post);
            if (count($modelUpdate) > 0) {
                Yii::$app->session->setFlash('alert', [
                    'options' => ['class' => 'alert-success'],
                    'body' => Yii::t('backend', 'Doctor Education ' . $msg . '.')
                ]);
                return $this->redirect(['view', 'id' => $user_id]);
            } else {
                return false;
            }
        }
        return $this->renderAjax('education-form', ['model' => $model, 'newmodel' => $newmodel, 'degreelist' => $degreelist]);
    }

    public function actionEducationUpsert() {

        if (Yii::$app->request->isAjax && Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $model = UserEducations::upsert($post);
            return (count($model) > 0) ? true : false;
        }
        return false;
    }

    public function actionExperienceList($user_id) {

        /* $searchModel = new UserEducationsSearch();
          $dataProvider = $searchModel->search(Yii::$app->request->queryParams,$user_id);

          return $this->renderAjax('education-list', [
          'searchModel' => $searchModel,
          'dataProvider' => $dataProvider,
          ]); */
        $edu_list = UserExperience::find()->where(['user_id' => $user_id])->all();
        return $this->renderAjax('experience-list', ['edu_list' => $edu_list]);
    }

    public function actionExperienceForm($user_id, $exp_id = NULL) {
        $newmodel = 0;
        $experience = UserExperience::findOne($exp_id);
        $msg = 'Added';
        if (empty($experience)) {
            $model = new UserExperience();
            $newmodel = 1;
        } else {
            $model = new UserExperience();
            $model->id = $experience['id'];
            $model->user_id = $experience['user_id'];
            $model->start = date('Y', $experience['start']);
            $model->end = date('Y', $experience['end']);
            $model->hospital_name = trim($experience->hospital_name);
        }

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $post['UserExperience']['user_id'] = $user_id;
            if ($exp_id) {
                $post['UserExperience']['id'] = $exp_id;
                $msg = 'Updated';
            }
            $modelUpdate = UserExperience::upsert($post);
            if (count($modelUpdate) > 0) {
                Yii::$app->session->setFlash('alert', [
                    'options' => ['class' => 'alert-success'],
                    'body' => Yii::t('backend', 'Doctor Experience ' . $msg . '.')
                ]);
                return $this->redirect(['view', 'id' => $user_id]);
            } else {
                return false;
            }
        }
        return $this->renderAjax('experience-form', ['model' => $model, 'newmodel' => $newmodel]);
    }

    public function actionRequestedHospital($id) {

        $doctor_id = $id;

        $lists = UserRequest::find()->andWhere(['request_to' => $doctor_id])
                        ->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        if (Yii::$app->request->post()) {
            $postData = Yii::$app->request->post();
        }
        return $this->render('requested-hospital', ['lists' => $lists, 'doctor_id' => $doctor_id]);
    }

    public function actionUpdateStatus($doctor_id = NULL) {
        if (Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $post['groupid'] = Groups::GROUP_HOSPITAL;
            $type = 'Add';
            $modelUpdate = UserRequest::updateStatus($post, $type);
            if (count($modelUpdate) > 0) {
                Yii::$app->session->setFlash('success', "'Request accepted'");
                return $this->redirect(['doctor/requested-hospital', 'id' => $post['request_to']]);
            } else {
                Yii::$app->session->setFlash('error', "'Sorry request couldnot accepted'");
            }
        }
        exit;
        return Null;
    }

    public function actionRequestSend() {

        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $model = $this->findModel($post['request_from']);
            if (count($model) > 0) {
                $post['groupid'] = $model->groupid;
                $result = UserRequest::updateStatus($post, 'edit');
                if ($result) {
                    $confirm_hospital = UserAddress::find()->where(['is_register' => 1])->andWhere(['user_id' => $post['request_from']])->one();
                    if ($confirm_hospital) {
                        $confirm_hospital->is_register = 2;
                        $confirm_hospital->save();
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function actionGetEditLivemodal() {
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            if (isset($post['LiveStatus'])) {
                $user_id = $post['userid'];
                $userProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
                $model = User::findOne($user_id);
                $model->admin_status = $post['LiveStatus']['status'];
                if ($model->save()) {

                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-success'],
                        'body' => Yii::t('backend', 'Profile status updated!')
                    ]);
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-danger'],
                        'body' => Yii::t('backend', 'Status not updated!')
                    ]);
                    return $this->redirect(['index']);
                }
            } else {
                $user_id = $post['id'];
                $userProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
                $user = User::findOne($user_id);

                echo $this->renderAjax('/common-view/_edit_live_status', [
                    'userProfile' => $userProfile, 'user' => $user
                ]);
                exit();
            }
        }
    }

    public function actionUpdateLivemodal() {
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();


            $user_id = $post['id'];
            $userProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
            $model = User::findOne($user_id);
            $model->admin_status = $post['val'];
            if ($model->save()) {
                echo 'success';
                exit();
            } else {
                Yii::$app->session->setFlash('alert', [
                    'options' => ['class' => 'alert-danger'],
                    'body' => Yii::t('backend', 'Status not updated!')
                ]);
                return $this->redirect(['index']);
            }
        }
    }

    public function actionGetPlanLivemodal() {
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            if (isset($post['PlanStatus'])) {
                $user_id = $post['userid'];
                $userProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
                $model = User::findOne($user_id);
                $model->user_plan = $post['PlanStatus']['status'];
                if ($model->save()) {

                    $user_plan = UserPlanDetail::find()->where(['user_id' => $user_id])->one();
                    if (empty($user_plan)) {
                        $user_plan = new UserPlanDetail();
                    }
                    $user_plan->user_id = $user_id;
                    $user_plan->from_date = $post['UserPlanDetail']['from_date'];
                    $user_plan->to_date = $post['UserPlanDetail']['to_date'];
                    $user_plan->status = 'pending';
                    $user_plan->save();

                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-success'],
                        'body' => Yii::t('backend', 'Profile plan updated!')
                    ]);
                    return $this->redirect(['index']);
                } else {
                    Yii::$app->session->setFlash('alert', [
                        'options' => ['class' => 'alert-danger'],
                        'body' => Yii::t('backend', 'Plan not updated!')
                    ]);
                    return $this->redirect(['index']);
                }
            } else {
                $user_id = $post['id'];
                $userProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
                $user = User::findOne($user_id);
                $user_plan = UserPlanDetail::find()->where(['user_id' => $user_id])->one();
                if (empty($user_plan)) {
                    $user_plan = new UserPlanDetail();
                }
                echo $this->renderAjax('/common-view/_edit_plan_status', [
                    'userProfile' => $userProfile, 'user' => $user, 'user_plan' => $user_plan
                ]);
                exit();
            }
        }
    }

    public function actionPrefixTitle() {
        $result = '<option value="">Select Title</option>';
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $group = Groups::allgroups();
            $rst = DrsPanel::prefixingList(strtolower($group[$post['type']]), 'list');
            if (count($rst) > 0) {
                foreach ($rst as $key => $item) {
                    $result = $result . '<option value="' . $item . '">' . $item . '</option>';
                }
            }
        }
        return $result;
    }

    public function actionGetAppointmentReport() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        if (Yii::$app->request->post()) {
            $params = Yii::$app->request->post();
           
            $model = DrsPanel::getBookingHistory($params);
            if (!empty($model['appointments'])) {
                $content = $this->renderPartial('/layouts/_reportView', ['appointments' => $model['appointments'], 'doctor' => $model['doctor']]);

                // setup kartik\mpdf\Pdf component
                $pdf = new Pdf([
                    // set to use core fonts only
                    'mode' => Pdf::MODE_UTF8,
                    // A4 paper format
                    'format' => Pdf::FORMAT_FOLIO,
                    // portrait orientation
                    'orientation' => Pdf::ORIENT_PORTRAIT,
                    // stream to browser inline
                    'destination' => Pdf::DEST_FILE,
                    // your html content input
                    'content' => $content,
                    // format content from your own css file if needed or use the
                    // enhanced bootstrap css built by Krajee for mPDF formatting 
                    //'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
                    // any css to be embedded if required
                    //'cssInline' => '.kv-heading-1{font-size:14px}',
                    // set mPDF properties on the fly
                    'options' => ['title' => 'Appointment Statement'],
                    // call mPDF methods on the fly
                    'methods' => [
                        'SetTitle' => 'Appointment Statement - drspanel.in',
                        'SetSubject' => 'Appointment',
                        'SetHeader' => ['DrsPanel Appointment Statement||Generated On: ' . date("r")],
                        'SetFooter' => ['|Page {PAGENO}|'],
                        'SetAuthor' => 'Drspanel',
                        'SetCreator' => 'Drspanel',
                        'SetKeywords' => 'Appointment',
                    ]
                ]);

                $pdf->filename = 'statement.pdf';

                // return the pdf output as per the destination setting
                $pdf->render();
                $result = ['status' => 'success'];
                return json_encode($result);
                exit();
            } else {
                Yii::$app->session->setFlash('error', "'Statement not available'");
                return $this->redirect(Yii::$app->request->referrer);
            }
        } else {
            return $this->redirect(Yii::$app->request->referrer);
        }
    }

    public function actionDeleteAppointment() {
        $params = Yii::$app->request->post();
        if (isset($params['dateFrom']) && $params['dateFrom'] != '' && isset($params['dateTo']) && $params['dateTo'] != '') {
            $deleteAppointment = DrsPanel::deleteAppointment($params);
            if ($deleteAppointment['status'] == 'success') {
                Yii::$app->session->setFlash('success', "'Statement delete successfully'");
                return $this->redirect(Yii::$app->request->referrer);
            } else {
                Yii::$app->session->setFlash('success', "'Somthing went problem.'");
                return $this->redirect(Yii::$app->request->referrer);
            }
        } else {
            Yii::$app->session->setFlash('error', "'Please select date range from date to to date'");
            return $this->redirect(Yii::$app->request->referrer);
        }
    }

}
