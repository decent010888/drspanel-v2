<?php

namespace frontend\modules\user\controllers;

use backend\models\AddScheduleForm;
use common\components\DrsImageUpload;
use common\models\UserAddress;
use common\models\UserAppointment;
use common\models\UserSchedule;
use common\models\MetaKeys;
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
use common\models\MetaValues;
use common\models\User;
use common\models\UserProfile;
use common\models\Groups;
use common\components\DrsPanel;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;
use yii\widgets\ActiveForm;
use yii\web\Response;

/**
 * Class AttenderController
 * @package frontend\modules\user\controllers
 * @author Eugene Terentev <eugene@terentev.net>
 */
class AttenderController extends \yii\web\Controller {

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
                            return $this->loginUser->groupid == Groups::GROUP_ATTENDER;
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

        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $userModel->load($post);
            $old_image = $userProfile->avatar;
            $userProfile->load($post);
            $userProfile->avatar = $old_image;

            if ($userModel->groupUniqueNumber(['phone' => $post['User']['phone'], 'groupid' => $userModel->groupid, 'id' => $userModel->id])) {
                $userModel->addError('phone', 'This phone number already exists.');
            }

            $upload = UploadedFile::getInstance($userProfile, 'avatar');
            if ($userModel->save() && $userProfile->save()) {
                if (isset($_FILES['UserProfile']['name']['avatar']) &&
                        !empty($_FILES['UserProfile']['name']['avatar']) && !empty($_FILES['UserProfile']['tmp_name'])) {
                    $imageUpload = DrsImageUpload::updateProfileImageWeb('attenders', $id, $upload);
                }
                Yii::$app->session->setFlash('success', "'Profile updated!'");
                return $this->redirect(['/attender/appointments']);
            }
        }

        return $this->render('/attender/edit-profile', ['model' => $userModel, 'userModel' => $userModel, 'userProfile' => $userProfile,
                    'genderList' => $genderlist]);
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

    public function actionMyPatients() {
        $id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($id);
        $parentGroup = $getParentDetails['parentGroup'];
        if ($parentGroup == Groups::GROUP_HOSPITAL) {
            
        } else {
            $parent_id = $getParentDetails['parent_id'];
            $lists = DrsPanel::myPatients(['doctor_id' => $parent_id]);
            return $this->render('/attender/doctor/my-patients', ['lists' => $lists]);
        }
    }

    public function actionCustomerCare() {
        $customer_phone = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Phone'])->one();
        $customer_email = MetaValues::find()->orderBy('id asc')
                        ->where(['key' => 8, 'status' => 1, 'label' => 'Email'])->one();
        $customer = array('phone' => $customer_phone, 'email' => $customer_email);
        return $this->render('/attender/customer-care', ['customer' => $customer]);
    }

    public function actionMyShifts($slug = '') {
        $id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($id);
        $parentGroup = $getParentDetails['parentGroup'];
        $parent_id = $getParentDetails['parent_id'];

        if ($parentGroup == Groups::GROUP_HOSPITAL) {
            $date = date('Y-m-d');
            $type = 'my-shifts';
            $hospital = $getParentDetails['parentModel'];
            if (!empty($slug)) {
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $address = UserAddress::find()->andWhere(['user_id' => $parent_id])->one();

                    $shifts = DrsPanel::getShiftListByAddress($doctor->id, $address->id);

                    return $this->render('shift/my-hospital-shifts', ['address' => $address, 'shifts' => $shifts, 'doctor' => $doctor, 'hospital' => $hospital, 'doctorProfile' => $doctorProfile]);
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
                $data_array = DrsPanel::getDoctorSliders($params);

                return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'my-shifts', 'userType' => 'attender', 'page_heading' => 'My Shifts']);
            }
        } else {
            $selectedShifts = Drspanel::shiftList(['user_id' => $parent_id, 'attender_id' => $id], 'list');

            $addressList = DrsPanel::doctorHospitalList($parent_id);
            $listadd = $addressList['apiList'];
            $shift_array = array();
            $s = 0;
            $shift_value = array();
            $sv = 0;
            $selectedShiftsIds = array();
            $address_list = array();
            foreach ($listadd as $address) {
                $shifts = DrsPanel::getShiftListByAddress($parent_id, $address['id']);

                foreach ($shifts as $key => $shift) {
                    if ($shift['hospital_id'] == 0) {
                        $shift_array[$s]['value'] = $shift['shifts_ids'];
                        $shift_array[$s]['label'] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                        $shift_value[$sv] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';

                        $shift_id_list = $shift['shifts_ids'];
                        foreach ($selectedShifts as $select => $valuesel) {
                            if (in_array($select, $shift_id_list)) {
                                $selectedShiftsIds[$sv] = $key;
                            }
                        }
                        $s++;
                        $sv++;
                    }
                }



                $address_repeat = array();
                foreach ($selectedShiftsIds as $selected_shift) {
                    if (isset($shifts[$selected_shift])) {
                        if (in_array($shifts[$selected_shift]['id'], $address_repeat)) {
                            
                        } else {
                            $address_list[] = $shifts[$selected_shift];
                            $address_repeat[] = $shifts[$selected_shift]['id'];
                        }
                    }
                }
            }


            return $this->render('shift/my-shifts', ['doctor_id' => $parent_id, 'address_list' => $address_list,
                        'allshifts' => $selectedShifts]);
        }
    }

    /* Today Timing */

    public function actionDayShifts($slug = '') {
        $id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($id);
        $parentGroup = $getParentDetails['parentGroup'];
        $string = Yii::$app->request->queryParams;
        if ($parentGroup == Groups::GROUP_HOSPITAL) {
            $speciality_check = 1;
            $date = date('Y-m-d');
            $type = 'day-shifts';
            $hospital = $getParentDetails['parentModel'];

            $slug = $slug;
            if (!empty($slug)) {
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $getShists = DrsPanel::getBookingShifts($doctor->id, $date, $id);

                    return $this->render('shift/day-shifts', ['defaultCurrrentDay' => strtotime($date), 'shifts' => $getShists, 'doctor' => $doctor, 'date' => $date]);
                    $speciality_check = 0;
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
                $data_array = DrsPanel::getDoctorSliders($params);

                return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'day-shifts', 'userType' => 'attender', 'page_heading' => 'Timing']);
            } else {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        } else {
            $parent_id = $getParentDetails['parent_id'];
            $doctor = User::findOne($parent_id);
            $params = Yii::$app->request->queryParams;
            if (!empty($params) && isset($params['date'])) {
                $date = $params['date'];
            } else {
                $date = date('Y-m-d');
            }
            $getShists = DrsPanel::getBookingShifts($parent_id, $date, $id);

            return $this->render('shift/day-shifts', ['defaultCurrrentDay' => strtotime($date), 'shifts' => $getShists, 'doctor' => $doctor, 'date' => $date]);
        }
    }

    public function actionUpdateShiftStatus() {
        $response["status"] = 0;
        $response["error"] = true;
        $response['message'] = 'You have do not permission.';
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();

            $id = Yii::$app->user->id;
            $getParentDetails = DrsPanel::getParentDetails($id);
            $parentGroup = $getParentDetails['parentGroup'];
            if ($parentGroup == Groups::GROUP_HOSPITAL) {
                $parent_id = $post['doctor_id'];
            } else {
                $parent_id = $getParentDetails['parent_id'];
            }
            $params['booking_closed'] = $post['status'];
            $params['doctor_id'] = $parent_id;
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

            $id = Yii::$app->user->id;
            $getParentDetails = DrsPanel::getParentDetails($id);
            $parentGroup = $getParentDetails['parentGroup'];
            if ($parentGroup == Groups::GROUP_HOSPITAL) {
                $parent_id = $post['user_id'];
            } else {
                $parent_id = $getParentDetails['parent_id'];
            }

            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));
            $appointments = DrsPanel::getBookingShifts($parent_id, $date, $id);
            echo $this->renderAjax('shift/_address-with-shift', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments]);
            exit();
        }
    }

    public function actionGetShiftDetails() {
        $user_id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($user_id);
        $parent_id = $getParentDetails['parent_id'];
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
            // echo "<pre>"; print_r($post); die;

            $update_shift = DrsPanel::updateShiftTiming($shift_id, $post);
            if (isset($post['date_dayschedule'])) {
                $date = $post['date_dayschedule'];
            } else {
                $date = date('Y-m-d');
            }
            if (isset($update_shift['error']) && $update_shift['error'] == true) {
                $html = $update_shift['message'];
                Yii::$app->session->setFlash('shifterror', "'$html'");
            } else {
                Yii::$app->session->setFlash('success', "'Shift updated successfully'");
            }
            return $this->redirect(['/attender/day-shifts', 'date' => $date]);
        }
    }

    /*     * **************************Booking/Appointment************************************** */

    public function actionAppointments($slug = '') {
        $id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($id);
        $parentGroup = $getParentDetails['parentGroup'];
        $date = date('Y-m-d');
        $type = '';
        $speciality_check = 0;
        $string = Yii::$app->request->queryParams;
        if (isset($string['type'])) {
            $type = $string['type'];
        }
        if ($parentGroup == Groups::GROUP_HOSPITAL) {
            $slug = $slug;
            $parent_id = $getParentDetails['parent_id'];
            $hospital = $getParentDetails['parentModel'];
            return $this->hospitalAppointment($slug, $type, $parent_id, $id, $date, $hospital, $string);
        } else {
            $parent_id = $getParentDetails['parent_id'];
            $doctor = $getParentDetails['parentModel'];
            return $this->doctorAppointment($type, $parent_id, $id, $date, $doctor, $string);
        }
    }

    function hospitalAppointment($slug, $type, $parent_id, $id, $date, $hospital, $string) {
        $selected_speciality = 0;
        $speciality_check = 0;
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
                            $current_shifts = $appointments['shifts'][0]['schedule_id'];
                            $bookings = $appointments['bookings'];
                        }
                    }

                    return $this->render('/attender/hospital/current-appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'bookings' => $bookings, 'type' => $type, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'userType' => 'attender']);
                } else {

                    //not found
                }
            } else {
                
                if (isset($string['speciality']) && !empty($string['speciality'])) {
                    $selected_speciality = $string['speciality'];
                } else {
                    $selected_speciality = 0;
                }

                $params['user_id'] = $id;
                $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
                $data_array = DrsPanel::getDoctorSliders($params);

                return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'appointment', 'userType' => 'attender', 'page_heading' => 'Appointment']);
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

                            return $this->render('/attender/hospital/current-affair', ['schedule_id' => $current_affairs['schedule_id'], 'is_completed' => $current_affairs['is_completed'], 'is_started' => $current_affairs['is_started'], 'is_cancelled' => $current_affairs['is_cancelled'], 'Shifts' => $shifts, 'appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'type' => 'current_shift', 'date' => $date, 'userType' => 'attender', 'shift_id' => $current_shifts,]);
                        } else {
                            return $this->render('/attender/hospital/current-affair', ['schedule_id' => '', 'shift_id' => '', 'is_completed' => false, 'is_started' => false, 'is_cancelled' => false, 'Shifts' => $shifts, 'appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'type' => 'current_shift', 'date' => $date, 'userType' => 'attender']);
                        }
                    } else {
                        // no shifts
                        return $this->render('/attender/hospital/current-affair', ['schedule_id' => '', 'shift_id' => '', 'is_completed' => false, 'is_started' => false, 'is_cancelled' => false, 'Shifts' => $shifts, 'appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'type' => 'current_shift', 'date' => $date, 'userType' => 'attender']);
                    }
                } else {
                    // not found
                }
            } else {
                if (isset($string['speciality']) && !empty($string['speciality'])) {
                    $selected_speciality = $string['speciality'];
                } else {
                    $selected_speciality = 0;
                }

                $params['user_id'] = $id;
                $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
                $data_array = DrsPanel::getDoctorSliders($params);

                return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'appointment', 'userType' => 'attender', 'page_heading' => 'Appointment']);
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
                            $current_shifts = $appointments['shifts'][0]['schedule_id'];

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

                    return $this->render('/attender/hospital/appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'slots' => $slots, 'type' => $type, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile, 'userType' => 'attender', 'message' => $message]);
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
            $data_array = DrsPanel::getDoctorSliders($params);

            return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'appointment', 'userType' => 'attender', 'page_heading' => 'Appointment']);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    function doctorAppointment($type, $parent_id, $id, $date, $doctor, $string) {
        if ($type == 'current_appointment') {
            $current_shifts = 0;
            $bookings = array();
            $getShists = DrsPanel::getBookingShifts($parent_id, $date, $id);
            $appointments = DrsPanel::getCurrentAppointments($parent_id, $date, $current_shifts, $getShists);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $current_shifts = $appointments['shifts'][0]['schedule_id'];
                    $bookings = $appointments['bookings'];
                }
            }
            return $this->render('/attender/doctor/current-appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'bookings' => $bookings, 'type' => $type, 'current_shifts' => $current_shifts, 'doctor' => $doctor]);
        } elseif ($type == 'current_shift') {
            $current_shifts = '';
            $getSlots = DrsPanel::getBookingShifts($parent_id, $date, $id);
            $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
            if (!empty($checkForCurrentShift)) {
                $current_shifts = isset($checkForCurrentShift['shift_id']) ? $checkForCurrentShift['shift_id'] : '';
                $current_affairs = DrsPanel::getCurrentAffair($checkForCurrentShift, $parent_id, $date, $current_shifts, $getSlots);
                if ($current_affairs['status'] && empty($current_affairs['error'])) {
                    $shifts = $current_affairs['all_shifts'];
                    $appointments = $current_affairs['data'];
                    $current_shifts = $current_affairs['schedule_id'];
                    return $this->render('/attender/doctor/current-affair', ['schedule_id' => $current_affairs['schedule_id'], 'is_completed' => $current_affairs['is_completed'], 'is_cancelled' => $current_affairs['is_cancelled'], 'is_started' => $current_affairs['is_started'], 'Shifts' => $shifts, 'appointments' => $appointments, 'shift_id' => $current_shifts, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => 'current_shift', 'date' => $date]);
                } else {
                    return $this->render('/attender/doctor/current-affair', ['schedule_id' => '', 'shift_id' => '', 'is_completed' => false, 'is_started' => false, 'is_cancelled' => false, 'Shifts' => array(), 'appointments' => array(), 'current_shifts' => array(), 'doctor' => $doctor, 'type' => 'current_shift', 'date' => $date]);
                    $shifts = $appointments = array();
                    $is_completed = 0;
                    $is_started = 0;
                }
            } else {
                Yii::$app->session->setFlash('error', "'Sorry attender Facility Not Added'");
            }
        } else {
            $type = 'book';
            $current_shifts = 0;
            $slots = array();
            $message = '';
            $getShists = DrsPanel::getBookingShifts($parent_id, $date, $id);
            $appointments = DrsPanel::getCurrentAppointments($parent_id, $date, $current_shifts, $getShists);
            if (!empty($appointments)) {
                if (isset($appointments['shifts']) && !empty($appointments['shifts'])) {
                    $current_shifts = $appointments['shifts'][0]['schedule_id'];

                    $shift_check = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $parent_id, 'schedule_id' => $current_shifts, 'status' => array('cancelled', 'completed')])->one();
                    if (!empty($shift_check)) {
                        if ($shift_check->status == 'completed') {
                            $message = 'Shift Completed!';
                        } else {
                            $message = 'Shift Cancelled!';
                        }
                    } else {
                        $slots = DrsPanel::getBookingShiftSlots($parent_id, $date, $current_shifts, 'available');
                        $message = 'Shift slots not available!';
                    }
                }
            }
            return $this->render('/attender/doctor/appointments', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'slots' => $slots, 'type' => $type, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'message' => $message]);
        }
    }

    public function actionAjaxToken() {
        $result = ['status' => false, 'msg' => 'Invalid Request.'];
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $current_shifts = $post['shift_id'];
            $doctor_id = $post['doctorid'];
            $date = (isset($post['date']) && !empty($post['date'])) ? $post['date'] : date('Y-m-d');
            $slots = DrsPanel::getBookingShiftSlots($doctor_id, $date, $current_shifts, 'available');
            echo $this->renderAjax('_slots', ['slots' => $slots, 'doctor_id' => $doctor_id]);
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
                    echo $this->renderAjax('/common/_current_bookings', ['bookings' => $appointments, 'doctor_id' => $id, 'userType' => 'attender', 'schedule_id' => $current_shifts, 'shift_id' => $checkForCurrentShift['shift_id'], 'is_completed' => $is_completed, 'is_cancelled' => $is_cancelled, 'is_started' => $is_started, 'doctor' => $doctor]);
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
            echo $this->renderAjax('/common/_bookings', ['bookings' => $bookings, 'doctor_id' => $doctor_id, 'userType' => 'attender']);
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
                    return $this->renderAjax('/common/_booking_confirm.php', ['doctor' => $doctor, 'slot' => $slot, 'schedule' => $schedule, 'address' => UserAddress::findOne($schedule->address_id), 'model' => $model, 'userType' => 'attender'
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
                    return $this->renderAjax('/common/_booking_confirm_step2.php', ['doctor' => $doctor, 'slot' => $slot, 'schedule' => $schedule, 'address' => UserAddress::findOne($schedule->address_id), 'model' => $model, 'userType' => 'attender'
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
                        Yii::$app->session->setFlash('error', "'Appointments are not paid'");
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
            $result['result'] = $this->renderAjax('/common/_appointment_date_slider', ['doctor_id' => $user_id, 'dates_range' => $dates_range, 'date' => $first, 'type' => $type, 'userType' => 'attender']);
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
            echo $this->renderAjax('/attender/doctor/_appointment_shift_slots', ['appointments' => $appointments, 'current_shifts' => $current_shifts, 'doctor' => $doctor, 'type' => $type, 'slots' => $slots, 'bookings' => $bookings]);
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
            echo $this->renderAjax('/common/_booking_detail', ['booking' => $booking, 'doctor_id' => $appointment->doctor_id, 'userType' => 'attender', 'booking_type' => $booking_type, 'current' => $current]);
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
            }
            $result = array('status' => 'error', 'title' => 'Error!', 'message' => 'Please try gain!');
            echo json_encode($result);
            exit();
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
            $response = DrsPanel::cancelAppointmentById($appointment_id, 'Attender');
            $html = $response['message'];
            if ($response['status'] == 'success') {
                $result = array('status' => 'success', 'title' => 'Success!', 'message' => $html);
                echo json_encode($result);
                exit();
            } else {
                $result = array('status' => 'error', 'title' => 'Error!', 'message' => 'Please try again');
                echo json_encode($result);
                exit();
            }
        }
        return $this->redirect(Yii::$app->request->referrer);
    }

    /*     * **************************History************************************** */

    public function actionPatientHistory($slug = '') {

        $date = date('Y-m-d');
        $user_id = Yii::$app->user->id;

        $getParentDetails = DrsPanel::getParentDetails($user_id);
        $parentGroup = $getParentDetails['parentGroup'];
        $parent_id = $getParentDetails['parent_id'];

        $current_selected = 0;
        $checkForCurrentShift = 0;
        $appointments = $shiftAll = $typeCount = $history = [];

        $string = Yii::$app->request->queryParams;

        if ($parentGroup == Groups::GROUP_HOSPITAL) {
            $speciality_check = 1;
            $date = date('Y-m-d');
            $type = 'history';
            $userType = 'attender';

            $hospital = $getParentDetails['parentModel'];

            if (!empty($slug)) {
                $speciality_check = 0;
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $parent_id = $doctor->id;

                    $getSlots = DrsPanel::getBookingShifts($parent_id, $date, $user_id);
                    if (!empty($getSlots)) {
                        $checkForCurrentShift = $getSlots[0]['schedule_id'];
                        $current_selected = $checkForCurrentShift;
                        $getAppointments = DrsPanel::appointmentHistory($parent_id, $date, $current_selected, $getSlots, '');
                        $shiftAll = DrsPanel::getDoctorAllShift($parent_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                        $appointments = $getAppointments['bookings'];
                        $history = $getAppointments['total_history'];
                        $typeCount = $getAppointments['type'];
                    }
                    return $this->render('/attender/history-statistics/patient-history', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'current_selected' => $current_selected, 'type' => $type, 'userType' => $userType]);
                }
            }

            if ($speciality_check == 1) {
                if (isset($string['speciality']) && !empty($string['speciality'])) {
                    $selected_speciality = $string['speciality'];
                } else {
                    $selected_speciality = 0;
                }

                $params['user_id'] = $user_id;
                $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
                $data_array = DrsPanel::getDoctorSliders($params);

                return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'history', 'userType' => 'attender', 'page_heading' => 'Patient History']);
            } else {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        } else {
            $doctor = User::findOne($parent_id);
            $getSlots = DrsPanel::getBookingShifts($parent_id, $date, $user_id);
            if (!empty($getSlots)) {
                $checkForCurrentShift = $getSlots[0]['schedule_id'];
                $current_selected = $checkForCurrentShift;
                $getAppointments = DrsPanel::appointmentHistory($parent_id, $date, $current_selected, $getSlots, '');
                $shiftAll = DrsPanel::getDoctorAllShift($parent_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                $appointments = $getAppointments['bookings'];
                $history = $getAppointments['total_history'];
                $typeCount = $getAppointments['type'];
            }
            return $this->render('/attender/history-statistics/patient-history', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'current_selected' => $current_selected]);
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
            echo $this->renderAjax('/attender/history-statistics/_history-content', ['history_count' => $history, 'typeCount' => $typeCount, 'appointments' => $appointments, 'shifts' => $shiftAll, 'doctor' => $doctor, 'current_selected' => $current_selected]);
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
            $doctor = User::findOne($doctor_id);
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
            echo $this->renderAjax('/attender/history-statistics/_history-patient', ['appointments' => $bookings, 'doctor_id' => $doctor_id, 'userType' => 'attender', 'history_count' => $history, 'typeCount' => $typeCount,]);
            exit();
        }
    }

    public function actionUserStatisticsData($slug = '') {
        $date = date('Y-m-d');

        $user_id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($user_id);
        $parentGroup = $getParentDetails['parentGroup'];
        $parent_id = $getParentDetails['parent_id'];

        $current_selected = 0;
        $checkForCurrentShift = 0;
        $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
        $appointments = $shiftAll = $typeCount = [];

        $string = Yii::$app->request->queryParams;
        $doctorProfile = array();
        if ($parentGroup == Groups::GROUP_HOSPITAL) {

            $speciality_check = 1;
            $date = date('Y-m-d');
            $type = 'user-statistics-data';
            $hospital = $getParentDetails['parentModel'];
            if (!empty($slug)) {
                $speciality_check = 0;
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $parent_id = $doctor->id;

                    $getSlots = DrsPanel::getBookingShifts($parent_id, $date, $user_id);
                    if (!empty($getSlots)) {
                        $checkForCurrentShift = $getSlots[0]['schedule_id'];
                        $current_selected = $checkForCurrentShift;
                        $getAppointments = DrsPanel::appointmentHistory($parent_id, $date, $current_selected, $getSlots, $typeselected);
                        $shiftAll = DrsPanel::getDoctorAllShift($parent_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                        $appointments = $getAppointments['bookings'];
                        $typeCount = $getAppointments['type'];
                    }
                    return $this->render('/attender/history-statistics/user-statistics-data', ['typeCount' => $typeCount, 'typeselected' => $typeselected, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'current_selected' => $current_selected]);
                }
            }

            if ($speciality_check == 1) {
                if (isset($string['speciality']) && !empty($string['speciality'])) {
                    $selected_speciality = $string['speciality'];
                } else {
                    $selected_speciality = 0;
                }

                $params['user_id'] = $user_id;
                $params['filter'] = json_encode(array(['type' => 'speciality', 'list' => [$selected_speciality]]));
                $data_array = DrsPanel::getDoctorSliders($params);

                return $this->render('/common/speciality-doctor-list', ['defaultCurrrentDay' => strtotime($date), 'data_array' => $data_array, 'hospital' => $hospital, 'selected_speciality' => $selected_speciality, 'type' => $type, 'actionType' => 'user_history', 'userType' => 'attender', 'page_heading' => 'User Statistics Data', 'doctor_id' => $parent_id]);
            } else {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        } else {
            $doctor = User::findOne($parent_id);

            $getSlots = DrsPanel::getBookingShifts($parent_id, $date, $user_id);
            if (!empty($getSlots)) {
                $checkForCurrentShift = $getSlots[0]['schedule_id'];
                $current_selected = $checkForCurrentShift;
                $getAppointments = DrsPanel::appointmentHistory($parent_id, $date, $current_selected, $getSlots, $typeselected);
                $shiftAll = DrsPanel::getDoctorAllShift($parent_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            return $this->render('/attender/history-statistics/user-statistics-data', ['typeCount' => $typeCount, 'typeselected' => $typeselected, 'appointments' => $appointments, 'shifts' => $shiftAll, 'defaultCurrrentDay' => strtotime($date), 'doctor' => $doctor, 'current_selected' => $current_selected, 'doctor_id' => $parent_id]);
        }
    }

    public function actionAjaxUserStatisticsData() {
        $user_id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($user_id);
        $parent_id = $getParentDetails['parent_id'];


        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $date = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['date']));
            $parent_id = $post['user_id'];
            $doctor = User::findOne($parent_id);

            $current_selected = 0;
            $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
            $checkForCurrentShift = 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($parent_id, $date, $user_id);
            if (!empty($getSlots)) {
                $checkForCurrentShift = $getSlots[0]['schedule_id'];
                $current_selected = $checkForCurrentShift;
                $getAppointments = DrsPanel::appointmentHistory($parent_id, $date, $current_selected, $getSlots, $typeselected);
                $shiftAll = DrsPanel::getDoctorAllShift($parent_id, $date, $checkForCurrentShift, $getSlots, $current_selected);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            return $this->renderAjax('/attender/history-statistics/_user-statistics-data', ['typeCount' => $typeCount, 'typeselected' => $typeselected, 'appointments' => $appointments, 'shifts' => $shiftAll, 'date' => strtotime($date), 'doctor' => $doctor, 'current_shifts' => $current_selected]);
        }
    }

    public function actionAjaxStatisticsData() {
        $user_id = Yii::$app->user->id;
        $getParentDetails = DrsPanel::getParentDetails($user_id);
        $parent_id = $getParentDetails['parent_id'];

        $result['status'] = false;
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();

            $parent_id = $post['user_id'];
            $doctor = User::findOne($parent_id);

            $date = ($post['date']) ? date('Y-m-d', strtotime($post['date'])) : date('Y-m-d');
            if (isset($post['type'])) {
                $typeselected = ($post['type'] == 'online') ? UserAppointment::BOOKING_TYPE_ONLINE : UserAppointment::BOOKING_TYPE_OFFLINE;
            } else {
                $typeselected = UserAppointment::BOOKING_TYPE_ONLINE;
            }
            $checkForCurrentShift = (isset($post['shift_id'])) ? $post['shift_id'] : 0;
            $appointments = $shiftAll = $typeCount = [];
            $getSlots = DrsPanel::getBookingShifts($parent_id, $date, $user_id);
            if (!empty($getSlots)) {
                $getAppointments = DrsPanel::appointmentHistory($parent_id, $date, $checkForCurrentShift, $getSlots, $typeselected);
                $appointments = $getAppointments['bookings'];
                $typeCount = $getAppointments['type'];
            }
            $result['status'] = true;
            $result['appointments'] = $this->renderAjax('/common/_appointment-token', ['appointments' => $appointments, 'typeselected' => $typeselected, 'typeCount' => $typeCount, 'userType' => 'attender', 'doctor' => $doctor]);
            $result['typeCount'] = $typeCount;
            $result['typeselected'] = $typeselected;
        }
        return json_encode($result);
    }

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
            if ($groupid != Groups::GROUP_ATTENDER) {
                return $this->goHome();
            } else {
                return parent::beforeAction($action);
            }
        }
        $this->redirect(array('/'));
    }

}
