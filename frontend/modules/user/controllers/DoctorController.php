<?php

namespace frontend\modules\user\controllers;

use backend\models\AddScheduleForm;
use common\components\DrsImageUpload;
use common\components\Logs;
use common\components\Notifications;
use common\models\Areas;
use common\models\MetaKeys;
use common\models\UserEducations;
use common\models\UserExperience;
use common\models\UserSchedule;
use common\models\UserScheduleDay;
use common\models\UserScheduleGroup;
use common\models\UserScheduleSlots;
use common\models\UserVerification;
use Yii;
use yii\authclient\AuthAction;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;
use common\models\User;
use common\models\UserProfile;
use common\models\UserRequest;
use common\models\Groups;
use backend\models\AttenderForm;
use backend\models\AttenderEditForm;
use common\components\DrsPanel;
use common\models\UserAppointment;
use common\models\UserAddress;
use common\models\UserAddressImages;
use common\models\MetaValues;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use frontend\models\AppointmentForm;
use yii\db\Query;
use kartik\mpdf\Pdf;

require_once ('../../vendor/autoload.php');

/**
 * Class DoctorController
 * @package frontend\modules\user\controllers
 * @author Eugene Terentev <eugene@terentev.net>
 */
class DoctorController extends \yii\web\Controller {

    private $loginUser;

    /**
     * @return array
     */
    public function actions() {
        return [
            'oauth' => [
                'class' => AuthAction::class,
                'successCallback' => [$this, 'successOAuthCallback']
            ]
        ];
    }

    /**
     * @return array
     */
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
                            return $this->loginUser->groupid == Groups::GROUP_DOCTOR;
                        }
                    ],
                ]
            ]
        ];
    }

    public function actionEditProfile() {
        $id = Yii::$app->user->id;
        $userModel = $this->findModel($id);
        $userProfile = UserProfile::findOne(['user_id' => $id]);
        $genderlist = DrsPanel::getGenderList();
        $degrees = MetaValues::find()->orderBy('id asc')->where(['key' => 2, 'status' => 1])->all();
        $specialities = MetaValues::find()->orderBy('id asc')->where(['key' => 5, 'status' => 1])->all();
        $treatment = array();
        if ($userProfile['speciality']) {
            $treatment = MetaValues::getValues(9, $userProfile['speciality']);
        }

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $userModel->load($post);
            $old_image = $userProfile->avatar;
            $userProfile->load($post);
            $userProfile->avatar = $old_image;

            if (isset($post['UserProfile']['degree'])) {
                $udegrees = $post['UserProfile']['degree'];
                $userProfile->speciality = $post['UserProfile']['speciality'];

                $treatment = $post['UserProfile']['treatment'];
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
                if (!empty($treatment)) {
                    $userProfile->treatment = implode(', ', $treatment);
                }
            }

            if ($userModel->groupUniqueNumber(['phone' => $post['User']['phone'], 'groupid' => $userModel->groupid, 'id' => $userModel->id])) {
                $userModel->addError('phone', 'This phone number already exists.');
            }
            $upload = UploadedFile::getInstance($userProfile, 'avatar');

            if ($userModel->admin_status == User::STATUS_ADMIN_PENDING) {
                $userModel->admin_status = User::STATUS_ADMIN_REQUESTED;
            }
            if ($userModel->save() && $userProfile->save()) {
                foreach ($udegrees as $degressData) {
                    $degreeModel = new MetaValues();
                    $checkValue = $degreeModel->find()->where('UPPER(label) = "' . strtoupper($degressData) . '" ')->count();
                    if ($checkValue == 0) {
                        $degreeModel->key = 2;
                        $degreeModel->label = $degressData;
                        $degreeModel->value = $degressData;
                        $degreeModel->status = 1;
                        $degreeModel->save();
                    }
                }
                if (isset($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['tmp_name'])) {
                    $imageUpload = DrsImageUpload::updateProfileImageWeb('doctors', $id, $upload);
                }
                Yii::$app->session->setFlash('success', "'Profile updated!'");
                return $this->redirect(['/doctor/profile']);
            }
        }

        if (isset($userProfile->speciality) && !empty($userProfile->speciality)) {
            $key = MetaValues::findOne(['value' => $userProfile->speciality]);
            $treatment = MetaValues::find()->andWhere(['status' => 1, 'key' => 9])->andWhere(['parent_key' => isset($key->id) ? $key->id : '0'])->all();
        }
        return $this->render('/doctor/edit-profile', ['model' => $userModel, 'userModel' => $userModel, 'userProfile' => $userProfile, 'genderList' => $genderlist, 'degrees' => $degrees, 'treatment' => $treatment, 'specialities' => $specialities,]);
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
                        $otp = DrsPanel::randomOTP();
                        $model->user_id = $user_id;
                        $model->email = $post['identity'];
                        $model->otp = $otp;
                        $model->save();
                        $userDetail = UserProfile::find()->where(['user_id' => $user_id])->one();
                        $userD = UserVerification::find()->where(['user_id' => $user_id])->one();
                        $userPhone = User::find()->andWhere(['id' => $user_id])->one();
                        \common\components\MailSend::sendOtpMail($userDetail, $userD, $otp);
                        $message = $otp . ' is the OTP for accessing your DrsPanel account. PLS DO NOT SHARE IT WITH ANYONE.';
                        $sendSms = Notifications::send_sms($message, $userPhone['phone'], 'No', $userPhone['countrycode'], 1);
                        //$model->otp = '';

                        echo $this->renderAjax('/common/_verify_otp_popup', ['type' => $post['type'], 'user' => $user, 'model' => $model]);
                        exit();
                    }
                }
            } else {

                $checkexist = User::find()->andWhere(['phone' => $post['identity'], 'groupid' => $user->groupid])->andWhere(['!=', 'id', $user_id])->one();
                $userDetail = UserProfile::find()->where(['user_id' => $user_id])->one();
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
                        $otp = DrsPanel::randomOTP();
                        $model->user_id = $user_id;
                        $model->phone = $post['identity'];
                        $model->otp = $otp;
                        $model->save();
                        \common\components\MailSend::sendOtpMail($userDetail, $otp);

                        $model->otp = '';

                        echo $this->renderAjax('/common/_verify_otp_popup', ['type' => $post['type'], 'user' => $user, 'model' => $model]);
                        exit();
                    }
                }
            }
            return json_encode($result);
        }
    }

    public function actionProfile() {
        $id = Yii::$app->user->id;
        $userModel = $this->findModel($id);
        $groupid = Groups::GROUP_DOCTOR;
        $userProfile = UserProfile::findOne(['user_id' => $id]);
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
                        foreach ($Usertreatments as $UsertreatmentsData) {
                            $UsertreatArraay[] = ucwords($UsertreatmentsData);
                        }
                        $Usertreatmentss = implode(',', $UsertreatArraay);

                        $post['UserProfile']['treatment'] = $Usertreatmentss;
                        foreach ($Usertreatments as $keyt => $valuet) {
                            $treatmentModel = MetaValues::find()->where(['key' => 9, 'parent_key' => $getSpecilaity->id])->andWhere('UPPER(value) = "' . strtoupper($valuet) . '" ')->one();
                            if (empty($treatmentModel)) {
                                $treatmentModel = new MetaValues();
                                if (!empty($getSpecilaity)) {
                                    $treatmentModel->parent_key = $getSpecilaity->id;
                                }
                                $treatmentModel->key = 9;
                                $treatmentModel->value = ucwords($valuet);
                                $treatmentModel->label = ucwords($valuet);
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
                if (!empty($modelUpdate)) {
                    Yii::$app->session->setFlash('success', "'Speciality/Treatments Updated'");
                    return $this->redirect(['/doctor/profile']);
                }
            }

            if (isset($post['UserProfile']['services'])) {
                $userServices = $post['UserProfile']['services'];
                if (!empty($userServices)) {
                    $post['UserProfile']['services'] = implode(', ', $userServices);
                }
                foreach ($userServices as $key => $value) {
                    $servicesModel = MetaValues::find()->where(['key' => 11])->andWhere('UPPER(value) = "' . strtoupper($value) . '" ')->one();
                    if (empty($servicesModel)) {
                        $servicesModel = new MetaValues();
                        $servicesModel->key = 11;
                        $servicesModel->value = ucwords($value);
                        $servicesModel->label = ucwords($value);
                        $servicesModel->status = 1;
                        $servicesModel->save();
                    }
                }
                $modelUpdate = UserProfile::upsert($post, $id, $groupid);
                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Services Updated'");
                    return $this->redirect(['/doctor/profile']);
                } else {
                    Yii::$app->session->setFlash('error', "'Sorry doctor Facility Not Added'");
                }
            }
        }

        $specialityList = UserProfile::find()->andWhere(['user_id' => $id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        $speciality = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 5])->all();

        $treatment = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 9])->all();

        $treatmentList = UserProfile::find()->andWhere(['user_id' => $id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        $profilepercentage = DrsPanel::profiledetails($userModel, $userProfile, $groupid, $id);

        $servicesList = UserProfile::find()->andWhere(['user_id' => $id])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->all();

        $services = DrsPanel::getMetaData('services', $id);

        return $this->render('/doctor/profile', ['userModel' => $userModel, 'userProfile' => $userProfile, 'speciality' => $speciality, 'specialityList' => $specialityList, 'treatments' => $treatment, 'treatmentList' => $treatmentList, 'profilepercentage' => $profilepercentage['complete_percentage'], 'services' => $services, 'servicesList' => $servicesList]);
    }

    public function actionAjaxTreatmentList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $form = ActiveForm::begin(['id' => 'profile-form']);
            $post = Yii::$app->request->post();
            $treatment_list = [];
            if (isset($post['id']) && !empty($post['id'])) {
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

                echo $this->renderAjax('/doctor/ajax-treatment-list', ['form' => $form,
                    'treatment_list' => $treatment_list, 'userProfile' => $userProfile]);
                exit();
            }
        }
    }

    /* Add-Edit Shifts */

    public function actionMyShifts() {
        $ids = Yii::$app->user->id;
        $address_list = DrsPanel::doctorHospitalList($ids);
        return $this->render('shift/my-shifts', ['doctor_id' => $ids, 'address_list' => $address_list['apiList']]);
    }

    public function actionAddShift($id = NULL) {
        $ids = Yii::$app->user->id;
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
        $newShift->user_id = $this->loginUser->id;
        if ($id) {
            $userShift = UserSchedule::findOne($id);
            $newShift->setShiftData($userShift);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['UserAddress'])) {
                // echo "<pre>"; print_r($post);die;
                $addAddress->load(Yii::$app->request->post());
                if (isset($post['AddScheduleForm'])) {
                    $newShift->setPostData($post);
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
                    //$addAddress->load($post['UserAddress']);
                    $addAddress->city = Drspanel::getCityName($post['UserAddress']['city_id']);
                    $addAddress->user_id = $ids;
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
                                            }
                                            /* elseif($endtime_check > $startTimeClnt && $endtime_check <= $endTimeClnt) {
                                              $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                              $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                              $errormsgloop[$error_msg]['shift']= $keyClnt;
                                              $errormsgloop[$error_msg]['weekday']= $value;
                                              $errormsgloop[$error_msg]['message']= 'is invalid time';
                                              $canAddEdit = false;
                                              $errorIndex++;$error_msg++;
                                              $msg = ' msg4';
                                              }
                                              elseif($startTimeClnt >= $starttime_check && $startTimeClnt < $endtime_check) {
                                              $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                              $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                              $errormsgloop[$error_msg]['shift']= $keyClnt;
                                              $errormsgloop[$error_msg]['weekday']= $value;
                                              $errormsgloop[$error_msg]['message']= 'is invalid time';
                                              $canAddEdit = false;
                                              $errorIndex++;$error_msg++;
                                              $msg = ' msg5';
                                              } */
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
                                    } elseif ($startTimeClnt > $startTimeDb && $startTimeClnt < $endTimeDb) {
                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                        $canAddEdit = false;
                                        $errorIndex++;
                                        $error_msg++;
                                        $msg = ' msg1';
                                    } elseif ($endTimeClnt > $startTimeDb && $endTimeClnt < $endTimeDb) {
                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                        $canAddEdit = false;
                                        $errorIndex++;
                                        $error_msg++;
                                        $msg = ' msg2';
                                    } elseif ($startTimeDb > $startTimeClnt && $startTimeDb < $endTimeClnt) {
                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                        $canAddEdit = false;
                                        $errorIndex++;
                                        $error_msg++;
                                        $msg = ' msg3';
                                    } elseif ($endTimeDb > $startTimeClnt && $endTimeDb < $endTimeClnt) {
                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                        $canAddEdit = false;
                                        $errorIndex++;
                                        $error_msg++;
                                        $msg = ' msg4';
                                    }

                                    /* elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                      $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                      $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                      $errormsgloop[$error_msg]['shift']= $keyClnt;
                                      $errormsgloop[$error_msg]['weekday']= $value;
                                      $errormsgloop[$error_msg]['message']= 'is invalid time';
                                      $canAddEdit = false;
                                      $errorIndex++;$error_msg++;
                                      $msg = ' msg1';
                                      } elseif ($endTimeClnt >= $startTimeDb && $endTimeClnt <= $endTimeDb) {
                                      $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                      $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                      $errormsgloop[$error_msg]['shift']= $keyClnt;
                                      $errormsgloop[$error_msg]['weekday']= $value;
                                      $errormsgloop[$error_msg]['message']= 'is invalid time';
                                      $canAddEdit = false;
                                      $errorIndex++;$error_msg++;
                                      $msg = ' msg2';
                                      } elseif ($startTimeDb >= $startTimeClnt && $startTimeDb <= $endTimeClnt) {
                                      $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                      $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                      $errormsgloop[$error_msg]['shift']= $keyClnt;
                                      $errormsgloop[$error_msg]['weekday']= $value;
                                      $errormsgloop[$error_msg]['message']= 'is invalid time';
                                      $canAddEdit = false;
                                      $errorIndex++;$error_msg++;
                                      $msg = ' msg3';
                                      } elseif ($endTimeDb > $startTimeClnt && $endTimeDb <= $endTimeClnt) {
                                      $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                      $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                      $errormsgloop[$error_msg]['shift']= $keyClnt;
                                      $errormsgloop[$error_msg]['weekday']= $value;
                                      $errormsgloop[$error_msg]['message']= 'is invalid time';
                                      $canAddEdit = false;
                                      $errorIndex++;$error_msg++;
                                      $msg = ' msg4';
                                      } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                      $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                      $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                      $errormsgloop[$error_msg]['shift']= $keyClnt;
                                      $errormsgloop[$error_msg]['weekday']= $value;
                                      $errormsgloop[$error_msg]['message']= 'is invalid time';
                                      $canAddEdit = false;
                                      $errorIndex++;$error_msg++;
                                      $msg = ' msg5';
                                      } */ else {
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
                                        /* elseif($starttime_check >= $startTimeClnt && $starttime_check <= $endTimeClnt) {
                                          $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                          $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                          $errormsgloop[$error_msg]['shift']= $keyClnt;
                                          $errormsgloop[$error_msg]['weekday']= $value;
                                          $errormsgloop[$error_msg]['message']= 'msg3 is invalid time';
                                          $canAddEdit = false;
                                          $errorIndex++;$error_msg++;
                                          $msg = ' msg3';
                                          }
                                          elseif($endtime_check > $startTimeClnt && $endtime_check <= $endTimeClnt) {
                                          $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                          $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                          $errormsgloop[$error_msg]['shift']= $keyClnt;
                                          $errormsgloop[$error_msg]['weekday']= $value;
                                          $errormsgloop[$error_msg]['message']= 'msg 4is invalid time';
                                          $canAddEdit = false;
                                          $errorIndex++;$error_msg++;
                                          $msg = ' msg4';
                                          }
                                          elseif($startTimeClnt >= $starttime_check && $startTimeClnt < $endtime_check) {
                                          $errormsgloop[$error_msg]['start_time']=$startTimeClnt;
                                          $errormsgloop[$error_msg]['end_time']= $endTimeClnt;
                                          $errormsgloop[$error_msg]['shift']= $keyClnt;
                                          $errormsgloop[$error_msg]['weekday']= $value;
                                          $errormsgloop[$error_msg]['message']= 'is invalid time';
                                          $canAddEdit = false;
                                          $errorIndex++;$error_msg++;
                                          $msg = ' msg5';
                                          } */
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
                    if ($canAddEdit == false || !empty($errormsgloop)) {
                        if (!empty($errormsgloop)) {
                            $html = array();
                            $weekdaysl = array();
                            $remove_duplicate = array();
                            foreach ($errormsgloop as $msgloop) {
                                $keyshifts = $msgloop['shift'];
                                if (!in_array($keyshifts . '-' . $msgloop['weekday'], $remove_duplicate)) {
                                    $remove_duplicate[] = $keyshifts . '-' . $msgloop['weekday'];
                                    $weekdaysl[$keyshifts][] = $msgloop['weekday'];
                                    $html[$keyshifts] = 'Shift time ' . date('h:i a', $msgloop['start_time']) . ' - ' . date('h:i a', $msgloop['end_time']) . ' on ' . implode(',', $weekdaysl[$keyshifts]) . ' ' . $msgloop['message'];
                                }
                            }
                            Yii::$app->session->setFlash('shifterror', implode(" , ", $html));
                        } else {
                            Yii::$app->session->setFlash('shifterror', "'Shift time invalid'");
                        }
                        return $this->render('shift/add-shift', ['defaultCurrrentDay' => strtotime($date), 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'modelAddress' => $addAddress, 'userAdddressImages' => $imgModel, 'postData' => $post]);
                    } elseif ($canAddEdit == true) {
                        if ($addAddress->save()) {
                            $files = UploadedFile::getInstances($imgModel, 'image');
                            if (!empty($files)) {
                                $imageUpload = DrsImageUpload::updateAddressImageListWeb($addAddress->id, $files);
                            }
                            if (!empty($insertShift)) {
                                foreach ($insertShift as $key => $value) {
                                    $saveScheduleData = new UserSchedule();
                                    $saveScheduleData->load(['UserSchedule' => $value['AddScheduleForm']]);
                                    $saveScheduleData->address_id = $addAddress->id;
                                    $saveScheduleData->start_time = strtotime($value['AddScheduleForm']['start_time']);
                                    $saveScheduleData->end_time = strtotime($value['AddScheduleForm']['end_time']);
                                    if ($saveScheduleData->save()) {
                                        
                                    } else {
                                        echo '<pre>';
                                        print_r($saveScheduleData->getErrors());
                                    }
                                }
                            }
                            //add shift keys to user_schedule table
                            $attender_shifts = DrsPanel::editShiftToAttender(Yii::$app->user->id);
                            $shifts_keys = Drspanel::addUpdateShiftKeys(Yii::$app->user->id);
                            $updateStatusShift = DrsPanel::userShiftsStatus(Yii::$app->user->id);
                            $loadtodayshifts = DrsPanel::getScheduleShifts(Yii::$app->user->id, date('Y-m-d'));

                            Yii::$app->session->setFlash('success', "'Shift Added Successfully'");
                            return $this->redirect(['/doctor/my-shifts']);
                        } else {
                            //echo "<pre>"; print_r($addAddress);
                            // echo "<pre>"; print_r($addAddress->getErrors());die;
                            Yii::$app->session->setFlash('error', "'Not added'");
                        }
                    } else {
                        Yii::$app->session->setFlash('error', "'Not added'");
                    }
                } else {
                    Yii::$app->session->setFlash('error', "'Please add atleast one shift'");
                }
            }
        }
        $scheduleslist = DrsPanel::weekSchedules($this->loginUser->id);
        $hospitals = DrsPanel::doctorHospitalList($this->loginUser->id);
        return $this->render('shift/add-shift', ['defaultCurrrentDay' => strtotime($date), 'hospitals' => $hospitals['apiList'], 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'listaddress' => $hospitals['listaddress'], 'scheduleslist' => $scheduleslist,
                    'modelAddress' => $addAddress, 'userAdddressImages' => $imgModel]);
    }

    public function actionEditShift($id = NULL) {
        $loginID = Yii::$app->user->id;
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
                                return $this->redirect(['/doctor/my-shifts']);
                            } else {
                                
                            }
                        } else {
                            Yii::$app->session->setFlash('error', "'Please Select days'");
                            return $this->redirect(['/doctor/my-shifts']);
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
                            return $this->redirect(['/doctor/my-shifts']);
                        } else {
                            Yii::$app->session->setFlash('error', "'Please try again'");
                        }
                    }
                }
            }
        }
        return $this->render('shift/edit-shift', ['defaultCurrrentDay' => strtotime($date), 'model' => $newShift, 'weeks' => $week_array, 'availibility_days' => $availibility_days, 'shifts' => $shifts, 'modelAddress' => $address, 'userAdddressImages' => $imgModel, 'addressImages' => $addressImages, 'disable_field' => $disable_field]);
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
            $loginID = Yii::$app->user->id;
            $address = UserAddress::findOne($address_id);
            if ($address->user_id == $loginID) {
                $disable_field = 0;
            } else {
                $disable_field = 1;
            }
            $addressImages = UserAddressImages::find()->where(['address_id' => $address_id])->all();
        }
        echo $this->renderAjax('shift/_address_images', ['form' => $form, 'addressImages' => $addressImages, 'userAdddressImages' => $userAdddressImages, 'disable_field' => $disable_field]);
        exit();
    }

    public function actionDeleteShiftWithAddress() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $address_id = $post['address_id'];
            $user_id = $post['user_id'];
            $address_delete = DrsPanel::deleteAddresswithShifts($user_id, $address_id);
            Yii::$app->session->setFlash('success', "'Shift Deleted!'");
            return $this->redirect(['/doctor/my-shifts']);
        }
        return NULL;
    }

    public function saveShiftData($ids, $keyClnt, $post, $day_shift, $shiftcount) {

        $shift['AddScheduleForm']['user_id'] = $ids;
        $shift['AddScheduleForm']['start_time'] = $post['AddScheduleForm']['start_time'][$keyClnt];
        $shift['AddScheduleForm']['end_time'] = $post['AddScheduleForm']['end_time'][$keyClnt];
        if (isset($post['AddScheduleForm']['appointment_time_duration'][$keyClnt]) && ($post['AddScheduleForm']['appointment_time_duration'][$keyClnt] > 0)) {
            $shift['AddScheduleForm']['appointment_time_duration'] = $post['AddScheduleForm']['appointment_time_duration'][$keyClnt];
        } else {
            $shift['AddScheduleForm']['appointment_time_duration'] = 1;
        }
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

    /* Today Timing */

    public function actionDayShifts() {
        $user_id = Yii::$app->user->id;
        if ($this->checkProfileInactive($user_id)) {
            Yii::$app->session->setFlash('error', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $doctor = User::findOne($user_id);
        $params = Yii::$app->request->queryParams;

        if (!empty($params) && isset($params['date'])) {
            $date = $params['date'];
        } else {
            $date = date('Y-m-d');
        }
        $getShists = DrsPanel::getBookingShifts($user_id, $date, $user_id);
        return $this->render('shift/day-shifts', ['defaultCurrrentDay' => strtotime($date), 'shifts' => $getShists, 'doctor' => $doctor, 'date' => $date]);
    }

    public function actionUpdateShiftStatus() {
        $response["status"] = 0;
        $response["error"] = true;
        $response['message'] = 'You have do not permission.';
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $params['booking_closed'] = $post['status'];
            $params['doctor_id'] = $this->loginUser->id;
            $params['date'] = date('Y-m-d', $post['date']);
            $params['schedule_id'] = $post['id'];
            $response = DrsPanel::updateShiftStatus($params);
            if (empty($response)) {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'You do not have permission.';
            }
        }
        return json_encode($response);
    }

    public function actionAjaxAddressList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));
            $appointments = DrsPanel::getBookingShifts($this->loginUser->id, $date, $this->loginUser->id);
            echo $this->renderAjax('shift/_address-with-shift', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments]);
            exit();
        }
    }

    public function actionGetShiftDetails() {
        $user_id = Yii::$app->user->id;
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['id']) && isset($post['date'])) {
                $schedule_id = $post['id'];
                $shift_id = $post['shift_id'];
                $date = date('Y-m-d', $post['date']);
                $weekday = DrsPanel::getDateWeekDay($date);
                $userSchedule = UserScheduleDay::find()->where(['schedule_id' => $schedule_id, 'date' => $date, 'weekday' => $weekday, 'user_id' => $user_id])->one();
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
        $user_id = Yii::$app->user->id;
        $updateShift = new AddScheduleForm();
        if (Yii::$app->request->isAjax) {
            $updateShift->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($updateShift);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $shift_id = $post['AddScheduleForm']['id'];
            $update_shift = DrsPanel::updateShiftTiming($shift_id, $post, $post['schedule_id']);
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
            return $this->redirect(['/doctor/day-shifts', 'date' => $date]);
        }
    }

    /*     * **************************Booking/Appointment************************************** */

    public function actionAppointments($type = '') {
        $id = Yii::$app->user->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('error', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $doctor = User::findOne($id);
        $date = date('Y-m-d');
        if ($type == 'current_appointment') {
            $current_shifts = 0;
            $bookings = array();
            $getSlots = DrsPanel::getBookingShifts($this->loginUser->id, $date, $this->loginUser->id);
            $appointments = DrsPanel::getCurrentAppointments($this->loginUser->id, $date, $current_shifts, $getSlots);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $current_shifts = $appointments['current_selected'];
                    $bookings = $appointments['bookings'];
                }
            }
            return $this->render('/doctor/appointment/current-appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'bookings' => $bookings, 'type' => $type, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'message' => '']);
        } elseif ($type == 'current_shift') {
            $current_shifts = '';
            $getSlots = DrsPanel::getBookingShifts($id, $date, $id);
            $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
            if (!empty($checkForCurrentShift)) {
                $current_shifts = isset($checkForCurrentShift['shift_id']) ? $checkForCurrentShift['shift_id'] : '';
                $current_affairs = DrsPanel::getCurrentAffair($checkForCurrentShift, $this->loginUser->id, $date, $current_shifts, $getSlots);

                if ($current_affairs['status'] == 1 && empty($current_affairs['error'])) {
                    $shifts = $current_affairs['all_shifts'];
                    $appointments = $current_affairs['data'];
                    $current_shifts = $current_affairs['schedule_id'];
                    return $this->render('/doctor/appointment/current-affair', ['schedule_id' => $current_affairs['schedule_id'], 'is_completed' => $current_affairs['is_completed'], 'is_cancelled' => $current_affairs['is_cancelled'], 'is_started' => $current_affairs['is_started'], 'Shifts' => $shifts, 'appointments' => $appointments, 'shift_id' => $current_shifts, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => 'current_shift', 'date' => $date]);
                } else {
                    return $this->render('/doctor/appointment/current-affair', ['schedule_id' => '', 'shift_id' => '', 'is_completed' => false, 'is_started' => false, 'is_cancelled' => false, 'Shifts' => array(), 'appointments' => array(), 'current_shifts' => array(), 'doctor' => $doctor, 'type' => 'current_shift', 'date' => $date]);
                }
            } else {
                Yii::$app->session->setFlash('error', "'Sorry doctor Facility Not Added'");
            }
        } else {
            $type = 'book';
            $current_shifts = 0;
            $slots = array();
            $message = '';
            $getSlots = DrsPanel::getBookingShifts($id, $date, $id);
            $appointments = DrsPanel::getCurrentAppointments($id, $date, $current_shifts, $getSlots);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $current_shifts = $appointments['current_selected'];

                    $shift_check = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $id, 'schedule_id' => $current_shifts, 'status' => array('cancelled', 'completed')])->one();
                    if (!empty($shift_check)) {
                        if ($shift_check->status == 'completed') {
                            $message = 'Shift Completed!';
                        } else {
                            $message = 'Shift Cancelled!';
                        }
                    } else {
                        $slots = DrsPanel::getBookingShiftSlots($id, $date, $current_shifts, 'available');
                        $message = 'Shift slots not available!';
                    }
                }
            }
            return $this->render('/doctor/appointment/appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'slots' => $slots, 'type' => $type, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'message' => $message]);
        }
    }

    public function actionAjaxToken() {
        $result = ['status' => false, 'msg' => 'Invalid Request.'];
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $current_shifts = $post['shift_id'];
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');
            $message = '';
            $slots = array();
            $shift_check = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $this->loginUser->id, 'schedule_id' => $current_shifts, 'status' => array('cancelled', 'completed')])->one();
            if (!empty($shift_check)) {
                if ($shift_check->status == 'completed') {
                    $message = 'Shift Completed!';
                } else {
                    $message = 'Shift Cancelled!';
                }
            } else {
                $slots = DrsPanel::getBookingShiftSlots($this->loginUser->id, $date, $current_shifts, 'available');
                $message = 'Shift slots not available!';
            }
            echo $this->renderAjax('/common/_slots', ['slots' => $slots, 'doctor_id' => $this->loginUser->id, 'userType' => 'doctor', 'message' => $message]);
            exit();
        }
    }

    public function actionAjaxCurrentAppointment() {
        $result = ['status' => false, 'msg' => 'Invalid Request.'];
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $bookings = array();
            $current_shifts = $post['shift_id'];
            $id = $this->loginUser->id;
            $doctor = User::findOne($id);
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');

            $getSlots = DrsPanel::getBookingShifts($this->loginUser->id, $date, $this->loginUser->id);
            $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
            if (!empty($checkForCurrentShift)) {
                $current_affairs = DrsPanel::getCurrentAffair($checkForCurrentShift, $this->loginUser->id, $date, $current_shifts, $getSlots);
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
                    echo $this->renderAjax('/common/_current_bookings', ['bookings' => $appointments, 'doctor_id' => $this->loginUser->id, 'userType' => 'doctor', 'schedule_id' => $current_shifts, 'shift_id' => $checkForCurrentShift['shift_id'], 'is_completed' => $is_completed, 'is_cancelled' => $is_cancelled, 'is_started' => $is_started, 'doctor' => $doctor]);
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
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');
            $getShists = DrsPanel::getBookingShifts($this->loginUser->id, $date, $this->loginUser->id);
            $appointments = DrsPanel::getCurrentAppointments($this->loginUser->id, $date, $current_shifts, $getShists);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $bookings = $appointments['bookings'];
                }
            }
            echo $this->renderAjax('/common/_bookings', ['bookings' => $bookings, 'doctor_id' => $this->loginUser->id, 'userType' => 'doctor']);
            exit();
        }
    }

    public function actionBookingConfirm() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $slot_id = explode('-', $post['slot_id']);
            $id = Yii::$app->user->id;
            $doctorProfile = UserProfile::find()->where(['user_id' => $id])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::findOne($doctorProfile->user_id);
                $slot = UserScheduleSlots::find()->andWhere(['user_id' => $doctor->id, 'id' => $slot_id[1], 'status' => 'available'])->one();
                if ($slot) {
                    $schedule = UserSchedule::findOne($slot->schedule_id);
                    $model = new AppointmentForm();
                    $model->doctor_id = $doctor->id;
                    $model->slot_id = $slot->id;
                    $model->schedule_id = $slot->schedule_id;
                    return $this->renderAjax('/common/_booking_confirm.php', ['doctor' => $doctor, 'slot' => $slot, 'schedule' => $schedule, 'address' => UserAddress::findOne($schedule->address_id), 'model' => $model, 'userType' => 'doctor'
                    ]);
                } else {
                    Yii::$app->session->setFlash('error', 'Slot not available for booking');
                    return $this->redirect(Yii::$app->request->referrer);
                }
            }
        }
    }

    public function actionBookingConfirmStep2() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $slot_id = $post['slot_id'];
            $id = Yii::$app->user->id;
            $doctorProfile = UserProfile::find()->where(['user_id' => $id])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::findOne($doctorProfile->user_id);
                $slot = UserScheduleSlots::find()->where(['user_id' => $doctor->id, 'id' => $slot_id])->one();
                if ($slot) {
                    $schedule = UserSchedule::findOne($slot->schedule_id);
                    $model = new AppointmentForm();
                    $model->doctor_id = $doctor->id;
                    $model->slot_id = $slot->id;
                    $model->schedule_id = $slot->schedule_id;
                    $model->user_name = ucfirst($post['name']);
                    $model->user_phone = $post['phone'];
                    $model->user_gender = $post['gender'];
                    return $this->renderAjax('/common/_booking_confirm_step2.php', ['doctor' => $doctor, 'slot' => $slot, 'schedule' => $schedule, 'address' => UserAddress::findOne($schedule->address_id), 'model' => $model, 'userType' => 'doctor'
                    ]);
                } else {
                    Yii::$app->session->setFlash('error', 'Slot not available for booking');
                    return $this->redirect(Yii::$app->request->referrer);
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
                        $check = DrsPanel::blockSlot($slot_id, Yii::$app->user->id);
                        if ($check == 'success') {
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

                                //Yii::$app->session->setFlash('success', "Appointment booked successfully.");
                                //return $this->renderAjax('/common/_booking_confirm_step2');
                                //return $this->redirect(['/doctor/appointments']);
                            }
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Slot not available for booking';
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
        echo json_encode($response);
        exit();
    }

    public function actionCurrentAppointmentShiftUpdate() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();

        $id = Yii::$app->user->id;
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
                        Notifications::shiftStartNotification($doctor->id, $shift, $date);
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
                            return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                        }
                    } else {
                        Yii::$app->session->setFlash('error', "'Please try again!'");
                        return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                    }
                } else {
                    $html = ucfirst($schedule_check->shift_label) . ' is pending';
                    Yii::$app->session->setFlash('error', "'$html'");
                    return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                }
            } else {
                Yii::$app->session->setFlash('error', "'Shift not found'");
                return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
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
                            return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                        }
                    } else {
                        Yii::$app->session->setFlash('error', "'Please try again!'");
                        return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                    }
                } else {
                    $html = ucfirst($schedule_check->shift_label) . ' is pending';
                    Yii::$app->session->setFlash('error', "'$html'");
                    return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                }
            } else {
                Yii::$app->session->setFlash('error', "'Shift not found'");
                return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
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
                        return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                    }
                } else {
                    Yii::$app->session->setFlash('error', "'Please try again'");
                    return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
                }
            } else {
                Yii::$app->session->setFlash('error', "'Shift not found'");
                return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
            }
        } else {
            Yii::$app->session->setFlash('error', "'Please try again!'");
            return $this->redirect(['/doctor/appointments', 'type' => 'current_shift']);
        }
    }

    public function actionAppointmentStatusUpdate() {
        $res = ['status' => false];
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $params = Yii::$app->request->post();
            $id = Yii::$app->user->id;
            $doctor = User::findOne($id);
            $date = date('Y-m-d');
            $shift = $params['schedule_id'];
            $status = $params['status'];
            $res = DrsPanel:: updateCurrentStatus($status, $params['doctor_id'], $date, $shift);
            if ($res['status'] == false) {
                $html = $res['message'];
                Yii::$app->session->setFlash('error', "'$html'");
                return $this->redirect(Yii::$app->request->referrer);
            } else {
                $res = ['status' => true];
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
            $result['result'] = $this->renderAjax('/common/_appointment_date_slider', ['doctor_id' => $user_id, 'dates_range' => $dates_range, 'date' => $first, 'type' => $type, 'userType' => 'doctor']);
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
            $doctor = User::findOne($id);
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $type = $post['type'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));

            $current_shifts = 0;
            $slots = array();
            $bookings = array();
            $message = '';
            $getShists = DrsPanel::getBookingShifts($this->loginUser->id, $date, $this->loginUser->id);
            $appointments = DrsPanel::getCurrentAppointments($this->loginUser->id, $date, $current_shifts, $getShists);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $current_shifts = $appointments['shifts'][0]['schedule_id'];
                    if ($type == 'book') {
                        $shift_check = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $this->loginUser->id, 'schedule_id' => $current_shifts, 'status' => array('cancelled', 'completed')])->one();
                        if (!empty($shift_check)) {
                            if ($shift_check->status == 'completed') {
                                $message = 'Shift Completed!';
                            } else {
                                $message = 'Shift Cancelled!';
                            }
                        } else {
                            $slots = DrsPanel::getBookingShiftSlots($this->loginUser->id, $date, $current_shifts, 'available');
                            $message = 'Shift slots not available!';
                        }
                    } else {
                        $bookings = $appointments['bookings'];
                    }
                }
            }
            echo $this->renderAjax('/common/_appointment_shift_slots', ['appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => $type, 'slots' => $slots, 'bookings' => $bookings, 'userType' => 'doctor', 'message' => $message]);
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

            echo $this->renderAjax('/common/_booking_detail', ['booking' => $booking, 'doctor_id' => $this->loginUser->id, 'userType' => 'doctor', 'booking_type' => $booking_type, 'current' => $current]);
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
                $appointment->payment_status = UserAppointment::PAYMENT_COMPLETED;
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
        return $this->redirect(['/doctor/appointments', 'type' => 'current_appointment']);
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
        return $this->redirect(['/doctor/appointments', 'type' => 'current_appointment']);
    }

    public function actionAjaxCancelAppointment() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $appointment_id = $post['appointment_id'];

            $response = DrsPanel::cancelAppointmentById($appointment_id, 'Doctor');
            $html = $response['message'];
            if ($response['status'] == 'success') {
                $result = array('status' => 'success', 'title' => 'Success!', 'message' => $html);
                echo json_encode($result);
                exit();
                //  Yii::$app->session->setFlash('success', "'$html'");
            } else {
                $result = array('status' => 'error', 'title' => 'Error!', 'message' => 'Please try again');
                echo json_encode($result);
                exit();
                // Yii::$app->session->setFlash('error', 'Please try again');
            }
            //return $this->redirect(Yii::$app->request->referrer);
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /*     * **************************History************************************** */

    public function actionPatientHistory() {
        $date = date('Y-m-d');
        $user_id = Yii::$app->user->id;
        if ($this->checkProfileInactive($user_id)) {
            Yii::$app->session->setFlash('error', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $doctor = User::findOne($user_id);
        $current_selected = 0;
        $checkForCurrentShift = 0;
        $appointments = $shiftAll = $typeCount = $history = [];
        $getSlots = DrsPanel::getBookingShifts($user_id, $date, $user_id);
        if (!empty($getSlots)) {
            $checkForCurrentShift = $getSlots[0]['schedule_id'];
            $current_selected = $checkForCurrentShift;
            $getAppointments = DrsPanel::appointmentHistory($user_id, $date, $current_selected, $getSlots, '');
            $shiftAll = DrsPanel::getDoctorAllShift($user_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
            $appointments = $getAppointments['bookings'];
            $history = $getAppointments['total_history'];
            $typeCount = $getAppointments['type'];
        }
        return $this->render('/doctor/history-statistics/patient-history', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'current_selected' => $current_selected]);
    }

    public function actionAjaxHistoryContent() {
        $user_id = Yii::$app->user->id;
        $doctor = User::findOne($user_id);
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctor_id = $post['user_id'];
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));
            $current_selected = 0;
            $checkForCurrentShift = 0;
            $appointments = $shiftAll = $typeCount = $history = [];
            $getSlots = DrsPanel::getBookingShifts($user_id, $date, $user_id);
            if (!empty($getSlots)) {
                $checkForCurrentShift = $getSlots[0]['schedule_id'];
                if ($current_selected == 0) {
                    $current_selected = $checkForCurrentShift;
                }
                $getAppointments = DrsPanel::appointmentHistory($user_id, $date, $current_selected, $getSlots);
                $shiftAll = DrsPanel::getDoctorAllShift($user_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                $appointments = $getAppointments['bookings'];
                $history = $getAppointments['total_history'];
                $typeCount = $getAppointments['type'];
            }
            echo $this->renderAjax('/doctor/history-statistics/_history-content', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $appointments, 'shifts' => $shiftAll, 'doctor' => $doctor, 'current_selected' => $current_selected]);
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
            $current_shifts = $post['shift_id'];
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');
            $getShists = DrsPanel::getBookingShifts($this->loginUser->id, $date, $this->loginUser->id);

            $status = [UserAppointment::STATUS_ACTIVE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_COMPLETED, UserAppointment::STATUS_CANCELLED];
            $appointments = DrsPanel::getCurrentAppointments($this->loginUser->id, $date, $current_shifts, $getShists, $status);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $bookings = $appointments['bookings'];
                    $history = $appointments['total_history'];
                    $typeCount = $appointments['type'];
                }
            }
            echo $this->renderAjax('/doctor/history-statistics/_history-patient', ['appointments' => $bookings, 'doctor_id' => $this->loginUser->id, 'userType' => 'doctor', 'history_count' => $history, 'typeCount' => $typeCount,]);
            exit();
        }
    }

    public function actionUserStatisticsData() {
        $date = date('Y-m-d');
        $user_id = Yii::$app->user->id;
        if ($this->checkProfileInactive($user_id)) {
            Yii::$app->session->setFlash('error', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $doctor = User::findOne($user_id);
        $current_selected = 0;
        $checkForCurrentShift = 0;
        $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
        $appointments = $shiftAll = $typeCount = [];
        $getSlots = DrsPanel::getBookingShifts($user_id, $date, $user_id);
        if (!empty($getSlots)) {
            $checkForCurrentShift = $getSlots[0]['schedule_id'];
            $current_selected = $checkForCurrentShift;
            $getAppointments = DrsPanel::appointmentHistory($user_id, $date, $current_selected, $getSlots, $typeselected);
            $shiftAll = DrsPanel::getDoctorAllShift($user_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
            $appointments = $getAppointments['bookings'];
            $typeCount = $getAppointments['type'];
        }
        return $this->render('/doctor/history-statistics/user-statistics-data', ['typeCount' => $typeCount, 'typeselected' => $typeselected, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'doctor_id' => $user_id, 'current_selected' => $current_selected]);
    }

    public function actionAjaxUserStatisticsData() {
        $user_id = Yii::$app->user->id;
        $doctor = User::findOne($user_id);
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));

            $current_selected = 0;
            $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
            $checkForCurrentShift = 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($this->loginUser->id, $date, $user_id);
            if (!empty($getSlots)) {
                $checkForCurrentShift = $getSlots[0]['schedule_id'];
                $current_selected = $checkForCurrentShift;
                $getAppointments = DrsPanel::appointmentHistory($user_id, $date, $current_selected, $getSlots, $typeselected);
                $shiftAll = DrsPanel::getDoctorAllShift($user_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            return $this->renderAjax('/doctor/history-statistics/_user-statistics-data', ['typeCount' => $typeCount, 'typeselected' => $typeselected, 'appointments' => $appointments, 'shifts' => $shiftAll, 'date' => strtotime($date), 'doctor' => $doctor, 'current_shifts' => $current_selected, 'doctor_id' => $user_id]);
        }
    }

    public function actionAjaxStatisticsData() {
        $user_id = $this->loginUser->id;
        $result['status'] = false;
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctor = User::findOne($user_id);
            $date = ($post['date']) ? date('Y-m-d', strtotime($post['date'])) : date('Y-m-d');
            if (isset($post['type'])) {
                $typeselected = ($post['type'] == 'online') ? UserAppointment::BOOKING_TYPE_ONLINE : UserAppointment::BOOKING_TYPE_OFFLINE;
            } else {
                $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
            }
            $checkForCurrentShift = (isset($post['shift_id'])) ? $post['shift_id'] : 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($this->loginUser->id, $date, $user_id);
            if (!empty($getSlots)) {
                $getAppointments = DrsPanel::appointmentHistory($user_id, $date, $checkForCurrentShift, $getSlots, $typeselected);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            $result['status'] = true;
            $result['appointments'] = $this->renderAjax('/common/_appointment-token', ['appointments' => $appointments, 'typeselected' => $typeselected, 'typeCount' => $typeCount, 'doctor' => $doctor, 'userType' => 'doctor', 'doctor_id' => $user_id]);
            $result['typeCount'] = $typeCount;
            $result['typeselected'] = $typeselected;
        }
        return json_encode($result);
    }

    public function actionAjaxPatientList() {
        $user_id = $this->loginUser->id;
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $date = ($post['date']) ? date('Y-m-d', strtotime($post['date'])) : date('Y-m-d');
            $current_selected = ($post['schedule_id']) ? $post['schedule_id'] : 0;
            $checkForCurrentShift = 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($this->loginUser->id, $date, $user_id);
            if (!empty($getSlots)) {
                $getAppointments = DrsPanel::appointmentHistory($user_id, $date, $current_selected, $getSlots);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            return $this->renderAjax('/doctor/history-statistics/_history-patient', ['appointments' => $appointments]);
        }
        return NULL;
    }

    /* Experience */

    public function actionExperiences($exp_id = NULL) {
        $user_id = Yii::$app->user->id;
        if ($exp_id) {
            $model = UserExperience::findOne($exp_id);
        } else {
            $model = new UserExperience();
        }
        $msg = 'Added';
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
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
                Yii::$app->session->setFlash('success', "'Experience Added!'");
                //return $this->redirect(['/doctor/experiences']);
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        $lists = UserExperience::find()->where(['user_id' => $user_id])->all();
        return $this->render('/doctor/experience/experiences', ['model' => $model, 'lists' => $lists]);
    }

    public function actionExperienceDetails($exp_id = NULL) {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user = UserExperience::findOne($post['id']);


            $model = new UserExperience();
            $model->id = $user['id'];
            $model->user_id = $user['user_id'];
            $model->start = date('Y', $user['start']);
            $model->end = date('Y', $user['end']);
            $model->hospital_name = trim($user->hospital_name);
            return $this->renderAjax('/doctor/experience/_experiences_edit', [
                        'model' => $model,
            ]);
        }
        return NULL;
    }

    public function actionExperienceUpdate() {
        $model = new UserExperience();
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $model->load($post);
            if ($model->upsert($post)) {
                Yii::$app->session->setFlash('success', "'Experience Updated!'");

                //return $this->redirect(['/doctor/experiences']);
                return $this->redirect(Yii::$app->request->referrer);
            }
        }
        return NULL;
    }

    public function actionExperienceDelete() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $experience = UserExperience::findOne($post['id']);
            $experience->delete();
        }
        Yii::$app->session->setFlash('success', "'Experience Deleted!'");
        //return $this->redirect(['/doctor/experiences']);
        return $this->redirect(Yii::$app->request->referrer);
    }

    /* Educations */

    public function actionEducations($exp_id = NULL) {
        $user_id = Yii::$app->user->id;
        $model = UserEducations::findOne($exp_id);
        $msg = 'Added';
        if (empty($model))
            $model = new UserEducations();
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $post['UserEducations']['user_id'] = $user_id;
            $degree_add = $post['UserEducations']['education'];
            if ($degree_add != '') {
                $getDegree = MetaValues::find()->where(['key' => 2, 'value' => $degree_add])->one();
                if (empty($getDegree)) {
                    $degreeModel = new MetaValues();
                    $degreeModel->parent_key = 0;
                    $degreeModel->key = 2;
                    $degreeModel->value = $degree_add;
                    $degreeModel->label = $degree_add;
                    $degreeModel->status = 0;
                    $degreeModel->save();
                }
                $post['UserEducations']['id'] = $exp_id;
                $post['UserEducations']['education'] = $degree_add;
                $msg = 'Updated';
            } else {
                $post['UserEducations']['id'] = $exp_id;
                $post['UserEducations']['education'] = '';
                $msg = 'Updated';
            }
            $modelUpdate = UserEducations::upsert($post);
            if (count($modelUpdate) > 0) {
                //return $this->redirect(['/doctor/educations']);
                return $this->redirect(Yii::$app->request->referrer);
            }
        }
        $degrees = MetaValues::find()->where(['key' => 2, 'status' => 1])->all();
        $degreelist = array();
        $speciality_list = $treatment_list = array();
        foreach ($degrees as $d_key => $degree) {
            $degreelist[$degree->value] = $degree->label;
        }
        $edu_list = UserEducations::find()->where(['user_id' => $user_id])->all();
        return $this->render('/doctor/education/educations', ['model' => $model,
                    'edu_list' => $edu_list, 'degreelist' => $degreelist]);
    }

    public function actionEducationDetails($exp_id = NULL) {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user = UserEducations::findOne($post['id']);
            $model = new UserEducations();
            $model->id = $user['id'];
            $model->user_id = $user['user_id'];
            $model->start = date('Y', $user['start']);
            $model->end = date('Y', $user['end']);
            $model->education = trim($user->education);
            $model->collage_name = trim($user->collage_name);

            $degrees = MetaValues::find()->where(['key' => 2, 'status' => 1])->all();
            $degreelist = array();
            foreach ($degrees as $d_key => $degree) {
                $degreelist[$degree->value] = $degree->label;
            }

            $listdegree = trim($user->education);
            if ($listdegree != '') {
                $checkValue = MetaValues::find()->where(['key' => 2, 'value' => $listdegree])->one();
                if (!empty($checkValue)) {
                    $degreelist[$checkValue->value] = $checkValue->label;
                }
            }


            return $this->renderAjax('/doctor/education/_educations_edit', [
                        'model' => $model, 'degreelist' => $degreelist
            ]);
        }
        return NULL;
    }

    public function actionEducationUpdate() {
        $model = new UserEducations();
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $model->load($post);
            if ($model->upsert($post)) {
                //return $this->redirect(['/doctor/educations']);
                return $this->redirect(Yii::$app->request->referrer);
            }
        }
        return NULL;
    }

    public function actionEducationDelete() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $education = UserEducations::findOne($post['id']);
            $education->delete();
        }
        //return $this->redirect(['/doctor/educations']);
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionMyPatients() {
        $id = $this->loginUser->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('error', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $lists = DrsPanel::myPatients(['doctor_id' => $this->loginUser->id]);
        return $this->render('/doctor/my-patients', ['lists' => $lists]);
    }

    public function actionCustomerCare() {
        $customer_phone = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Phone'])->one();
        $customer_email = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Email'])->one();
        $customer = array('phone' => $customer_phone, 'email' => $customer_email);
        return $this->render('/doctor/customer-care', ['customer' => $customer]);
    }

    public function actionServices($service_id = NULL) {
        $user_id = Yii::$app->user->id;
        $groupid = Groups::GROUP_DOCTOR;
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
                    $post['UserProfile']['services'] = implode(', ', $Userservices);
                }

                $modelUpdate = UserProfile::upsert($post, $user_id, $groupid);
                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Doctor Services Updated'");
                    return $this->redirect(['/doctor/services']);
                } else {
                    Yii::$app->session->setFlash('error', "'Sorry doctor Facility Not Added'");
                }
            }
        }

        $servicesList = UserProfile::find()->andWhere(['user_id' => $user_id])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->all();
        $services = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 11])->all();

        return $this->render('/doctor/services', ['model' => $model, 'services' => $services, 'servicesList' => $servicesList]);
    }

    public function actionAjaxCityList() {
        $result = '<option value="">Select City</option>';
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $rst = Drspanel::getCitiesList($post['state_id'], 'name');
            foreach ($rst as $key => $item) {
                $result = $result . '<option value="' . $item->name . '">' . $item->name . '</option>';
            }
        }
        return $result;
    }

    public function actionCityList() {
        $city_list = [];
        $form = ActiveForm::begin(['id' => 'shiftform']);
        if (Yii::$app->request->post()) {
            $modelAddress = new UserAddress();
            $post = Yii::$app->request->post();
            $rst = Drspanel::getCitiesList($post['state_id'], 'name');
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
            $checkValue = Areas::find()->where(['city_id' => $city, 'name' => $area])->one();
            if (empty($lat) && empty($lng) && !empty($checkValue)) {
                $lat = $checkValue->lat;
                $lng = $checkValue->lng;
            }
            echo $this->renderAjax('/doctor/shift/_map_location', ['lat' => $lat, 'lng' => $lng]);
            exit();
        }
    }

    /* Hospital Request */

    public function actionAcceptHospitalRequest($doctor_id = NULL) {
        $doctor_id = $this->loginUser->id;
        if ($this->checkProfileInactive($doctor_id)) {
            Yii::$app->session->setFlash('error', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $usergroupid = Groups::GROUP_HOSPITAL;
        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            if (isset($post['UserRequest']['request_to'])) {
                $Userrequstto = $post['UserRequest']['request_to'];
                foreach ($Userrequstto as $value) {
                    $postData['groupid'] = Groups::GROUP_HOSPITAL;
                    $postData['request_from'] = $hospital_id;
                    $postData['request_to'] = $value;
                    $postData['status'] = 2;
                    $type = 'Add';
                    $modelUpdate = UserRequest::updateStatus($postData, $type);
                }
                if (count($modelUpdate) > 0) {
                    Yii::$app->session->setFlash('success', "'Request accepted'");
                    return $this->redirect(['/doctor/accept-hospital-request']);
                } else {
                    Yii::$app->session->setFlash('error', "'Sorry request couldnot accept'");
                }
            }

            if (isset($post['UserProfile']['name'])) {
                $lists = UserProfile::find()->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->one();
                return $this->render('/doctor/accept-hospital-request', ['listsRecord' => $lists, 'doctor_id' => $doctor_id]);
            }
        }
        $lists = UserRequest::find()->andWhere(['request_to' => $doctor_id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        return $this->render('/doctor/accept-hospital-request', ['lists' => $lists, 'doctor_id' => $doctor_id]);
    }

    public function actionHospitalDetail($slug = '') {
        $doctor_id = $this->loginUser->id;
        if ($this->checkProfileInactive($doctor_id)) {
            Yii::$app->session->setFlash('erroralert', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $string = Yii::$app->request->queryParams;
        if (isset($string['speciality']) && !empty($string['speciality'])) {
            $selected_speciality = $string['speciality'];
        } else {
            $selected_speciality = 0;
        }
        if (!empty($slug)) {
            $profile = UserProfile::findOne(['slug' => $slug]);
            if (!empty($profile)) {
                $groupid = $profile->groupid;
                $user = User::find()->where(['id' => $profile->user_id, 'admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]])->one();
                if (!empty($user)) {
                    $hospital_id = $profile->user_id;
                    $getspecialities = Drspanel::getMyHospitalSpeciality($hospital_id);
                    $userAddress = UserAddress::findOne(['user_id' => $hospital_id]);
                    if (!empty($userAddress)) {
                        $addressImages = UserAddressImages::find()->where(['address_id' => $userAddress->id])->all();
                    } else {
                        $addressImages = array();
                    }
                    return $this->render('hospital-detail', [
                                'profile' => $profile, 'user' => $user, 'groupid' => $groupid, 'getspecialities' => $getspecialities,
                                'loginID' => $doctor_id, 'selected_speciality' => $selected_speciality, 'addressImages' => $addressImages]);
                }
            }
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }

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
            $post['groupid'] = Groups::GROUP_HOSPITAL;
            $type = 'Add';


            $modelUpdate = UserRequest::updateStatus($post, $type);
            if (count($modelUpdate) > 0) {
                Yii::$app->session->setFlash('success', "'Request accepted'");
                return $this->redirect(['/doctor/accept-hospital-request']);
            } else {
                Yii::$app->session->setFlash('error', "'Sorry request couldnot accepted'");
            }
        }

        exit;

        return Null;
    }

    /* Attender */

    public function actionAttendersList() {
        $id = $this->loginUser->id;
        if ($this->checkProfileInactive($id)) {
            Yii::$app->session->setFlash('error', "'Profile yet to be approved!'");
            return $this->redirect('profile');
        }
        $model = new AttenderForm();
        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }


        $addressList = DrsPanel::doctorHospitalList($this->loginUser->id);
        $listadd = $addressList['apiList'];
        $shift_array = array();
        $s = 0;
        $shift_value = array();
        $sv = 0;
        foreach ($listadd as $address) {
            $shifts = DrsPanel::getShiftListByAddress($this->loginUser->id, $address['id']);
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
            $model->parent_id = $this->loginUser->id;
            $model->created_by = 'Doctor';
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
                Yii::$app->session->setFlash('success', "'Attender Added!'");
                return $this->redirect(['/doctor/attenders']);
            }
        }

        $list = DrsPanel::attenderList(['parent_id' => $this->loginUser->id], 'apilist');
        return $this->render('/doctor/attender/list', ['list' => $list, 'user' => $this->loginUser,
                    'model' => $model,
                    'roles' => ArrayHelper::map(Yii::$app->authManager->getRoles(), 'name', 'name'),
                    'hospitals' => $addressList['listaddress'],
                    'shifts' => $shift_value]);
    }

    public function actionAttenderDetails() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user = $this->findModel($post['id']);
            $id = Yii::$app->user->id;

            $selectedShifts = Drspanel::shiftList(['user_id' => $id, 'attender_id' => $post['id']], 'list');

            $addressList = DrsPanel::doctorHospitalList($this->loginUser->id);
            $listadd = $addressList['apiList'];
            $shift_array = array();
            $s = 0;
            $shift_value = array();
            $sv = 0;
            $selectedShiftsIds = array();
            foreach ($listadd as $address) {
                $shifts = DrsPanel::getShiftListByAddress($this->loginUser->id, $address['id']);
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
            $model = new AttenderEditForm();
            $model->id = $post['id'];
            $model->name = $user['userProfile']['name'];
            $model->phone = trim($user->phone);
            $model->email = $user->email;
            $model->shift_id = $selectedShiftsIds;
            return $this->renderAjax('/doctor/attender/edit', [
                        'model' => $model,
                        'hospitals' => $addressList['listaddress'],
                        'shifts' => $shift_value,
            ]);
        }
        return NULL;
    }

    public function actionAttenderUpdate() {
        $model = new AttenderEditForm();
        $id = Yii::$app->user->id;

        if (Yii::$app->request->isAjax) {
            $model->load($_POST);
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();

            $attender_id = $post['AttenderEditForm']['id'];

            $addressList = DrsPanel::doctorHospitalList($this->loginUser->id);
            $listadd = $addressList['apiList'];
            $shift_array = array();
            $s = 0;
            $shift_value = array();
            $sv = 0;
            foreach ($listadd as $address) {
                $shifts = DrsPanel::getShiftListByAddress($this->loginUser->id, $address['id']);
                foreach ($shifts as $key => $shift) {
                    $shift_array[$s]['value'] = $shift['shifts_ids'];
                    $shift_array[$s]['label'] = $shift['name'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                    $shift_value[$sv] = $shift['name'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                    $s++;
                    $sv++;
                }
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
                if (!empty($upload)) {
                    $upload->saveAs($uploadDir . $image_name);
                }
                if (!empty($post['AttenderEditForm']['shift_id']) && count($post['AttenderEditForm']['shift_id']) > 0) {
                    $sel_shift = $post['AttenderEditForm']['shift_id'];
                    $shift_val = array();
                    foreach ($sel_shift as $s) {
                        $shift_selected_ids = $shift_array[$s];
                        $list = $shift_selected_ids['value'];
                        foreach ($list as $list) {
                            $shift_val[] = $list;
                        }
                    }
                    $addupdateAttender = DrsPanel::addUpdateAttenderToShifts($shift_val, $attender_id);
                } else {
                    $addupdateAttender = DrsPanel::addUpdateAttenderToShifts(array(), $attender_id);
                }
                Yii::$app->session->setFlash('success', "'Attender Updated!'");

                return $this->redirect(['/doctor/attenders']);
            }
        }
        return $this->redirect(['/doctor/attenders']);
    }

    public function actionAttenderDelete() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            if ($user = User::find()->andWhere(['id' => $post['id']])->andWhere(['groupid' => Groups::GROUP_ATTENDER])->andWhere(['parent_id' => $this->loginUser->id])->one()) {
                if (DrsPanel::attenderDelete($user->id)) {
                    Yii::$app->session->setFlash('success', "'Attender Deleted!'");
                    return $this->redirect(['/doctor/attenders']);
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

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {

        if (!Yii::$app->user->isGuest) {
            $groupid = Yii::$app->user->identity->userProfile->groupid;
            if ($groupid != Groups::GROUP_DOCTOR) {
                $this->redirect(array('/'));
            } else {
                return parent::beforeAction($action);
            }
        }

        $this->redirect(array('/'));
    }

    public function pr($data) {
        echo '<pre>';
        print_r($data);
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
                //return $this->redirect(Yii::$app->request->referrer);
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
