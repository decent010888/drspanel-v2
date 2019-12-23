<?php

namespace frontend\modules\user\controllers;

use backend\models\AddScheduleForm;
use common\components\DrsImageUpload;
use common\components\Logs;
use common\models\Areas;
use common\models\HospitalAttender;
use common\models\MetaKeys;
use common\models\UserAddressImages;
use common\models\UserSchedule;
use common\models\UserScheduleDay;
use common\models\UserScheduleGroup;
use common\models\UserScheduleSlots;
use common\models\UserVerification;
use frontend\models\AppointmentForm;
use Yii;
use yii\authclient\AuthAction;
use yii\base\Exception;
use yii\base\InvalidParamException;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;
use common\commands\SendEmailCommand;
use common\models\User;
use common\models\UserProfile;
use common\models\UserRequest;
use common\models\Groups;
use backend\models\AttenderForm;
use backend\models\AttenderEditForm;
use common\components\DrsPanel;
use common\models\UserAppointment;
use common\models\UserAddress;
use common\models\UserAboutus;
use common\models\MetaValues;
use frontend\modules\user\models\SignupForm;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\data\Pagination;
use yii\db\Query;
use kartik\mpdf\Pdf;

/**
 * Class HospitalController
 * @package frontend\modules\user\controllers
 * @author Eugene Terentev <eugene@terentev.net>
 */
class HospitalController extends \yii\web\Controller {

    /**
     * @return array
     */
    private $loginUser;

    public function actions() {
        return [
            'oauth' => [
                'class' => AuthAction::class,
                'successCallback' => [$this, 'successOAuthCallback']
            ]
        ];
    }

    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $this->loginUser = Yii::$app->user->identity;
                            return $this->loginUser->groupid == Groups::GROUP_HOSPITAL;
                        }
                    ],
                ]
            ]
        ];
    }

    public function actionProfile() {
        $id = Yii::$app->user->id;
        $userModel = $this->findModel($id);
        $groupid = Groups::GROUP_HOSPITAL;
        $userProfile = UserProfile::findOne(['user_id' => $id]);
        $userAddress = UserAddress::findOne(['user_id' => $id]);
        $userAboutus = UserAboutus::findOne(['user_id' => $id]);
        $genderlist = [UserProfile::GENDER_MALE => 'Male', UserProfile::GENDER_FEMALE => 'Female'];
        if (Yii::$app->request->isAjax) {
            $userProfile->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($userProfile);
        }
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if (isset($post['UserProfile']['speciality']) && !empty($post['UserProfile']['speciality'])) {
                $Userspecialities = $post['UserProfile']['speciality'];
                $Usertreatments = $post['UserProfile']['treatment'];
                if (!empty($Userspecialities)) {
                    $metakey_speciality = MetaKeys::findOne(['key' => 'speciality']);
                    $getSpecilaity = MetaValues::find()->where(['key' => $metakey_speciality->id, 'value' => $Userspecialities])->one();
                    if (!empty($Usertreatments)) {
                        $post['UserProfile']['treatment'] = implode(',', $Usertreatments);
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
                        $post['UserProfile']['treatment'] = '';
                    }
                }
                $modelUpdate = UserProfile::upsert($post, $id, $groupid);
                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Speciality/Treatments Updated'");
                    return $this->redirect(['/hospital/profile']);
                }
            }

            if (isset($post['UserProfile']['services'])) {
                $userServices = $post['UserProfile']['services'];
                if (!empty($userServices)) {
                    $post['UserProfile']['services'] = implode(',', $userServices);
                }
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
                $modelUpdate = UserProfile::upsert($post, $id, $groupid);
                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Services Updated'");
                    return $this->redirect(['/hospital/profile']);
                } else {
                    Yii::$app->session->setFlash('error', "'Sorry hospital Facility Not Added'");
                }
            }
        }

        $specialityList = UserProfile::find()->andWhere(['user_id' => $id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        $speciality = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 5])->all();
        $treatment = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 9])->all();

        $treatmentList = UserProfile::find()->andWhere(['user_id' => $id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        $profilepercentage = DrsPanel::profiledetails($userModel, $userProfile, $groupid);

        $servicesList = UserProfile::find()->andWhere(['user_id' => $id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        $services = DrsPanel::getMetaData('services', $id);
        return $this->render('/hospital/profile', ['userModel' => $userModel, 'userProfile' => $userProfile, 'speciality' => $speciality, 'specialityList' => $specialityList, 'treatments' => $treatment, 'treatmentList' => $treatmentList, 'services' => $services, 'servicesList' => $servicesList, 'useraddressList' => $userAddress, 'userAboutus' => $userAboutus]);
    }

    public function actionAjaxTreatmentList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $form = ActiveForm::begin(['id' => 'profile-form']);
            $post = Yii::$app->request->post();
            $treatment_list = [];
            if (isset($post['id']) && !empty($post['user_id'])) {
                $userProfile = UserProfile::findOne(['user_id' => $post['user_id']]);
                $key = MetaValues::findOne(['value' => $post['id']]);
                $treatments = MetaValues::find()->andWhere(['status' => 1, 'key' => 9, 'parent_key' => $key->id])->all();
                $all_active_values = array();
                foreach ($treatments as $treatment) {
                    $all_active_values[] = $treatment->value;
                    $treatment_list[$treatment->value] = $treatment->label;
                }

                $treatments = $userProfile->treatment;
                if (!empty($treatments)) {
                    $treatments = explode(',', $treatments);
                    foreach ($treatments as $treatment) {
                        if (!in_array($treatment, $all_active_values)) {
                            $checkValue = MetaValues::find()->where(['parent_key' => $key->id, 'value' => $treatment])->one();
                            if (!empty($checkValue)) {
                                $treatment_list[$checkValue->value] = $checkValue->label;
                            }
                        }
                    }
                }

                echo $this->renderAjax('/hospital/ajax-treatment-list', ['form' => $form, 'treatment_list' => $treatment_list, 'userProfile' => $userProfile]);
                exit();
            }
        }
    }

    public function actionEditProfile() {
        $id = Yii::$app->user->id;
        $userModel = $this->findModel($id);
        $userProfile = UserProfile::findOne(['user_id' => $id]);
        if (!empty($userProfile->dob)) {
            $userProfile->dob = date('Y', strtotime($userProfile->dob));
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $userModel->load($post);
            $old_image = $userProfile->avatar;
            $userProfile->load($post);
            $userProfile->avatar = $old_image;
            $userProfile->gender = 0;

            if ($userModel->groupUniqueNumber(['phone' => $post['User']['phone'],
                        'groupid' => $userModel->groupid, 'id' => $userModel->id])) {
                $userModel->addError('phone', 'This phone number already exists.');
            }
            if (isset($post['UserProfile']['dob'])) {
                $datec = $post['UserProfile']['dob'] . '-01-01';
                $userProfile->dob = date('Y-01-01', strtotime($datec));
            }
            $upload = UploadedFile::getInstance($userProfile, 'avatar');

            if ($userModel->admin_status == User::STATUS_ADMIN_PENDING) {
                $userModel->admin_status = User::STATUS_ADMIN_REQUESTED;
            }
            if ($userModel->save() && $userProfile->save()) {

                $userAddress = UserAddress::findOne(['user_id' => $id]);
                if (!empty($userAddress)) {
                    $userAddress->name = $userProfile->name;
                    $userAddress->save();
                }
                if (isset($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['tmp_name'])) {
                    $imageUpload = DrsImageUpload::updateProfileImageWeb('hospitals', $id, $upload);
                }
                Yii::$app->session->setFlash('success', "'Profile Updated'");
                return $this->redirect(['/hospital/profile']);
            }
        }
        return $this->render('/hospital/edit-profile', ['model' => $userModel, 'userModel' => $userModel, 'userProfile' => $userProfile]);
    }

    public function actionProfileFieldEdit() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['user_id']) && !empty($post['user_id'])) {
                $user = User::findOne($post['user_id']);
                $userProfile = UserProfile::findOne(['user_id' => $post['user_id']]);
                echo $this->renderAjax('/common/_edit_input_popup', ['type' => $post['type'], 'user' => $user, 'userType' => $post['userType']]);
                exit();
            }
        }
    }

    public function actionSendOtp() {
        $post = Yii::$app->request->post();

        if (isset($post['UserVerification'])) {
            $user_id = $post['UserVerification']['user_id'];
            $model = UserVerification::find()->where(['user_id' => $user_id])->one();
            if ($model->otp == $post['UserVerification']['otp']) {
                $user = User::findOne($user_id);
                if ($post['type'] == 'email') {
                    $user->email = $model->email;
                    if ($user->save()) {
                        $userProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
                        $userProfile->email = $model->email;
                        $userProfile->save();

                        Yii::$app->getSession()->setFlash('success', "'Email updated.'");
                        return $this->redirect('edit-profile');
                    }
                } else {
                    $user->phone = $model->phone;
                    if ($user->save()) {
                        Yii::$app->getSession()->setFlash('success', "'Mobile number updated.'");
                        return $this->redirect('edit-profile');
                    }
                }
            } else {
                Yii::$app->getSession()->setFlash('error', "'Invalid OTP Please Try Again.'");
                return $this->redirect('edit-profile');
            }
        } else {
            $type = $post['type'];
            $user_id = $post['id'];
            $user = User::findOne($post['id']);
            if ($type == 'email') {
                $checkexist = User::find()->andWhere(['email' => $post['identity']])->andWhere(['!=', 'id', $user_id])->one();
                if ($checkexist) {
                    $data = ['identity' => 'Email already register'];
                    $result = ['status' => true, 'error' => true, 'data' => $data];
                } else {
                    if ($user->email == $post['identity']) {
                        $data = ['identity' => 'You have entered same email as old'];
                        $result = ['status' => true, 'error' => true, 'data' => $data];
                    } else {
                        //update email & send otp
                        $model = UserVerification::find()->where(['user_id' => $user_id])->one();
                        if (empty($model)) {
                            $model = new UserVerification();
                        }
                        $model->user_id = $user_id;
                        $model->email = $post['identity'];
                        $model->otp = 1234;
                        $model->save();

                        $model->otp = '';

                        echo $this->renderAjax('/common/_verify_otp_popup', ['type' => $post['type'], 'user' => $user, 'model' => $model]);
                        exit();
                    }
                }
            } else {

                $checkexist = User::find()->andWhere(['phone' => $post['identity'], 'groupid' => $user->groupid])->andWhere(['!=', 'id', $user_id])->one();
                if ($checkexist) {
                    $data = ['identity' => 'Mobile number already register'];
                    $result = ['status' => true, 'error' => true, 'data' => $data];
                } else {
                    if ($user->phone == $post['identity']) {
                        $data = ['identity' => 'You have entered same mobile number as old'];
                        $result = ['status' => true, 'error' => true, 'data' => $data];
                    } else {
                        //update email & send otp
                        $model = UserVerification::find()->where(['user_id' => $user_id])->one();
                        if (empty($model)) {
                            $model = new UserVerification();
                        }
                        $model->user_id = $user_id;
                        $model->phone = $post['identity'];
                        $model->otp = 1234;
                        $model->save();


                        $model->otp = '';

                        echo $this->renderAjax('/common/_verify_otp_popup', ['type' => $post['type'], 'user' => $user, 'model' => $model]);
                        exit();
                    }
                }
            }
            return json_encode($result);
        }
    }

    public function actionAddress() {
        $id = Yii::$app->user->id;
        $userModel = $this->findModel($id);
        $userProfile = UserProfile::findOne(['user_id' => $id]);
        $userAddress = UserAddress::findOne(['user_id' => $id]);
        $imgModel = new UserAddressImages();
        if (empty($userAddress)) {
            $userAddress = new UserAddress();
            $userAddress->type = 'Hospital';
            $userAddress->user_id = $id;
            $userAddress->name = $userProfile->name;
            $addressImages = array();
        } else {
            $addressImages = UserAddressImages::find()->where(['address_id' => $userAddress->id])->all();
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $userAddress->load(Yii::$app->request->post());
            $userAddress->city = Drspanel::getCityName($post['UserAddress']['city_id']);

            if (isset($post['UserAddress']['lat']) && !empty($post['UserAddress']['lat'])) {
                $userAddress->lat = $post['UserAddress']['lat'];
            } else {
                $userAddress->lat = (string) 26.912434;
            }

            if (isset($post['UserAddress']['lng']) && !empty($post['UserAddress']['lng'])) {
                $userAddress->lng = $post['UserAddress']['lng'];
            } else {
                $userAddress->lng = (string) 75.787270;
            }
            if ($userAddress->save()) {
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
                    $imageUpload = DrsImageUpload::updateAddressImageListWeb($userAddress->id, $files);
                }
                Yii::$app->session->setFlash('success', "'Address Updated'");
                return $this->redirect(['/hospital/profile']);
            } else {
                // echo "<pre>"; print_r($userAddress->getErrors());die;
            }
        }
        return $this->render('/hospital/address', ['model' => $userModel, 'userModel' => $userModel, 'userProfile' => $userProfile, 'userAddress' => $userAddress, 'userAdddressImages' => $imgModel, 'addressImages' => $addressImages,]);
    }

    public function actionMapAreaList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $lat = '26.900470';
            $lng = '75.828670';
            $area = $post['area'];
            $city = $post['city'];
            $checkValue = Areas::find()->where(['city_id' => $city, 'name' => $area])->one();
            if (!empty($checkValue)) {
                $lat = $checkValue->lat;
                $lng = $checkValue->lng;
            }
            echo $this->renderAjax('/hospital/shift/_map_location', ['lat' => $lat, 'lng' => $lng]);
            exit();
        }
    }

    public function actionDeleteImages() {
        $form = ActiveForm::begin(['id' => 'profile-form']);
        $userAdddressImages = new UserAddressImages();
        $addressImages = array();
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $id = $post['id'];
            $deleteAddressimg = UserAddressImages::findOne($id);
            $address_id = $deleteAddressimg->address_id;
            if (!empty($deleteAddressimg)) {
                $deleteAddressimg->delete();
            }
            $addressImages = UserAddressImages::find()->where(['address_id' => $address_id])->all();
        }
        echo $this->renderAjax('/hospital/_address_images', ['form' => $form, 'addressImages' => $addressImages, 'userAdddressImages' => $userAdddressImages]);
        exit();
    }

    public function actionAboutus() {
        $user_id = $this->loginUser->id;
        $userAboutus = UserAboutus::find()->where(['user_id' => $user_id])->one();
        if (empty($userAboutus)) {
            $userAboutus = new UserAboutus();
        }

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $userAboutus->load($post);
            $userAboutus->user_id = $user_id;
            if ($userAboutus->save()) {
                Yii::$app->session->setFlash('success', "'About Us Added'");
                return $this->redirect(['/hospital/profile']);
            }
        }
        return $this->render('/hospital/aboutus', ['userProfile' => $userAboutus]);
    }

    public function actionServices($service_id = NULL) {
        $user_id = Yii::$app->user->id;
        $groupid = Groups::GROUP_HOSPITAL;
        if ($user_id) {
            $model = UserProfile::findOne($user_id);
        } else {
            $model = new UserProfile();
        }
        $msg = 'Added';
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if (isset($post['UserProfile']['services'])) {
                $Userservices = $post['UserProfile']['services'];
                if (!empty($Userservices)) {
                    $post['UserProfile']['services'] = implode(',', $Userservices);
                }

                $modelUpdate = UserProfile::upsert($post, $user_id, $groupid);
                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Hospital Services Updated'");
                    return $this->redirect(['/hospital/services']);
                } else {
                    Yii::$app->session->setFlash('error', "'Sorry hospital Services Not updated'");
                }
            }
        }

        $servicesList = UserProfile::find()->andWhere(['user_id' => $user_id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();
        $services = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 11])->all();

        return $this->render('/hospital/services', ['model' => $model, 'services' => $services, 'servicesList' => $servicesList]);
    }

    public function actionSpeciality($speciality_id = NULL) {
        $user_id = Yii::$app->user->id;
        $groupid = Groups::GROUP_HOSPITAL;

        $getspecialities = Drspanel::getMyHospitalSpeciality($user_id);

        $specialityList = UserProfile::find()->andWhere(['user_id' => $user_id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();
        $speciality = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 5])->all();

        $treatment = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 9])->all();

        $treatmentList = UserProfile::find()->andWhere(['user_id' => $user_id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        return $this->render('/hospital/speciality', ['specialities' => $getspecialities, 'specialityList' => $specialityList, 'treatments' => $treatment, 'treatmentList' => $treatmentList]);
    }

    public function actionCustomerCare() {
        $customer_phone = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Phone'])->one();
        $customer_email = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Email'])->one();
        $customer = array('phone' => $customer_phone, 'email' => $customer_email);
        return $this->render('/hospital/customer-care', ['customer' => $customer]);
    }

    public function actionMyShifts($slug = '') {
        $id = Yii::$app->user->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }

        $type = 'my-shifts';
        $hospital = UserProfile::findOne($id);
        $date = date('Y-m-d');

        if (!empty($slug)) {
            $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::findOne($doctorProfile->user_id);

                $address = UserAddress::find()->andWhere(['user_id' => $id])->one();

                $shifts = DrsPanel::getShiftListByAddress($doctor->id, $address->id);

                return $this->render('shift/my-shifts', ['address' => $address, 'shifts' => $shifts, 'doctor' => $doctor, 'hospital' => $hospital, 'doctorProfile' => $doctorProfile]);
            } else {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        } else {
            $string = Yii::$app->request->queryParams;
            if (isset($string['speciality']) && !empty($string['speciality'])) {
                $selected_speciality = $string['speciality'];
            } else {
                $selected_speciality = 0;
            }
            $params['user_id'] = $id;
            $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
            $data_array = DrsPanel::getMyDoctorList($params);

            return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'my-shifts', 'page_heading' => 'My Shifts', 'userType' => 'hospital']);
        }
    }

    /*     * **************************Today Timing********************************** */

    public function actionDayShifts($slug = '') {
        $id = Yii::$app->user->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $type = 'day-shifts';
        $hospital = UserProfile::findOne($id);
        $date = date('Y-m-d');
        if (!empty($slug)) {
            $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
            if (!empty($doctorProfile)) {
                /* if($this->checkProfileInactive($doctorProfile->user_id)){
                  Yii::$app->session->setFlash('erroralert', "'Doctor Profile yet to be approved!'");
                  return $this->redirect(Yii::$app->request->referrer);
                  } */
                $doctor = User::findOne($doctorProfile->user_id);
                $params = Yii::$app->request->queryParams;
                if (!empty($params) && isset($params['date'])) {
                    $date = $params['date'];
                } else {
                    $date = date('Y-m-d');
                }
                $getSlots = DrsPanel::getBookingShifts($doctor->id, $date, $id);
                return $this->render('shift/day-shifts', ['defaultCurrrentDay' => strtotime($date), 'shifts' => $getSlots, 'doctor' => $doctor, 'date' => $date]);
            } else {
                // not found
            }
        } else {
            $string = Yii::$app->request->queryParams;
            if (isset($string['speciality']) && !empty($string['speciality'])) {
                $selected_speciality = $string['speciality'];
            } else {
                $selected_speciality = 0;
            }
            $params['user_id'] = $id;
            $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
            $data_array = DrsPanel::getMyDoctorList($params);

            return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'day-shifts', 'page_heading' => 'Timing', 'userType' => 'hospital']);
        }
    }

    public function actionUpdateShiftStatus() {
        $response["status"] = 0;
        $response["error"] = true;
        $response['message'] = 'You have do not permission.';
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $params['booking_closed'] = $post['status'];
            $params['doctor_id'] = $post['user_id'];
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
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $user_id = $post['user_id'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));
            $appointments = DrsPanel::getBookingShifts($user_id, $date, $this->loginUser->id);
            echo $this->renderAjax('shift/_address-with-shift', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments]);
            exit();
        }
    }

    public function actionGetShiftDetails() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['id']) && isset($post['date'])) {
                $schedule_id = $post['id'];
                $shift_id = $post['shift_id'];
                $date = date('Y-m-d', $post['date']);
                $weekday = DrsPanel::getDateWeekDay($date);
                $userSchedule = UserScheduleDay::find()->where(['schedule_id' => $schedule_id, 'date' => $date, 'weekday' => $weekday])->one();
                if (empty($userSchedule)) {
                    $userSchedule = UserSchedule::findOne($schedule_id);
                }
                if (!empty($userSchedule)) {
                    $model = new AddScheduleForm();
                    $model->setShiftData($userSchedule);
                    $model->id = $shift_id;
                    $model->user_id = $userSchedule->user_id;
                    echo $this->renderAjax('shift/_day_shift_edit_form', ['model' => $model, 'date' => $date, 'schedule_id' => $schedule_id]);
                    exit();
                }
            }
        }
        return NULL;
    }

    public function actionShiftUpdate() {
        $updateShift = new AddScheduleForm();
        if (Yii::$app->request->isAjax) {
            $updateShift->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($updateShift);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $shift_id = $post['AddScheduleForm']['id'];
            $update_shift = DrsPanel::updateShiftTiming($shift_id, $post);
            if (isset($post['date_dayschedule'])) {
                $date = $post['date_dayschedule'];
            } else {
                $date = date('Y-m-d');
            }
            if (isset($update_shift['error']) && $update_shift['error'] == true) {
                Yii::$app->session->setFlash('shifterror', $update_shift['message']);
            } else {
                Yii::$app->session->setFlash('success', "'Shift updated successfully'");
            }
            return $this->redirect(['/hospital/day-shifts', 'date' => $date]);
        }
    }

    /*     * **************************Booking/Appointment************************************** */

    public function actionAppointments($slug = '') {
        $id = Yii::$app->user->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $hospital = UserProfile::findOne($id);
        $string = Yii::$app->request->queryParams;
        $date = date('Y-m-d');
        $type = '';
        $speciality_check = 0;
        if (isset($string['type'])) {
            $type = $string['type'];
        }
        if ($type == 'current_appointment') {
            $current_shifts = 0;
            if (!empty($slug)) {
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $current_shifts = 0;
                    $bookings = array();
                    $getShists = DrsPanel::getBookingShifts($doctor->id, $date, $id);
                    $appointments = DrsPanel::getCurrentAppointments($doctor->id, $date, $current_shifts, $getShists);
                    if (!empty($appointments)) {
                        if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                            $current_shifts = $appointments['current_selected'];
                            $bookings = $appointments['bookings'];
                        }
                    }

                    return $this->render('/hospital/appointment/current-appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'bookings' => $bookings, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'type' => $type, 'userType' => 'hospital']);
                }
            } else {
                $speciality_check = 1;
            }
        } elseif ($type == 'current_shift') {
            if (!empty($slug)) {
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $current_shifts = '';
                    $shifts = array();
                    $appointments = array();
                    $getSlots = DrsPanel::getBookingShifts($doctor->id, $date, $id);
                    $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
                    if (!empty($checkForCurrentShift)) {
                        $current_shifts = isset($checkForCurrentShift['shift_id']) ? $checkForCurrentShift['shift_id'] : '';
                        $current_affairs = DrsPanel::getCurrentAffair($checkForCurrentShift, $doctor->id, $date, $current_shifts, $getSlots);
                        if ($current_affairs['status'] && empty($current_affairs['error'])) {
                            $shifts = $current_affairs['all_shifts'];
                            $appointments = $current_affairs['data'];
                            $current_shifts = $current_affairs['schedule_id'];

                            return $this->render('/hospital/appointment/current-affair', ['schedule_id' => $current_affairs['schedule_id'], 'is_completed' => $current_affairs['is_completed'], 'is_cancelled' => $current_affairs['is_cancelled'], 'is_started' => $current_affairs['is_started'], 'Shifts' => $shifts, 'appointments' => $appointments, 'current_shifts' => $current_shifts, 'shift_id' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'type' => 'current_shift', 'date' => $date, 'userType' => 'hospital']);
                        } else {
                            return $this->render('/hospital/appointment/current-affair', ['schedule_id' => '', 'is_completed' => false, 'is_started' => false, 'is_cancelled' => false, 'Shifts' => $shifts, 'appointments' => $appointments, 'shift_id' => $current_shifts, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'type' => 'current_shift', 'date' => $date, 'userType' => 'hospital']);
                        }
                    } else {
                        // no shifts
                        return $this->render('/hospital/appointment/current-affair', ['schedule_id' => 0, 'is_completed' => 0, 'is_started' => 0, 'Shifts' => $shifts, 'appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'type' => 'current_shift', 'date' => $date, 'userType' => 'hospital']);
                    }
                }
            } else {
                $speciality_check = 1;
            }
        } else {
            $type = 'book';
            $current_shifts = 0;
            $slots = array();
            $message = '';
            if (!empty($slug)) {
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $getShists = DrsPanel::getBookingShifts($doctor->id, $date, $id);
                    $appointments = DrsPanel::getCurrentAppointments($doctor->id, $date, $current_shifts, $getShists);
                    if (!empty($appointments)) {
                        if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                            $current_shifts = $appointments['current_selected'];

                            $shift_check = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $doctor->id, 'schedule_id' => $current_shifts, 'status' => array('cancelled', 'completed')])->one();
                            if (!empty($shift_check)) {
                                if ($shift_check->status == 'completed') {
                                    $message = 'Shift Completed!';
                                } else {
                                    $message = 'Shift Cancelled!';
                                }
                            } else {
                                $slots = DrsPanel::getBookingShiftSlots($doctor->id, $date, $current_shifts, 'available');
                                $message = 'Shift slots not available!';
                            }
                        }
                    }
                    return $this->render('/hospital/appointment/appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'slots' => $slots, 'type' => $type, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'userType' => 'hospital', 'message' => $message]);
                }
            } else {
                $speciality_check = 1;
            }
        }

        if ($speciality_check == 1) {
            if (isset($string['speciality']) && !empty($string['speciality'])) {
                $selected_speciality = $string['speciality'];
            } else {
                $selected_speciality = 0;
            }
            $params['user_id'] = $id;
            $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
            $data_array = DrsPanel::getMyDoctorList($params);

            return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'appointment', 'userType' => 'hospital', 'page_heading' => 'Appointment',]);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionAjaxToken() {
        $result = ['status' => false, 'msg' => 'Invalid Request.'];
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $current_shifts = $post['shift_id'];
            $doctor_id = $post['doctorid'];
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');
            $message = '';
            $slots = array();
            $shift_check = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $doctor_id, 'schedule_id' => $current_shifts, 'status' => array('cancelled', 'completed')])->one();
            if (!empty($shift_check)) {
                if ($shift_check->status == 'completed') {
                    $message = 'Shift Completed!';
                } else {
                    $message = 'Shift Cancelled!';
                }
            } else {
                $slots = DrsPanel::getBookingShiftSlots($doctor_id, $date, $current_shifts, 'available');
                $message = 'Shift slots not available!';
            }
            echo $this->renderAjax('/common/_slots', ['slots' => $slots, 'doctor_id' => $doctor_id, 'userType' => 'hospital', 'message' => $message]);
            exit();
        }
    }

    public function actionAjaxCurrentAppointment() {
        $result = ['status' => false, 'msg' => 'Invalid Request.'];
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $bookings = array();
            $current_shifts = $post['shift_id'];
            $id = $post['user_id'];
            $doctor = User::findOne($id);
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');

            $getSlots = DrsPanel::getBookingShifts($id, $date, $this->loginUser->id);
            $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
            if (!empty($checkForCurrentShift)) {
                $current_affairs = DrsPanel::getCurrentAffair($checkForCurrentShift, $id, $date, $current_shifts, $getSlots);
                if ($current_affairs['status'] && empty($current_affairs['error'])) {
                    $shifts = $current_affairs['all_shifts'];
                    $appointments = $current_affairs['data'];
                    foreach ($shifts as $shift) {
                        if ($shift['schedule_id'] == $current_shifts) {
                            $current_shifts = $shift['schedule_id'];
                            $is_started = $shift['is_started'];
                            $is_completed = $shift['is_completed'];
                            $is_cancelled = $shift['is_cancelled'];
                            break;
                        }
                    }
                    echo $this->renderAjax('/common/_current_bookings', ['bookings' => $appointments, 'doctor_id' => $id, 'userType' => 'hospital', 'schedule_id' => $current_shifts, 'shift_id' => $checkForCurrentShift['shift_id'], 'is_completed' => $is_completed, 'is_cancelled' => $is_cancelled, 'is_started' => $is_started, 'doctor' => $doctor]);
                    exit();
                }
            }
        }
    }

    public function actionAjaxAppointment() {
        $result = ['status' => false, 'msg' => 'Invalid Request.'];
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $bookings = array();
            $current_shifts = $post['shift_id'];
            if (isset($post['doctorid'])) {
                $doctor_id = $post['doctorid'];
            } else {
                $doctor_id = $post['user_id'];
            }
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');
            $getShists = DrsPanel::getBookingShifts($doctor_id, $date, $this->loginUser->id);
            $appointments = DrsPanel::getCurrentAppointments($doctor_id, $date, $current_shifts, $getShists);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $bookings = $appointments['bookings'];
                }
            }
            echo $this->renderAjax('/common/_bookings', ['bookings' => $bookings, 'doctor_id' => $doctor_id, 'userType' => 'hospital']);
            exit();
        }
    }

    public function actionBookingConfirm() {
        $id = Yii::$app->user->id;
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $slot_id = explode('-', $post['slot_id']);
            $slot = UserScheduleSlots::find()->andWhere(['id' => $slot_id[1]])->one();
            if (!empty($slot)) {
                $doctor_id = $slot->user_id;
                $doctorProfile = UserProfile::find()->where(['user_id' => $doctor_id])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $schedule = UserSchedule::findOne($slot->schedule_id);
                    $model = new AppointmentForm();
                    $model->doctor_id = $doctor->id;
                    $model->slot_id = $slot->id;
                    $model->schedule_id = $slot->schedule_id;
                    return $this->renderAjax('/common/_booking_confirm.php', ['doctor' => $doctor, 'slot' => $slot, 'schedule' => $schedule, 'address' => UserAddress::findOne($schedule->address_id), 'model' => $model, 'userType' => 'hospital'
                    ]);
                }
            }
        }
        return NULL;
    }

    public function actionBookingConfirmStep2() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $id = Yii::$app->user->id;
            $slot_id = $post['slot_id'];
            $slot = UserScheduleSlots::find()->andWhere(['id' => $slot_id])->one();
            if (!empty($slot)) {
                $doctor_id = $slot->user_id;
                $doctorProfile = UserProfile::find()->where(['user_id' => $doctor_id])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $schedule = UserSchedule::findOne($slot->schedule_id);
                    $model = new AppointmentForm();
                    $model->doctor_id = $doctor->id;
                    $model->slot_id = $slot->id;
                    $model->schedule_id = $slot->schedule_id;
                    $model->user_name = ucfirst($post['name']);
                    $model->user_phone = $post['phone'];
                    $model->user_gender = $post['gender'];
                    return $this->renderAjax('/common/_booking_confirm_step2.php', ['doctor' => $doctor, 'slot' => $slot, 'schedule' => $schedule, 'address' => UserAddress::findOne($schedule->address_id), 'model' => $model, 'userType' => 'hospital'
                    ]);
                }
            }
        }
        return NULL;
    }

    public function actionAppointmentBooked() {
        $user_id = Yii::$app->user->id;
        $response["status"] = 0;
        $response["error"] = true;
        $response['message'] = 'Does not match require parameters';
        if (Yii::$app->request->post() && Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $postData = $post['AppointmentForm'];
            $doctor_id = $post['AppointmentForm']['doctor_id'];
            $doctor = User::findOne($doctor_id);
            if (!empty($doctor)) {
                $doctorProfile = UserProfile::find()->where(['user_id' => $doctor->id])->one();
                $slot_id = $post['AppointmentForm']['slot_id'];
                $schedule_id = $post['AppointmentForm']['schedule_id'];

                $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
                if (!empty($slot)) {
                    $schedule = UserSchedule::findOne($schedule_id);
                    $address = UserAddress::findOne($schedule->address_id);

                    $userphone = User::find()->where(['phone' => $postData['user_phone'], 'groupid' => Groups::GROUP_PATIENT])->one();
                    if ($userphone) {
                        $user_id = $userphone->id;
                    } else {
                        $user_id = 0;
                    }

                    if ($slot->status == 'available') {
                        $data['UserAppointment']['booking_type'] = UserAppointment::BOOKING_TYPE_OFFLINE;
                        $data['UserAppointment']['booking_id'] = DrsPanel::generateBookingID();
                        $data['UserAppointment']['type'] = $slot->type;
                        $data['UserAppointment']['token'] = $slot->token;

                        $data['UserAppointment']['user_id'] = $user_id;
                        $data['UserAppointment']['user_name'] = ucfirst($postData['user_name']);
                        $data['UserAppointment']['user_age'] = (isset($postData['age'])) ? $postData['age'] : '0';
                        $data['UserAppointment']['user_phone'] = $postData['user_phone'];
                        $data['UserAppointment']['user_address'] = isset($postData['address']) ? $postData['address'] : '';
                        $data['UserAppointment']['user_gender'] = (isset($postData['user_gender'])) ? $postData['user_gender'] : '3';

                        $data['UserAppointment']['doctor_id'] = $doctor->id;
                        $data['UserAppointment']['doctor_name'] = $doctor['userProfile']['name'];
                        $data['UserAppointment']['doctor_phone'] = ($address->phone != '') ? $address->phone : $address->landline;

                        $data['UserAppointment']['doctor_address'] = DrsPanel::getAddressShow($address->id);
                        $data['UserAppointment']['doctor_address_id'] = $schedule->address_id;

                        if (isset($slot->fees_discount) && $slot->fees_discount < $slot->fees && $slot->fees_discount > 0) {
                            $data['UserAppointment']['doctor_fees'] = $slot->fees_discount;
                        } else {
                            $data['UserAppointment']['doctor_fees'] = $slot->fees;
                        }

                        $data['UserAppointment']['date'] = $slot->date;
                        $data['UserAppointment']['weekday'] = $slot->weekday;
                        $data['UserAppointment']['start_time'] = $slot->start_time;
                        $data['UserAppointment']['end_time'] = $slot->end_time;
                        $data['UserAppointment']['shift_name'] = $slot->shift_name;
                        $data['UserAppointment']['shift_label'] = $slot->shift_label;
                        $data['UserAppointment']['schedule_id'] = $schedule_id;
                        $data['UserAppointment']['slot_id'] = $slot_id;
                        $data['UserAppointment']['book_for'] = UserAppointment::BOOK_FOR_SELF;
                        $data['UserAppointment']['payment_type'] = 'cash';
                        $data['UserAppointment']['service_charge'] = 0;
                        $data['UserAppointment']['status'] = UserAppointment::STATUS_AVAILABLE;
                        $data['UserAppointment']['payment_status'] = UserAppointment::PAYMENT_COMPLETED;

                        $addAppointment = DrsPanel::addAppointment($data, 'doctor');

                        if ($addAppointment['type'] == 'model_error') {
                            $response = DrsPanel::validationErrorMessage($addAppointment['data']);
                        } else {
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response['message'] = 'Success';
                            $response['appointment_id'] = $addAppointment['data'];
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Slot not available for booking';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Can not add booking for this slot';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Doctor Details not found';
            }
        }
        return json_encode($response);
    }

    public function actionCurrentAppointmentShiftUpdate() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();

        $id = $params['doctor_id'];
        $doctor = User::findOne($id);
        $date = date('Y-m-d');
        $shift = $params['schedule_id'];
        $status = $params['status'];
        if ($status == 'start') {
            $schedule_check = UserScheduleGroup::find()->where(['user_id' => $doctor->id, 'date' => $date, 'status' => array('pending', 'current')])->orderBy('shift asc')->one();
            if (!empty($schedule_check)) {
                if ($schedule_check->schedule_id == $shift) {
                    $schedule_check->status = 'current';
                    if ($schedule_check->save()) {
                        $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_ACTIVE])->orderBy('token asc')->one();
                        if (empty($checkFirstAppointment)) {
                            $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                            if (!empty($checkFirstAppointment)) {
                                $checkFirstAppointment->status = UserAppointment::STATUS_ACTIVE;
                                $checkFirstAppointment->actual_time = time();
                                $checkFirstAppointment->save();
                            }
                        }
                        $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                        $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
                        if (!empty($checkForCurrentShift)) {
                            $response = DrsPanel::getCurrentAffair($checkForCurrentShift, $params['doctor_id'], $date, $shift, $getSlots);
                            echo json_encode($response);
                            exit;
                        } else {
                            Yii::$app->session->setFlash('error', "'Please try again!'");
                            return $this->redirect(Yii::$app->request->referrer);
                        }
                    } else {
                        Yii::$app->session->setFlash('error', "'Please try again!'");
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                } else {
                    $html = ucfirst($schedule_check->shift_label) . ' is pending';
                    Yii::$app->session->setFlash('error', "'$html'");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } else {
                Yii::$app->session->setFlash('error', "'Shift not found'");
                return $this->redirect(Yii::$app->request->referrer);
            }
        } elseif ($status == 'cancel') {
            $schedule_check = UserScheduleGroup::find()->where(['user_id' => $doctor->id, 'date' => $date, 'status' => array('pending', 'current')])->orderBy('shift asc')->one();
            if (!empty($schedule_check)) {
                if ($schedule_check->schedule_id == $shift) {
                    $schedule_check->status = 'cancelled';
                    if ($schedule_check->save()) {
                        $cancelAppointments = DrsPanel::cancelAppointmentsBySchedule($schedule_check->schedule_id, $date, $doctor->id, $by = 'Doctor');
                        $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                        $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
                        if (!empty($checkForCurrentShift)) {
                            $response = DrsPanel::getCurrentAffair($checkForCurrentShift, $params['doctor_id'], $date, $shift, $getSlots);
                            echo json_encode($response);
                            exit;
                        } else {
                            Yii::$app->session->setFlash('error', "'Please try again!'");
                            return $this->redirect(Yii::$app->request->referrer);
                        }
                    } else {
                        Yii::$app->session->setFlash('error', "'Please try again!'");
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                } else {
                    $html = ucfirst($schedule_check->shift_label) . ' is pending';
                    Yii::$app->session->setFlash('error', "'$html'");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } else {
                Yii::$app->session->setFlash('error', "'Shift not found'");
                return $this->redirect(Yii::$app->request->referrer);
            }
        } elseif ($status == 'completed') {
            $schedule_check = UserScheduleGroup::find()->where(['user_id' => $params['doctor_id'], 'date' => $date, 'status' => 'current', 'schedule_id' => $shift])->one();
            if (!empty($schedule_check)) {
                $schedule_check->status = 'completed';
                if ($schedule_check->save()) {

                    $cancelAppointments = DrsPanel::cancelAppointmentsBySchedule($schedule_check->schedule_id, $date, $doctor->id, $by = 'Doctor');
                    $date = date('Y-m-d');
                    $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                    $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
                    if (!empty($checkForCurrentShift)) {
                        $response = DrsPanel::getCurrentAffair($checkForCurrentShift, $params['doctor_id'], $date, $shift, $getSlots);
                        Yii::$app->session->setFlash('success', "'Shift status updated'");
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                } else {
                    Yii::$app->session->setFlash('error', "'Please try again'");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } else {
                Yii::$app->session->setFlash('error', "'Shift not found'");
                return $this->redirect(Yii::$app->request->referrer);
            }
        } else {
            Yii::$app->session->setFlash('error', "'Please try again!'");
            return $this->redirect(Yii::$app->request->referrer);
        }
    }

    public function actionAppointmentStatusUpdate() {
        $res = ['status' => false, 'msg' => 'You have not access.'];
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $params = Yii::$app->request->post();
            $id = $params['doctor_id'];
            $doctor = User::findOne($id);
            $date = date('Y-m-d');
            $shift = $params['schedule_id'];
            $status = $params['status'];

            if ($status == 'next' || $status == 'skip') {
                $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_ACTIVE])->orderBy('token asc')->one();
                if (!empty($checkFirstAppointment)) {
                    $status_update = ($status == 'next') ? UserAppointment::STATUS_COMPLETED : UserAppointment::STATUS_SKIP;
                    $checkFirstAppointment->status = $status_update;
                    if ($checkFirstAppointment->save()) {
                        $res = ['status' => true];
                        $secondAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                        if (!empty($secondAppointment)) {
                            $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                            $secondAppointment->actual_time = time();
                            $secondAppointment->save();
                        } else {
                            $secondAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_SKIP])->orderBy('token asc')->one();
                            if (!empty($secondAppointment)) {
                                $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                                $secondAppointment->actual_time = time();
                                $secondAppointment->save();
                            }
                        }
                    }
                } else {
                    $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                    if (!empty($checkFirstAppointment)) {
                        $status_update = ($status == 'next') ? UserAppointment::STATUS_COMPLETED : UserAppointment::STATUS_SKIP;
                        $checkFirstAppointment->status = $status_update;
                        if ($checkFirstAppointment->save()) {
                            $res = ['status' => true];
                            $secondAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                            if (!empty($secondAppointment)) {
                                $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                                $secondAppointment->actual_time = time();
                                $secondAppointment->save();
                            } else {
                                $secondAppointment = UserAppointment::find()->where(['doctor_id' => $params['doctor_id'], 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_SKIP])->orderBy('token asc')->one();
                                if (!empty($secondAppointment)) {
                                    $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                                    $secondAppointment->actual_time = time();
                                    $secondAppointment->save();
                                }
                            }
                        }
                    } else {
                        Yii::$app->session->setFlash('error', 'Appointments are not paid');
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                }
            } else {
                
            }
        }
        return json_encode($res);
    }

    public function actionGetNextSlots() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user_id = $post['user_id'];
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $type = $post['type'];
            $first = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['key']));
            $dates_range = DrsPanel::getSliderDates($first);

            $result['status'] = true;
            $result['result'] = $this->renderAjax('/common/_appointment_date_slider', ['doctor_id' => $user_id, 'dates_range' => $dates_range, 'date' => $first, 'type' => $type, 'userType' => 'hospital']);
            $result['date'] = $first;
            echo json_encode($result);
            exit;
        }
        echo 'error';
        exit;
    }

    public function actionGetDateShifts() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $id = Yii::$app->user->id;
            $doctor_id = $post['user_id'];
            $doctor = User::findOne($doctor_id);
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $type = $post['type'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));

            $current_shifts = 0;
            $slots = array();
            $bookings = array();
            $getShists = DrsPanel::getBookingShifts($doctor_id, $date, $id);
            $appointments = DrsPanel::getCurrentAppointments($doctor_id, $date, $current_shifts, $getShists);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $current_shifts = $appointments['shifts'][0]['schedule_id'];
                    if ($type == 'book') {
                        $slots = DrsPanel::getBookingShiftSlots($doctor_id, $date, $current_shifts, 'available');
                    } else {
                        $bookings = $appointments['bookings'];
                    }
                }
            }
            echo $this->renderAjax('/common/_appointment_shift_slots', ['appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => $type, 'slots' => $slots, 'bookings' => $bookings, 'userType' => 'hospital']);
            exit;
        }
        echo 'error';
        exit;
    }

    public function actionGetAppointmentDetail() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $appointment_id = $post['appointment_id'];
            $booking_type = isset($post['booking_type']) ? $post['booking_type'] : '';
            $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
            $booking = DrsPanel::patientgetappointmentarray($appointment);

            if (isset($post['current'])) {
                $current = 1;
            } else {
                $current = 0;
            }
            echo $this->renderAjax('/common/_booking_detail', ['booking' => $booking, 'doctor_id' => $this->loginUser->id, 'userType' => 'hospital', 'booking_type' => $booking_type, 'current' => $current]);
            exit();
        }
    }

    public function actionAppointmentPaymentConfirm() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $appointment_id = $post['appointment_id'];
            $booking_type = isset($post['booking_type']) ? $post['booking_type'] : '';

            $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
            if ($booking_type == 'pending') {
                $appointment->status = 'available';
                if ($appointment->save()) {
                    $checkappointments = UserAppointment::find()->where(['doctor_id' => $appointment->doctor_id, 'date' => $appointment->date, 'schedule_id' => $appointment->schedule_id, 'status' => UserAppointment::STATUS_ACTIVE])->orderBy('token asc')->one();
                    if (empty($checkappointments)) {
                        $schedule_check = UserScheduleGroup::find()->where(['user_id' => $appointment->doctor_id, 'date' => $appointment->date, 'status' => 'current', 'schedule_id' => $appointment->schedule_id])->orderBy('shift asc')->one();
                        if (!empty($schedule_check)) {
                            $secondAppointment = UserAppointment::find()->where(['doctor_id' => $appointment->doctor_id, 'date' => $appointment->date, 'schedule_id' => $appointment->schedule_id, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                            if (!empty($secondAppointment)) {
                                $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                                $secondAppointment->actual_time = time();
                                $secondAppointment->save();
                            }
                        }
                    }
                    $result = array('status' => 'success', 'title' => 'Success!', 'message' => 'Payment Confirmed!');
                    echo json_encode($result);
                    exit();
                }
            } else {
                $result = array('status' => 'error', 'title' => 'Error!', 'message' => 'Please try gain!');
                echo json_encode($result);
                exit();
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionAppointmentConsultingConfirm() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $appointment_id = $post['appointment_id'];
            $booking_type = isset($post['booking_type']) ? $post['booking_type'] : '';

            $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
            $appointment->status = UserAppointment::STATUS_ACTIVE;
            $appointment->actual_time = time();
            if ($appointment->save()) {
                $checkappointments = UserAppointment::find()->where(['doctor_id' => $appointment->doctor_id, 'date' => $appointment->date, 'schedule_id' => $appointment->schedule_id, 'status' => UserAppointment::STATUS_ACTIVE])->andWhere(['!=', 'id', $appointment_id])->orderBy('token asc')->one();
                if (!empty($checkappointments)) {
                    $checkappointments->status = UserAppointment::STATUS_AVAILABLE;
                    $checkappointments->save();
                }
                $result = array('status' => 'success', 'title' => 'Success!', 'message' => 'Payment Confirmed!');
                echo json_encode($result);
                exit();
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionAjaxCancelAppointment() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $appointment_id = $post['appointment_id'];
            $response = DrsPanel::cancelAppointmentById($appointment_id, 'Hospital');
            $html = $response['message'];
            if ($response['status'] == 'success') {
                //Yii::$app->session->setFlash('success', "'$html'");
                $result = array('status' => 'success', 'title' => 'Success!', 'message' => $html);
                echo json_encode($result);
                exit();
            } else {
                $result = array('status' => 'error', 'title' => 'Error!', 'message' => 'Please try again');
                echo json_encode($result);
                exit();
                //Yii::$app->session->setFlash('error', "'$html'");
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /*     * ***************************************************************************** */

    public function actionUpdateStatus($doctor_id = NULL) {
        /* $lists= DrsPanel::myPatients(['doctor_id'=>$this->loginUser->id]); */
        $hospital_id = $this->loginUser->id;
        $usergroupid = Groups::GROUP_HOSPITAL;
        if (!empty($doctor_id) && !empty($hospital_id)) {
            $model = UserRequest::find()->andWhere(['request_from' => $hospital_id, 'request_to' => $hospital_id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();
        } else {
            $model = new UserRequest();
        }
        if (Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            if ($post['type'] == 'send') {
                $post['groupid'] = Groups::GROUP_HOSPITAL;
                $type = 'Add';
                $modelUpdate = UserRequest::updateStatus($post, $type);
                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Request sent'");
                    return $this->redirect(['/hospital/find-doctors']);
                } else {
                    Yii::$app->session->setFlash('error', "'Sorry request couldnot sent'");
                }
            } elseif ($post['type'] == 'remove') {
                $cond['request_from'] = $hospital_id;
                $cond['request_to'] = $post['request_to'];
                $lists = UserRequest::deleteAll($cond);
                $useraddress = UserAddress::find()->where(['user_id' => $hospital_id])->one();
                if (!empty($useraddress)) {
                    $deleteshiftwithappointments = DrsPanel::deleteAddresswithShifts($post['request_to'], $useraddress->id);
                }
                Yii::$app->session->setFlash('success', "'Doctor Removed from list'");
                return $this->redirect(['/hospital/my-doctors']);
            } else {
                $cond['request_from'] = $hospital_id;
                $cond['request_to'] = $post['request_to'];
                $lists = UserRequest::deleteAll($cond);
                Yii::$app->session->setFlash('success', "'Request cancelled'");
                return $this->redirect(['/hospital/find-doctors']);
            }
        }

        exit;

        return Null;
    }

    public function actionCityList() {
        $result = '<option value="">Select City</option>';
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $rst = Drspanel::getCitiesList($post['state_id'], 'name');
            foreach ($rst as $key => $item) {
                $result = $result . '<option value="' . $item->id . '">' . $item->name . '</option>';
            }
        }
        return $result;
    }

    public function actionCityAreaList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $form = ActiveForm::begin(['id' => 'profile-form']);
            $post = Yii::$app->request->post();
            $area_list = [];
            if (isset($post['id']) && !empty($post['id'])) {
                $userAddress = new UserAddress();
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

                echo $this->renderAjax('/hospital/_area_field', ['form' => $form, 'area_list' => $area_list, 'userAddress' => $userAddress]);
                exit();
            }
        }
    }

    public function actionShifts() {
        $date = date('Y-m-d');
        $current_shifts = 0;
        $hospitals = DrsPanel::doctorHospitalList($this->loginUser->id);
        $appointments = DrsPanel::getCurrentAppointments($this->loginUser['id'], $date, $current_shifts);
        return $this->render('/hospital/shifts', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'hospitals' => $hospitals['apiList']]);
    }

    public function actionAjaxShiftDetails() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $userShift = UserSchedule::findOne($post['id']);
            if ($userShift) {
                $newShift = new AddScheduleForm();
                $newShift->setShiftData($userShift);
                return $this->render('/hospital/shift/_editShift', ['userShift' => $newShift]);
            }
        }
        return NULL;
    }

    /*     * **************************History************************************** */

    public function actionPatientHistory($slug = '') {
        $id = Yii::$app->user->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }

        $hospital = UserProfile::findOne($id);
        $date = date('Y-m-d');
        $type = 'history';
        $userType = 'hospital';

        if (!empty($slug)) {
            $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::findOne($doctorProfile->user_id);

                $current_selected = 0;
                $checkForCurrentShift = 0;
                $appointments = $shiftAll = $typeCount = $history = [];
                $getSlots = DrsPanel::getBookingShifts($doctor->id, $date, $id);
                if (!empty($getSlots)) {
                    $checkForCurrentShift = $getSlots[0]['schedule_id'];
                    $current_selected = $checkForCurrentShift;
                    $getAppointments = DrsPanel::appointmentHistory($doctor->id, $date, $current_selected, $getSlots, '');
                    $shiftAll = DrsPanel::getDoctorAllShift($doctor->id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                    $appointments = $getAppointments['bookings'];
                    $history = $getAppointments['total_history'];
                    $typeCount = $getAppointments['type'];
                }
                return $this->render('/hospital/history-statistics/patient-history', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'current_selected' => $current_selected, 'type' => $type, 'userType' => $userType, 'hospital' => $hospital]);
            } else {
                
            }
        } else {
            $string = Yii::$app->request->queryParams;
            if (isset($string['speciality']) && !empty($string['speciality'])) {
                $selected_speciality = $string['speciality'];
            } else {
                $selected_speciality = 0;
            }
            $params['user_id'] = $id;
            $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
            $data_array = DrsPanel::getMyDoctorList($params);

            return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'history', 'page_heading' => 'Patient History', 'userType' => 'hospital']);
        }
    }

    public function actionAjaxHistoryContent() {
        $user_id = Yii::$app->user->id;
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctor_id = $post['user_id'];
            $doctor = User::findOne($doctor_id);
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));
            $current_selected = 0;
            $checkForCurrentShift = 0;
            $appointments = $shiftAll = $typeCount = $history = [];
            $getSlots = DrsPanel::getBookingShifts($doctor_id, $date, $user_id);
            if (!empty($getSlots)) {
                $checkForCurrentShift = $getSlots[0]['schedule_id'];
                if ($current_selected == 0) {
                    $current_selected = $checkForCurrentShift;
                }
                $getAppointments = DrsPanel::appointmentHistory($doctor_id, $date, $current_selected, $getSlots);
                $shiftAll = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                $appointments = $getAppointments['bookings'];
                $history = $getAppointments['total_history'];
                $typeCount = $getAppointments['type'];
            }
            echo $this->renderAjax('/doctor/history-statistics/_history-content', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $appointments, 'shifts' => $shiftAll, 'doctor' => $doctor, 'current_selected' => $current_selected, 'userType' => 'hospital']);
            exit;
        }
        echo 'error';
        exit;
    }

    public function actionAjaxHistoryAppointment() {
        $result = ['status' => false, 'msg' => 'Invalid Request.'];
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $bookings = array();
            $doctor_id = $post['user_id'];
            $current_shifts = $post['shift_id'];
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');
            $getShists = DrsPanel::getBookingShifts($doctor_id, $date, $this->loginUser->id);

            $status = [UserAppointment::STATUS_ACTIVE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_COMPLETED, UserAppointment::STATUS_CANCELLED];
            $appointments = DrsPanel::getCurrentAppointments($doctor_id, $date, $current_shifts, $getShists, $status);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $bookings = $appointments['bookings'];
                    $history = $appointments['total_history'];
                    $typeCount = $appointments['type'];
                }
            }
            echo $this->renderAjax('/hospital/history-statistics/_history-patient', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $bookings, 'doctor_id' => $doctor_id, 'userType' => 'hospital']);
            exit();
        }
    }

    public function actionUserStatisticsData($slug = '') {
        $id = Yii::$app->user->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $hospital = UserProfile::findOne($id);
        $date = date('Y-m-d');
        $type = 'user_history';
        $userType = 'hospital';

        $current_selected = 0;
        $checkForCurrentShift = 0;
        $appointments = $shiftAll = $typeCount = $history = [];
        $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;


        if (!empty($slug)) {
            $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::findOne($doctorProfile->user_id);

                $getSlots = DrsPanel::getBookingShifts($doctor->id, $date, $id);
                if (!empty($getSlots)) {
                    $checkForCurrentShift = $getSlots[0]['schedule_id'];
                    $current_selected = $checkForCurrentShift;
                    $getAppointments = DrsPanel::appointmentHistory($doctor->id, $date, $current_selected, $getSlots, $typeselected);
                    $shiftAll = DrsPanel::getDoctorAllShift($doctor->id, $date, $checkForCurrentShift, $getSlots, $current_selected);


                    $appointments = $getAppointments['bookings'];
                    $typeCount = $getAppointments['type'];
                }
                return $this->render('/hospital/history-statistics/user-statistics-data', ['typeCount' => $typeCount, 'typeselected' => $typeselected, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'current_selected' => $current_selected]);
            } else {
                // not found
            }
        } else {
            $string = Yii::$app->request->queryParams;
            if (isset($string['speciality']) && !empty($string['speciality'])) {
                $selected_speciality = $string['speciality'];
            } else {
                $selected_speciality = 0;
            }
            $params['user_id'] = $id;
            $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
            $data_array = DrsPanel::getMyDoctorList($params);

            return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'user_history', 'page_heading' => 'User Statistics Data', 'userType' => 'hospital']);
        }
    }

    public function actionAjaxUserStatisticsData() {
        $user_id = Yii::$app->user->id;
        $hospital = UserProfile::findOne($user_id);
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctor_id = $post['user_id'];
            $doctor = User::findOne($doctor_id);
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));

            $current_selected = 0;
            $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
            $checkForCurrentShift = 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($doctor_id, $date, $user_id);
            if (!empty($getSlots)) {
                $checkForCurrentShift = $getSlots[0]['schedule_id'];
                $current_selected = $checkForCurrentShift;
                $getAppointments = DrsPanel::appointmentHistory($doctor_id, $date, $current_selected, $getSlots, $typeselected);
                $shiftAll = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            return $this->renderAjax('/hospital/history-statistics/_user-statistics-data', ['typeCount' => $typeCount, 'typeselected' => $typeselected, 'appointments' => $appointments, 'shifts' => $shiftAll, 'date' => strtotime($date), 'doctor' => $doctor, 'current_shifts' => $current_selected, 'userType' => 'hospital']);
        }
    }

    public function actionAjaxStatisticsData() {
        $user_id = Yii::$app->user->id;
        $hospital = UserProfile::findOne($user_id);
        $result['status'] = false;
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctor_id = $post['user_id'];
            $doctor = User::findOne($doctor_id);
            $date = ($post['date']) ? date('Y-m-d', strtotime($post['date'])) : date('Y-m-d');
            if (isset($post['type'])) {
                $typeselected = ($post['type'] == 'online') ? UserAppointment::BOOKING_TYPE_ONLINE : UserAppointment::BOOKING_TYPE_OFFLINE;
            } else {
                $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
            }
            $checkForCurrentShift = (isset($post['shift_id'])) ? $post['shift_id'] : 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($doctor_id, $date, $user_id);
            if (!empty($getSlots)) {
                $getAppointments = DrsPanel::appointmentHistory($doctor_id, $date, $checkForCurrentShift, $getSlots, $typeselected);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            $result['status'] = true;
            $result['appointments'] = $this->renderAjax('/common/_appointment-token', ['appointments' => $appointments, 'typeselected' => $typeselected, 'typeCount' => $typeCount,
                'doctor' => $doctor, 'userType' => 'hospital']);
            $result['typeCount'] = $typeCount;
            $result['typeselected'] = $typeselected;
        }
        return json_encode($result);
    }

    public function actionAjaxPatientList() {
        $user_id = $this->loginUser->id;
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctor_id = $post['user_id'];
            $doctor = User::findOne($doctor_id);
            $date = ($post['date']) ? date('Y-m-d', strtotime($post['date'])) : date('Y-m-d');
            $current_selected = ($post['schedule_id']) ? $post['schedule_id'] : 0;
            $checkForCurrentShift = 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($doctor_id, $date, $user_id);
            if (!empty($getSlots)) {
                $getAppointments = DrsPanel::appointmentHistory($doctor_id, $date, $current_selected, $getSlots);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            return $this->renderAjax('/hospital/history-statistics/_history-patient', ['appointments' => $appointments, 'doctor_id' => $doctor_id, 'userType' => 'hospital']);
        }
        return NULL;
    }

    /* Ajax search result on string enter */

    public function actionGetSearchList() {

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = array();
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $q = trim($post['term']);
            $query = new Query;
            if ($q != '') {
                $words = explode(' ', $q);
                $words = DrsPanel::search_permute($words);
                $userprofilelist = DrsPanel::getUserSearchListArray($words);
                foreach ($userprofilelist as $result) {
                    $data[] = array('id' => $result['id'], 'category_check' => $result['category'], 'category' => Yii::t('db', $result['category']), 'query' => $result['query'], 'label' => $result['label'], 'avator' => $result['avator']);
                }

                /* Category List search */
                $categories = DrsPanel::getSpecialitySearchListArray($words);
                if (!empty($categories)) {
                    foreach ($categories as $cat) {
                        $data[] = array('id' => '', 'category_check' => 'Specialization', 'category' => Yii::t('db', 'Specialization'), 'query' => $cat['query'], 'label' => Yii::t('db', $cat['label']), 'filters' => 'Specialization');
                    }
                }

                $data[] = array('id' => '', 'category_check' => 'Search', 'category' => Yii::t('db', 'Search'), 'query' => $q, 'label' => Yii::t('db', 'Doctor') . ' ' . Yii::t('db', 'named') . ' ' . $q, 'filters' => 'Doctor', 'avator' => '');
                $data[] = array('id' => '', 'category_check' => 'Search', 'category' => Yii::t('db', 'Search'), 'query' => $q, 'label' => Yii::t('db', 'Hospital') . ' ' . Yii::t('db', 'named') . ' ' . $q, 'filters' => 'Hospital', 'avator' => '');
                $out = array_values($data);
            }
            /*  else {

              $data= DrsPanel::getTypeDefaultListArray();
              foreach($data as $group){
              $out[]=array('id'=>'','category_check'=>'Groups','category'=>'Groups','query'=>'','label'=>Yii::t('db',$group['name']),'filters'=>$group['name'],'avator'=>'');
              }
              } */
            return $data;
            exit();
        }
    }

    /* Ajax search result details url */

    public function actionGetDetailurl() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $id = $post['id'];
            $out = array();

            if ($id != '') {
                if ($post['search_type'] == 'Specialization') {
                    $path = 'specialization';
                    $out = array('result' => 'success', 'fullpath' => 1, 'path' => '/' . $path . '/');
                } else {
                    $groupalias = DrsPanel::getusergroupalias($id);
                    if ($groupalias) {
                        $out = array('result' => 'success', 'fullpath' => 1, 'path' => '/' . $groupalias . '/');
                    } else {
                        if (isset($post['filter'])) {
                            if (isset($post['slug'])) {
                                $path = '/hospital?results_type=' . strtolower($post['filter']) . '&q=';
                                $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $post['filter'], 'slug' => $post['slug'], 'path' => $path);
                            } else {
                                $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $post['filter'], 'path' => '/hospital?results_type=' . $post['filter']);
                            }
                            $this->setSearchCookie($out);
                            return $out;
                            exit();
                        }
                    }
                }
                return $out;
                exit();
            } elseif (isset($post['filter'])) {
                $restype = strtolower($post['filter']);
                if (isset($post['slug'])) {
                    $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $restype, 'slug' => $post['slug'], 'path' => '/hospital?results_type=' . $restype . '&q=');
                } else {
                    $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $restype, 'path' => '/hospital?results_type=' . $restype);
                }
                $this->setSearchCookie($out);
                return $out;
                exit();
            } else {
                
            }
        }
        return array('result' => 'fail');
        exit();
    }

    public function setSearchCookie($codearray) {
        $baseurl = $_SERVER['HTTP_HOST'];
        $json = json_encode($codearray, true);
        setcookie('search_filter', $json, time() + 60 * 60, '/', $baseurl, false);
        return $codearray;
    }

    public function actionMyPatients() {
        $lists = DrsPanel::myPatients(['doctor_id' => $this->loginUser->id]);
        Yii::$app->session->setFlash('success', "'Profile Updated'");
        return $this->render('/hospital/my-patients', ['lists' => $lists]);
    }

    public function actionMyDoctors() {
        $hospital_id = $this->loginUser->id;
        if ($this->checkProfileInactive($hospital_id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $usergroupid = Groups::GROUP_HOSPITAL;

        //$search['shift']=true;
        $search = array();
        $lists = DrsPanel::doctorsHospitalList($hospital_id, 'Confirm', $usergroupid, $hospital_id, $search);
        $command = $lists->createCommand();
        $countQuery_speciality = clone $lists;
        $countTotal = $countQuery_speciality->count();

        $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());

        $selected_speciality = 0;
        $params = Yii::$app->request->queryParams;
        if (isset($params['speciality'])) {
            $selected_speciality = $params['speciality'];
            $listcat[] = $params['speciality'];

            $valuecat = array();
            if (!empty($listcat)) {
                foreach ($listcat as $cateval) {
                    $metavalues = MetaValues::find()->where(['id' => $cateval])->one();
                    if (!empty($metavalues)) {
                        $valuecat[] = $metavalues->value;
                    }
                }
                $query_array = array();
                foreach ($valuecat as $needle) {
                    $query_array[] = sprintf('FIND_IN_SET("%s",`user_profile`.`speciality`)', $needle);
                }
                $query_str = implode(' OR ', $query_array);
                $lists->andWhere(new \yii\db\Expression($query_str));
            }
            $command = $lists->createCommand();
        }

        $lists = $command->queryAll();
        $count_result = count($lists);

        if ($count_result > 0) {
            $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);
            $groups_v['id'] = 0;
            $groups_v['value'] = 'All';
            $groups_v['label'] = 'All';
            $groups_v['count'] = $countTotal;
            $groups_v['icon'] = '';
            $groups_v['isChecked'] = true;
            array_unshift($s_list, $groups_v);
            $speciality_list = $s_list;
        } else {
            $speciality_list = array();
        }
        return $this->render('/hospital/my-doctors', ['lists' => $lists, 'speciality_list' => $speciality_list, 'selected_speciality' => $selected_speciality, 'user_id' => $hospital_id]);
    }

    public function actionFindDoctors($doctor_id = NULL) {
        $hospital_id = $this->loginUser->id;
        $string = Yii::$app->request->queryParams;
        if ($this->checkProfileInactive($hospital_id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $usergroupid = Groups::GROUP_HOSPITAL;
        if (!empty($doctor_id) && !empty($hospital_id)) {
            $model = UserRequest::find()->andWhere(['request_from' => $hospital_id, 'request_to' => $hospital_id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();
        } else {
            $model = new UserRequest();
        }
        $userDoctorModel = new UserProfile();
        $doctorFind = '';
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if (isset($post['UserRequest']['request_to'])) {
                $Userrequstto = $post['UserRequest']['request_to'];
                foreach ($Userrequstto as $value) {
                    $postData['groupid'] = Groups::GROUP_HOSPITAL;
                    $postData['request_from'] = $hospital_id;
                    $postData['request_to'] = $value;
                    $postData['status'] = 1;
                    $type = 'Add';
                    $modelUpdate = UserRequest::updateStatus($postData, $type);
                }

                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Request sent'");
                    return $this->redirect(['/hospital/find-doctors']);
                } else {
                    Yii::$app->session->setFlash('error', "'Sorry request couldnot sent'");
                }
            }
            if (isset($post['UserProfile'])) {
                $doctorFind = UserProfile::find()->andFilterWhere(['like', 'name', $post['UserProfile']['name']]
                        )->all();
                return $this->render('/hospital/find-doctors', ['userDoctorModel' => $userDoctorModel, 'findDoctor' => $doctorFind]);
            }
        } else {
            $status = '';
            $city = '';
            if (!empty($string)) {
                if (isset($string['city']) && !empty($string['city'])) {
                    $city = $string['city'];
                }
                if (isset($string['status']) && !empty($string['status'])) {
                    $status = $string['status'];
                }
            }

            if ($status == '') {
                $lists = DrsPanel::doctorsHospitalList($hospital_id, 'All', $usergroupid, $hospital_id);
                $lists_fetch = $lists;
            } else {
                $lists = DrsPanel::doctorsHospitalList($hospital_id, ucfirst($status), $usergroupid, $hospital_id);
                $lists_fetch = DrsPanel::doctorsHospitalList($hospital_id, 'All', $usergroupid, $hospital_id);
            }
            $command = $lists->createCommand();

            $countQuery_speciality = clone $lists_fetch;
            $countTotal = $countQuery_speciality->count();
            $fetchCount = Drspanel::fetchSpecialityCount($lists_fetch->createCommand()->queryAll());

            $selected_speciality = 0;
            $params = Yii::$app->request->queryParams;
            if (isset($params['speciality'])) {
                $selected_speciality = $params['speciality'];
                $listcat[] = $params['speciality'];

                $valuecat = array();
                if (!empty($listcat)) {
                    foreach ($listcat as $cateval) {
                        $metavalues = MetaValues::find()->where(['id' => $cateval])->one();
                        if (!empty($metavalues)) {
                            $valuecat[] = $metavalues->value;
                        }
                    }
                    $query_array = array();
                    foreach ($valuecat as $needle) {
                        $query_array[] = sprintf('FIND_IN_SET("%s",`user_profile`.`speciality`)', $needle);
                    }
                    $query_str = implode(' OR ', $query_array);
                    $lists->andWhere(new \yii\db\Expression($query_str));
                }
                $command = $lists->createCommand();
            }

            if ($city != '') {
                $userLocation = $this->getLocationUsersArray($city);
                $lists->andWhere((['user.id' => $userLocation]));
                $command = $lists->createCommand();
            }

            $lists = $command->queryAll();
            $count_result = count($lists);

            $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);
            $groups_v['id'] = 0;
            $groups_v['value'] = 'All';
            $groups_v['label'] = 'All';
            $groups_v['count'] = $countTotal;
            $groups_v['icon'] = '';
            $groups_v['isChecked'] = true;
            array_unshift($s_list, $groups_v);
            $speciality_list = $s_list;


            return $this->render('/hospital/find-doctors', ['lists' => $lists, 'model' => $model, 'user_id' => $hospital_id, 'userDoctorModel' => $userDoctorModel, 'findDoctor' => $doctorFind, 'speciality_list' => $speciality_list, 'selected_speciality' => $selected_speciality, 'sel_city' => $city, 'sel_status' => $status]);
        }
    }

    public function actionDoctorDetail($slug = '') {
        $hospital_id = $this->loginUser->id;
        if ($this->checkProfileInactive($hospital_id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        if (!empty($slug)) {
            $profile = UserProfile::findOne(['slug' => $slug]);
            if (!empty($profile)) {
                $groupid = $profile->groupid;
                $user = User::find()->where(['id' => $profile->user_id, 'admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]])->one();
                if (!empty($user)) {
                    return $this->render('doctor-detail', [
                                'profile' => $profile, 'user' => $user, 'groupid' => $groupid,
                                'loginid' => $hospital_id]);
                }
            }
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionAttendersList() {
        $hospital_id = $this->loginUser->id;
        if ($this->checkProfileInactive($hospital_id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $model = new AttenderForm();
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (!empty($post['AttenderForm']['shift_id']) && count($post['AttenderForm']['shift_id']) > 0) {
                $post['AttenderForm']['shift_id'] = implode(',', $post['AttenderForm']['shift_id']);
            }
            if (!empty($post['AttenderForm']['doctor_id']) && count($post['AttenderForm']['doctor_id']) > 0) {
                $post['AttenderForm']['doctor_id'] = implode(',', $post['AttenderForm']['doctor_id']);
            }

            $model->load($post);
            $model->groupid = Groups::GROUP_ATTENDER;
            $model->parent_id = $this->loginUser->id;
            $model->created_by = 'Hospital';
            $upload = UploadedFile::getInstance($model, 'avatar');
            if (!empty($upload)) {
                $uploadDir = Yii::getAlias('@storage/web/source/attenders/');
                $image_name = time() . rand() . '.' . $upload->extension;
                $model->avatar = $image_name;
                $model->avatar_path = '/storage/web/source/attenders/';
                $model->avatar_base_url = Yii::getAlias('@frontendUrl');
            }
            if ($res = $model->signup()) {
                if (!empty($upload)) {
                    $upload->saveAs($uploadDir . $image_name);
                }
                Yii::$app->session->setFlash('success', "'Attender Added!'");
                return $this->redirect(['/hospital/attenders']);
            }
        }
        $hospitalId = Groups::GROUP_HOSPITAL;
        $addressList = DrsPanel::doctorHospitalList($this->loginUser->id);
        $list = DrsPanel::attenderList(['parent_id' => $this->loginUser->id], 'apilist');
        $hospital_id = $this->loginUser->id;

        $lists = DrsPanel::doctorsHospitalList($hospital_id, 'Confirm', Groups::GROUP_HOSPITAL, $hospital_id);
        $command = $lists->createCommand();
        $requests = $command->queryAll();

        // pr($requests);die;

        return $this->render('/hospital/attender/list', ['list' => $list, 'hospitalId' => $hospitalId, 'user' => $this->loginUser,
                    'model' => $model,
                    'roles' => ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name'),
                    'hospitals' => $addressList['listaddress'],
                    'doctors' => $requests]);
    }

    public function actionAttenderDetails() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            // pr($post);die;
            $user = $this->findModel($post['id']);
            $addressList = DrsPanel::attenderHospitalList($post['id']);
            $shiftList = Drspanel::shiftList(['user_id' => $this->loginUser->id], 'list');

            $hospital_id = $this->loginUser->id;

            $doctorlists = DrsPanel::doctorsHospitalList($hospital_id, 'Confirm', Groups::GROUP_HOSPITAL, $hospital_id);
            $selectedDoctors = Drspanel::doctorsHospitalList($hospital_id, 'Confirm', Groups::GROUP_HOSPITAL, ['doctor_id' => $post['id']]);
            $command2 = $selectedDoctors->createCommand();
            $requests2 = $command2->queryAll();

            $command = $doctorlists->createCommand();
            $requests = $command->queryAll();
            $model = new AttenderEditForm();
            $model->id = $post['id'];
            $model->name = $user['userProfile']['name'];
            $model->avatar = $user['userProfile']['avatar'];
            $model->avatar_base_url = $user['userProfile']['avatar'];
            $model->avatar_path = $user['userProfile']['avatar_path'];
            $model->phone = trim($user->phone);
            $model->email = $user->email;

            $selectedDoctors = Drspanel::getAttenderDoctors($post['id']);
            $model->doctor_id = $selectedDoctors;

            $hospitalId = Groups::GROUP_HOSPITAL;
            return $this->renderAjax('/hospital/attender/edit', [
                        'model' => $model,
                        'hospitals' => $addressList,
                        'doctor_lists' => $requests,
                        'hospitalId' => $hospitalId
            ]);
        }
        return NULL;
    }

    public function actionAttenderUpdate() {
        $hospital_id = Yii::$app->user->id;
        $model = new AttenderEditForm();
        if (Yii::$app->request->isAjax) {

            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();


            if (!empty($post['AttenderEditForm']['shift_id']) && count($post['AttenderEditForm']['shift_id']) > 0) {
                $post['AttenderEditForm']['shift_id'] = implode(',', $post['AttenderEditForm']['shift_id']);
            }
            $model->load($post);
            $upload = UploadedFile::getInstance($model, 'avatar');
            if (!empty($upload)) {
                $uploadDir = Yii::getAlias('@storage/web/source/attenders/');
                $image_name = time() . rand() . '.' . $upload->extension;
                $model->avatar = $image_name;
                $model->avatar_path = '/storage/web/source/attenders/';
                $model->avatar_base_url = Yii::getAlias('@frontendUrl');
            }

            if ($res = $model->update()) {
                if (isset($post['AttenderEditForm']['doctor_id']) && !empty($post['AttenderEditForm']['doctor_id'])) {
                    $attender_id = $post['AttenderEditForm']['id'];
                    $doctors = $post['AttenderEditForm']['doctor_id'];
                    $addupdateHospitalDoctors = DrsPanel::addUpdateDoctorsToHospitalAttender($doctors, $attender_id, $hospital_id);
                }
                if (!empty($upload)) {
                    $upload->saveAs($uploadDir . $image_name);
                }
                Yii::$app->session->setFlash('success', "'Attender Updated!'");

                return $this->redirect(['/hospital/attenders']);
            }
        }
        return NULL;
    }

    public function actionAttenderDelete() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();

            if ($user = User::find()->andWhere(['id' => $post['id']])->andWhere(['groupid' => Groups::GROUP_ATTENDER])->andWhere(['parent_id' => $this->loginUser->id])->one()) {
                if (DrsPanel::attenderDelete($user->id)) {
                    $cond['attender_id'] = $user->id;
                    $cond['hospital_id'] = $this->loginUser->id;
                    HospitalAttender::deleteAll($cond);
                    Yii::$app->session->setFlash('success', "'Attender Deleted!'");
                    return $this->redirect(['/hospital/attenders']);
                }
            }
        }
        return NULL;
    }

    public function checkProfileInactive($id) {
        $user = User::findOne($id);
        if ($user->admin_status == User::STATUS_ADMIN_APPROVED || $user->admin_status == User::STATUS_ADMIN_LIVE_APPROVED) {
            return false;
        } else {
            return true;
        }
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

    function getLocationUsersArray($city) {
        $userLocation = array();
        if (isset($city) && $city != '') {
            $city_id = DrsPanel::getCityId($city, 'Rajasthan');
            $latlong = DrsPanel::getCityLatLong($city_id);
            if (!empty($latlong)) {
                $latitude = $latlong['lat'];
                $longitude = $latlong['lng'];
                $userLocation = DrsPanel::getLocationUserList($latitude, $longitude);
            }
        }
        return $userLocation;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        if (!Yii::$app->user->isGuest) {
            $groupid = Yii::$app->user->identity->userProfile->groupid;
            if ($groupid != Groups::GROUP_HOSPITAL) {

                return $this->redirect(array('/'));
            } else {
                return parent::beforeAction($action);
            }
        }
        $this->redirect(array('/'));
    }

    public function actionGetAppointmentReport() {
        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        if (Yii::$app->request->post()) {
            $params = Yii::$app->request->post();
            $model = DrsPanel::getBookingHistory($params);
            if (!empty($model['appointments'])) {
                $content = $this->renderPartial('//layouts/_reportView', ['appointments' => $model['appointments'], 'doctor' => $model['doctor']]);

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
                echo json_encode($result);
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
