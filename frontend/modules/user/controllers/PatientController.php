<?php

namespace frontend\modules\user\controllers;

use common\components\DrsImageUpload;
use common\models\MetaValues;
use common\models\PatientMemberRecords;
use common\models\UserRating;
use common\models\UserRatingLogs;
use common\models\UserReminder;
use common\models\UserScheduleGroup;
use common\models\UserScheduleSlots;
use common\models\UserVerification;
use frontend\modules\user\models\RecordShareModel;
use Yii;
use yii\authclient\AuthAction;
use yii\db\Query;
use yii\filters\AccessControl;
use common\components\DrsPanel;
use yii\base\Exception;
use common\models\Groups;
use common\models\UserFavorites;
use common\models\User;
use common\models\UserProfile;
use common\models\UserAppointment;
use common\models\UserAddress;
use common\models\PatientMemberFiles;
use common\models\PatientMembers;
use frontend\modules\user\models\PatientMemberForm;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\widgets\ActiveForm;

/**
 * Class PatientController
 * @package frontend\modules\user\controllers
 * @author Eugene Terentev <eugene@terentev.net>
 */
class PatientController extends \yii\web\Controller {

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
                            return $this->loginUser['groupid'] == Groups::GROUP_PATIENT;
                        }
                    ],
                ]
            ]
        ];
    }

    public function actionProfile() {
        $id = Yii::$app->user->id;
        $userModel = $this->findModel($id);
        $userProfile = UserProfile::findOne(['user_id' => $id]);
        $genderlist = DrsPanel::getGenderList();
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $heightFI = array();
           
            $heightFI['feet'] = $post['UserProfile']['height'];
            $heightFI['inch'] = $post['UserProfile']['inch'];
            $address = $post['UserProfile']['address1'];
            $state = $post['UserProfile']['state'];
            $city_id = $post['UserProfile']['city_id'];
            $area = $post['UserProfile']['area'];
            $cityName = DrsPanel::getCityName($city_id);
            
            if ($post['UserProfile'] || $post['User']) {
                if ($address != '' && $area != '' && $cityName != '' && $state != '') {
                    $location = $address . ', ' . $area . ', ' . $cityName . ', ' . $state;
                    // Get lat long from google
                    $latlong = $this->get_lat_long($location); // create a function with the name "get_lat_long" given as below
                    $map = explode(',', $latlong);
                    $post['UserProfile']['lat'] = $map[0];
                    $post['UserProfile']['lng'] = $map[1];
                }

                $old_image = $userProfile->avatar;
                $userModel->load($post);
                $userProfile->load($post);
                $userProfile->avatar = $old_image;
                $userProfile->avatar = $userProfile->avatar;
                $userProfile->height = json_encode($heightFI);

                if ($userModel->groupUniqueNumber(['phone' => $post['User']['phone'], 'groupid' => $userModel->groupid, 'id' => $userModel->id])) {
                    $userModel->addError('phone', 'This phone number already exists.');
                }
                $upload = UploadedFile::getInstance($userProfile, 'avatar');
                if ($userModel->save() && $userProfile->save()) {
                    if (isset($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['tmp_name'])) {
                        $imageUpload = DrsImageUpload::updateProfileImageWeb('patients', $id, $upload);
                    }
                    Yii::$app->session->setFlash('success', "'Profile Updated.'");
                    return $this->redirect(['/']);
                }
            }
        }
        if ($userProfile->height) {
            $height = json_decode($userProfile->height);
            $userProfile->height = $height->feet;
            $userProfile->inch = $height->inch;
        }
        return $this->render('/patient/profile', ['userModel' => $userModel, 'userProfile' => $userProfile, 'genderList' => $genderlist]);
    }

    // function to get  the address
    public function get_lat_long($address) {

        $address = str_replace(" ", "+", $address);

        $json = file_get_contents("https://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&key=AIzaSyD68G6UYDDxDthxDtQCjidVP5dgth3P-o0");
        $json = json_decode($json);
        
        $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
        $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
       
        return $lat . ',' . $long;
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

    public function actionCityList() {
        $city_list = [];
        $form = ActiveForm::begin(['id' => 'shiftform']);
        if (Yii::$app->request->post()) {
            $modelAddress = new UserProfile();
            $post = Yii::$app->request->post();
            $rst = DrsPanel::getCitiesList($post['state_id'], 'name');
            foreach ($rst as $key => $item) {
                $city_list[$item->id] = $item->name;
            }
            echo $this->renderAjax('/patient/_shift_city_field', ['form' => $form, 'city_list' => $city_list, 'modelAddress' => $modelAddress]);
            exit();
        }
    }

    public function actionCityAreaList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $form = ActiveForm::begin(['id' => 'shiftform']);
            $post = Yii::$app->request->post();
            $area_list = [];
            if (isset($post['id']) && !empty($post['id'])) {
                $modelAddress = new UserProfile();
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

                echo $this->renderAjax('/patient/_shift_area_field', ['form' => $form, 'area_list' => $area_list, 'modelAddress' => $modelAddress]);
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
            echo $this->renderAjax('/patient/_map_location', ['lat' => $lat, 'lng' => $lng]);
            exit();
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
                        return $this->redirect('profile');
                    }
                } else {
                    $user->phone = $model->phone;
                    if ($user->save()) {
                        Yii::$app->getSession()->setFlash('success', "'Mobile number updated.'");
                        return $this->redirect('profile');
                    }
                }
            } else {
                Yii::$app->getSession()->setFlash('error', "'Invalid OTP Please Try Again.'");
                return $this->redirect('profile');
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

    public function actionMyDoctors() {
        $id = Yii::$app->user->id;
        return $this->render('/patient/my-doctors', ['doctors' => DrsPanel::patientMyDoctorsList($id),
                    'id' => $id]
        );
    }

    public function actionRecords() {
        $id = Yii::$app->user->id;
        return $this->render('/patient/records', ['doctors' => DrsPanel::patientDoctorList($id),
                    'id' => $id]
        );
    }

    public function actionPatientAppointments($id) {
        $member = PatientMembers::find()->where(['id' => $id])->one();
        if (!empty($member)) {
            $appList = new Query();
            $appList = UserAppointment::find();
            $appList->where(['user_id' => $member->user_id]);
            $appList->andWhere(['user_name' => $member->name, 'user_phone' => $member->phone]);
            $appList->all();
            $command = $appList->createCommand();
            $appointments = $command->queryAll();

            return $this->render('/patient/patient-appointments', ['appointments' => $appointments]
            );
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionPatientRecordFiles($slug = NULL) {
        $id = Yii::$app->user->id;
        $userModel = $this->findModel($id);
        $userProfile = UserProfile::findOne(['user_id' => $id]);
        $UserAddress = UserAddress::findOne(['user_id' => $id]);
        $PatientRecordFilesSlug = PatientMembers::find()->andWhere(['slug' => $slug])->one();
        if (!empty($PatientRecordFilesSlug)) {
            $member_id = $PatientRecordFilesSlug->id;
            $records_list = PatientMemberRecords::find()->andWhere(['member_id' => $member_id])->all();
            $files = array();
            foreach ($records_list as $record) {
                $files[] = $record->files_id;
            }
            $records = PatientMemberFiles::find()->where(['id' => $files])->all();
            return $this->render('/patient/patient-record-files', ['records' => $records, 'id' => $id, 'member_id' => $member_id]);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionAddUpdateRecord() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            if (isset($post['PatientMemberFiles'])) {
                if (isset($_FILES['PatientMemberFiles'])) {
                    $PatientRecordImages = new PatientMemberFiles();
                    $member_id = $post['member_id'];
                    $member = PatientMembers::find()->where(['id' => $member_id])->one();
                    $uploads = UploadedFile::getInstances($PatientRecordImages, 'image');
                    if (!is_dir("../../storage/web/source/records/"))
                        mkdir("../../storage/web/source/records", 0775, true);

                    foreach ($uploads as $key => $file) {
                        $uploadDir = Yii::getAlias('@storage/web/source/records/');
                        $file_name = $file->name;
                        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                        $image_name = time() . rand() . '.' . $extension;
                        if ($file->saveAs($uploadDir . $image_name)) {
                            $imgModelPatient = new PatientMemberFiles();
                            $imgModelPatient->image = $image_name;
                            $imgModelPatient->image_base_url = Yii::getAlias('@storageUrl');
                            $imgModelPatient->image_path = '/source/records/';
                            $imgModelPatient->image_type = $extension;
                            $imgModelPatient->image_name = $post['PatientMemberFiles']['image_name'];
                            if ($imgModelPatient->save()) {
                                $recordAdd = new PatientMemberRecords();
                                $recordAdd->member_id = $member_id;
                                $recordAdd->files_id = $imgModelPatient->id;
                                $recordAdd->save();
                            } else {
                                
                            }
                        } else {
                            
                        }
                    }
                }
                return $this->redirect(['patient-record-files', 'slug' => $member->slug]);
            } else {
                $member_id = $post['member_id'];
                $type = $post['type'];
                $member = PatientMembers::find()->where(['id' => $member_id])->one();
                if ($type == 'add') {
                    $recordModel = new PatientMemberFiles();
                    echo $this->renderAjax('/patient/_addupdaterecord', ['recordModel' => $recordModel, 'member_id' => $member_id]);
                    exit;
                } else {
                    
                }
            }
        }
    }

    public function actionShareRecord() {
        $current_id = Yii::$app->user->id;
        if (Yii::$app->request->post()) {
            $modelShare = new RecordShareModel();
            $post = Yii::$app->request->post();
            if (isset($post['RecordShareModel'])) {
                if (Yii::$app->request->isAjax) {
                    $modelShare->load($_POST);
                    Yii::$app->response->format = Response::FORMAT_JSON;
                    return ActiveForm::validate($modelShare);
                }
                $member_id = $post['RecordShareModel']['member_id'];
                $mobile = $post['RecordShareModel']['phone'];
                $member = PatientMembers::find()->where(['id' => $member_id])->one();
                $checkUser = User::findOne(['phone' => $mobile, 'groupid' => Groups::GROUP_PATIENT]);
                if (!empty($checkUser)) {
                    if ($checkUser->id != $current_id) {
                        $memberdata = array();
                        $memberdata['user_id'] = $checkUser->id;
                        $memberdata['name'] = $member->name;
                        $memberdata['phone'] = $member->phone;
                        $memberdata['gender'] = $member->gender;
                        $memberInsert = DrsPanel::memberUpsert($memberdata, array());
                        if ($memberInsert) {
                            $memberRecords = PatientMemberRecords::find()->where(['member_id' => $member_id])->all();
                            foreach ($memberRecords as $mrecord) {
                                $newRecord = new PatientMemberRecords();
                                $newRecord->member_id = $memberInsert;
                                $newRecord->files_id = $mrecord->files_id;
                                $newRecord->save();
                            }
                            Yii::$app->session->setFlash('success', "'Record Shared!'");
                            return $this->redirect(['records']);
                        } else {
                            Yii::$app->session->setFlash('error', "'Please try again!'");
                            return $this->redirect(['records']);
                        }
                    } else {
                        Yii::$app->session->setFlash('error', "'Please share record with other member'");
                        return $this->redirect(['records']);
                    }
                } else {
                    Yii::$app->session->setFlash('error', "'Mobile number not registered!'");
                    return $this->redirect(['records']);
                }
            } else {
                $member_id = $post['member_id'];
                $member = PatientMembers::find()->where(['id' => $member_id])->one();
                $modelShare->member_id = $member_id;
                echo $this->renderAjax('/patient/_sharerecord', ['member_id' => $member_id, 'modelShare' => $modelShare,]);
                exit;
            }
        }
    }

    public function actionDeleteRecord() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $record_id = $post['record_id'];
            $member_id = $post['member_id'];
            $member = PatientMembers::find()->where(['id' => $member_id])->one();
            $reminder = PatientMemberFiles::find()->where(['id' => $record_id])->one();
            if ($reminder->delete()) {
                $cond['files_id'] = $record_id;
                $lists = PatientMemberRecords::deleteAll($cond);
            }
            Yii::$app->session->setFlash('success', "'Record Deleted!'");
            return $this->redirect(['patient-record-files', 'slug' => $member->slug]);
        }
    }

    public function actionPatientRecordUpdate() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $pid = isset($post['PatientMembers']['id']) ? $post['PatientMembers']['id'] : '0';

            if (isset($post['PatientMembers'])) {
                $patientrecordData = PatientMembers::findOne($pid);

                $patientrecordData->load($post);

                if ($patientrecordData->save()) {
                    Yii::$app->session->setFlash('success', "'Patient Record Updated!'");
                }
                return $this->redirect(['/patient/records']);
            }

            if (isset($_FILES['PatientMembersFiles'])) {
                $PatientRecordImages = PatientMembers::findOne($pid);
                $uploads = UploadedFile::getInstances($PatientRecordImages, 'image');
                if (!empty($uploads)) {
                    $uploadDir = Yii::getAlias('@storage/web/source/hospitals/');
                    foreach ($uploads as $key => $file) {
                        $image_name = time() . rand(1, 9999) . '_' . $key . '.' . $file->extension;
                        if ($file->saveAs($uploadDir . $image_name)) {
                            $imgModelPatient = new PatientMemberFiles();
                            $imgModelPatient->member_id = $pMember->id;
                            $imgModelPatient->user_id = Yii::$app->user->id;
                            $imgModelPatient->image = $image_name;
                            $imgModelPatient->image_base_url = Yii::getAlias('@storageUrl');
                            $imgModelPatient->image_path = '/source/members/';

                            $imgModelPatient->save();
                        }
                    }
                }
            } else {
                $id = $post['id'];
                $patientrecordDataRow = PatientMembers::findOne($id);
                $patientrecordFilesDataRow = PatientMemberFiles::find()->where(['member_id' => $id])->one();
                if (empty($patientrecordFilesDataRow)) {
                    $patientrecordFilesDataRow = new PatientMemberFiles();
                    $patientrecordFilesDataRow->member_id = $id;
                }
                $genderlist = DrsPanel::getGenderList();
                echo $this->renderAjax('/patient/_editrecord', ['model' => $patientrecordDataRow, 'patientmemberData' => $patientrecordFilesDataRow, 'genderList' => $genderlist]);
                exit;
            }
        }
    }

    public function actionAppointments($type = '') {
        $id = Yii::$app->user->id;
        if ($type == 'upcoming') {
            $appointments = DrsPanel::patientAppoitmentList($id, array(UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_ACTIVE), $type);
        } elseif ($type == 'past') {
            $appointments = DrsPanel::patientAppoitmentList($id, array(UserAppointment::STATUS_COMPLETED, UserAppointment::STATUS_CANCELLED), $type);
        } else {
            $type = 'all';
            $appointments = DrsPanel::patientAppoitmentList($id, [], $type);
        }
        return $this->render('/patient/appointments', ['type' => $type, 'appointments' => $appointments]);
    }

    public function actionAppointmentDetails($id) {
        $appointment_details = UserAppointment::find()->andWhere(['id' => $id])->one();
        if (!empty($appointment_details)) {
            $appointment_doctorData = UserProfile::find()->andWhere(['user_id' => $appointment_details->doctor_id])->one();
            $appointment_hospitalData = UserAddress::find()->andWhere(['id' => $appointment_details->doctor_address_id])->one();
        } else {
            $appointment_doctorData = array();
            $appointment_hospitalData = array();
        }
        return $this->render('/patient/_appointment_details', ['appointments' => $appointment_details, 'appointment_doctorData' => $appointment_doctorData, 'appointment_hospitalData' => $appointment_hospitalData]);
    }

    public function actionAjaxCheckReminder() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user_id = $this->loginUser->id;
            if (isset($post['UserReminder'])) {
                $appointment_id = $post['UserReminder']['appointment_id'];
                $reminder = UserReminder::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id])->one();
                if (empty($reminder)) {
                    $reminder = new UserReminder();
                }
                $reminder->user_id = $user_id;
                $reminder->appointment_id = $appointment_id;
                $reminder->reminder_date = $post['UserReminder']['reminder_date'];
                $reminder->reminder_time = $post['UserReminder']['reminder_time'];
                $reminder->reminder_datetime = (int) strtotime($post['UserReminder']['reminder_date'] . ' ' . $post['UserReminder']['reminder_time']);
                ;
                $reminder->status = 'pending';
                if ($reminder->save()) {
                    return $this->redirect(Yii::$app->request->referrer);
                } else {
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } else {
                $lists = UserReminder::find()->where(['user_id' => $user_id])->orderBy('id desc')->one();
                $appointment_id = $post['appointment_id'];

                $reminder = UserReminder::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id])->one();
                if (empty($reminder)) {
                    $reminder = new UserReminder();
                    $reminder->user_id = $user_id;
                    $reminder->appointment_id = $appointment_id;
                    $type = 'Add';
                } else {
                    $type = 'Update';
                }
                $appointment = UserAppointment::findOne($appointment_id);
                $appointment_detail = DrsPanel::patientgetappointmentarray($appointment);
                echo $this->renderAjax('_addupdatereminder', ['reminder' => $reminder, 'type' => $type, 'doctorData' => $appointment_detail]);
                exit;
            }
        }
    }

    public function actionAjaxCheckRating() {
        $hospital_rate = 0;
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user_id = $this->loginUser->id;
            if (isset($post['UserRatingLogs'])) {
                $appointment_id = $post['UserRatingLogs']['appointment_id'];
                $appointment = UserAppointment::findOne($appointment_id);
                $address = UserAddress::findOne($appointment->doctor_address_id);
                $address_user = User::findOne($address->user_id);

                if ($address_user->groupid == Groups::GROUP_HOSPITAL) {
                    if (isset($post['UserRatingLogs']['hospital_rating']) && $post['UserRatingLogs']['hospital_rating'] > 0) {
                        $rating_logs_hospital = UserRatingLogs::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id, 'user_type' => 'hospital'])->one();
                        if (empty($rating_logs_hospital)) {
                            $rating_logs_hospital = new UserRatingLogs();
                        }
                        $rating_logs_hospital->user_id = $user_id;
                        $rating_logs_hospital->user_type = 'hospital';
                        $rating_logs_hospital->doctor_id = $address->user_id;
                        $rating_logs_hospital->appointment_id = $appointment_id;
                        $rating_logs_hospital->rating = $post['UserRatingLogs']['hospital_rating'];
                        $rating_logs_hospital->review = $post['UserRatingLogs']['hospital_review'];
                        if ($rating_logs_hospital->save()) {
                            $rating = UserRating::find()->where(['user_id' => $address->user_id])->one();
                            if (empty($rating)) {
                                $rating = new UserRating();
                            }
                            $rating->user_id = $address->user_id;
                            $rating->show_rating = 'User';
                            $rating->admin_rating = 0;
                            $rating->users_rating = DrsPanel::calculateRatingAverage($address->user_id);
                            if ($rating->save()) {
                                $rating->users_rating = DrsPanel::calculateRatingAverage($address->user_id);
                                if ($rating->save()) {
                                    $user = UserProfile::findOne(['user_id' => $address->user_id]);
                                    $user->rating = $rating->users_rating;
                                    $user->save();
                                }
                            }
                        }
                    }
                    $rating_logs = UserRatingLogs::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id, 'user_type' => 'doctor'])->one();
                    if (empty($rating_logs)) {
                        $rating_logs = new UserRatingLogs();
                    }
                    $rating_logs->user_id = $user_id;
                    $rating_logs->user_type = 'doctor';
                    $rating_logs->doctor_id = $appointment->doctor_id;
                    $rating_logs->appointment_id = $appointment_id;
                    $rating_logs->rating = $post['UserRatingLogs']['rating'];
                    $rating_logs->review = $post['UserRatingLogs']['review'];
                    if ($rating_logs->save()) {

                        $rating = UserRating::find()->where(['user_id' => $appointment->doctor_id])->one();
                        if (empty($rating)) {
                            $rating = new UserRating();
                        }
                        $rating->user_id = $appointment->doctor_id;
                        $rating->show_rating = 'User';
                        $rating->admin_rating = 0;
                        $rating->users_rating = DrsPanel::calculateRatingAverage($appointment->doctor_id);
                        if ($rating->save()) {
                            $rating->users_rating = DrsPanel::calculateRatingAverage($appointment->doctor_id);
                            if ($rating->save()) {
                                $user = UserProfile::findOne(['user_id' => $appointment->doctor_id]);
                                $user->rating = $rating->users_rating;
                                $user->save();
                            }
                        }
                        return $this->redirect(Yii::$app->request->referrer);
                    } else {
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                } else {
                    $rating_logs = UserRatingLogs::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id, 'user_type' => 'doctor'])->one();
                    if (empty($rating_logs)) {
                        $rating_logs = new UserRatingLogs();
                    }
                    $rating_logs->user_id = $user_id;
                    $rating_logs->user_type = 'doctor';
                    $rating_logs->doctor_id = $appointment->doctor_id;
                    $rating_logs->appointment_id = $appointment_id;
                    $rating_logs->rating = $post['UserRatingLogs']['rating'];
                    $rating_logs->review = $post['UserRatingLogs']['review'];
                    if ($rating_logs->save()) {

                        $rating = UserRating::find()->where(['user_id' => $appointment->doctor_id])->one();
                        if (empty($rating)) {
                            $rating = new UserRating();
                        }
                        $rating->user_id = $appointment->doctor_id;
                        $rating->show_rating = 'User';
                        $rating->admin_rating = 0;
                        $rating->users_rating = DrsPanel::calculateRatingAverage($appointment->doctor_id);
                        if ($rating->save()) {
                            $rating->users_rating = DrsPanel::calculateRatingAverage($appointment->doctor_id);
                            if ($rating->save()) {
                                $user = UserProfile::findOne(['user_id' => $appointment->doctor_id]);
                                $user->rating = $rating->users_rating;
                                $user->save();
                            }
                        }
                        return $this->redirect(Yii::$app->request->referrer);
                    } else {
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                }
            } else {
                $appointment_id = $post['appointment_id'];
                $appointment = UserAppointment::findOne($appointment_id);
                $address = UserAddress::findOne($appointment->doctor_address_id);
                $address_user = User::findOne($address->user_id);
                $rating_logs = UserRatingLogs::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id, 'user_type' => 'doctor'])->one();
                if (empty($rating_logs)) {
                    $rating_logs = new UserRatingLogs();
                    $rating_logs->user_id = $user_id;
                    $rating_logs->doctor_id = $appointment->doctor_id;
                    $rating_logs->appointment_id = $appointment_id;
                    $rating_logs->user_type = 'doctor';
                    $type = 'Add';
                } else {
                    $type = 'Appointment';
                    $rating_logs_hospital = UserRatingLogs::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id, 'user_type' => 'hospital'])->one();
                    if (!empty($rating_logs_hospital)) {
                        $rating_logs->hospital_rating = $rating_logs_hospital->rating;
                        $rating_logs->hospital_review = $rating_logs_hospital->review;
                    }
                }
                if ($address_user->groupid == Groups::GROUP_HOSPITAL) {
                    $hospital_rate = 1;
                } else {
                    $hospital_rate = 0;
                }

                $appointment_detail = DrsPanel::patientgetappointmentarray($appointment);
                echo $this->renderAjax('_addupdaterating', ['rating_logs' => $rating_logs, 'type' => $type,
                    'doctorData' => $appointment_detail, 'hospital_rate' => $hospital_rate]);
                exit;
            }
        }
    }

    public function actionAjaxCheckReminderDelete() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            /*  echo '<pre>';
              print_r($post);die; */
            if (isset($post['appointment_id'])) {
                $appointment_id = $post['appointment_id'];
                $reminder = UserReminder::find()->where(['appointment_id' => $appointment_id])->one();
                $reminder->delete();
            }
        }
        Yii::$app->session->setFlash('success', "Reminder Deleted!'");
        return $this->redirect(['/patient/reminder']);
    }

    public function actionAjaxCheckReminderList() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user_id = $this->loginUser->id;
            if (isset($post['UserReminder'])) {
                $appointment_id = $post['UserReminder']['appointment_id'];
                $reminder = UserReminder::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id])->one();
                if (empty($reminder)) {
                    $reminder = new UserReminder();
                }
                $reminder->user_id = $user_id;
                $reminder->appointment_id = $appointment_id;
                $reminder->reminder_date = $post['UserReminder']['reminder_date'];
                $reminder->reminder_time = $post['UserReminder']['reminder_time'];
                $reminder->reminder_datetime = (int) strtotime($post['UserReminder']['reminder_date'] . ' ' . $post['UserReminder']['reminder_time']);
                ;
                $reminder->status = 'pending';
                if ($reminder->save()) {
                    Yii::$app->session->setFlash('success', "'Reminder Updated.'");
                    return $this->redirect(Yii::$app->request->referrer);
                } else {
                    Yii::$app->session->setFlash('error', "'Please try again!'");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } else {
                $lists = UserReminder::find()->where(['user_id' => $user_id])->orderBy('id desc')->one();
                $appointment_id = $post['appointment_id'];

                $reminder = UserReminder::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id])->one();
                if (empty($reminder)) {
                    $reminder = new UserReminder();
                    $reminder->user_id = $user_id;
                    $reminder->appointment_id = $appointment_id;
                    $type = 'Add';
                } else {
                    $type = 'Update';
                }
                $appointment = UserAppointment::findOne($appointment_id);
                $appointment_detail = DrsPanel::patientgetappointmentarray($appointment);
                echo $this->renderAjax('_updatereminder', ['reminder' => $reminder, 'type' => $type, 'doctorData' => $appointment_detail]);
                exit;
            }
        }
    }

    public function actionReminder() {
        $user_id = Yii::$app->user->id;
        $reminders = DrsPanel::getPatientReminders($user_id);
        return $this->render('reminder', ['reminders' => $reminders]);
    }

    public function actionAjaxCancelAppointment() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $appointment_id = $post['appointment_id'];
            $response = DrsPanel::cancelAppointmentById($appointment_id, 'Patient');
            $html = $response['message'];
            if ($response['status'] == 'success') {
                Yii::$app->session->setFlash('success', "'$html'");
            } else {
                Yii::$app->session->setFlash('error', "'$html'");
            }
            return $this->redirect(Yii::$app->request->referrer);
        }
    }

    public function actionCustomerCare() {
        $customer_phone = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Phone'])->one();
        $customer_email = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Email'])->one();
        $customer = array('phone' => '', 'email' => $customer_email);
        return $this->render('/patient/customer-care', ['customer' => $customer]);
    }

    public function actionFavorite() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $data['user_id'] = $this->loginUser->id;
            $data['profile_id'] = $post['profile_id'];
            $data['status'] = UserFavorites::STATUS_FAVORITE;
            return DrsPanel::userFavoriteUpsert($data);
        }
        return NULL;
    }

    public function actionMyPayments() {
        $id = Yii::$app->user->id;
        return $this->render('/patient/my-payments');
    }

    public function actionLiveStatus($id) {
        $userAppointment = UserAppointment::findOne($id);
        if (!empty($userAppointment)) {
            $doctor_id = $userAppointment->doctor_id;
            $schedule_id = $userAppointment->schedule_id;
            $date = $userAppointment->date;
            $scheduleGroup = UserScheduleGroup::find()->andWhere(['user_id' => $doctor_id, 'schedule_id' => $schedule_id, 'date' => $date])->one();
            if (!empty($scheduleGroup)) {
                if ($scheduleGroup->status == 'current') {
                    $appointment = DrsPanel::liveStatusData($doctor_id, $schedule_id, $date, $id);
                    // echo "<pre>"; print_r($appointment);die;
                    if ($appointment) {
                        return $this->render('live-status', ['appointments' => $appointment, 'userAppointment' => $userAppointment]);
                    } else {
                        Yii::$app->session->setFlash('error', "'Something went wrong, Please try again.'");
                        return $this->redirect(Yii::$app->request->referrer);
                    }
                } elseif ($scheduleGroup->status == 'pending') {
                    Yii::$app->session->setFlash('titlesuccess', "'Shift not started'");
                    return $this->redirect(Yii::$app->request->referrer);
                } elseif ($scheduleGroup->status == 'completed') {
                    Yii::$app->session->setFlash('titlesuccess', "'Shift Completed'");
                    return $this->redirect(Yii::$app->request->referrer);
                } else {
                    Yii::$app->session->setFlash('error', "'Details not found,Please try again!'");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } else {
                Yii::$app->session->setFlash('titlesuccess', "'Shift not started'");
                return $this->redirect(Yii::$app->request->referrer);
            }
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
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
            $getShists = DrsPanel::getBookingShifts($this->loginUser->id, $date, $this->loginUser->id);
            $appointments = DrsPanel::getCurrentAppointments($this->loginUser->id, $date, $current_shifts, $getShists);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $current_shifts = $appointments['shifts'][0]['schedule_id'];
                    if ($type == 'book') {
                        $slots = DrsPanel::getBookingShiftSlots($this->loginUser->id, $date, $current_shifts, 'available');
                    } else {
                        $bookings = $appointments['bookings'];
                    }
                }
            }
            echo $this->renderAjax('/common/_appointment_shift_slots', ['appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => $type, 'slots' => $slots, 'bookings' => $bookings, 'userType' => 'doctor']);
            exit;
        }
        echo 'error';
        exit;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        if (!Yii::$app->user->isGuest) {
            $groupid = Yii::$app->user->identity->userProfile->groupid;
            if ($groupid != Groups::GROUP_PATIENT) {
                return $this->goHome();
            } else {
                return parent::beforeAction($action);
            }
        }
        $this->redirect(array('/'));
    }

    public function actionGetRefundStatus() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $appointmentID = $post['appointment_id'];
            $getAppDetail = \common\models\Transaction::find()->where(['appointment_id' => $appointmentID, 'type' => 'refund'])->one();
            $paytmResponse = json_decode($getAppDetail['paytm_response']);
            $refundResponse = \common\components\Payment::get_refund_status($paytmResponse, $appointmentID);
            if ($refundResponse) {
                $html = $refundResponse['body']['resultInfo']['resultMsg'];
                if ($refundResponse['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS') {
                    Yii::$app->session->setFlash('success', "'$html'");
                } else {
                    Yii::$app->session->setFlash('error', "'$html'");
                }
            }

            return $this->redirect(Yii::$app->request->referrer);
        }
    }

}
