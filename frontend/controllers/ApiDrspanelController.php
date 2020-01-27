<?php

namespace frontend\controllers;

use backend\models\DoctorForm;
use backend\models\HospitalForm;
use backend\models\PatientForm;
use Codeception\Step\Meta;
use common\components\DrsImageUpload;
use common\components\DrsPanel;
use common\components\Logs;
use common\components\Notifications;
use common\components\Payment;
use common\models\Areas;
use common\models\Article;
use common\models\Cities;
use common\models\HospitalAttender;
use common\models\MetaKeys;
use common\models\MetaValues;
use common\models\PatientMemberRecords;
use common\models\States;
use common\models\Tempuser;
use common\models\Transaction;
use common\models\User;
use common\models\UserAboutus;
use common\models\UserAddress;
use common\models\UserAddressImages;
use common\models\UserAppointment;
use common\models\UserAppointmentTemp;
use common\models\UserFavorites;
use common\models\UserExperience;
use common\models\UserEducations;
use common\models\UserRating;
use common\models\UserRatingLogs;
use common\models\UserReminder;
use common\models\UserSchedule;
use common\models\UserScheduleDay;
use common\models\UserScheduleGroup;
use common\models\UserScheduleSlots;
use common\models\PatientMembers;
use common\models\PatientMemberFiles;
use common\models\UserSettings;
use common\models\UserRequest;
use common\models\UserVerification;
use League\Uri\Modifiers\AppendLabel;
use Yii;
use yii\data\Pagination;
use yii\db\Query;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\helpers\Url;
use common\components\ApiFields;
use common\models\UserProfile;
use common\models\Groups;
use common\models\Page;
use backend\models\AddScheduleForm;
use backend\models\AttenderForm;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use kartik\mpdf\Pdf;

/**
 * Site controller
 */
class ApiDrspanelController extends ActiveController {

    public $modelClass = '';

    public function beforeAction($action) {
        return parent::beforeAction($action);
        $this->enableCsrfValidation = false;
    }

    /*
     * @Param Null
     * @Function used for get groups
     * @return json
     */

    public function actionGroups() {
        $groups = Groups::find()->where(['show' => Groups::GROUP_ACTIVE])->select(['id', 'name'])->asArray()->all();
        $response["status"] = 1;
        $response["error"] = false;
        $response['data'] = $groups;

        $response['message'] = Yii::t('db', 'Success');

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function used for get gender
     * @return json
     */

    public function actionGender() {
        $gender[0] = array('id' => UserProfile::GENDER_MALE, 'label' => 'Male');
        $gender[1] = array('id' => UserProfile::GENDER_FEMALE, 'label' => 'Female');
        $gender[2] = array('id' => UserProfile::GENDER_OTHER, 'label' => 'Other');

        $response["status"] = 1;
        $response["error"] = false;
        $response['data'] = $gender;
        $response['message'] = Yii::t('db', 'Success');

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function used for get country code
     * @return json
     */

    public function actionGetCountryCode() {
        $countrycode = DrsPanel::getCountryCode();
        $response["status"] = 1;
        $response["error"] = false;
        $response['data'] = $countrycode;
        $response['message'] = Yii::t('common', 'Success');

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function used for get country code
     * @return json
     */

    public function actionGetStates() {
        $countrycode = DrsPanel::getStateList();
        $response["status"] = 1;
        $response["error"] = false;
        $response['data'] = $countrycode;
        $response['message'] = Yii::t('common', 'Success');

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function used for get city list by coountry
     * @return json
     */

    public function actionGetCity() {
        $response = $groups_v = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['state_id']) && $params['state_id'] != '') {
            $stateid = $params['state_id'];
            $countrycode = DrsPanel::getCitiesList($stateid);
            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $countrycode;
            $response['message'] = Yii::t('common', 'Success');
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'State id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function used for get area list by city
     * @return json
     */

    public function actionGetAreas() {
        $response = $groups_v = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['city_id']) && $params['city_id'] != '') {
            $cityid = $params['city_id'];
            $areas = DrsPanel::getCityAreasList($cityid);
            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $areas;
            $response['message'] = Yii::t('common', 'Success');
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'City id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function used for get state/city/area list
     * @return json
     */

    public function actionGetStateCityAreas() {
        $response = $groups_v = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['state_id']) && $params['state_id'] != '') {
            if (isset($params['city_id']) && $params['city_id'] != '') {

                $countrycode = DrsPanel::getStateList();

                $state_name = $params['state_id'];
                $state_id = States::getIdByName($state_name);
                $citylist = DrsPanel::getCitiesList($state_id);

                $city_name = $params['city_id'];
                $city_id = Cities::getIdByNameState($city_name, $state_id);
                $areas = DrsPanel::getCityAreasList($city_id);

                $response["status"] = 1;
                $response["error"] = false;
                $response['data']['state'] = $countrycode;
                $response['data']['city'] = $citylist;
                $response['data']['area'] = $areas;
                $response['message'] = Yii::t('common', 'Success');
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'City id required';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'State id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function used for get meta data
     * @return json
     */

    public function actionGetMetaData() {
        $response = $groups_v = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['type']) && $params['type'] != '') {

            if ($params['type'] == 'speciality' && isset($params['count']) && $params['count'] == 1 && $params['search_type'] == 'doctor') {
                if (isset($params['term'])) {
                    $term = $params['term'];
                } else {
                    $term = '';
                }

                $userLocation = $this->getLocationUsersArray($params);

                $lists = new Query();
                $lists = UserProfile::find();
                $lists->joinWith('user');
                $lists->where(['user_profile.groupid' => Groups::GROUP_DOCTOR]);
                $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                    'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
                $lists->andWhere(['user_profile.shifts' => 1]);
                $lists->andWhere((['user.id' => $userLocation]));
                $command = $lists->createCommand();
                $countQuery = clone $lists;
                $countTotal = $countQuery->count();
                $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());
                $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount, $term);

                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $s_list;
                $response['message'] = 'Success';
            } elseif ($params['type'] == 'speciality' && isset($params['count']) && $params['count'] == 1 && $params['search_type'] == 'hospital') {

                if (isset($params['term'])) {
                    $term = $params['term'];
                } else {
                    $term = '';
                }
                $userLocation = $this->getLocationUsersArray($params);
                $lists = new Query();
                $lists = UserProfile::find();
                $lists->joinWith('user');
                $lists->where(['user_profile.groupid' => Groups::GROUP_HOSPITAL]);
                $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                    'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
                $lists->andWhere(['user_profile.shifts' => 1]);
                $lists->andWhere((['user.id' => $userLocation]));
                $command = $lists->createCommand();
                $countQuery = clone $lists;
                $countTotal = $countQuery->count();
                $fetchCount = Drspanel::fetchHospitalSpecialityCount($command->queryAll());
                $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount, $term);

                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $s_list;
                $response['message'] = 'Success';
            } else {
                $key = MetaKeys::findOne(['key' => $params['type']]);
                if (!empty($key)) {
                    $metavalues = MetaValues::find()->where(['key' => $key->id, 'status' => 1])->all();

                    $groups_v['type'] = $key->key;
                    $groups_v['list'] = array();
                    $m = 0;
                    $all_active_values = array();
                    foreach ($metavalues as $values) {
                        $all_active_values[] = $values->value;
                        $groups_v['list'][$m]['id'] = $values->id;
                        $groups_v['list'][$m]['label'] = $values->label;
                        $groups_v['list'][$m]['value'] = $values->value;
                        if (isset($values->count)) {
                            $groups_v['list'][$m]['count'] = $values->count;
                        }
                        $groups_v['list'][$m]['icon'] = ($values->icon) ? $values->base_path . $values->file_path . $values->icon : '';
                        $m++;
                    }

                    if ($params['type'] == 'services' && !empty($params['user_id'])) {
                        $user_id = $params['user_id'];
                        $profile = UserProfile::findOne($user_id);
                        $services = $profile->services;
                        if (!empty($services)) {
                            $services = explode(',', $services);
                            foreach ($services as $service) {
                                if (!in_array($service, $all_active_values)) {
                                    $checkValue = MetaValues::find()->where(['key' => $key->id, 'value' => $service])->one();
                                    if (!empty($checkValue)) {
                                        $groups_v['list'][$m]['id'] = $checkValue->id;
                                        $groups_v['list'][$m]['label'] = $checkValue->label;
                                        $groups_v['list'][$m]['value'] = $checkValue->value;
                                        if (isset($checkValue->count)) {
                                            $groups_v['list'][$m]['count'] = $checkValue->count;
                                        }
                                        $groups_v['list'][$m]['icon'] = ($checkValue->icon) ? $checkValue->base_path . $checkValue->file_path . $checkValue->icon : '';
                                        $m++;
                                    }
                                }
                            }
                        }
                    }

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = $groups_v;
                    $response['message'] = 'Success';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Type not found';
                }
            }
        } else {
            $keys = MetaKeys::find()->all();
            if (!empty($keys)) {
                $l = 0;
                foreach ($keys as $key) {
                    $metavalues = MetaValues::find()->where(['key' => $key->id])->all();
                    $m = 0;
                    $groups_v[$l]['type'] = $key->key;
                    $groups_v[$l]['list'] = array();
                    foreach ($metavalues as $values) {
                        $groups_v[$l]['list'][$m]['id'] = $values->id;
                        $groups_v[$l]['list'][$m]['label'] = $values->label;
                        $groups_v[$l]['list'][$m]['value'] = $values->value;
                        $groups_v[$l]['list'][$m]['value'] = $values->count;
                        $groups_v[$l]['list'][$m]['icon'] = ($values->icon) ? $values->base_path . $values->file_path . $values->icon : '';
                        $m++;
                    }
                    $l++;
                }
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $groups_v;
                $response['message'] = 'Success';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Data not found';
            }
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetSuggestionList() {
        $response = $data = $out = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        $query = new Query;
        $user = array();
        $q = '';
        $type = '';
        $listg = array();
        if (isset($params['type']) && $params['type'] != '') {
            $type = $params['type'];
        }

        if ($type == 'hospital') {
            $group = Groups::GROUP_HOSPITAL;
        } else {
            $type = 'doctor';
            $group = Groups::GROUP_DOCTOR;
        }

        if (isset($params['term'])) {
            $q = trim($params['term']);
        }
        if ($q != '') {
            $words = explode(' ', $q);
            $words = DrsPanel::search_permute($words);
            $userprofilelist = DrsPanel::getUserSearchListArray($words, $group);
            $list = array();
            foreach ($userprofilelist as $result) {
                $avator = DrsPanel::getUserThumbAvator($result['id']);
                $list[] = array('type' => $type, 'id' => $result['id'], 'query' => $result['query'], 'label' => $result['label'], 'avator' => $avator, 'speciality' => $result['speciality']);
                $category = $result['category'];
            }
            $listg[$type]['type'] = $type;
            $listg[$type]['label'] = ucfirst($type);
            $listg[$type]['list'] = $list;

            $lists = new Query();
            $lists = UserProfile::find();
            $lists->joinWith('user');
            $lists->where(['user_profile.groupid' => $group]);
            $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
            $lists->andWhere(['user_profile.shifts' => 1]);
            $command = $lists->createCommand();
            $countQuery = clone $lists;
            $countTotal = $countQuery->count();
            $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());
            $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount, $q);

            $listg['speciality']['type'] = 'speciality';
            $listg['speciality']['label'] = 'Speciality';
            $listg['speciality']['list'] = $s_list;

            $out = array_values($listg);
        } else {
            $lists = new Query();
            $lists = UserProfile::find();
            $lists->joinWith('user');
            $lists->where(['user_profile.groupid' => $group]);
            $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
            $lists->andWhere(['user_profile.shifts' => 1]);
            $command = $lists->createCommand();
            $countQuery = clone $lists;
            $countTotal = $countQuery->count();
            $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());
            $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount, $term = '');

            $listg['speciality']['type'] = 'speciality';
            $listg['speciality']['label'] = 'Speciality';
            $listg['speciality']['list'] = $s_list;
            $out = array_values($listg);
        }

        if (empty($response)) {
            $data_array = $out;
            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $data_array;
            $response['message'] = 'Search Filter List';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for getting list of title types by user group
     */

    public function actionGetTitleType() {
        $response = $groups_v = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['groupid']) && $params['groupid'] != '') {
            $list = $this->titletype($params['groupid']);
            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $list;
            $response['message'] = Yii::t('db', 'Success');
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Group id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for getting list of treatments by speciality
     */

    public function actionGetTreatments() {
        $response = $arraytreat = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['speciality']) && $params['speciality'] != '') {
            $speciality = $params['speciality'];
            if (isset($params['user_id'])) {
                $arraytreat = $this->treatment($speciality, $params['user_id']);
            } else {
                $arraytreat = $this->treatment($speciality);
            }
            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $arraytreat;
            $response['message'] = Yii::t('common', 'Success');
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Speciality required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for sending otp
     */

    public function actionSendOtp() {
        $fields = ApiFields::sendOtpFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            if (!isset($params['countrycode'])) {
                $params['countrycode'] = '91';
            }
            $checkUser = DrsPanel::sendOtpStep($params['mobile'], $params['countrycode'], $params['groupid']);
            if (!empty($checkUser)) {
                if ($checkUser['type'] == 'error') {
                    $response["status"] = 0;
                    $response["error"] = true;
                    if (!empty($checkUser['data'])) {
                        $response["data"] = $checkUser['data'];
                    }
                    $response['message'] = $checkUser['message'];
                } else {
                    $checkUser['groupid'] = $params['groupid'];
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response["data"] = $checkUser;
                    $response['message'] = 'Otp sended on number entered by you, please verfiy it';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Please try again!';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for verify otp
     */

    public function actionVerifyOtp() {
        $fields = ApiFields::verifyotpFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }

        if (empty($required)) {
            if (!isset($params['countrycode'])) {
                $params['countrycode'] = '91';
            }
            $mobile = $params['mobile'];
            $otp = $params['otp'];
            $userType = $params['groupid'];
            $countrycode = $params['countrycode'];
            $token = ($params['token']) ? $params['token'] : '';
            $device_type = ($params['device_type']) ? $params['device_type'] : '';
            $device_id = ($params['device_id']) ? $params['device_id'] : '';
            $checkUser = DrsPanel::verifyOtpStep($mobile, $countrycode, $otp, $userType, $token, $device_type, $device_id);
            if ($checkUser['type'] == 'success') {
                $response["status"] = 1;
                $response["error"] = false;
                if ($checkUser['userType'] == 'old') {
                    $response["data"] = $checkUser['data'];
                    $response["data"]['login_status'] = $checkUser['userType'];
                } else {
                    $response["data"]['mobile'] = $checkUser['mobile'];
                    $response["data"]['countrycode'] = $checkUser['countrycode'];
                    $response["data"]['login_status'] = $checkUser['userType'];
                }
                $response['message'] = 'Success';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                if (!empty($checkUser['data'])) {
                    $response["data"] = $checkUser['data'];
                }
                $response['message'] = $checkUser['message'];
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for signup
     */

    public function actionSignup() {
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        $response = $data = $required = array();

        if (isset($params['groupid'])) {
            $userType = $params['groupid'];
            if ($userType == Groups::GROUP_PATIENT || $userType == Groups::GROUP_DOCTOR) {
                $fields = ApiFields::signupFields();
            } else {
                $fields = ApiFields::signupFieldsHospital();
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $params)) {
                    
                } else {
                    $required[] = $field;
                }
            }
            if (empty($required)) {
                $params['countrycode'] = (isset($params['countrycode'])) ? $params['countrycode'] : '91';
                $checkEmail = User::getEmailExist($params['email']);
                if ($checkEmail > 0) {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response["type"] = 'Email_Already_Registered';
                    $response['message'] = Yii::t('common', 'This email address has already been taken.');
                } else {
                    $checkVerification = DrsPanel::checkOtpVerified($params['mobile'], $params['countrycode'], $params['groupid']);
                    if ($checkVerification['type'] == 'success') {
                        if ($params['groupid'] == Groups::GROUP_DOCTOR) {
                            $model = new DoctorForm();
                            $modelLabel = 'DoctorForm';
                        } elseif ($params['groupid'] == Groups::GROUP_PATIENT) {
                            $model = new PatientForm();
                            $modelLabel = 'PatientForm';
                        } else {
                            $model = new HospitalForm();
                            $modelLabel = 'HospitalForm';
                        }
                        $model->groupid = $params['groupid'];
                        $model->name = $params['name'];
                        $model->email = $params['email'];
                        $model->countrycode = $params['countrycode'];
                        $model->phone = $params['mobile'];
                        $model->token = $params['token'];
                        $model->device_id = $params['device_id'];
                        $model->device_type = $params['device_type'];
                        if ($userType == Groups::GROUP_PATIENT || $userType == Groups::GROUP_DOCTOR) {
                            $model->dob = $params['dob'];
                            $model->gender = $params['gender'];
                        }
                        if ($model->validate()) {
                            $resp = $model->signup();
                            if ($resp == "ERROR") {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = Yii::t('common', 'Mandatory fields are required.');
                            } elseif ($resp == "EMAIL_NOT_VALID") {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = Yii::t('common', 'Email id is not valid.');
                            } elseif ($resp == "USER_NOT_SAVE") {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = Yii::t('common', 'User couldn\'t be  saved');
                            } else {
                                $user_id = $resp->getId();
                                $user = User::findOne(['id' => $user_id]);
                                $sendSignupNotification = Notifications::signupNotify($user_id);
                                if (!empty($user)) {
                                    if (isset($_FILES['image'])) {
                                        $imageUpload = DrsImageUpload::updateProfileImageApp($user->id, $_FILES);
                                    }
                                    $user->mobile_verified = 1;
                                    $user->save();
                                    $profile = UserProfile::findOne(['user_id' => $user->id]);
                                    $data_array = DrsPanel::profiledetails($user, $profile, $user->groupid, $user->id);
                                    $response["status"] = 1;
                                    $response["error"] = false;
                                    $response['data'] = $data_array;
                                    if ($params['groupid'] == Groups::GROUP_DOCTOR) {
                                        $response['message'] = Yii::t('common', 'Your account has been successfully created. Please update your profile & request for live');
                                    } else {
                                        $response['message'] = Yii::t('common', 'Your account has been successfully created.');
                                    }
                                } else {
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["message"] = 'Please try again';
                                }
                            }
                        } else {
                            $response = DrsPanel::validationErrorMessage($model->getErrors());
                        }
                    } elseif ($checkVerification['type'] == 'verification_error') {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response["type"] = 'Mobile_Invalid';
                        $response['message'] = Yii::t('common', 'Mobile number not verified');
                    } elseif ($checkVerification['type'] == 'already_registered') {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response["type"] = 'Mobile_Already_Registered';
                        $response['message'] = Yii::t('common', 'Mobile number already registered');
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response["type"] = 'Invalid';
                        $response['message'] = Yii::t('common', 'Please try again');
                    }
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $fields_req = implode(',', $required);
                $response['message'] = $fields_req . ' required';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'User Type group Required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for get user status
     */

    public function actionGetUserStatus() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $user_id = $params['user_id'];
            $user = User::findOne($user_id);
            $profile = UserProfile::findOne(['user_id' => $user_id]);
            if (!empty($profile)) {
                if ($profile->groupid != Groups::GROUP_PATIENT) {
                    $data_array['status'] = $user->admin_status;
                } else {
                    $data_array['status'] = $user->admin_status;
                }
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $data_array;
                $response['message'] = 'Profile Status';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserID required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for getuser profile status
     */

    public function actionGetProfileStatus() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                $profile = UserProfile::findOne(['user_id' => $user->id]);
                $response["status"] = 1;
                $response["error"] = false;
                $response['profile_status'] = $user->admin_status;
                $response['message'] = 'Profile Status';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for logout and device token clear
     */

    public function actionLogout() {
        $fields = ApiFields::logoutFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }

        if (empty($required)) {
            $user_id = $params['user_id'];
            $checkUser = User::findOne($user_id);
            if (!empty($checkUser)) {
                $checkUser->token = '';
                if ($checkUser->save()) {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Success';
                    Yii::info($response, __METHOD__);
                    \Yii::$app->response->format = Response::FORMAT_JSON;
                    return $response;
                }
                /* else{
                  $response["status"] = 0;
                  $response["error"] = true;
                  $response['message'] = 'Please try again';
                  } */
            }
            /* else{
              $response["status"] = 0;
              $response["error"] = true;
              $response['message'] = 'User not found';
              } */
        }
        $response["status"] = 1;
        $response["error"] = false;
        $response['message'] = 'Success';
        /* else{
          $response["status"] = 0;
          $response["error"] = true;
          $fields_req= implode(',',$required);
          $response['message'] = $fields_req.' required';
          } */
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for editing email or phone
     */

    public function actionSendEmailPhoneOtp() {
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        $response = $data = $required = array();

        if ((isset($params['user_id']) && isset($params['email'])) ||
                (isset($params['user_id']) && isset($params['mobile']))) {
            $user_id = $params['user_id'];
            if (isset($params['email'])) {
                $type = 'email';
            } else {
                $type = 'phone';
            }
            $user = User::findOne($user_id);
            if (!empty($user)) {
                if ($type == 'email') {
                    $checkexist = User::find()->andWhere(['email' => $params['email']])->andWhere(['!=', 'id', $user_id])->one();
                    if ($checkexist) {
                        $response["status"] = 0;
                        $response["error"] = true;
                        //$response['user_verified']=DrsPanel::getProfileStatus($user_id);
                        $response['message'] = 'Email already register';
                    } else {
                        if ($user->email == $params['email']) {
                            $response["status"] = 0;
                            $response["error"] = true;
                            //  $response['user_verified']=DrsPanel::getProfileStatus($user_id);
                            $response['message'] = 'You have entered same email as old';
                        } else {
                            $otp = DrsPanel::randomOTP();
                            //update email & send otp
                            $model = UserVerification::find()->where(['user_id' => $user_id])->one();
                            if (empty($model)) {
                                $model = new UserVerification();
                            }
                            $model->user_id = $user_id;
                            $model->email = $params['email'];
                            $model->otp = 1234;
                            if ($model->save()) {
                                $userDetail = UserProfile::find()->where(['user_id' => $user_id])->one();
                                $userPhone = User::find()->andWhere(['id' => $user_id])->one();
                                $userD = UserVerification::find()->where(['user_id' => $user_id])->one();
                                \common\components\MailSend::sendOtpMail($userDetail, $userD, $otp);
                                $message = $otp . ' is the OTP for accessing your DrsPanel account. PLS DO NOT SHARE IT WITH ANYONE.';
                                $sendSms = Notifications::send_sms($message, $userPhone['phone'], 'No', $userPhone['countrycode'], 1);
                                $response["status"] = 1;
                                $response["error"] = false;
                                //  $response['user_verified']=DrsPanel::getProfileStatus($user_id);
                                $response['message'] = 'Otp sended on number registered by you, please verfiy it';
                            } else {
                                $response["status"] = 0;
                                $response["error"] = true;
                                // $response['user_verified']=DrsPanel::getProfileStatus($user_id);
                                $response['message'] = 'Please try again';
                            }
                        }
                    }
                } else {
                    $checkexist = User::find()->andWhere(['phone' => $params['mobile'], 'groupid' => $user->groupid])->andWhere(['!=', 'id', $user_id])->one();
                    if ($checkexist) {
                        $response["status"] = 0;
                        $response["error"] = true;
                        // $response['user_verified']=DrsPanel::getProfileStatus($user_id);
                        $response['message'] = 'Mobile number already register';
                    } else {
                        if ($user->phone == $params['mobile']) {
                            $response["status"] = 0;
                            $response["error"] = true;
                            //  $response['user_verified']=DrsPanel::getProfileStatus($user_id);
                            $response['message'] = 'You have entered same mobile number as old';
                        } else {
                            $otp = DrsPanel::randomOTP();
                            //update email & send otp
                            $model = UserVerification::find()->where(['user_id' => $user_id])->one();
                            if (empty($model)) {
                                $model = new UserVerification();
                            }
                            $model->user_id = $user_id;
                            $model->phone = $params['mobile'];
                            $model->otp = 1234;
                            if ($model->save()) {
                                $userDetail = UserProfile::find()->where(['user_id' => $user_id])->one();
                                $userD = UserVerification::find()->where(['user_id' => $user_id])->one();
                                $userPhone = User::find()->andWhere(['id' => $user_id])->one();
                                \common\components\MailSend::sendOtpMail($userDetail, $userD, $otp);
                                $message = $otp . ' is the OTP for accessing your DrsPanel account. PLS DO NOT SHARE IT WITH ANYONE.';
                                $sendSms = Notifications::send_sms($message, $userPhone['phone'], 'No', $userPhone['countrycode'], 1);
                                $response["status"] = 1;
                                $response["error"] = false;
                                //  $response['user_verified']=DrsPanel::getProfileStatus($user_id);
                                $response['message'] = 'Otp sended on email registered by you, please verfiy it';
                            } else {
                                $response["status"] = 0;
                                $response["error"] = true;
                                // $response['user_verified']=DrsPanel::getProfileStatus($user_id);
                                $response['message'] = 'Please try again';
                            }
                        }
                    }
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Required fields missing';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for verigying email or phone otp
     */

    public function actionVerifyEmailPhoneOtp() {
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        $response = $data = $required = array();

        if ((isset($params['user_id']) && isset($params['email']) && isset($params['otp'])) ||
                (isset($params['user_id']) && isset($params['mobile']) && isset($params['otp']))) {

            $user_id = $params['user_id'];
            $model = UserVerification::find()->where(['user_id' => $user_id])->one();
            if (!empty($model)) {
                if (isset($params['email'])) {
                    $type = 'email';
                } else {
                    $type = 'phone';
                }

                if ($model->otp == $params['otp']) {
                    $user = User::findOne($user_id);
                    $profile = UserProfile::find()->where(['user_id' => $user_id])->one();
                    $userType = $user->groupid;
                    $data_array = DrsPanel::profiledetails($user, $profile, $userType);
                    if ($type == 'email') {
                        $user->email = $model->email;
                        if ($user->save()) {
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response['data'] = $data_array;
                            $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                            $response['message'] = 'Email updated';
                        }
                    } else {
                        $user->phone = $model->phone;
                        if ($user->save()) {
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response['data'] = $data_array;
                            $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                            $response['message'] = 'Mobile number updated';
                        }
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                    $response['message'] = 'Invalid OTP Please Try Again.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                $response['message'] = 'Please try again';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Required fields missing';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for getuser profile details
     */

    public function actionGetProfile() {
        $response = $data = $arraytreat = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                $profile = UserProfile::findOne(['user_id' => $user->id]);
                $data_array = DrsPanel::profiledetails($user, $profile, $user->groupid, $params['user_id']);
                $response["status"] = 1;
                $response["error"] = false;
                $response['profile'] = $data_array;
                $response['service_charge'] = DrsPanel::getMetaData('service_charge');
                $response['blood_group'] = DrsPanel::getMetaData('blood_group');
                $response['degree'] = DrsPanel::getMetaData('degree');
                $response['speciality'] = DrsPanel::getMetaData('speciality');
                if ($user->groupid == Groups::GROUP_PATIENT) {
                    $lists = DrsPanel::getPatientAppointments($params['user_id'], 'upcoming');
                    $lists->all();
                    $command = $lists->createCommand();
                    $lists = $command->queryAll();
                    $list_a = DrsPanel::getPatientAppointmentsList($lists);
                    $upcoming = array_values($list_a);
                    $response['upcoming_appointments'] = (!empty($upcoming)) ? $upcoming : '';
                }
                if (!empty($profile->speciality)) {
                    $arraytreat = $this->treatment($profile->speciality, $params['user_id']);
                    $response['treatment'] = $arraytreat;
                } else {
                    $response['treatment'] = [];
                }

                if ($user->groupid == Groups::GROUP_HOSPITAL) {
                    $getspecialities = Drspanel::getMyHospitalSpeciality($profile->user_id);
                    $Servicedata = explode(',', $getspecialities['speciality']);
                    $Treatmentdata = explode(',', $getspecialities['treatments']);
                    $response['profile']['speciality'] = $Servicedata;
                    $response['profile']['treatment'] = $Treatmentdata;
                    $response['services'] = DrsPanel::getMetaData('services', $params['user_id']);
                    $response['address_type'] = DrsPanel::getMetaData('address_type');
                }


                $response['title_type'] = $this->titletype($user->groupid);

                $response['message'] = 'Profile Data';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for edit doctor profile details
     */

    public function actionEditDoctorProfile() {
        $fields = ApiFields::editDoctorFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {

            if (!empty($params['user_id'])) {
                $user_id = $params['user_id'];
                $user = User::findOne(['id' => $user_id]);
                $profile = UserProfile::findOne(['user_id' => $user_id]);
                if (!empty($profile)) {
                    $groupid = $profile->groupid;
                    $params['countrycode'] = (isset($params['countrycode'])) ? $params['countrycode'] : '91';
                    $checkMobileUpdate = DrsPanel::checkmobileUpdate($user_id, $params['countrycode'], $params['mobile'], $groupid);
                    if ($checkMobileUpdate['type'] == 'error') {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                        $response['message'] = $checkMobileUpdate['message'];
                    } else {
                        $checkEmail = DrsPanel::checkemailUpdate($user_id, $params['email']);
                        if ($checkEmail['type'] == 'error') {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                            $response['message'] = $checkEmail['message'];
                        } else {
                            $user->email = $params['email'];
                            $user->countrycode = $params['countrycode'];
                            $user->phone = $params['mobile'];

                            if (isset($_FILES['image'])) {
                                $imageUpload = DrsImageUpload::updateProfileImageApp($user->id, $_FILES);
                            }
                            if ($user->save()) {
                                $profile->name = $params['name'];
                                $profile->email = $params['email'];
                                $profile->dob = $params['dob'];
                                $profile->gender = $params['gender'];
                                if (isset($params['blood_group'])) {
                                    $profile->blood_group = $params['blood_group'];
                                }

                                $profile->experience = $params['experience'];
                                $profile->description = strip_tags($params['description']);

                                $degrees = json_decode($params['degree']);
                                $specialities = json_decode($params['speciality']);

                                if (is_array($degrees) && !empty($degrees)) {
                                    $profile->degree = implode(',', $degrees);
                                }
                                if (is_array($specialities) && !empty($specialities)) {
                                    $profile->speciality = implode(',', $specialities);
                                }

                                if (isset($params['treatment'])) {
                                    $treatment = json_decode($params['treatment']);
                                    if (is_array($treatment) && !empty($treatment)) {
                                        $profile->treatment = implode(',', $treatment);
                                    }
                                }

                                if (isset($params['services'])) {
                                    $services = json_decode($params['services']);
                                    if (is_array($services) && !empty($services)) {
                                        $profile->services = implode(',', $services);
                                    }
                                }

                                if ($profile->save()) {
                                    if ($checkMobileUpdate['message'] == 'new') {
                                        $user->otp = '1234';
                                        $user->mobile_verified = 0;
                                        if ($user->save()) {
                                            $response["status"] = 1;
                                            $response["error"] = false;
                                            $response["otp_alert"] = 1;
                                            $response['data'] = DrsPanel::profiledetails($user, $profile, $groupid, $params['user_id']);
                                            $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                                            $response['message'] = 'Profile saved & otp sended';
                                        } else {
                                            $response = DrsPanel::validationErrorMessage($user->getErrors());
                                        }
                                    } else {
                                        $getResponseMessage = DrsPanel::getDoctorProfileMsg($profile->user_id);
                                        $response["status"] = 1;
                                        $response["error"] = false;
                                        $response["otp_alert"] = 0;
                                        $response['data'] = DrsPanel::profiledetails($user, $profile, $groupid, $params['user_id']);
                                        $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                                        $response['message'] = $getResponseMessage;
                                    }
                                } else {
                                    $response = DrsPanel::validationErrorMessage($profile->getErrors());
                                    $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                                }
                            } else {
                                $response = DrsPanel::validationErrorMessage($user->getErrors());
                                $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                            }
                        }
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = Yii::t('db', 'User not found');
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = Yii::t('db', 'UserId required');
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for add/edit doctor education details
     */

    public function actionUpsertSpecialityTreatment() {
        $fields = ApiFields::specialityTreatment();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();



        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $user_id = $params['user_id'];
            $userProfile = UserProfile::findOne($user_id);
            $metakey = MetaKeys::findOne(['key' => 'Treatment']);

            $metakey_speciality = MetaKeys::findOne(['key' => 'speciality']);

            if (!empty($userProfile)) {
                $Userspecialities = $params['speciality'];

                $getSpecilaity = MetaValues::find()->where(['key' => $metakey_speciality->id, 'value' => $Userspecialities])->one();

                $Usertreatments = $params['treatment'];
                if (isset($Userspecialities) && isset($Usertreatments)) {
                    $Usertreatments = json_decode($params['treatment']);
                    if (!empty($Usertreatments)) {
                        $existing_services = array();
                        $new_services = array();
                        foreach ($Usertreatments as $Usertreatment) {
                            if (isset($Usertreatment->id)) {
                                $existing_services[] = $Usertreatment->value;
                            } else {
                                $new_services[] = $Usertreatment->value;

                                $checkValue = MetaValues::find()->where(['key' => $metakey->id, 'value' => $Usertreatment->value])->one();
                                if (empty($checkValue)) {
                                    $model = new MetaValues();
                                    if (!empty($metakey)) {
                                        if (!empty($getSpecilaity)) {
                                            $model->parent_key = $getSpecilaity->id;
                                        }
                                        $model->key = $metakey->id;
                                        $model->label = $Usertreatment->label;
                                        $model->value = $Usertreatment->value;
                                        $model->slug = DrsPanel::metavalues_slugify($Usertreatment->label);
                                        $model->status = 0;
                                        $model->save();
                                    }
                                }
                            }
                        }
                        $treatments = array_merge($existing_services, $new_services);
                        $userProfile->speciality = $Userspecialities;
                        $userProfile->treatment = implode(',', $treatments);
                    } else {
                        $userProfile->speciality = $Userspecialities;
                        $userProfile->treatment = '';
                    }

                    if ($userProfile->save()) {
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                        $response['message'] = 'Speciality & treatment updated';
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                        $response["data"] = DrsPanel::validationErrorMessage($userProfile->getErrors());
                        $response['message'] = 'Please try again';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['user_verified'] = DrsPanel::getProfileStatus($user_id);
                    $response['message'] = 'Please select speciality & treatment';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for get doctor education list
     */

    public function actionEducationList() {
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);
        if (isset($post['doctor_id']) && !empty($post['doctor_id'])) {
            $list = DrsPanel::getDoctorEducation($post['doctor_id']);
            if ($list) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Success';
                //$response['user_verified']=DrsPanel::getProfileStatus($post['doctor_id']);
                $response['data'] = DrsPanel::listEducation($list);
            } else {
                $response["status"] = 1;
                $response["error"] = false;
                //$response['user_verified']=DrsPanel::getProfileStatus($post['doctor_id']);
                $response['message'] = 'You have not any education.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for add/edit doctor education details
     */

    public function actionUpsertEducation() {
        $fields = ApiFields::doctorEduction();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $model = DrsPanel::upsertEducation($post);
            if ($model) {
                if ($model->getErrors()) {
                    $response = DrsPanel::validationErrorMessage($model->getErrors());
                } else {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Success';
                    $response['data'] = $model;
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Edcuation not saved.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for get doctor experience list
     */

    public function actionExperienceList() {
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);
        if (isset($post['doctor_id']) && !empty($post['doctor_id'])) {
            $list = DrsPanel::getDoctorExperience($post['doctor_id']);
            if ($list) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Success';
                $response['data'] = DrsPanel::listExperience($list);
            } else {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'You have not any experience.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for add/edit doctor experience details
     */

    public function actionUpsertExperience() {
        $fields = ApiFields::doctorExperience();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $startyears = $post['start'];
            $model = DrsPanel::upsertExperience($post);
            if ($model) {
                if ($model->getErrors()) {
                    $response = DrsPanel::validationErrorMessage($model->getErrors());
                } else {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Success';
                    $response['data'] = $model;
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Experience not saved.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for edit hospital profile details
     */

    public function actionEditHospitalProfile() {
        $fields = ApiFields::editHospitalFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {

            if (!in_array(null, $params)) {
                if (!empty($params['user_id'])) {
                    $user_id = $params['user_id'];
                    $user = User::findOne(['id' => $user_id]);
                    $profile = UserProfile::findOne(['user_id' => $user_id]);
                    if (!empty($profile)) {
                        $groupid = $profile->groupid;
                        $params['countrycode'] = (isset($params['countrycode'])) ? $params['countrycode'] : '91';
                        $checkMobileUpdate = DrsPanel::checkmobileUpdate($user_id, $params['countrycode'], $params['mobile'], $groupid);
                        if ($checkMobileUpdate['type'] == 'error') {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = $checkMobileUpdate['message'];
                        } else {
                            $checkEmail = DrsPanel::checkemailUpdate($user_id, $params['email']);
                            if ($checkEmail['type'] == 'error') {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = $checkEmail['message'];
                            } else {
                                $user->email = $params['email'];
                                $user->countrycode = $params['countrycode'];
                                $user->phone = $params['mobile'];

                                if (isset($_FILES['image'])) {
                                    $imageUpload = DrsImageUpload::updateProfileImageApp($user->id, $_FILES);
                                }
                                if ($user->save()) {
                                    $profile->name = $params['name'];
                                    $profile->email = $params['email'];
                                    $profile->dob = $params['dob'];
                                    $profile->gender = 0;
                                    if ($profile->save()) {

                                        $userAddress = UserAddress::findOne(['user_id' => $user_id]);
                                        if (!empty($userAddress)) {
                                            $userAddress->name = $profile->name;
                                            $userAddress->save();
                                        }

                                        if ($checkMobileUpdate['message'] == 'new') {
                                            $user->otp = '1234';
                                            $user->mobile_verified = 0;
                                            if ($user->save()) {
                                                $response["status"] = 1;
                                                $response["error"] = false;
                                                $response["otp_alert"] = 1;
                                                $response['data'] = DrsPanel::profiledetails($user, $profile, $groupid);
                                                $response['message'] = 'Profile saved & otp sended';
                                            } else {
                                                $response = DrsPanel::validationErrorMessage($user->getErrors());
                                            }
                                        } else {
                                            $getResponseMessage = DrsPanel::getDoctorProfileMsg($profile->user_id);
                                            $response["status"] = 1;
                                            $response["error"] = false;
                                            $response["otp_alert"] = 0;
                                            $response['data'] = DrsPanel::profiledetails($user, $profile, $groupid);
                                            $response['message'] = $getResponseMessage;
                                        }
                                    } else {
                                        $response = DrsPanel::validationErrorMessage($profile->getErrors());
                                    }
                                } else {
                                    $response = DrsPanel::validationErrorMessage($user->getErrors());
                                }
                            }
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = Yii::t('db', 'User not found');
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = Yii::t('db', 'UserId required');
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = Yii::t('db', 'Mandatory fields are required.');
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for edit patient profile details
     */

    public function actionEditPatientProfile() {
        $fields = ApiFields::editPatientFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {

            if (!in_array(null, $params)) {
                if (!empty($params['user_id'])) {
                    $user_id = $params['user_id'];
                    $user = User::findOne(['id' => $user_id]);
                    $profile = UserProfile::findOne(['user_id' => $user_id]);
                    if (!empty($profile)) {
                        $groupid = $profile->groupid;
                        $params['countrycode'] = (isset($params['countrycode'])) ? $params['countrycode'] : '91';
                        $checkMobileUpdate = DrsPanel::checkmobileUpdate($user_id, $params['countrycode'], $params['mobile'], $groupid);
                        if ($checkMobileUpdate['type'] == 'error') {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = $checkMobileUpdate['message'];
                        } else {
                            $checkEmail = DrsPanel::checkemailUpdate($user_id, $params['email']);
                            if ($checkEmail['type'] == 'error') {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = $checkEmail['message'];
                            } else {
                                if (isset($_FILES['image'])) {
                                    $imageUpload = DrsImageUpload::updateProfileImageApp($user->id, $_FILES);
                                }
                                $user->username = $params['email'];
                                $user->email = $params['email'];
                                $user->countrycode = $params['countrycode'];
                                $user->phone = $params['mobile'];
                                if ($user->save()) {
                                    $profile->name = $params['name'];
                                    $profile->email = $params['email'];
                                    $profile->dob = $params['dob'];
                                    $profile->gender = (int) $params['gender'];
                                    $profile->blood_group = isset($params['blood_group']) ? $params['blood_group'] : '';
                                    $profile->weight = isset($params['weight']) ? $params['weight'] : 0;
                                    $height = array('feet' => isset($params['height_feet']) ? $params['height_feet'] : 0, 'inch' => isset($params['height_inch']) ? $params['height_inch'] : 0);
                                    $profile->height = json_encode($height);
                                    $profile->marital = isset($params['marital']) ? $params['marital'] : '';
                                    $profile->location = isset($params['location']) ? $params['location'] : '';
                                    if ($profile->save()) {
                                        if ($checkMobileUpdate['message'] == 'new') {
                                            $user->otp = '1234';
                                            $user->mobile_verified = 0;
                                            if ($user->save()) {
                                                $response["status"] = 1;
                                                $response["error"] = false;
                                                $response["otp_alert"] = 1;
                                                $response['data'] = DrsPanel::profiledetails($user, $profile, $groupid);
                                                $response['message'] = 'Profile saved & otp sended';
                                            } else {
                                                $response = DrsPanel::validationErrorMessage($user->getErrors());
                                            }
                                        } else {
                                            $response["status"] = 1;
                                            $response["error"] = false;
                                            $response["otp_alert"] = 0;
                                            $response['data'] = DrsPanel::profiledetails($user, $profile, $groupid);

                                            $response['message'] = 'Profile saved successfully';
                                        }
                                    } else {
                                        $response = DrsPanel::validationErrorMessage($profile->getErrors());
                                    }
                                } else {
                                    $response = DrsPanel::validationErrorMessage($user->getErrors());
                                }
                            }
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = Yii::t('db', 'User not found');
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = Yii::t('db', 'UserId required');
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = Yii::t('db', 'Mandatory fields are required.');
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for add address
     */

    public function actionAddNewAddress() {
        $fields = ApiFields::addressFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            if (isset($params['user_id']) && $params['user_id'] != '') {
                $user = User::findOne(['id' => $params['user_id']]);
                if (!empty($user)) {
                    if ($user->groupid == Groups::GROUP_HOSPITAL) {
                        $userProfile = UserProfile::findOne(['user_id' => $params['user_id']]);
                        $addAddress = UserAddress::findOne(['user_id' => $params['user_id']]);
                        if (empty($addAddress)) {
                            $addAddress = new UserAddress();
                        }
                    } else {
                        $addAddress = new UserAddress();
                    }
                    $data['UserAddress']['user_id'] = $params['user_id'];
                    $data['UserAddress']['type'] = $params['type'];
                    $data['UserAddress']['name'] = $userProfile->name;
                    $data['UserAddress']['address'] = $params['address'];
                    $data['UserAddress']['city'] = $params['city'];
                    $data['UserAddress']['state'] = $params['state'];
                    $data['UserAddress']['area'] = ($params['area']) ? $params['area'] : '';
                    $data['UserAddress']['country'] = $params['country'];
                    $data['UserAddress']['phone'] = $params['mobile'];
                    $data['UserAddress']['is_request'] = 0;

                    $data['UserAddress']['city_id'] = DrsPanel::getCityId($params['city'], $params['state']);

                    if (isset($params['lat'])) {
                        $data['UserAddress']['lat'] = $params['lat'];
                    } else {
                        $data['UserAddress']['lat'] = '26.943040';
                    }

                    if (isset($params['lng'])) {
                        $data['UserAddress']['lng'] = $params['lng'];
                    } else {
                        $data['UserAddress']['lng'] = '75.757060';
                    }

                    if (isset($params['landline'])) {
                        $data['UserAddress']['landline'] = $params['landline'];
                    }

                    $addAddress->load($data);
                    if ($addAddress->save()) {
                        $imageUpload = '';
                        if (isset($_FILES['image'])) {
                            $imageUpload = DrsImageUpload::updateAddressImage($addAddress->id, $_FILES);
                        }
                        if (isset($_FILES['images'])) {
                            $imageUpload = DrsImageUpload::updateAddressImageList($addAddress->id, $_FILES, 'images');
                        }
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response["data"] = $imageUpload;
                        $response['message'] = 'Address added successfully';
                    } else {
                        $response = DrsPanel::validationErrorMessage($addAddress->getErrors());
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'User not found';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'UserId Required';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for update address
     */

    public function actionUpdateAddress() {
        $fields = ApiFields::updateaddressFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $addAddress = UserAddress::findOne(['id' => $params['address_id']]);
            if (!empty($addAddress)) {
                $userProfile = UserProfile::findOne(['user_id' => $addAddress->user_id]);
                $data['UserAddress']['type'] = $params['type'];
                $data['UserAddress']['name'] = $userProfile->name;
                $data['UserAddress']['address'] = $params['address'];
                $data['UserAddress']['city'] = $params['city'];
                $data['UserAddress']['state'] = $params['state'];
                $data['UserAddress']['area'] = ($params['area']) ? $params['area'] : $addAddress->area;
                $data['UserAddress']['country'] = $params['country'];
                $data['UserAddress']['phone'] = $params['mobile'];

                $data['UserAddress']['city_id'] = DrsPanel::getCityId($params['city'], $params['state']);

                if (isset($params['landline'])) {
                    $data['UserAddress']['landline'] = $params['landline'];
                }

                if (isset($params['lat'])) {
                    $data['UserAddress']['lat'] = $params['lat'];
                } else {
                    $data['UserAddress']['lat'] = $addAddress->lat;
                }

                if (isset($params['lng'])) {
                    $data['UserAddress']['lng'] = $params['lng'];
                } else {
                    $data['UserAddress']['lng'] = $addAddress->lng;
                }
                $addAddress->load($data);
                if ($addAddress->save()) {
                    $imageUpload = '';
                    if (isset($_FILES['image'])) {
                        $imageUpload = DrsImageUpload::updateAddressImage($addAddress->id, $_FILES);
                    }

                    if (isset($params['deletedImages'])) {
                        $images = json_decode($params['deletedImages']);
                        foreach ($images as $image) {
                            $address_file = UserAddressImages::findOne($image);
                            $address_file->delete();
                        }
                    }

                    if (isset($_FILES['images'])) {
                        $imageUpload = DrsImageUpload::updateAddressImageList($addAddress->id, $_FILES, 'images');
                    }

                    $response["status"] = 1;
                    $response["error"] = false;
                    // $response["data"] = $imageUpload;
                    $response['message'] = 'Success';
                } else {
                    $response = DrsPanel::validationErrorMessage($addAddress->getErrors());
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Address not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for add/edit profile service details
     */

    public function actionAddUpdateServices() {
        $fields = ApiFields::addupdateServices();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $user_id = $post['user_id'];
            $profile = UserProfile::findOne($post['user_id']);
            if (!empty($profile)) {
                $metakey = MetaKeys::findOne(['key' => 'Services']);

                if (isset($post['services'])) {
                    $services = json_decode($post['services']);
                    if (!empty($services)) {
                        $existing_services = array();
                        $new_services = array();
                        foreach ($services as $service) {
                            if (isset($service->id)) {
                                $existing_services[] = $service->value;
                            } else {
                                $new_services[] = $service->value;

                                $checkValue = MetaValues::find()->where(['key' => $metakey->id, 'value' => $service->value])->one();
                                if (empty($checkValue)) {
                                    $model = new MetaValues();
                                    if (!empty($metakey)) {
                                        $model->key = $metakey->id;
                                        $model->label = $service->label;
                                        $model->value = $service->value;
                                        $model->status = 0;
                                        $model->save();
                                    }
                                }
                            }
                        }
                        $services = array_merge($existing_services, $new_services);
                        if (!empty($services)) {
                            $val_serv = implode(',', $services);
                            $profile->services = $val_serv;
                        } else {
                            $profile->services = '';
                        }
                    } else {
                        $profile->services = '';
                    }
                }
                if ($profile->save()) {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Services/Facilities updated';
                    $response['services'] = DrsPanel::getMetaData('services', $user_id);
                    $response['data'] = $profile;
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Services/Facilities not saved.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for add/edit profile description details
     */

    public function actionAddUpdateAboutus() {
        $fields = ApiFields::addupdateAboutus();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $profile = UserProfile::findOne($post['user_id']);
            $useraboutus = UserAboutus::find()->where(['user_id' => $post['user_id']])->one();
            if (empty($useraboutus)) {
                $useraboutus = new UserAboutus();
            }
            $useraboutus->user_id = $post['user_id'];
            $useraboutus->description = $post['description'];
            if (isset($post['vision']) & !empty($post['vision'])) {
                $useraboutus->vision = $post['vision'];
            } else {
                $useraboutus->vision = '';
            }

            if (isset($post['mission']) & !empty($post['mission'])) {
                $useraboutus->mission = $post['mission'];
            } else {
                $useraboutus->mission = '';
            }

            if (isset($post['timing']) & !empty($post['timing'])) {
                $useraboutus->timing = $post['timing'];
            } else {
                $useraboutus->timing = '';
            }
            if ($useraboutus->save()) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'About us updated';
                $response['data'] = $profile;
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'About us not saved.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionAddShift() {
        $fields = ApiFields::addShiftFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }

        if (empty($required)) {
            if (isset($params['user_id']) && $params['user_id'] != '') {
                $user = User::findOne(['id' => $params['user_id']]);
                if (!empty($user)) {
                    $addAddress = new UserAddress();
                    $data['UserAddress']['user_id'] = $params['user_id'];
                    $data['UserAddress']['name'] = $params['name'];
                    $data['UserAddress']['city'] = $params['city'];
                    $data['UserAddress']['state'] = $params['state'];
                    $data['UserAddress']['address'] = $params['address'];
                    $data['UserAddress']['area'] = $params['area'];
                    $data['UserAddress']['phone'] = $params['mobile'];
                    $data['UserAddress']['landline'] = isset($params['landline']) ? $params['landline'] : '';
                    $data['UserAddress']['is_request'] = 0;
                    $addAddress->load($data);
                    if ($addAddress->save()) {
                        $imageUpload = '';
                        if (isset($_FILES['image'])) {
                            $imageUpload = DrsImageUpload::updateAddressImage($addAddress->id, $_FILES);
                        }
                        if (isset($_FILES['images'])) {
                            $imageUpload = DrsImageUpload::updateAddressImageList($addAddress->id, $_FILES, 'images');
                        }

                        $shift = array();
                        $address_id = $addAddress->id;
                        if ((isset($params['shiftList']) && !empty($params['shiftList']))) {
                            $shiftList = json_decode($params['shiftList']);
                            foreach ($shiftList as $shift_v) {
                                $weekArray = explode(',', $shift_v->weekday);
                                //foreach($weekArray as $weekDay){
                                $shift = array();
                                $shift['AddScheduleForm']['weekday'] = $weekArray;
                                $shift['AddScheduleForm']['address_id'] = $address_id;
                                $shift['AddScheduleForm']['user_id'] = $params['user_id'];
                                $shift['AddScheduleForm']['start_time'] = $shift_v->start_time;
                                $shift['AddScheduleForm']['end_time'] = $shift_v->end_time;
                                $shift['AddScheduleForm']['appointment_time_duration'] = $shift_v->appointment_time_duration;

                                $shift['AddScheduleForm']['patient_limit'] = $shift_v->patient_limit;

                                $shift['AddScheduleForm']['consultation_fees'] = (isset($shift_v->consultation_fees) && ($shift_v->consultation_fees > 0) ) ? $shift_v->consultation_fees : 0;
                                $shift['AddScheduleForm']['emergency_fees'] = (isset($shift_v->emergency_fees) && ($shift_v->emergency_fees > 0) ) ? $shift_v->emergency_fees : 0;
                                $shift['AddScheduleForm']['consultation_fees_discount'] = (isset($shift_v->consultation_fees_discount) && ($shift_v->consultation_fees_discount > 0) ) ? $shift_v->consultation_fees : 0;
                                $shift['AddScheduleForm']['emergency_fees_discount'] = (isset($shift_v->emergency_fees_discount) && ($shift_v->emergency_fees_discount > 0) ) ? $shift_v->emergency_fees_discount : 0;

                                $addUpdateShift = DrsPanel::upsertShift($shift, 0, $address_id);
                                // }
                            }
                        }
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response["data"] = $addUpdateShift;
                        $response['message'] = 'Shift added successfully';
                    } else {
                        $response = DrsPanel::validationErrorMessage($addAddress->getErrors());
                    }
                }
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }


        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionEditShiftWithCancelAppointment() {
        $response = $data = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                $shifts_for_cancel = json_decode($params['shifts_for_cancel']);
                foreach ($shifts_for_cancel as $key => $value) {

                    $userAppointment = UserSchedule::findOne($value->id);

                    if (!empty($userAppointment)) {
                        $appointment = UserAppointment::find()->where(['doctor_id' => $value->user_id, 'schedule_id' => $value->id])->all();

                        $query = UserAppointment::updateAll(['status' => UserAppointment::STATUS_CANCELLED], ['doctor_id' => $value->user_id, 'schedule_id' => $value->id]);

                        $userAppointment->load(['UserSchedule' => $value]);
                        $userAppointment->save();
                    }

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response["data"] = [];
                    $response['message'] = 'Shift cancelled Successfully';
                }
            }
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function saveShiftData($day_shift, $newshiftInsert, $params) {
        $shift = array();
        $shift['AddScheduleForm']['weekday'] = $day_shift->weekday;
        $shift['AddScheduleForm']['user_id'] = $params['user_id'];
        if (isset($newshiftInsert->id)) {
            $shift['AddScheduleForm']['id'] = $newshiftInsert->id;
        }
        $shift['AddScheduleForm']['start_time'] = $newshiftInsert->start_time;
        $shift['AddScheduleForm']['end_time'] = $newshiftInsert->end_time;
        $shift['AddScheduleForm']['appointment_time_duration'] = $newshiftInsert->appointment_time_duration;

        $shift['AddScheduleForm']['patient_limit'] = $newshiftInsert->patient_limit;

        $shift['AddScheduleForm']['consultation_fees'] = (isset($newshiftInsert->consultation_fees) && ($newshiftInsert->consultation_fees > 0) ) ? $newshiftInsert->consultation_fees : 0;
        $shift['AddScheduleForm']['emergency_fees'] = (isset($newshiftInsert->emergency_fees) && ($newshiftInsert->emergency_fees > 0) ) ? $newshiftInsert->emergency_fees : 0;
        $shift['AddScheduleForm']['consultation_fees_discount'] = (isset($newshiftInsert->consultation_fees_discount) && ($newshiftInsert->consultation_fees_discount > 0) ) ? $newshiftInsert->consultation_fees_discount : 0;
        $shift['AddScheduleForm']['emergency_fees_discount'] = (isset($newshiftInsert->emergency_fees_discount) && ($newshiftInsert->emergency_fees_discount > 0) ) ? $newshiftInsert->emergency_fees_discount : 0;
        return $shift;
    }

    public function actionEditShift() {
        $fields = ApiFields::addShiftFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();

        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }

        if (empty($required)) {
            if (isset($params['user_id']) && $params['user_id'] != '') {
                $user = User::findOne(['id' => $params['user_id']]);
                if (!empty($user)) {
                    $addAddress = UserAddress::findOne(['id' => $params['address_id']]);
                    $data['UserAddress']['user_id'] = $params['user_id'];
                    $data['UserAddress']['name'] = $params['name'];
                    $data['UserAddress']['city'] = $params['city'];
                    $data['UserAddress']['state'] = $params['state'];
                    $data['UserAddress']['address'] = $params['address'];
                    $data['UserAddress']['area'] = $params['area'];
                    $data['UserAddress']['phone'] = $params['mobile'];
                    $data['UserAddress']['landline'] = isset($params['landline']) ? $params['landline'] : '';
                    $data['UserAddress']['is_request'] = 0;
                    $addAddress->load($data);

                    if ($addAddress->save()) {

                        $imageUpload = '';
                        if (isset($_FILES['images'])) {
                            $imageUpload = DrsImageUpload::updateAddressImage($addAddress->id, $_FILES);
                        }
                        /* if (isset($_FILES['images'])){
                          $imageUpload=DrsImageUpload::updateAddressImageList($addAddress->id,$_FILES);
                          } */

                        $shift = array();
                        $address_id = $addAddress->id;

                        if ((isset($params['shiftList']) && !empty($params['shiftList']))) {
                            $shiftList = json_decode($params['shiftList']);
                            // echo '<pre>';
                            // print_r($shiftList);
                            // die;

                            foreach ($shiftList as $shift_v) {
                                $weekArray = explode(',', $shift_v->weekday);
                                //foreach($weekArray as $weekDay){
                                $shift = array();
                                $shift['AddScheduleForm']['weekday'] = $weekArray;
                                $shift['AddScheduleForm']['address_id'] = $address_id;
                                $shift['AddScheduleForm']['user_id'] = $params['user_id'];
                                $shift['AddScheduleForm']['start_time'] = $shift_v->start_time;
                                $shift['AddScheduleForm']['end_time'] = $shift_v->end_time;
                                $shift['AddScheduleForm']['appointment_time_duration'] = $shift_v->appointment_time_duration;

                                $shift['AddScheduleForm']['patient_limit'] = $shift_v->patient_limit;

                                $shift['AddScheduleForm']['consultation_fees'] = (isset($shift_v->consultation_fees) && ($shift_v->consultation_fees > 0) ) ? $shift_v->consultation_fees : 0;
                                $shift['AddScheduleForm']['emergency_fees'] = (isset($shift_v->emergency_fees) && ($shift_v->emergency_fees > 0) ) ? $shift_v->emergency_fees : 0;
                                $shift['AddScheduleForm']['consultation_fees_discount'] = (isset($shift_v->consultation_fees_discount) && ($shift_v->consultation_fees_discount > 0) ) ? $shift_v->consultation_fees : 0;
                                $shift['AddScheduleForm']['emergency_fees_discount'] = (isset($shift_v->emergency_fees_discount) && ($shift_v->emergency_fees_discount > 0) ) ? $shift_v->emergency_fees_discount : 0;
                                $addUpdateShift = DrsPanel::upsertShift($shift, 0, $address_id);
                                // }
                            }
                        }

                        $response["status"] = 1;
                        $response["error"] = false;
                        $response["data"] = $addUpdateShift;
                        $response['message'] = 'Shift Edit successfully';
                    } else {
                        $response = DrsPanel::validationErrorMessage($addAddress->getErrors());
                    }
                }
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }


        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for getting doctor my Shifts list
     */

    public function actionGetMyShifts() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'], 'myshifts')) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        if (isset($params['user_id']) && $params['user_id'] != '') {
            if (isset($params['current_login_id']) && $params['current_login_id'] != '') {
                $doctor = User::findOne(['id' => $params['user_id']]);
                $shift_array = array();
                $user = User::findOne(['id' => $params['current_login_id']]);


                if (!empty($doctor) && !empty($user)) {
                    if ($user->groupid == Groups::GROUP_DOCTOR) {
                        $parent_id = $params['user_id'];
                    } elseif ($user->groupid == Groups::GROUP_ATTENDER) {
                        $attender_id = $params['current_login_id'];
                        $parent_id = $params['user_id'];

                        $getParentDetails = DrsPanel::getParentDetails($attender_id);
                        $parentGroup = $getParentDetails['parentGroup'];
                        $attender_parent_id = $getParentDetails['parent_id'];
                        if ($parentGroup == Groups::GROUP_HOSPITAL) {
                            $hospital_address = UserAddress::findOne(['user_id' => $attender_parent_id]);
                            $addressObject = DrsPanel::addressShiftArray($hospital_address, $attender_parent_id);
                            $shift_array['address'][0] = $addressObject;
                            $shifts = DrsPanel::getShiftListByAddress($parent_id, $hospital_address->id);

                            $shift_array['address'][0]['shifts'] = $shifts;
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response['data'] = $shift_array;
                            $response['user_verified'] = DrsPanel::getProfileStatus($doctor->id);
                            $response['message'] = 'Address & Shift List';

                            Yii::info($response, __METHOD__);
                            \Yii::$app->response->format = Response::FORMAT_JSON;
                            return $response;
                        } else {
                            $selectedShifts = Drspanel::shiftList(['user_id' => $parent_id, 'attender_id' => $attender_id], 'list');
                        }
                    } elseif ($user->groupid == Groups::GROUP_HOSPITAL) {
                        $hospital_address = UserAddress::findOne(['user_id' => $params['current_login_id']]);
                        $addressObject = DrsPanel::addressShiftArray($hospital_address, $params['current_login_id']);
                        $shift_array['address'][0] = $addressObject;
                        $shifts = DrsPanel::getShiftListByAddress($params['user_id'], $hospital_address->id);
                        $shift_array['address'][0]['shifts'] = $shifts;
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response['data'] = $shift_array;
                        $response['user_verified'] = DrsPanel::getProfileStatus($doctor->id);
                        $response['message'] = 'Address & Shift List';

                        Yii::info($response, __METHOD__);
                        \Yii::$app->response->format = Response::FORMAT_JSON;
                        return $response;
                    } else {
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response['message'] = 'No Shifts';

                        Yii::info($response, __METHOD__);
                        \Yii::$app->response->format = Response::FORMAT_JSON;
                        return $response;
                    }

                    $address_list = DrsPanel::doctorHospitalList($parent_id);
                    $listadd = (!empty($address_list)) ? $address_list['apiList'] : [];
                    $shift_array = array();
                    $s = 0;

                    if ($user->groupid == Groups::GROUP_DOCTOR) {
                        foreach ($listadd as $key => $list) {
                            $shift_array['address'][$key] = $list;
                            $shifts = DrsPanel::getShiftListByAddress($parent_id, $list['id']);
                            $shift_array['address'][$key]['shifts'] = $shifts;
                        }
                    } else {

                        foreach ($listadd as $key => $list) {
                            $shift_array['address'][$key] = $list;
                            $shifts = DrsPanel::getShiftListByAddress($parent_id, $list['id']);
                            foreach ($shifts as $keyshift => $shift) {
                                $unselect = 0;
                                if (array_key_exists($shift['shift_id'], $selectedShifts)) {
                                    
                                } else {
                                    unset($shifts[$keyshift]);
                                }
                            }
                            if (!empty($shifts)) {
                                $shift_array['address'][$key]['shifts'] = $shifts;
                            } else {
                                unset($shift_array['address'][$key]);
                            }
                        }
                        $shift_array['address'] = array_values($shift_array['address']);
                    }
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = $shift_array;
                    $response['user_verified'] = DrsPanel::getProfileStatus($doctor->id);
                    $response['message'] = 'Address & Shift List';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['user_verified'] = DrsPanel::getProfileStatus($doctor->id);
                    $response['message'] = 'User not found';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'CurrentLoginId Required';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for delete shift
     */

    public function actionDeleteShift() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['address_id']) && !empty($params['address_id'])) {
            if (isset($params['shifts_id']) && !empty($params['shifts_id'])) {
                $deleteShift = DrsPanel::deleteShift($params['user_id'], $params['address_id'], $params['shifts_id']);
                if ($deleteShift['type'] == 'error') {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = $deleteShift['message'];
                } else {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Shift Deleted';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Missing Required Fields';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for get shift details week day wise
     */

    public function actionGetShiftsDetail() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                if (isset($params['weekday']) && $params['weekday'] != '') {
                    $weeks = DrsPanel::getWeekArray();
                    if (in_array($params['weekday'], $weeks)) {
                        $data_array = DrsPanel::getAllShiftDetail($params['user_id'], $params['weekday']);
                    } else {
                        $data_array = DrsPanel::getAllShiftDetail($params['user_id']);
                    }
                } else {
                    $data_array = DrsPanel::getAllShiftDetail($params['user_id']);
                }
                $hospital = DrsPanel::doctorHospitalList($params['user_id']);
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $data_array;
                $response['weekday'] = date('l', strtotime(date('Y-m-d')));
                $response['date'] = date('Y-m-d');
                $response['address_list'] = (count($hospital['apiList']) > 0) ? $hospital['apiList'] : [];
                $response['message'] = 'Shift Details';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for add or update shifts week wise
     */

    public static function actionUpsertShift() {
        $fields = ApiFields::doctorShiftUpsertFields();
        $response = $data = $schedule = $required = array();
        $id = NULL;
        $post['AddScheduleForm'] = $params = Yii::$app->request->post();
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }

        Yii::info($post, __METHOD__);
        $model = new AddScheduleForm();

        if (empty($required)) {
            if (isset($params['shift_id'])) {
                $id = $params['shift_id'];
                $schedule_id = $params['schedule_id'];
                $schedule = UserSchedule::findOne($schedule_id);
                if (empty($schedule)) {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'User schedule does not exits.';
                    Yii::info($response, __METHOD__);
                    \Yii::$app->response->format = Response::FORMAT_JSON;
                    return $response;
                } else {
                    $model->id = $params['shift_id'];
                    $model->start_time = $params['start_time'];
                    $model->end_time = $params['end_time'];
                }
                $post['AddScheduleForm']['weekday'] = array($schedule->weekday);
            } else {
                $post['AddScheduleForm']['weekday'] = explode(',', $params['weekday']);
            }
            $post['AddScheduleForm']['consultation_fees'] = (isset($params['consultation_fees'])) ? $params['consultation_fees'] : 0;
            $post['AddScheduleForm']['emergency_fees'] = (isset($params['emergency_fees'])) ? $params['emergency_fees'] : 0;

            $post['AddScheduleForm']['consultation_fees_discount'] = (isset($params['consultation_fees_discount'])) ? $params['consultation_fees_discount'] : 0;
            $post['AddScheduleForm']['emergency_fees_discount'] = (isset($params['emergency_fees_discount'])) ? $params['emergency_fees_discount'] : 0;

            $post['AddScheduleForm']['id'] = $id;

            $model->load($post);
            if ($model) {
                $addUpdateShift = DrsPanel::updateShiftTiming($id, $post, $schedule_id);
                if (isset($addUpdateShift['error']) && $addUpdateShift['error'] == true) {
                    $response["status"] = 0;
                    $response["error"] = $addUpdateShift['error'];
                    $response["data"] = [];
                    $response['message'] = $addUpdateShift['message'];
                    Yii::info($response, __METHOD__);
                    \Yii::$app->response->format = Response::FORMAT_JSON;
                    return $response;
                } else {
                    $response["status"] = 1;
                    $response["error"] = 0;
                    $response["data"] = [];
                    $response['message'] = 'Shift Updated Successfully';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response["data"] = [];
                $response['message'] = 'Something went wrong';
                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for getting all shifts for particular date
     */

    public function actionGetBookingShifts() {
        $response = $datameta = $required = $logindUser = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $userLogin = User::find()->where(['id' => $params['user_id']])->one();
            $doctor = User::find()->where(['id' => $params['doctor_id']])->one();
            if (!empty($userLogin) && !empty($doctor)) {
                if (isset($params['date']) && !empty($params['date'])) {
                    $date = $params['date'];
                } else {
                    $date = date('Y-m-d');
                }
                $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);

                $datameta['date'] = $date;
                $datameta['week'] = DrsPanel::getDateWeekDay($date);
                $datameta['shifts'] = $getSlots;
                $datameta['address_list'] = DrsPanel::getBookingAddressShifts($params['doctor_id'], $date, $params['user_id']);
                $response["status"] = 1;
                $response["error"] = false;
                $response["data"] = $datameta;
                if (isset($params['current_login_id'])) {
                    $response["user_verified"] = DrsPanel::currentUserVerified($params['current_login_id'], $params['doctor_id']);
                } else {
                    $response["user_verified"] = DrsPanel::currentUserVerified($params['user_id'], $params['doctor_id']);
                }
                $response['message'] = 'Success';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Something went wrong, Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id  and user id required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for getting all slots for particular shift
     */

    public function actionGetBookingShiftSlots() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        if (isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $doctor = User::find()->where(['id' => $params['doctor_id']])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one();
            if ($doctor) {
                if (isset($params['schedule_id']) && !empty($params['schedule_id']) && isset($params['date']) && !empty($params['date'])) {
                    $date = $params['date'];
                    $getSlots = DrsPanel::getBookingShiftSlots($params['doctor_id'], $date, $params['schedule_id'], 'available');
                    if (!empty($getSlots)) {
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response["data"] = $getSlots;
                        $response['message'] = 'Success';
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Shift completed or cancelled';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Required parameter missing';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Doctor id is not registered';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for current appoint affair shift update(start or complete) for today
     */
    public function actionUpdateShift() {
        $fields = ApiFields::updateshift();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
                $userLogin = User::find()->where(['id' => $params['user_id']])->one();
                $doctor = User::find()->where(['id' => $params['doctor_id']])->one();
                if (!empty($userLogin) && !empty($doctor)) {
                    $date = date('Y-m-d');
                    $shift = $params['schedule_id'];
                    $status = $params['status'];

                    if ($status == 'start') {
                        $schedule_check = UserScheduleGroup::find()->where(['user_id' => $doctor->id, 'date' => $date, 'status' => array('pending', 'current')])->orderBy('shift asc')->one();
                        if (!empty($schedule_check)) {
                            if ($schedule_check->schedule_id == $shift) {
                                $schedule_check->status = 'current';
                                if ($schedule_check->save()) {

                                    Notifications::shiftStartNotification($params['doctor_id'], $shift, $date);

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
                                        $response = $this->getCurrentAffair($checkForCurrentShift, $params['doctor_id'], $date, $shift, $getSlots);
                                    } else {
                                        $response["status"] = 0;
                                        $response["data"] = $checkForCurrentShift;
                                    }
                                } else {
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["data"] = $schedule_check->getErrors();
                                    $response['message'] = 'Shift error';
                                }
                            } else {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = ucfirst($schedule_check->shift_label) . ' is pending';
                            }
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Shift not found';
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
                                    } else {
                                        $response["status"] = 0;
                                        $response["error"] = true;
                                        $response['message'] = 'Please try again';
                                    }
                                } else {
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response['message'] = 'Please try again';
                                }
                            } else {
                                $html = ucfirst($schedule_check->shift_label) . ' is pending';
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = $html;
                            }
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Shift not found';
                        }
                    } elseif ($status == 'completed') {
                        $schedule_check = UserScheduleGroup::find()->where(['user_id' => $params['doctor_id'], 'date' => $date, 'status' => 'current'])->one();
                        if (!empty($schedule_check)) {
                            $schedule_check->status = 'completed';
                            if ($schedule_check->save()) {
                                $date = date('Y-m-d');
                                $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                                $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
                                if (!empty($checkForCurrentShift)) {
                                    $response = $this->getCurrentAffair($checkForCurrentShift, $params['doctor_id'], $date, $shift, $getSlots);
                                }
                            }
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Shift not found';
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Please try again.';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Something went wrong, Please try again.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Doctor id  and user id required';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for updating appointment by doctor
     */

    public function actionDoctorAppointmentUpdate() {
        $fields = ApiFields::doctorappointmentupdate();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $doctor_id = $params['doctor_id'];
            $user = User::findOne($doctor_id);
            if (!empty($user)) {
                $doctorProfile = UserProfile::findOne(['user_id' => $doctor_id]);
                $appointment = UserAppointment::findOne($params['appointment_id']);
                if (!empty($appointment)) {
                    if ($params['status'] == 'next') {
                        $res = DrsPanel::updateCurrentStatus($params['status'], $doctor_id, $appointment->date, $appointment->schedule_id);
                    } elseif ($params['status'] == 'skip') {
                        $res = DrsPanel::updateCurrentStatus($params['status'], $doctor_id, $appointment->date, $appointment->schedule_id);
                    } else {
                        $res = ['status' => false, 'message' => 'Please try again'];
                    }
                    if ($res['status'] == false) {
                        $html = $res['message'];
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = $html;
                    } else {
                        $date = date('Y-m-d');
                        $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                        $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
                        if (!empty($checkForCurrentShift)) {
                            $response = $this->getCurrentAffair($checkForCurrentShift, $params['doctor_id'], $date, $params['schedule_id'], $getSlots);
                        }
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Appointment not found';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Doctor Details not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDoctorUpdateStatus() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        $appointment_id = $params['appointment_id'];
        $status = $params['status'];
        $appointment = UserAppointment::findOne($appointment_id);
        if (!empty($appointment)) {

            if ($params['status'] == 'paid') {
                $appointment->status = UserAppointment::STATUS_AVAILABLE;
            } elseif ($params['status'] == 'consulting') {
                $appointment->status = UserAppointment::STATUS_ACTIVE;
                $appointment->actual_time = time();
            } else {
                
            }
            if ($appointment->save()) {
                if ($params['status'] == 'consulting') {
                    $checkappointments = UserAppointment::find()->where(['doctor_id' => $appointment->doctor_id, 'date' => $appointment->date, 'schedule_id' => $appointment->schedule_id, 'status' => UserAppointment::STATUS_ACTIVE])->andWhere(['!=', 'id', $appointment_id])->orderBy('token asc')->one();
                    if (!empty($checkappointments)) {
                        $checkappointments->status = UserAppointment::STATUS_AVAILABLE;
                        $checkappointments->save();
                    }
                } else {
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
                }
                $date = date('Y-m-d');
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = DrsPanel::getappointmentarray($appointment);
                $response['message'] = 'Appointment updated';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['data'] = $appointment->getErrors();
                $response['message'] = 'Appointment not updated';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Appointment not found';
        }


        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for adding appointment from doctor panel
     */

    public function actionDoctorAddAppointment() {
        $fields = ApiFields::doctorAppointmentFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {

            $check = DrsPanel::blockSlot($params['slot_id'], $params['user_id']);
            if ($check == 'success') {
                $response = DrsPanel::addTemporaryAppointment($params, 'doctor', 'app');
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Slot not available for booking';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for updating appointment by patient
     */

    public function actionPatientAddAppointment() {
        $fields = ApiFields::patientAppointmentFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $check = DrsPanel::blockSlot($params['slot_id'], $params['user_id']);
            if ($check == 'success') {
                $response = DrsPanel::addTemporaryAppointment($params, 'patient', 'app');
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Slot not available for booking';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for listing doctor current appointment affair
     */

    public function actionCurrentAppointmentAffair() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $userLogin = User::find()->where(['id' => $params['user_id']])->one();
            $doctor = User::find()->where(['id' => $params['doctor_id']])->one();
            if (!empty($userLogin) && !empty($doctor)) {
                $date = date('Y-m-d');

                if (isset($params['schedule_id']) && !empty($params['schedule_id'])) {
                    $shift = $params['schedule_id'];
                } else {
                    $shift = '';
                }

                $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                $checkForCurrentShift = DrsPanel::getDoctorCurrentShift($getSlots);
                if (!empty($checkForCurrentShift)) {
                    $response = $this->getCurrentAffair($checkForCurrentShift, $params['doctor_id'], $date, $shift, $getSlots);
                    $response['user_verified'] = DrsPanel::getProfileStatus($doctor->id);
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['user_verified'] = DrsPanel::getProfileStatus($doctor->id);
                    $response['message'] = 'Please try again';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Something went wrong, Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id  and user id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /*
     * @Param Null
     * @Function is used for listing doctor current appointment list
     */

    public function actionCurrentAppointments() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $userLogin = User::find()->where(['id' => $params['user_id']])->one();
            $doctor = User::find()->where(['id' => $params['doctor_id']])->one();
            if (!empty($userLogin) && !empty($doctor)) {
                if (isset($params['date'])) {
                    $date = $params['date'];
                } else {
                    $date = date('Y-m-d');
                }
                if (isset($params['schedule_id']) && !empty($params['schedule_id'])) {
                    $current_selected = $params['schedule_id'];
                } else {
                    $current_selected = 0;
                }

                $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);

                $getAppointments = DrsPanel::getCurrentAppointments($params['doctor_id'], $date, $current_selected, $getSlots);
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $getAppointments;
                $response['data']['date'] = $date;
                $response['user_verified'] = DrsPanel::getProfileStatus($doctor->id);
                $response['message'] = 'Today Appointments List';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Something went wrong, Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id  and user id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionAddUpdateReminder() {
        $fields = ApiFields::patientaddreminder();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $user_id = $params['user_id'];
            $appointment_id = $params['appointment_id'];
            $reminder = UserReminder::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id])->one();
            if (empty($reminder)) {
                $reminder = new UserReminder();
            }

            $reminder->user_id = $user_id;
            $reminder->appointment_id = $params['appointment_id'];
            $reminder->reminder_date = $params['date'];
            $reminder->reminder_time = $params['time'];
            $reminder->reminder_datetime = (int) strtotime($params['date'] . ' ' . $params['time']);
            ;
            $reminder->status = 'pending';
            if ($reminder->save()) {
                $response["status"] = 1;
                $response["error"] = false;
                $response["message"] = "Success";
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response["data"] = $reminder->getErrors();
                $response["message"] = "Error";
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetReminders() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                $getReminders = DrsPanel::getPatientReminders($params['user_id']);
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $getReminders;
                $response['message'] = 'Reminder List';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDeleteReminder() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['appointment_id'])) {
            $appointment_id = $params['appointment_id'];
            $reminder = UserReminder::find()->where(['appointment_id' => $appointment_id])->one();
            if ($reminder->delete()) {
                $response["status"] = 1;
                $response["error"] = false;
                $response["message"] = "Success";
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response["data"] = $reminder->getErrors();
                $response["message"] = "Error";
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response["message"] = "Appointment id required";
        }


        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDoctorDetail() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['doctor_id']) && $params['doctor_id'] != '' && isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['doctor_id'], 'groupid' => Groups::GROUP_DOCTOR]);
            if (!empty($user)) {
                $profile = UserProfile::findOne(['user_id' => $user->id]);
                $data_array = DrsPanel::profiledetails($user, $profile, $user->groupid, $params['user_id']);
                $data_array['is_favorite'] = DrsPanel::checkProfileFavorite($params['user_id'], $params['doctor_id']);
                unset($data_array['shift_time']);
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $data_array;
                $response['date'] = date('Y-m-d');
                $response['message'] = 'Data';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Doctor not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionFindDoctors() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        $offset = 0;
        $recordlimit = 10;
        $totalpages = 0;
        $count_result = 0;
        $groupid = Groups::GROUP_DOCTOR;

        $userLocation = $this->getLocationUsersArray($params);
        if (isset($params['type']) && $params['type'] != '') {
            $type = $params['type'];
        } else {
            $type = 'list';
        }

        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => $groupid]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
        $lists->andWhere(['user_profile.shifts' => 1]);

        $term = '';
        $v1 = '';
        if (isset($params['search']) && !empty($params['search'])) {
            $term = $params['search'];
        }

        if ($term != '') {
            $q_explode = explode(' ', $term);
            $usersearch = array();
            foreach ($q_explode as $word) {
                if (!preg_match('/[^A-Za-z0-9]+/', $word)) {
                    $usersearch[] = "user_profile.name LIKE '%" . $word . "%'";
                }
            }
            $v1 = implode(' or ', $usersearch);
        }
        if ($v1 != '') {
            $lists->andFilterWhere(['or', $v1]);
        }

        $command = $lists->createCommand();

        $listcat = $valuecat = $listareas = $listtreatment = [];
        $gender = '';
        $availability = '';
        $sort = '';

        if (isset($params['filter'])) {
            $filters = json_decode($params['filter']);

            foreach ($filters as $filter) {
                if ($filter->type == 'speciality') {
                    $listcat = $filter->list;
                }

                if ($filter->type == 'treatment') {
                    $listtreatment = $filter->list;
                }

                if ($filter->type == 'areas') {
                    $listareas = $filter->list;
                }

                if ($filter->type == 'gender') {
                    $gender = $filter->list;
                    $gender = $gender[0];
                }

                if ($filter->type == 'availability') {
                    $availability = $filter->list;
                    $availability = $availability[0];
                }

                if ($filter->type == 'sort') {
                    $sort = $filter->list;
                    $sort = $sort[0];
                }
            }

            if (!empty($listcat)) {
                $valuecat = [];
                foreach ($listcat as $cateval) {
                    $metavalues = MetaValues::find()->where(['slug' => $cateval])->one();
                    if ($metavalues)
                        $valuecat[] = $metavalues->value;
                }

                $query_array = array();
                foreach ($valuecat as $needle) {
                    $query_array[] = sprintf('FIND_IN_SET("%s",`user_profile`.`speciality`)', $needle);
                }
                $query_str = implode(' OR ', $query_array);
                $lists->andWhere(new \yii\db\Expression($query_str));
            }

            if (!empty($listtreatment)) {
                $valuetreat = [];
                foreach ($listtreatment as $treatval) {
                    $metavalues = MetaValues::find()->where(['slug' => $treatval])->one();
                    if ($metavalues)
                        $valuetreat[] = $metavalues->value;
                }

                $query_array = array();
                foreach ($valuetreat as $needle) {
                    $query_array[] = sprintf('FIND_IN_SET("%s",`user_profile`.`treatment`)', $needle);
                }
                $query_str = implode(' OR ', $query_array);
                $lists->andWhere(new \yii\db\Expression($query_str));
            }

            if (!empty($listareas)) {
                $getAreaUser = DrsPanel::getAreaUser($listareas);
                $lists->andWhere(['user.id' => $getAreaUser]);
            }

            if ($gender != '') {
                $lists->andWhere(['user_profile.gender' => $gender]);
            }

            if ($availability != '') {
                $getAvailabilityUser = DrsPanel::getAvailibilityUser($availability);
                $userLocation = array_intersect($userLocation, $getAvailabilityUser);
                $lists->andWhere(['user.id' => $getAvailabilityUser]);
            } else {
                $lists->andWhere((['user.id' => $userLocation]));
            }
        } else {
            $lists->andWhere(['user.id' => $userLocation]);
        }

        if ($sort != '') {
            if ($sort == 'price_highttolow') {
                $lists->orderBy('user_profile.consultation_fees desc');
            } elseif ($sort == 'price_lowtohigh') {
                $lists->orderBy('user_profile.consultation_fees asc');
            } elseif ($sort == 'rating_lowtohigh') {
                $lists->orderBy('user_profile.rating asc');
            } elseif ($sort == 'rating_highttolow') {
                $lists->orderBy('user_profile.rating desc');
            } else {
                
            }
        } else {
            if (!empty($userLocation)) {
                $lists->orderBy([new \yii\db\Expression('FIELD (user.user_plan, "sponsered","paid","other")')], [new \yii\db\Expression('FIELD (user.id, ' . implode(',', (array_values($userLocation))) . ')')]);
            } else {
                $lists->orderBy([new \yii\db\Expression('FIELD (user.user_plan, "sponsered","paid","other")')]);
            }
        }


        if ($type == 'list') {
            if (isset($params['offset']) && $params['offset'] != '') {
                $offset = $params['offset'];
            }
            $countQuery = clone $lists;
            $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
            $lists->limit($recordlimit);
            $lists->offset($offset);
            $lists->all();
            $command = $lists->createCommand();
            $lists = $command->queryAll();

            if (isset($totalpages)) {
                $count_result = $totalpages->totalCount;
            }
            if ($count_result == null) {
                $count_result = count($lists);
                $offset = count($lists);
            } else {
                $oldoffset = $offset;
                $offset = $offset + $recordlimit;
                if ($offset > $count_result) {
                    $offset = $oldoffset + count($lists);
                }
            }

            $totallist['totalcount'] = $count_result;
            $totallist['offset'] = $offset;

            $list_a = $this->getList($lists, 'list', 49);
            $data_array = array_values($list_a);
            $response["status"] = 1;
            $response["error"] = false;
            $response['pagination'] = $totallist;
            $response['data'] = $data_array;

            if (isset($params['city_id']) && $params['city_id'] != '' && isset($params['city_name']) && $params['city_name'] != '') {
                $city_id = $params['city_id'];
                $city_name = $params['city_name'];
            } else {
                $city_name = 'Jaipur';
            }
            $response['filters'] = DrsPanel::getFilterArray('doctor', $city_name, 1);
            $response['sort'] = DrsPanel::getSortArray();
            $response['message'] = 'Doctors List';
        } else {
            $lists = $command->queryAll();
            $list_a = $this->getList($lists, 'list', $params['user_id']);
            $data_array = array_values($list_a);
            $response["status"] = 1;
            $response["error"] = false;
            $response['mapdata'] = $data_array;
            $response['message'] = 'Doctors List';
        }


        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionFindHospitals() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        $offset = 0;
        $recordlimit = 20;
        $totalpages = 0;
        $count_result = 0;
        $groupid = Groups::GROUP_HOSPITAL;

        $userLocation = $this->getLocationUsersArray($params);

        if (isset($params['type']) && $params['type'] != '') {
            $type = $params['type'];
        } else {
            $type = 'list';
        }

        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => $groupid]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
        $lists->andWhere(['user_profile.shifts' => 1]);
        $addSpeciality = Drspanel::addHospitalSpecialityCount($lists->createCommand()->queryAll());

        $lists->joinWith('hospitalSpecialityTreatment');

        $term = '';
        $v1 = '';
        if (isset($params['search']) && !empty($params['search'])) {
            $term = $params['search'];
        }

        if ($term != '') {
            $q_explode = explode(' ', $term);
            $usersearch = array();
            foreach ($q_explode as $word) {
                $usersearch[] = "user_profile.name LIKE '%" . $word . "%'";
            }
            $v1 = implode(' or ', $usersearch);
        }
        if ($v1 != '') {
            $lists->andFilterWhere(['or', $v1]);
        }

        $command = $lists->createCommand();

        $listcat = $valuecat = $listareas = [];
        $gender = '';
        $sort = '';

        if (isset($params['filter'])) {
            $filters = json_decode($params['filter']);

            foreach ($filters as $filter) {
                if ($filter->type == 'speciality') {
                    $listcat = $filter->list;
                }
                if ($filter->type == 'areas') {
                    $listareas = $filter->list;
                }

                if ($filter->type == 'sort') {
                    $sort = $filter->list;
                    $sort = $sort[0];
                }
            }

            if (!empty($listcat)) {
                foreach ($listcat as $cateval) {
                    $metavalues = MetaValues::find()->where(['slug' => $cateval])->one();
                    if ($metavalues)
                        $valuecat[] = $metavalues->value;
                }

                $query_array = array();
                foreach ($valuecat as $needle) {
                    $query_array[] = sprintf('FIND_IN_SET("%s",`hospital_speciality_treatment`.`speciality`)', $needle);
                }
                $query_str = implode(' OR ', $query_array);
                $lists->andWhere(new \yii\db\Expression($query_str));
            }

            if (!empty($listareas)) {
                $getAreaUser = DrsPanel::getAreaUser($listareas);
                $lists->andWhere(['user.id' => $getAreaUser]);
            }
        }

        $lists->andWhere(['user.id' => $userLocation]);

        if ($sort != '') {
            if ($sort == 'rating_lowtohigh') {
                $lists->orderBy('user_profile.rating asc');
            } elseif ($sort == 'rating_highttolow') {
                $lists->orderBy('user_profile.rating desc');
            } else {
                
            }
        } else {
            if (!empty($userLocation)) {
                $lists->orderBy([new \yii\db\Expression('FIELD (user.user_plan, "sponsered","paid","other")')], [new \yii\db\Expression('FIELD (user.id, ' . implode(',', (array_values($userLocation))) . ')')]);
            } else {
                $lists->orderBy([new \yii\db\Expression('FIELD (user.user_plan, "sponsered","paid","other")')]);
            }
        }

        if ($type == 'list') {
            if (isset($params['offset']) && $params['offset'] != '') {
                $offset = $params['offset'];
            }
            $countQuery = clone $lists;
            $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
            $lists->limit($recordlimit);
            $lists->offset($offset);
            $lists->all();
            $command = $lists->createCommand();
            $lists = $command->queryAll();

            if (isset($totalpages)) {
                $count_result = $totalpages->totalCount;
            }
            if ($count_result == null) {
                $count_result = count($lists);
                $offset = count($lists);
            } else {
                $oldoffset = $offset;
                $offset = $offset + $recordlimit;
                if ($offset > $count_result) {
                    $offset = $oldoffset + count($lists);
                }
            }

            $totallist['totalcount'] = $count_result;
            $totallist['offset'] = $offset;

            $list_a = $this->getList($lists, 'list');
            $data_array = array_values($list_a);
            $response["status"] = 1;
            $response["error"] = false;
            $response['pagination'] = $totallist;
            $response['data'] = $data_array;
            $response['filters'] = DrsPanel::getFilterArray('hospital', '', 1);
            $response['sort'] = DrsPanel::getSortArray();
            $response['message'] = 'Hospitals List';
        } else {
            $lists = $command->queryAll();
            $list_a = $this->getList($lists, 'list');
            $data_array = array_values($list_a);
            $response["status"] = 1;
            $response["error"] = false;
            $response['mapdata'] = $data_array;
            $response['message'] = 'Hospitals List';
        }


        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetMyDoctors() {
        $response = $data = $dataarray = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && $params['user_id'] != '') {

            $userList = DrsPanel::patientMyDoctorsList($params['user_id']);


            $offset = 0;
            $recordlimit = 20;
            $totalpages = 0;
            $count_result = 0;

            if (isset($params['offset']) && $params['offset'] != '') {
                $offset = $params['offset'];
            }
            $groupid = Groups::GROUP_DOCTOR;
            $lists = new Query();
            $lists = UserProfile::find();
            $lists->joinWith('user');
            $lists->where(['user_profile.groupid' => $groupid]);
            $lists->andWhere(['user.id' => $userList]);
            $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);

            $countQuery = clone $lists;
            $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
            $lists->limit($recordlimit);
            $lists->offset($offset);
            $lists->all();
            $command = $lists->createCommand();
            $lists = $command->queryAll();

            if (isset($totalpages)) {
                $count_result = $totalpages->totalCount;
            }
            if ($count_result == null) {
                $count_result = count($lists);
                $offset = count($lists);
            } else {
                $oldoffset = $offset;
                $offset = $offset + $recordlimit;
                if ($offset > $count_result) {
                    $offset = $oldoffset + count($lists);
                }
            }

            $totallist['totalcount'] = $count_result;
            $totallist['offset'] = $offset;

            $list_a = $this->getList($lists, 'list');
            $data_array = array_values($list_a);
            $response["status"] = 1;
            $response["error"] = false;
            $response['pagination'] = $totallist;
            $response['data'] = $data_array;
            $response['filters'] = DrsPanel::getFilterArray('doctor', '', 1);
            $response['sort'] = DrsPanel::getSortArray();
            $response['message'] = 'Doctors List';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetMyPatients() {
        $response = $data = $data_array = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['doctor_id']) && $params['doctor_id'] != '') {
            $offset = 0;
            $recordlimit = 20;
            $totalpages = 0;
            $count_result = 0;
            if (isset($params['offset']) && $params['offset'] != '') {
                $offset = $params['offset'];
            }
            $result = DrsPanel::myPatients($params);
            $response["status"] = 1;
            $response["error"] = false;
            $response['pagination'] = $result['pagination'];
            $response['data'] = $result['data'];
            $response['message'] = 'Patient List';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'DoctorId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetMyAppointments() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                if (isset($params['type']) && $params['type'] != '') {
                    $type = $params['type'];
                    if ($type == 'upcoming') {
                        $allckecked = false;
                        $upcomingchecked = true;
                        $pastchecked = false;
                    } elseif ($type == 'past') {
                        $allckecked = false;
                        $upcomingchecked = false;
                        $pastchecked = true;
                    } else {
                        $allckecked = true;
                        $upcomingchecked = false;
                        $pastchecked = false;
                    }
                } else {
                    $type = 'all';
                    $allckecked = true;
                    $upcomingchecked = false;
                    $pastchecked = false;
                }

                $offset = 0;
                $recordlimit = 20;
                $totalpages = 0;
                $count_result = 0;

                if (isset($params['offset']) && $params['offset'] != '') {
                    $offset = $params['offset'];
                }

                $datecheck = strtotime(date('Y-m-d 00:00:00'));
                $pastcheck = strtotime(date('Y-m-d 23:59:59'));

                $lists = DrsPanel::getPatientAppointments($params['user_id'], $type);

                if ($type == 'upcoming') {
                    $lists->andWhere(['>=', 'appointment_time', $datecheck]);
                } elseif ($type == 'past') {
                    $lists->andWhere(['<=', 'appointment_time', $pastcheck]);
                } else {
                    
                }
                $countQuery = clone $lists;
                $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
                $lists->limit($recordlimit);
                $lists->offset($offset);
                $lists->all();
                $command = $lists->createCommand();
                $lists = $command->queryAll();

                if (isset($totalpages)) {
                    $count_result = $totalpages->totalCount;
                }
                if ($count_result == null) {
                    $count_result = count($lists);
                    $offset = count($lists);
                } else {
                    $oldoffset = $offset;
                    $offset = $offset + $recordlimit;
                    if ($offset > $count_result) {
                        $offset = $oldoffset + count($lists);
                    }
                }

                $totallist['totalcount'] = $count_result;
                $totallist['offset'] = $offset;

                $list_a = DrsPanel::getPatientAppointmentsList($lists);
                $getAppointments = array_values($list_a);

                $appointmentList[0] = array('key' => 'all', 'label' => 'All', 'isChecked' => $allckecked);
                $appointmentList[1] = array('key' => 'upcoming', 'label' => 'Upcoming', 'isChecked' => $upcomingchecked);
                $appointmentList[2] = array('key' => 'past', 'label' => 'Past', 'isChecked' => $pastchecked);

                $data['type'] = $appointmentList;
                $data['selected'] = $type;
                $data['appointments'] = $getAppointments;

                $response["status"] = 1;
                $response["error"] = false;
                $response["pagination"] = $totallist;
                $response['data'] = $data;
                $response['message'] = 'Appointments List';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for getting appointment details
     */
    public function actionGetAppointmentDetails() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['appointment_id']) && $params['appointment_id'] != '') {
            $appointment_id = $params['appointment_id'];
            $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
            if (!empty($appointment)) {
                $getAppointments = DrsPanel::patientgetappointmentarray($appointment);
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $getAppointments;
                $response['message'] = 'Appointments Detail';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Appointment not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'AppointmentId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionAddReviewRating() {
        $fields = ApiFields::addreviewrating();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $user_id = $params['user_id'];
            $appointment_id = $params['appointment_id'];
            $appointment = UserAppointment::findOne($appointment_id);
            $address = UserAddress::findOne($appointment->doctor_address_id);
            $address_user = User::findOne($address->user_id);

            if ($address_user->groupid == Groups::GROUP_HOSPITAL) {
                if (isset($params['hospital_rating']) && $params['hospital_rating'] > 0) {
                    $rating_logs_hospital = UserRatingLogs::find()->where(['user_id' => $user_id, 'appointment_id' => $appointment_id, 'user_type' => 'hospital'])->one();
                    if (empty($rating_logs_hospital)) {
                        $rating_logs_hospital = new UserRatingLogs();
                    }
                    $rating_logs_hospital->user_id = $user_id;
                    $rating_logs_hospital->user_type = 'hospital';
                    $rating_logs_hospital->doctor_id = $address->user_id;
                    $rating_logs_hospital->appointment_id = $appointment_id;
                    $rating_logs_hospital->rating = $params['hospital_rating'];
                    $rating_logs_hospital->review = $params['hospital_review'];
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
            }
            $ratingreview = UserRatingLogs::find()->where(['doctor_id' => $params['doctor_id'],
                        'user_id' => $user_id, 'appointment_id' => $params['appointment_id'], 'user_type' => 'doctor'])->one();
            if (empty($ratingreview)) {
                $review = new UserRatingLogs();
                $review->user_id = $user_id;
                $review->doctor_id = $params['doctor_id'];
                $review->appointment_id = $params['appointment_id'];
                $review->rating = $params['rating'];
                $review->review = $params['review'];
                $review->user_type = 'doctor';
                if ($review->save()) {
                    $rating = UserRating::find()->where(['user_id' => $params['doctor_id']])->one();
                    if (empty($rating)) {
                        $rating = new UserRating();
                    }
                    $rating->user_id = $params['doctor_id'];
                    $rating->show_rating = 'User';
                    $rating->admin_rating = 0;
                    $rating->users_rating = DrsPanel::calculateRatingAverage($params['doctor_id']);
                    if ($rating->save()) {
                        $rating->users_rating = DrsPanel::calculateRatingAverage($params['doctor_id']);
                        if ($rating->save()) {
                            $user = UserProfile::findOne(['user_id' => $params['doctor_id']]);
                            $user->rating = $rating->users_rating;
                            $user->save();
                        }
                    }
                    $updateRatingToProfile = DrsPanel::ratingUpdateToProfile($params['doctor_id']);
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response["message"] = "Success";
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response["data"] = $review->getErrors();
                    $response["message"] = "Error";
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response["message"] = "You have already given rating";
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetReviewRating() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && $params['user_id'] != '') {
            $user = User::findOne(['id' => $params['user_id'], 'groupid' => Groups::GROUP_DOCTOR]);
            if (!empty($user)) {

                $offset = 0;
                $recordlimit = 20;

                if (isset($params['offset']) && $params['offset'] != '') {
                    $offset = $params['offset'];
                }

                $listarray = DrsPanel::getRatingList($params['user_id'], $offset, $recordlimit);
                $response["status"] = 1;
                $response["error"] = false;
                $response["pagination"] = $listarray['totallist'];
                $response['data'] = $listarray['list'];
                $response['message'] = 'Data';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Doctor not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetPagesList() {
        $pages = Page::find()->where(['status' => 1])->all();
        $response = $static = array();
        $l = 0;
        foreach ($pages as $page) {
            $static[$l]['id'] = $page->id;
            $static[$l]['title'] = $page->title;
            $static[$l]['slug'] = $page->slug;
            $l++;
        }
        $response["status"] = 1;
        $response["error"] = false;
        $response['data'] = $static;
        $response['message'] = 'Success';
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionPage() {
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        $rows = array();

        if (isset($params['slug']) && !empty($params['slug'])) {
            $model = Page::find()->where(['slug' => '' . $params['slug'] . ''])->one();
            if ($model) {
                $rows['title'] = $model['title'];
                $rows['body'] = $model['body'];

                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $rows;
                $response['message'] = 'Success';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Page not found.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Required parameter missing.';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetFilterArray() {
        $response = $data = $data_array = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        $filter = DrsPanel::getFilterArray();
        $response["status"] = 1;
        $response["error"] = false;
        $response['data'] = $filter;
        $response['message'] = 'Success';
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionHomeScreenData() {
        $response = $data = $data_array = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['city_id']) && $params['city_id'] != '' && isset($params['city_name']) && $params['city_name'] != '') {
            $city_id = $params['city_id'];
            $city_name = $params['city_name'];
        } else {
            $city_id = 15098;
            $city_name = 'Jaipur';
        }

        $response["status"] = 1;
        $response["error"] = false;
        $homeData = DrsPanel::homeScreenData($city_name, $city_id);
        $data['list'][0]['type'] = 'speciality';
        $data['list'][0]['label'] = 'Popular Specialization';
        $data['list'][0]['sub_categories'] = $homeData['speciality'];
        $data['list'][1]['type'] = 'treatment';
        $data['list'][1]['label'] = 'Popular Treatments';
        $data['list'][1]['sub_categories'] = $homeData['treatment'];
        $data['list'][2]['type'] = 'hospitals';
        $data['list'][2]['label'] = 'Popular Hospitals';
        $data['list'][2]['sub_categories'] = $homeData['hospitals'];
        $response['sliders'] = $homeData['slider_images'];
        $response['cities'] = DrsPanel::getCitiesList();
        $selected['id'] = $city_id;
        $selected['name'] = $city_name;
        $response['selected_city'] = $selected;
        $response['data'] = $data['list'];
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDoctorHospitals() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        if (isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $doctor = User::find()->where(['id' => $params['doctor_id']])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one();
            if ($doctor) {
                $data = DrsPanel::doctorHospitalList($params['doctor_id']);
                if (count($data['apiList'])) {

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Success';
                    $response['data'] = $data['apiList'];
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Please add address.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Are You sour doctor.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionAppointmentShedules() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        if (isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $andwhere['user_id'] = $params['doctor_id'];
            if (isset($params['date']) && !empty($params['date'])) {
                $andwhere['weekday'] = date('l', strtotime($params['date']));
            } else {
                $andwhere['weekday'] = date('l', strtotime(date('Y-m-d')));
            }

            if (isset($params['shift_id']) && !empty($params['shift_id'])) {
                $andwhere['id'] = $params['shift_id'];
            }
            $doctor = User::find()->where(['id' => $params['doctor_id']])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one();
            if ($doctor) {
                $data = DrsPanel::appointmentShedules($andwhere);
                if (count($data)) {

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Success';
                    $response['data'] = $data;
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'You have no any appointment.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Are You sour doctor.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetSettingDetail() {
        $response = $data = $dataarray = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && $params['user_id'] != '') {
            $settings = UserSettings::find()->where(['user_id' => $params['user_id']])->all();
            if (empty($settings)) {
                $setting = new UserSettings();
                $setting->user_id = $params['user_id'];
                $setting->key_name = 'show_countdown';
                $setting->key_value = 1;
                $setting->save();

                $setting1 = new UserSettings();
                $setting1->user_id = $params['user_id'];
                $setting1->key_name = 'popup_notify';
                $setting1->key_value = 1;
                $setting1->save();

                $setting1 = new UserSettings();
                $setting1->user_id = $params['user_id'];
                $setting1->key_name = 'show_fees';
                $setting1->key_value = 1;
                $setting1->save();

                $settings = UserSettings::find()->where(['user_id' => $params['user_id']])->all();
            }

            $s = 0;
            foreach ($settings as $setting) {
                $dataarray[$s]['setting_id'] = $setting->id;
                $dataarray[$s]['key'] = $setting->key_name;
                $dataarray[$s]['value'] = $setting->key_value;
                $s++;
            }
            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $dataarray;
            $response['message'] = 'Setting Data';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionAddSettingDetail() {
        $response = $data = $dataarray = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && $params['user_id'] != '') {
            if (isset($params['setting_id']) && $params['setting_id'] != '' && isset($params['value']) && $params['value'] != '') {
                $setting = UserSettings::find()->where(['user_id' => $params['user_id'], 'id' => $params['setting_id']])->one();
                $setting->key_value = $params['value'];
                if ($setting->save()) {
                    $settings = UserSettings::find()->where(['user_id' => $params['user_id']])->all();
                    $s = 0;
                    foreach ($settings as $setting) {
                        $dataarray[$s]['setting_id'] = $setting->id;
                        $dataarray[$s]['key'] = $setting->key_name;
                        $dataarray[$s]['value'] = $setting->key_value;
                        $s++;
                    }
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = $dataarray;
                    $response['message'] = 'Setting Updated';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['data'] = $setting->getErrors();
                    $response['message'] = 'Please try again';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Setting key id/value Required';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionAddAttender() {
        $fields = ApiFields::attenderUpsert();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($post['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $attender = new AttenderForm();
            $post['groupid'] = Groups::GROUP_ATTENDER;
            $attender->load(['AttenderForm' => $post]);
            if ($attender->signup()) {
                $user = User::find()->where(['email' => $attender->email])->one();
                if (!empty($user)) {
                    if (isset($_FILES['image'])) {
                        $imageUpload = DrsImageUpload::updateProfileImageApp($user->id, $_FILES);
                    }
                    if (isset($post['shift_id']) && ($post['shift_id'] != '')) {

                        $addressList = DrsPanel::doctorHospitalList($post['parent_id']);
                        $listadd = $addressList['apiList'];
                        $shift_array = array();
                        $s = 0;
                        $shift_value = array();
                        $sv = 0;
                        foreach ($listadd as $address) {
                            $shifts = DrsPanel::getShiftListByAddress($post['parent_id'], $address['id']);
                            foreach ($shifts as $key => $shift) {
                                if ($shift['hospital_id'] == 0) {
                                    $shift_array[$s]['value'] = $shift['shifts_ids'];
                                    $shift_array[$s]['label'] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                                    $shift_value[$sv] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                                    $s++;
                                    $sv++;
                                }
                            }
                        }

                        $sel_shift = explode(',', $post['shift_id']);
                        $shift_val = array();
                        foreach ($sel_shift as $s) {
                            $shift_selected_ids = $shift_array[$s];
                            $list = $shift_selected_ids['value'];
                            foreach ($list as $list) {
                                $shift_val[] = $list;
                            }
                        }
                        $addupdateAttender = DrsPanel::addUpdateAttenderToShifts($shift_val, $user->id);
                    }

                    if (isset($post['doctor_id']) && !empty($post['doctor_id'])) {
                        $doctors = explode(',', $post['doctor_id']);
                        $addupdateHospitalDoctors = DrsPanel::addUpdateDoctorsToHospitalAttender($doctors, $user->id, $post['parent_id']);
                    }

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = $user;
                    $response['message'] = 'Attender successfully added.';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Please try again';
                }
            } else {
                $validation_error = Drspanel::validationErrorMessage($attender->getErrors());
                $response["status"] = 0;
                $response["error"] = true;
                if (!empty($validation_error)) {
                    $response['errordata'] = $validation_error;
                    $response['message'] = $validation_error['message'];
                } else {
                    $response['message'] = 'Require fields does not match.';
                }
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionUpdateAttender() {
        $fields = ApiFields::attenderUpdate();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($post['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }

        if (empty($required)) {
            if (isset($post['id']) && !empty($post['id'])) {
                $attender = User::findOne($post['id']);
                if (empty($attender)) {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Attender id does not match.';
                    Yii::info($response, __METHOD__);
                    \Yii::$app->response->format = Response::FORMAT_JSON;
                    return $response;
                }
                $userProfile = UserProfile::findOne($post['id']);
                $userData['email'] = $post['email'];
                $userData['phone'] = $post['phone'];
                $profileData['name'] = $post['name'];
                if (isset($post['dob']) && !empty($post['dob'])) {
                    $profileData['dob'] = $post['dob'];
                }
                if (isset($post['gender']) && !empty($post['gender'])) {
                    $profileData['gender'] = $post['gender'];
                }
                $userData['countrycode'] = isset($post['countrycode']) ? $post['countrycode'] : 91;
                $attender->load(['User' => $userData]);
                $userProfile->load(['UserProfile' => $profileData]);
                if ($attender->save() && $userProfile->save()) {
                    if (isset($_FILES['image'])) {
                        $imageUpload = DrsImageUpload::updateProfileImageApp($attender->id, $_FILES);
                    }
                    if (isset($post['shift_id']) && ($post['shift_id'] != '')) {
                        $addressList = DrsPanel::doctorHospitalList($post['parent_id']);
                        $listadd = $addressList['apiList'];
                        $shift_array = array();
                        $s = 0;
                        $shift_value = array();
                        $sv = 0;
                        foreach ($listadd as $address) {
                            $shifts = DrsPanel::getShiftListByAddress($post['parent_id'], $address['id']);
                            foreach ($shifts as $key => $shift) {
                                if ($shift['hospital_id'] == 0) {
                                    $shift_array[$s]['value'] = $shift['shifts_ids'];
                                    $shift_array[$s]['label'] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                                    $shift_value[$sv] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                                    $s++;
                                    $sv++;
                                }
                            }
                        }

                        $sel_shift = explode(',', $post['shift_id']);
                        $shift_val = array();
                        foreach ($sel_shift as $s) {
                            $shift_selected_ids = $shift_array[$s];
                            $list = $shift_selected_ids['value'];
                            foreach ($list as $list) {
                                $shift_val[] = $list;
                            }
                        }
                        $addupdateAttender = DrsPanel::addUpdateAttenderToShifts($shift_val, $attender->id);
                    } else {
                        $addupdateAttender = DrsPanel::addUpdateAttenderToShifts(array(), $attender->id);
                    }

                    if (isset($post['doctor_id']) && !empty($post['doctor_id'])) {
                        $doctors = explode(',', $post['doctor_id']);
                        $addupdateHospitalDoctors = DrsPanel::addUpdateDoctorsToHospitalAttender($doctors, $attender->id, $attender->parent_id);
                    } else {
                        $addupdateHospitalDoctors = DrsPanel::addUpdateDoctorsToHospitalAttender(array(), $attender->id, $attender->parent_id);
                    }

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = $post;
                    $response['message'] = 'Attender successfully updated.';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['data'] = $attender->getErrors();
                    $response['message'] = 'Require fields does not match.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Attender id required.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionAttenderList() {
        $fields = ApiFields::attenderList();
        $response = $data = $required = array();
        $id = NULL;
        $params = Yii::$app->request->post();

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }

        Yii::info($params, __METHOD__);
        if (empty($required)) {
            $search['parent_id'] = $params['doctor_id'];
            if (isset($params['address_id']) && !empty($params['address_id'])) {
                $search['address_id'] = $params['address_id'];
            }
            $data = DrsPanel::attenderList($search, 'apilist');
            if ($data) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Success';
                $response['user_verified'] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['data'] = $data;
            } else {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Please added attender.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDeleteAttender() {
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($post['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        if (isset($post['id']) && !empty($post['id'])) {
            $attender = User::findOne($post['id']);
            if (empty($attender)) {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Attender id does not match.';
                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
            $userProfile = UserProfile::findOne($post['id']);
            $addupdateAttender = DrsPanel::addUpdateAttenderToShifts(array(), $attender->id);

            $cond['id'] = $attender->id;
            $cond1['user_id'] = $userProfile->user_id;
            User::deleteAll($cond);
            UserProfile::deleteAll($cond1);

            if (isset($post['user_id']) && !empty($post['user_id'])) {
                $cond2['attender_id'] = $attender->id;
                $cond2['hospital_id'] = $post['user_id'];
                HospitalAttender::deleteAll($cond2);
            }
            $response["status"] = 1;
            $response["error"] = false;
            $response['message'] = 'Attender deleted.';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Attender id required.';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDoctorShiftList() {
        $fields = ApiFields::shiftList();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }

        Yii::info($post, __METHOD__);
        if (empty($required)) {
            $doctor_id = $post['doctor_id'];
            $addressList = DrsPanel::doctorHospitalList($doctor_id);
            $listadd = $addressList['apiList'];
            $shift_array = array();
            $s = 0;
            foreach ($listadd as $address) {
                $shifts = DrsPanel::getShiftListByAddress($doctor_id, $address['id']);
                foreach ($shifts as $key => $shift) {
                    if ($shift['hospital_id'] == 0) {
                        $shift_array[$s]['id'] = $s;
                        $shift_array[$s]['value'] = $shift['shifts_ids'];
                        $shift_array[$s]['label'] = $shift['name'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                        $s++;
                    }
                }
            }
            if (!empty($shift_array)) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Success';
                $response['data'] = $shift_array;
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'You have not any shift added.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionHospitalShiftList() {
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        Yii::info($post, __METHOD__);
        if (isset($post['hospital_id']) && !empty($post['hospital_id'])) {
            $search['user_id'] = $post['hospital_id'];
            $shifts = Drspanel::shiftList($search, 'apilist', 'hospital');
            if ($shifts) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Success';
                $response['data'] = $shifts;
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'You have not any shift.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'hospital id required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for paytm callback action to update payment details
     */
    public function actionPaytmWalletCallback() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        $request = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        Yii::info($request, __METHOD__);
        $callback = Payment::paytm_wallet_callback($params, $request);
        if (!empty($callback) && isset($callback['STATUS'])) {
            if ($callback['STATUS'] != 'TXN_SUCCESS') {
                $response["status"] = 0;
                $response["error"] = true;
                $response["message"] = $callback['RESPMSG'];
                $response["data"] = $callback;
                Yii::$app->response->statusCode = 201;
                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            } else {
                $response["status"] = 1;
                $response["error"] = false;
                $response["message"] = "Appointment booked successfully";
                $response["data"] = $callback;
                Yii::$app->response->statusCode = 200;
                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response["message"] = 'hospital id required';
            $response["data"] = [];
            Yii::$app->response->statusCode = 201;
            Yii::info($response, __METHOD__);
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return $response;
        }
    }

    public function actionPaytmFailure() {
        $response = $data = $required = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['appointment_id']) && $params['appointment_id'] != '' && isset($params['status']) && $params['status'] != '') {
            $appointment = UserAppointmentTemp::find()->where(['id' => $params['appointment_id']])->one();
            $appointment->payment_status = UserAppointment::PAYMENT_PENDING;
            if ($appointment->save()) {

                $schedule_id = $appointment->schedule_id;
                $slot_id = $appointment->slot_id;
                $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
                $slot->status = 'available';
                $slot->save();

                $transaction = Transaction::find()->where(['temp_appointment_id' => $appointment_id])->one();
                $transaction->status = 'failed';
                $transaction->paytm_response = json_encode($data);
                if ($transaction->save()) {
                    $addLog = Logs::transactionLog($transaction->id, 'Transaction failed');
                }
                $response["status"] = 0;
                $response["error"] = true;
                $response["message"] = 'failed';
                $response["data"] = [];
                Yii::$app->response->statusCode = 201;
                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response["message"] = 'failed';
            $response["data"] = [];
            Yii::$app->response->statusCode = 201;
            Yii::info($response, __METHOD__);
            \Yii::$app->response->format = Response::FORMAT_JSON;
            return $response;
        }
    }

    /**
     * @Param Null
     * @Function is used for sending paytm response to api end
     */
    public function actionPaytmResponse() {
        $response = $data = $required = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['appointment_id']) && $params['appointment_id'] != '') {
            $appointment_id = $params['appointment_id'];
            $appointment_temp = UserAppointmentTemp::find()->where(['id' => $appointment_id])->one();

            if (!empty($appointment_temp)) {
                if ($appointment_temp->payment_status == UserAppointment::PAYMENT_COMPLETED) {
                    $transaction = Transaction::find()->where(['temp_appointment_id' => $appointment_id])->one();
                    $appointment_id = $transaction->appointment_id;
                    $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
                    if (!empty($appointment)) {
                        $response["data"] = DrsPanel::patientgetappointmentarray($appointment);
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response['message'] = 'Payment successfully done!';

                        /* if($appointment->payment_status == UserAppointment::PAYMENT_COMPLETED){

                          }
                          else{
                          $response["status"] = 0;
                          $response["error"] = true;
                          $response['message']= 'Payment Pending';
                          } */
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Appointment not found';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Payment Pending or Failed';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Appointment not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'AppointmentId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionHospitalAppointmentDoctors() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        $offset = 0;
        $recordlimit = 20;
        $totalpages = 0;
        $count_result = 0;
        if (isset($params['user_id']) && !empty($params['user_id'])) {

            $user = User::find()->where(['id' => $params['user_id']])->one();
            if (!empty($user)) {
                $groupid = Groups::GROUP_DOCTOR;
                if (isset($params['lat']) && isset($params['lng']) && $params['lat'] != '' && $params['lng'] != '') {
                    $latitude = $params['lat'];
                    $longitude = $params['lng'];
                    //$user=Appelavocat::getLocationUserList($latitude,$longitude);
                }

                if (isset($params['type']) && $params['type'] != '') {
                    $type = $params['type'];
                } else {
                    $type = 'list';
                }

                $lists = new Query();
                $lists = UserProfile::find();
                $lists->joinWith('user');
                $lists->where(['user_profile.groupid' => $groupid]);
                $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                    'user.admin_status' => User::STATUS_ADMIN_APPROVED]);
                if ($user->groupid == Groups::GROUP_ATTENDER) {
                    $hospitals_id = $user->parent_id;
                    $attender = $user;
                } else {
                    $hospitals_id = $params['user_id'];
                }
                if (isset($params['date']) && !empty($params['date'])) {
                    $date = $params['date'];
                } else {
                    $date = date('Y-m-d');
                }
                $ids = DrsPanel::dateWiseHospitalDoctors($hospitals_id, $date, $attender);

                $lists->andWhere(['user.id' => $ids]);
                $command = $lists->createCommand();

                $listcat = array();
                $valuecat = array();
                $gender = '';

                if (isset($params['filter'])) {
                    $filters = json_decode($params['filter']);

                    foreach ($filters as $filter) {
                        if ($filter->type == 'speciality') {
                            $listcat = $filter->list;
                        }

                        if ($filter->type == 'gender') {
                            $gender = $filter->list;
                        }
                    }

                    if (!empty($listcat)) {
                        foreach ($listcat as $cateval) {
                            $metavalues = MetaValues::find()->where(['id' => $cateval])->one();
                            if ($metavalues) {
                                $valuecat[] = $metavalues->value;
                            }
                        }

                        //$lists->andWhere(['in', 'user_profile.speciality', $valuecat]);
                        //$searchvalue=implode(',',$valuecat);
                        $query_array = array();
                        foreach ($valuecat as $needle) {
                            $query_array[] = sprintf('FIND_IN_SET("%s",`user_profile`.`speciality`)', $needle);
                        }
                        $query_str = implode(' OR ', $query_array);
                        $lists->andWhere(new \yii\db\Expression($query_str));
                    }

                    if ($gender != '') {
                        $lists->andWhere(['user_profile.gender' => $gender]);
                    }
                }

                if (isset($params['sort']) && !empty($params['sort'])) {
                    $sort = json_decode($params['sort']);
                    if ($sort->type == 'price') {
                        if ($sort->value == 'low to high') {
                            $lists->orderBy('user_profile.consultation_fees asc');
                        } else {
                            $lists->orderBy('user_profile.consultation_fees desc');
                        }
                    }

                    if ($sort->type == 'rating') {
                        if ($sort->value == 'low to high') {
                            $lists->orderBy('user_profile.rating asc');
                        } else {
                            $lists->orderBy('user_profile.rating desc');
                        }
                    }
                }


                if ($type == 'list') {
                    if (isset($params['offset']) && $params['offset'] != '') {
                        $offset = $params['offset'];
                    }
                    $countQuery = clone $lists;
                    $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
                    $lists->limit($recordlimit);
                    $lists->offset($offset);
                    $lists->all();
                    $command = $lists->createCommand();
                    $lists = $command->queryAll();

                    if (isset($totalpages)) {
                        $count_result = $totalpages->totalCount;
                    }
                    if ($count_result == null) {
                        $count_result = count($lists);
                        $offset = count($lists);
                    } else {
                        $oldoffset = $offset;
                        $offset = $offset + $recordlimit;
                        if ($offset > $count_result) {
                            $offset = $oldoffset + count($lists);
                        }
                    }

                    $totallist['totalcount'] = $count_result;
                    $totallist['offset'] = $offset;

                    $list_a = $this->getList($lists, 'list');
                    $data_array = array_values($list_a);
                    $response['pagination'] = $totallist;
                    $response['data'] = $data_array;
                    $response['filters'] = DrsPanel::getFilterArray();
                    $response['sort'] = DrsPanel::getSortArray();
                } else {
                    $lists = $command->queryAll();
                    $list_a = $this->getList($lists, 'list');
                    $data_array = array_values($list_a);
                    $response['mapdata'] = $data_array;
                }
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Doctors List';
                $response['profile'] = DrsPanel::hospitalProfile($params['user_id']);
                $s_list = DrsPanel::getMetaData('speciality');
                $groups_v['id'] = 0;
                $groups_v['value'] = 'All';
                $groups_v['label'] = 'All';
                $groups_v['count'] = 0;
                $groups_v['icon'] = '';
                $groups_v['isChecked'] = true;
                array_unshift($s_list, $groups_v);
                $response['speciality'] = $s_list;
                $response['date'] = $date;
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Are you sour login with hospital.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionFindDoctorHospitals() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        $offset = 0;
        $recordlimit = 20;
        $totalpages = 0;
        $count_result = 0;
        if (isset($params['user_id']) && !empty($params['user_id'])) {

            $user = User::find()->where(['id' => $params['user_id']])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one();
            if (!empty($user)) {
                $groupid = Groups::GROUP_HOSPITAL;
                if (isset($params['lat']) && isset($params['lng']) && $params['lat'] != '' && $params['lng'] != '') {
                    $latitude = $params['lat'];
                    $longitude = $params['lng'];
                    //$user=Appelavocat::getLocationUserList($latitude,$longitude);
                }

                if (isset($params['type']) && $params['type'] != '') {
                    $type = $params['type'];
                } else {
                    $type = 'list';
                }

                $doctor_id = $params['user_id'];



                $lists = new Query();
                $lists = UserProfile::find();
                $lists->joinWith('user');
                $lists->where(['user_profile.groupid' => $groupid]);
                $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                    'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);


                if (isset($params['list_type']) && $params['list_type'] == 'Requested') { //  Requested hospitals list
                    $reqUserSearch = ['status' => [UserRequest::Request_Confirmed, UserRequest::Requested], 'request_to' => $doctor_id, 'groupid' => Groups::GROUP_HOSPITAL];
                    $requested = UserRequest::requestedUser($reqUserSearch, 'request_to');
                    $lists->andWhere(['user.id' => $requested]);
                } else { // Confirm hospitals list
                    $confirmDrSearch = ['request_to' => $doctor_id, 'groupid' => Groups::GROUP_HOSPITAL, 'status' => [UserRequest::Request_Confirmed, UserRequest::Requested]];
                    $confirmDr = UserRequest::requestedUser($confirmDrSearch, 'request_to');
                    $lists->andWhere(['user.id' => $confirmDr]);
                }

                $command = $lists->createCommand();

                if ($type == 'list') {
                    if (isset($params['offset']) && $params['offset'] != '') {
                        $offset = $params['offset'];
                    }
                    $countQuery = clone $lists;
                    $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
                    $lists->limit($recordlimit);
                    $lists->offset($offset);
                    $lists->all();
                    $command = $lists->createCommand();
                    $lists = $command->queryAll();

                    if (isset($totalpages)) {
                        $count_result = $totalpages->totalCount;
                    }
                    if ($count_result == null) {
                        $count_result = count($lists);
                        $offset = count($lists);
                    } else {
                        $oldoffset = $offset;
                        $offset = $offset + $recordlimit;
                        if ($offset > $count_result) {
                            $offset = $oldoffset + count($lists);
                        }
                    }

                    $totallist['totalcount'] = $count_result;
                    $totallist['offset'] = $offset;

                    $list_a = $this->getList($lists, 'list', $params['user_id']);
                    $data_array = array_values($list_a);
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['pagination'] = $totallist;
                    $response['data'] = $data_array;
                    $response['user_verified'] = DrsPanel::getProfileStatus($params['current_login_id']);
                    $response['message'] = 'Hospitals List';
                } else {
                    $lists = $command->queryAll();
                    $list_a = $this->getList($lists, 'hospital_doctors', $params['user_id']);
                    $data_array = array_values($list_a);
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['mapdata'] = $data_array;
                    $response['user_verified'] = DrsPanel::getProfileStatus($params['current_login_id']);
                    $response['message'] = 'Hospitals List';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for find all doctors on hospital panel
     */
    public function actionFindAllDoctors() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        $offset = 0;
        $recordlimit = 10;
        $totalpages = 0;
        $count_result = 0;
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $user = User::find()->where(['id' => $params['user_id']])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->one();
            if (!empty($user)) {
                $hospital_id = $params['user_id'];
                $lists = DrsPanel::doctorsHospitalList($hospital_id, 'All', $usergroup = $user->groupid, $params['user_id']);

                $term = '';
                $v1 = '';
                if (isset($params['search']) && !empty($params['search'])) {
                    $term = $params['search'];
                    if ($term != '') {
                        $q_explode = explode(' ', $term);
                        $usersearch = array();
                        foreach ($q_explode as $word) {
                            $usersearch[] = "user_profile.name LIKE '%" . $word . "%'";
                        }
                        $v1 = implode(' or ', $usersearch);
                    }
                    if ($v1 != '') {
                        $lists->andFilterWhere(['or', $v1]);
                    }
                }

                if (isset($params['city_id']) && $params['city_id'] != '' && isset($params['city_name']) && $params['city_name'] != '') {
                    $city_id = $params['city_id'];
                    $city_name = $params['city_name'];

                    $userLocation = $this->getLocationUsersArray($params);
                    $lists->andWhere((['user.id' => $userLocation]));
                } else {
                    $city_id = '';
                    $city_name = '';
                }

                $command = $lists->createCommand();
                $countQuery_speciality = clone $lists;
                $countTotal = $countQuery_speciality->count();

                $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());

                if (isset($params['filter'])) {
                    $filters = json_decode($params['filter']);

                    foreach ($filters as $filter) {
                        if ($filter->type == 'speciality') {
                            $listcat = $filter->list;
                        }
                    }
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
                }

                if (isset($params['offset']) && $params['offset'] != '') {
                    $offset = $params['offset'];
                }

                $countQuery = clone $lists;
                $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
                $lists->limit($recordlimit);
                $lists->offset($offset);
                $lists->all();
                $command = $lists->createCommand();
                $lists = $command->queryAll();

                if (isset($totalpages)) {
                    $count_result = $totalpages->totalCount;
                }
                if ($count_result == null) {
                    $count_result = count($lists);
                    $offset = count($lists);
                } else {
                    $oldoffset = $offset;
                    $offset = $offset + $recordlimit;
                    if ($offset > $count_result) {
                        $offset = $oldoffset + count($lists);
                    }
                }
                $totallist['totalcount'] = $count_result;
                $totallist['offset'] = $offset;
                $list_a = $this->getList($lists, 'hospital_doctors', $hospital_id);
                $data_array = array_values($list_a);
                $response["status"] = 1;
                $response["error"] = false;
                $response['pagination'] = $totallist;
                $response['data'] = $data_array;
                $response['user_verified'] = DrsPanel::getProfileStatus($hospital_id);


                $response['cities'] = DrsPanel::getCitiesList();
                $selected['id'] = $city_id;
                $selected['name'] = $city_name;
                $response['selected_city'] = $selected;

                $response['message'] = 'Doctors List';

                if (count($data_array) > 0) {
                    $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);
                    $groups_v['id'] = 0;
                    $groups_v['value'] = 'All';
                    $groups_v['label'] = 'All';
                    $groups_v['count'] = $countTotal;
                    $groups_v['icon'] = '';
                    $groups_v['isChecked'] = true;
                    array_unshift($s_list, $groups_v);
                    $response['speciality'] = $s_list;
                } else {
                    $response['speciality'] = array();
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Are you sure you are login with hospital or hospital attender.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionHospitalDoctorsList() {
        $response = $data = $required = $user = $lists = $list_a = $search = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        $offset = 0;
        $recordlimit = 20;
        $totalpages = 0;
        $count_result = 0;
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $user = User::find()->where(['id' => $params['user_id']])->andWhere(['groupid' => [Groups::GROUP_HOSPITAL, Groups::GROUP_ATTENDER]])->one();
            if (!empty($user)) {
                if ($user->groupid == Groups::GROUP_ATTENDER) {
                    $hospital_id = $user->parent_id;
                } else {
                    $hospital_id = $params['user_id'];
                }

                if (isset($params['shift'])) {
                    $search['shift'] = true;
                }
                if (isset($params['current'])) {
                    $search['current'] = true;
                }

                $lists = DrsPanel::doctorsHospitalList($hospital_id, 'Confirm', $usergroup = $user->groupid, $params['user_id'], $search);

                $term = '';
                $v1 = '';
                if (isset($params['search']) && !empty($params['search'])) {
                    $term = $params['search'];
                }

                if ($term != '') {
                    $q_explode = explode(' ', $term);
                    $usersearch = array();
                    foreach ($q_explode as $word) {
                        $usersearch[] = "user_profile.name LIKE '%" . $word . "%'";
                    }
                    $v1 = implode(' or ', $usersearch);
                }
                if ($v1 != '') {
                    $lists->andFilterWhere(['or', $v1]);
                }

                $command = $lists->createCommand();


                $countQuery_speciality = clone $lists;
                $countTotal = $countQuery_speciality->count();

                $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());

                if (isset($params['filter'])) {
                    $filters = json_decode($params['filter']);

                    foreach ($filters as $filter) {
                        if ($filter->type == 'speciality') {
                            $listcat = $filter->list;
                        }
                    }
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
                }
                $command = $lists->createCommand();

                if (isset($params['offset']) && $params['offset'] != '') {
                    $offset = $params['offset'];
                }
                $countQuery = clone $lists;
                $totalpages = new Pagination(['totalCount' => $countQuery->count()]);
                $lists->limit($recordlimit);
                $lists->offset($offset);
                $lists->all();
                $command = $lists->createCommand();
                $lists = $command->queryAll();

                if (isset($totalpages)) {
                    $count_result = $totalpages->totalCount;
                }
                if ($count_result == null) {
                    $count_result = count($lists);
                    $offset = count($lists);
                } else {
                    $oldoffset = $offset;
                    $offset = $offset + $recordlimit;
                    if ($offset > $count_result) {
                        $offset = $oldoffset + count($lists);
                    }
                }

                $totallist['totalcount'] = $count_result;
                $totallist['offset'] = $offset;

                $list_a = $this->getList($lists, 'hospital_doctors', $hospital_id);
                $data_array = array_values($list_a);

                $response["status"] = 1;
                $response["error"] = false;
                $response['pagination'] = $totallist;
                $response['data'] = $data_array;
                $response['message'] = 'Doctors List';

                if ($user->groupid == Groups::GROUP_HOSPITAL) {
                    $user = User::findOne($params['user_id']);
                    $profile = UserProfile::findOne(['user_id' => $user->id]);
                    $response['profile'] = DrsPanel::profiledetails($user, $profile, $user->groupid, $params['user_id']);
                } else {
                    $response['profile'] = DrsPanel::hospitalProfile($params['user_id']);
                }

                if (!empty($data_array)) {
                    $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);
                    $groups_v['id'] = 0;
                    $groups_v['value'] = 'All';
                    $groups_v['label'] = 'All';
                    $groups_v['count'] = $countTotal;
                    $groups_v['icon'] = '';
                    $groups_v['isChecked'] = true;
                    array_unshift($s_list, $groups_v);
                    $response['speciality'] = $s_list;
                } else {
                    $response['speciality'] = [];
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Are you sure you are login with hospital or hospital attender.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for sending request to doctor
     */
    public function actionHospitalSendRequest() {
        $fields = ApiFields::userRequestFields();
        $response = $data = $required = array();
        $id = NULL;
        $post = Yii::$app->request->post();
        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }

        Yii::info($post, __METHOD__);
        if (empty($required)) {
            $user = User::find()->andWhere(['id' => $post['request_from']])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->one();
            if (!empty($user)) {
                $address = UserAddress::find()->where(['user_id' => $post['request_from']])->one();
                if (empty($address)) {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Please add address to your profile first';

                    Yii::info($response, __METHOD__);
                    \Yii::$app->response->format = Response::FORMAT_JSON;
                    return $response;
                }
                $post['groupid'] = Groups::GROUP_HOSPITAL;
                $post['request_from'] = $post['request_from'];
                $req_to_ids = explode(',', $post['request_to']);
                if (count($req_to_ids)) {
                    foreach ($req_to_ids as $key => $value) {
                        $post['request_to'] = $value;
                        UserRequest::updateStatus($post, 'Add');
                    }
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Request Submitted.';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Request to ids required in comma separated.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDoctorAcceptRequest() {
        $fields = ApiFields::userRequestFields();
        $response = $data = $required = array();
        $id = NULL;
        $post = Yii::$app->request->post();
        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }

        Yii::info($post, __METHOD__);
        if (empty($required)) {

            $user = User::find()->andWhere(['id' => $post['request_from']])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one();
            if (!empty($user)) {
                $req_to_ids = explode(',', $post['request_to']);
                if (count($req_to_ids)) {
                    $i = $j = 0;
                    $accepted = $doNot = [];
                    foreach ($req_to_ids as $key => $value) {
                        $update['status'] = 2;
                        $update['request_from'] = $value;
                        $update['request_to'] = $post['request_from'];
                        if (UserRequest::updateStatus($update, 'edit')) {
                            $accepted[$i] = $value;
                            $i++;
                        } else {
                            $doNot[$j] = $value;
                            $j++;
                        }
                    }
                    if (count($accepted) > 0) {
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response['message'] = 'Request Accepted.';
                    } else {
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response["data"] = implode(',', $doNot);
                        $response['message'] = 'Request Not Accepted.';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Request to ids required in comma separated.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionRequestUpdate() {
        $fields = ApiFields::hospitalRequestUpdate();
        $response = $data = $required = array();
        $id = NULL;
        $post = Yii::$app->request->post();

        if (isset($post['current_login_id'])) {
            if ($this->checkProfileInactive($post['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($post['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }

        Yii::info($post, __METHOD__);
        if (empty($required)) {
            $hospital_id = $post['hospital_id'];
            $doctor_id = $post['doctor_id'];
            $user = User::findOne($post['current_login_id']);



            if ($post['type'] == 'cancel') {
                $cond['request_from'] = $hospital_id;
                $cond['request_to'] = $doctor_id;
                $lists = UserRequest::deleteAll($cond);

                $response["status"] = 1;
                $response["error"] = false;
                $response["user_verified"] = DrsPanel::getProfileStatus($post['current_login_id']);
                $response['message'] = 'Request cancelled.';

                $lists = DrsPanel::doctorsHospitalList($hospital_id, 'Confirm', $usergroup = $user->groupid, $post['current_login_id'], $search['shift'] = true);
                $command = $lists->createCommand();
                $countQuery_speciality = clone $lists;
                $countTotal = $countQuery_speciality->count();
                $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());
                $data_array = $command->queryAll();

                if (!empty($data_array)) {
                    $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);
                    $groups_v['id'] = 0;
                    $groups_v['value'] = 'All';
                    $groups_v['label'] = 'All';
                    $groups_v['count'] = $countTotal;
                    $groups_v['icon'] = '';
                    $groups_v['isChecked'] = true;
                    array_unshift($s_list, $groups_v);
                    $response['speciality'] = $s_list;
                } else {
                    $response['speciality'] = [];
                }
            } elseif ($post['type'] == 'remove') {
                $cond['request_from'] = $hospital_id;
                $cond['request_to'] = $doctor_id;
                $lists = UserRequest::deleteAll($cond);
                $useraddress = UserAddress::find()->where(['user_id' => $hospital_id])->one();
                if (!empty($useraddress)) {
                    $deleteshiftwithappointments = DrsPanel::deleteAddresswithShifts($doctor_id, $useraddress->id);
                }

                $response["status"] = 1;
                $response["error"] = false;
                $response["user_verified"] = DrsPanel::getProfileStatus($post['current_login_id']);
                $response['message'] = 'Request removed.';

                $lists = DrsPanel::doctorsHospitalList($hospital_id, 'Confirm', $usergroup = $user->groupid, $post['current_login_id'], $search['shift'] = true);
                $command = $lists->createCommand();
                $countQuery_speciality = clone $lists;
                $countTotal = $countQuery_speciality->count();
                $fetchCount = Drspanel::fetchSpecialityCount($command->queryAll());
                $data_array = $command->queryAll();

                if (!empty($data_array)) {
                    $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);
                    $groups_v['id'] = 0;
                    $groups_v['value'] = 'All';
                    $groups_v['label'] = 'All';
                    $groups_v['count'] = $countTotal;
                    $groups_v['icon'] = '';
                    $groups_v['isChecked'] = true;
                    array_unshift($s_list, $groups_v);
                    $response['speciality'] = $s_list;
                } else {
                    $response['speciality'] = [];
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($post['current_login_id']);
                $response['message'] = 'Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionPatientMembers() {
        $response = $data = $required = $user = $lists = $list_a = $memberData = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        $MemberFiles = DrsPanel::membersList($params['user_id']);
        foreach ($MemberFiles as $value) {
            $row['user_id'] = $value['user_id'];
            $row['member_id'] = $value['id'];
            $row['name'] = $value['name'];
            $row['phone'] = $value['phone'];
            $row['gender'] = $value['gender'];
            $memberData[] = $row;
        }
        $offset = 0;
        $recordlimit = 20;
        $totalpages = 0;
        $count_result = 0;
        if (isset($params['user_id']) && !empty($params['user_id'])) {
            if ($user = User::find()->andWhere(['id' => $params['user_id']])->andWhere(['groupid' => Groups::GROUP_PATIENT])->one()) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = $memberData;
                $response['message'] = 'success.';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'You have not access.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionPatientMembersRecordsList() {
        $response = $data = $required = $user = $lists = $list_a = $memberData = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        $MemberFiles = DrsPanel::membersListFiles($params['member_id']);
        $response["status"] = 1;
        $response["error"] = false;
        $response['data'] = $MemberFiles;
        $response['message'] = 'success.';

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionPatientMemberImagesUpload() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        $offset = 0;
        $recordlimit = 20;
        $totalpages = 0;
        $count_result = 0;
        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['member_id']) && !empty($params['member_id']) && isset($_FILES['file']) && !empty($_FILES)) {
            $member = PatientMembers::find()->andWhere(['user_id' => $params['user_id']])->andWhere(['id' => $params['member_id']])->one();

            if (isset($params['record_label'])) {
                $record_label = $params['record_label'];
            } else {
                $record_label = 'Record';
            }
            if (!empty($member)) {
                $image_upload = DrsImageUpload::memberImages($member, $record_label, $_FILES);
                $response["status"] = 1;
                $response["error"] = false;
                $response["data"] = DrsPanel::membersListFiles($params['member_id']);
                $response['message'] = 'success.';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'You have not members.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDeletePatientRecord() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        $record_id = $params['record_id'];
        $member_id = $params['member_id'];
        $member = PatientMembers::find()->where(['id' => $member_id])->one();
        if (is_array($record_id)) {
            foreach ($record_id as $record) {
                $record_file = PatientMemberFiles::find()->where(['id' => $record])->one();
                if (!empty($record_id)) {
                    if ($record_file->delete()) {
                        $cond['files_id'] = $record;
                        $lists = PatientMemberRecords::deleteAll($cond);
                    }
                }
            }
        } else {
            $record_file = PatientMemberFiles::find()->where(['id' => $record_id])->one();
            if (!empty($record_id)) {
                if ($record_file->delete()) {
                    $cond['files_id'] = $record_id;
                    $lists = PatientMemberRecords::deleteAll($cond);
                }
            }
        }

        $response["status"] = 1;
        $response["error"] = false;
        $response['message'] = 'success.';

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionSharePatientRecord() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && isset($params['member_id']) && isset($params['mobile'])) {
            $member_id = $params['member_id'];
            $mobile = $params['mobile'];
            $current_id = $params['user_id'];
            $member = PatientMembers::find()->where(['id' => $member_id])->one();
            if (!empty($member)) {
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
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response['message'] = 'Record Shared!';
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Please try again';
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Please share record with other member';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Mobile number not registered!';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Member not found.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Required parameters missing';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionExperienceDelete() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        $experience_id = $params['id'];
        $experience = UserExperience::findOne($experience_id);
        if (!empty($experience)) {
            $experience->delete();
            $response["status"] = 1;
            $response["error"] = false;
            $response['message'] = 'Experience deleted successfully';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Experience already deleted';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionEducationDelete() {
        $response = $data = $required = $user = $lists = $list_a = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        $education_id = $params['id'];
        $education = UserEducations::findOne($education_id);
        if (!empty($education)) {
            $education->delete();
            $response["status"] = 1;
            $response["error"] = false;
            $response['message'] = 'Education deleted successfully';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Education already deleted';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetPatientAppointments() {
        $response = $data = $required = $user = $lists = $list_a = $memberData = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        $member_id = $params['member_id'];
        $member = PatientMembers::find()->where(['id' => $member_id])->one();
        if (!empty($member)) {
            $appList = new Query();
            $appList = UserAppointment::find();
            $appList->where(['user_id' => $member->user_id]);
            $appList->andWhere(['user_name' => $member->name, 'user_phone' => $member->phone]);
            $appList->all();
            $command = $appList->createCommand();
            $lists = $command->queryAll();

            $appointments = array();
            if (!empty($lists)) {
                $i = 0;
                foreach ($lists as $list) {
                    $lista = UserAppointment::findOne($list['id']);
                    $appointments[$i] = DrsPanel::patientgetappointmentarray($lista);
                    $i++;
                }
            }

            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $appointments;
            $response['message'] = 'success.';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Member not found';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionFavoriteUpsert() {
        $fields = ApiFields::favoriteUpsertFields();
        $response = $data = $required = array();
        $post = Yii::$app->request->post();
        foreach ($fields as $field) {
            if (array_key_exists($field, $post)) {
                
            } else {
                $required[] = $field;
            }
        }


        Yii::info($post, __METHOD__);
        if (empty($required)) {
            $data['user_id'] = $post['user_id'];
            $data['profile_id'] = $post['profile_id'];
            $data['status'] = $post['status'];
            $upsert = DrsPanel::userFavoriteUpsert($data);
            if ($upsert) {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Favorite Successfully.';
            } else {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Error.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used by doctor for block online appointment (Daily Patient Limit Screen)
     */
    public function actionUpdateShiftStatus() {
        $fields = ApiFields::updateShiftStatus();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $response = DrsPanel::updateShiftStatus($params);
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for getting patient history date wise on doctor side
     */
    public function actionPatientHistory() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                if (isset($params['date']) && $params['date'] != '') {
                    $date = $params['date'];
                } else {
                    $date = date('Y-m-d');
                }

                if (isset($params['schedule_id']) && $params['schedule_id'] != '') {
                    $current_selected = $params['schedule_id'];
                } else {
                    $current_selected = 0;
                }

                $checkForCurrentShift = 0;
                $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                if (!empty($getSlots)) {
                    $checkForCurrentShift = $getSlots[0]['schedule_id'];

                    if ($current_selected == 0) {
                        $current_selected = $checkForCurrentShift;
                    }
                    $getAppointments = DrsPanel::appointmentHistory($params['doctor_id'], $date, $current_selected, $getSlots, '');

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = $getAppointments;
                    $response['data']['all_shifts'] = DrsPanel::getDoctorAllShift($params['doctor_id'], $date, $checkForCurrentShift, $getSlots, $current_selected);
                    $response['user_verified'] = DrsPanel::getProfileStatus($params['doctor_id']);
                    $response['message'] = 'Today Appointments List';
                } else {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['user_verified'] = DrsPanel::getProfileStatus($params['doctor_id']);
                    $response['message'] = 'No shifts available';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for getting user statistics data date wise on doctor side
     */
    public function actionUserStatisticsData() {
        $response = $data = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }

        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $user = User::findOne(['id' => $params['user_id']]);
            if (!empty($user)) {
                if (isset($params['date']) && $params['date'] != '') {
                    $date = $params['date'];
                } else {
                    $date = date('Y-m-d');
                }

                if (isset($params['schedule_id']) && $params['schedule_id'] != '') {
                    $current_selected = (int) $params['schedule_id'];
                } else {
                    $current_selected = 0;
                }

                if (isset($params['type']) && $params['type'] != '') {
                    $typewise = $params['type'];
                } else {
                    $typewise = UserAppointment::BOOKING_TYPE_ONLINE;
                }

                $checkForCurrentShift = 0;
                $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                if (!empty($getSlots)) {
                    $checkForCurrentShift = $getSlots[0]['schedule_id'];

                    if ($current_selected == 0) {
                        $current_selected = $checkForCurrentShift;
                    }
                    $getAppointments = DrsPanel::appointmentHistory($params['doctor_id'], $date, $current_selected, $getSlots, $typewise);

                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = $getAppointments;
                    $response['data']['all_shifts'] = DrsPanel::getDoctorAllShift($params['doctor_id'], $date, $checkForCurrentShift, $getSlots, $current_selected);
                    $response['user_verified'] = DrsPanel::getProfileStatus($params['doctor_id']);
                    $response['message'] = 'Today Appointments List';
                } else {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = '';

                    $total_history['total_appointed'] = 0;
                    $total_history['total_offline'] = 0;
                    $total_history['total_online'] = 0;
                    $total_history['total_cancelled'] = 0;
                    $total_history['total_not_appointed'] = 0;
                    $type['online'] = 0;
                    $type['offline'] = 0;

                    $response['newdata'] = array('schedule_id' => $current_selected,
                        'date' => $date, 'total_history' => $total_history,
                        'type' => $type, 'typeselected' => $typewise, 'bookings' => array());
                    $response['newdata']['all_shifts'] = array();
                    $response['user_verified'] = DrsPanel::getProfileStatus($params['doctor_id']);
                    $response['message'] = 'No shifts available';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for deleting patient appointment history
     */
    public function actionDeletePatientHistory() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }


        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $doctor_id = $params['doctor_id'];
            $user = User::findOne(['id' => $doctor_id]);
            if (!empty($user)) {
                if ((isset($params['from_date']) && !empty($params['from_date'])) && (isset($params['to_date']) && !empty($params['to_date']))) {
                    $from_date = $params['from_date'];
                    $to_date = $params['to_date'];

                    $period = new \DatePeriod(
                            new \DateTime($from_date), new \DateInterval('P1D'), new \DateTime($to_date)
                    );

                    foreach ($period as $key => $value) {
                        $date = $value->format('Y-m-d');
                        $getSlots = DrsPanel::getBookingShifts($params['doctor_id'], $date, $params['user_id']);
                        $getShiftSlots = array();

                        foreach ($getSlots as $shift) {
                            $getShiftSlots[] = $shift['schedule_id'];
                        }

                        $appointments = UserAppointment::find()->where(['date' => $date, 'schedule_id' => $getShiftSlots])->andWhere(['doctor_id' => $doctor_id])->all();
                        foreach ($appointments as $appointment) {
                            $appointment->is_deleted = 1;
                            $appointment->deleted_by = 'Doctor';
                            if ($appointment->save()) {
                                $addLog = Logs::appointmentLog($appointment->id, 'Appointment deleted');
                            }
                        }
                    }
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Appointments history deleted successfully';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Required parameter missing';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @Param Null
     * @Function is used for export patient appointment history
     */
    public function actionExportPatientHistory() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['current_login_id'])) {
            if ($this->checkProfileInactive($params['current_login_id'])) {
                $response["status"] = 2;
                $response["error"] = true;
                $response["user_verified"] = DrsPanel::getProfileStatus($params['current_login_id']);
                $response['message'] = 'Profile yet to be approved!';

                Yii::info($response, __METHOD__);
                \Yii::$app->response->format = Response::FORMAT_JSON;
                return $response;
            }
        }


        if (isset($params['user_id']) && !empty($params['user_id']) && isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $doctor_id = $params['doctor_id'];
            $current_login = $params['user_id'];
            $user = User::findOne(['id' => $doctor_id]);
            if (!empty($user)) {
                if ((isset($params['from_date']) && !empty($params['from_date'])) && (isset($params['to_date']) && !empty($params['to_date']))) {
                    $from_date = $params['from_date'];
                    $to_date = $params['to_date'];
                    $export = DrsPanel::exportPatientHistoryExcel($doctor_id, $current_login, $from_date, $to_date);
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = 'Email send successfully';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Required parameter missing';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetHospitalDoctors() {
        $response = $data = $lists = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);

        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $hospital_id = $params['user_id'];
            $lists = DrsPanel::myHospitalDoctors($hospital_id, 'Confirm');

            $l = 0;
            $list_a = array();
            foreach ($lists as $list) {
                $user = User::findOne($list);
                $profile = UserProfile::findOne($list);
                $groupid = $profile->groupid;

                $list_a[$l]['user_id'] = $profile->user_id;
                $list_a[$l]['groupid'] = $groupid;
                $list_a[$l]['name'] = $profile->name;
                $list_a[$l]['profile_image'] = Drspanel::getUserAvator($profile->user_id);
                $list_a[$l]['countrycode'] = $user->countrycode;
                $list_a[$l]['phone'] = $user->phone;
                $list_a[$l]['gender'] = $profile->gender;
                $list_a[$l]['blood_group'] = $profile->blood_group;
                $list_a[$l]['dob'] = $profile->dob;
                if (!empty($profile->dob)) {
                    $list_a[$l]['age'] = Drspanel::getAge($profile->dob);
                } else {
                    $list_a[$l]['age'] = '';
                }
                $list_a[$l]['degree'] = $profile->degree;
                $list_a[$l]['speciality'] = $profile->speciality;
                $list_a[$l]['experience'] = $profile->experience;
                $list_a[$l]['description'] = strip_tags($profile->description);
                $list_a[$l]['address'] = Drspanel::getAddress($profile->user_id);
                $list_a[$l]['fees'] = $profile->consultation_fees;
                $list_a[$l]['fees_discount'] = $profile->consultation_fees_discount;

                $list_a[$l]['show_fees'] = DrsPanel::getUserSetting($profile->user_id, 'show_fees');

                $rating = Drspanel::getRatingStatus($profile->user_id);
                $list_a[$l]['rating'] = $rating['rating'];


                $l++;
            }
            $response["status"] = 1;
            $response["error"] = false;
            $response["data"] = $list_a;
            $response['message'] = 'Doctors list';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'UserId Required';
        }

        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetBookingAddressShifts() {
        $response = $datameta = $required = $logindUser = array();
        $params = Yii::$app->request->queryParams;
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && !empty($params['user_id']) &&
                isset($params['doctor_id']) && !empty($params['doctor_id'])) {
            $userLogin = User::find()->where(['id' => $params['user_id']])->one();
            $doctor = User::find()->where(['id' => $params['doctor_id']])->one();
            if (!empty($doctor)) {
                if (isset($params['date']) && !empty($params['date'])) {
                    $date = $params['date'];
                } else {
                    $date = date('Y-m-d');
                }
                $getSlots = DrsPanel::getBookingAddressShifts($params['doctor_id'], $date, $params['user_id']);
                $datameta['date'] = $date;
                $datameta['week'] = DrsPanel::getDateWeekDay($date);
                $response["status"] = 1;
                $response["error"] = false;
                $response["data"] = $getSlots;
                $response['message'] = 'Success';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Something went wrong, Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Doctor id required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetShiftBookingDays() {
        $fields = ApiFields::shiftbookingdays();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $doctor = User::find()->where(['id' => $params['doctor_id']])->one();
            if (!empty($doctor)) {
                $date = $params['next_date'];

                $getSlots = DrsPanel::getAddressShiftsDays($params);
                $datameta['date'] = $date;
                $datameta['week'] = DrsPanel::getDateWeekDay($date);
                $response["status"] = 1;
                $response["error"] = false;
                $response["data"] = $getSlots;
                $response["data"]['current_date'] = date('Y-m-d');
                $response["data"]['current_time'] = time();
                $response['message'] = 'Success';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Something went wrong, Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetLiveStatus() {
        $fields = ApiFields::liveStatus();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $user = User::find()->where(['id' => $params['doctor_id']])->one();
            if (!empty($user)) {
                $scheduleGroup = UserScheduleGroup::find()->andWhere(['user_id' => $params['doctor_id'], 'schedule_id' => $params['schedule_id'], 'date' => $params['appointment_date']])->one();
                if (!empty($scheduleGroup)) {
                    if ($scheduleGroup->status == 'current') {
                        $userAppointment = UserAppointment::findOne($params['appointment_id']);
                        $getAppointments = DrsPanel::patientgetappointmentarray($userAppointment);
                        $appointment = DrsPanel::liveStatusData($params['doctor_id'], $params['schedule_id'], $params['appointment_date'], $params['appointment_id']);
                        if ($appointment) {
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response["data"] = $appointment;
                            $response["appointment"] = $getAppointments;
                            $response['message'] = 'Success';
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Something went wrong, Please try again.';
                        }
                    } elseif ($scheduleGroup->status == 'pending') {
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response["data"] = [];
                        $response['message'] = 'Shift not started';
                    } elseif ($scheduleGroup->status == 'completed') {
                        $response["status"] = 1;
                        $response["error"] = false;
                        $response["data"] = [];
                        $response['message'] = 'Shift completed';
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Details not found,Please try again!';
                    }
                } else {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response["data"] = [];
                    $response['message'] = 'Shift not started.';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Something went wrong, Please try again.';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDeleteShiftForDays() {
        $fields = ApiFields::deleteShift();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $schedule_ids = explode(',', $params['schedule_id']);
            $deleteAllShift = DrsPanel::deleteShiftForDays($params['doctor_id'], $schedule_ids);
            $response["status"] = 1;
            $response["error"] = false;
            $response['message'] = 'Shift Deleted Successfully';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionEditShiftForDate() {
        $fields = ApiFields::todayTimingShift();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            if (isset($params['user_id']) && $params['user_id'] != '') {
                $user = User::findOne(['id' => $params['user_id']]);
                if (!empty($user)) {
                    $canAddEdit = true;
                    $msg = ' invalid';
                    if (isset($params['address_id']) && isset($params['shift_id']) && isset($params['date']) && isset($params['schedule_id'])) {
                        $weekday = DrsPanel::getDateWeekDay($params['date']);
                        $schedule = UserSchedule::findOne($params['schedule_id']);
                        if (!empty($schedule)) {
                            $dayShiftsFromDb = UserScheduleDay::find()->where(['user_id' => $params['user_id']])->andwhere(['address_id' => $params['address_id'], 'schedule_id' => $schedule->id])->andwhere(['!=', 'id', $params['shift_id']])->andwhere(['date' => $params['date']])->all();

                            if (!empty($dayShiftsFromDb)) {
                                foreach ($dayShiftsFromDb as $key => $dayshiftValuedb) {
                                    $dbstart_time = date('Y-m-d', $dayshiftValuedb->start_time);

                                    $dbend_time = date('Y-m-d', $dayshiftValuedb->end_time);

                                    $nstart_time = $dbstart_time . ' ' . $params['start_time'];

                                    $nend_time = $dbend_time . ' ' . $params['end_time'];

                                    $startTimeClnt = strtotime($nstart_time);

                                    $endTimeClnt = strtotime($nend_time);

                                    $startTimeDb = $dayshiftValuedb->start_time;

                                    $endTimeDb = $dayshiftValuedb->end_time;

                                    if ($startTimeClnt >= $startTimeDb && $startTimeClnt <= $endTimeDb) {
                                        $canAddEdit = false;
                                        $msg = ' already exists';
                                    } elseif ($endTimeClnt >= $startTimeDb && $endTimeClnt <= $endTimeDb) {
                                        $canAddEdit = false;
                                    } elseif ($startTimeDb >= $startTimeClnt && $startTimeDb <= $endTimeClnt) {
                                        $canAddEdit = false;
                                    } elseif ($endTimeDb >= $startTimeClnt && $endTimeDb <= $endTimeClnt) {
                                        $canAddEdit = false;
                                    } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                                        $canAddEdit = false;
                                    }
                                    if ($canAddEdit == false) {
                                        $response["status"] = 0;
                                        $response["error"] = true;
                                        $response["data"] = [];
                                        $response['message'] = 'Shift ' . date('h:i a', $startTimeClnt) . ' - ' . date('h:i a', $endTimeClnt) . ' on ' . $dayshiftValuedb->weekday . $msg;
                                        Yii::info($response, __METHOD__);
                                        \Yii::$app->response->format = Response::FORMAT_JSON;
                                        return $response;
                                    }
                                }
                            }
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Schedule not found';
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Required field missing';
                    }
                    if ($canAddEdit == true) {
                        $schedulegroup = UserScheduleGroup::findOne($params['schedule_id']);
                        if (!empty($schedulegroup)) {
                            $schedulegroup->load(['UserScheduleGroup' => $params]);
                            $schedulegroup->address_id = $params['address_id'];

                            $schedulegroup->shift_label = 'Shift ' . $params['start_time'] . ' - ' . $params['end_time'];
                            if ($schedulegroup->save()) {
                                $scheduleDay = UserScheduleDay::find()->where(['schedule_id' => $params['schedule_id'], 'date' => $params['date'], 'user_id' => $params['user_id']])->one();
                                if (empty($schedulegroup)) {
                                    $schedulegroup = new UserScheduleDay();
                                }

                                $scheduleDay->user_id = $schedule->user_id;
                                $scheduleDay->schedule_id = $schedule->id;
                                $scheduleDay->shift_belongs_to = $schedule->shift_belongs_to;
                                $scheduleDay->attender_id = $schedule->attender_id;
                                $scheduleDay->hospital_id = $schedule->hospital_id;
                                $scheduleDay->address_id = $schedule->address_id;
                                $scheduleDay->shift = (string) $schedule->shift;
                                $scheduleDay->start_time = strtotime($params['start_time']);
                                $scheduleDay->end_time = strtotime($params['end_time']);
                                $scheduleDay->patient_limit = $params['patient_limit'];
                                $scheduleDay->appointment_time_duration = $params['appointment_time_duration'];
                                $scheduleDay->consultation_fees = $params['consultation_fees'];
                                $scheduleDay->emergency_fees = $params['emergency_fees'];
                                $scheduleDay->consultation_fees_discount = $params['consultation_fees_discount'];
                                $scheduleDay->emergency_fees_discount = $params['emergency_fees_discount'];
                                $scheduleDay->date = $params['date'];
                                $scheduleDay->weekday = $weekday;
                                $scheduleDay->status = 'pending';
                                $scheduleDay->booking_closed = 1;
                                $scheduleDay->save();
                                UserScheduleSlots::deleteAll(['schedule_id' => $params['schedule_id'], 'date' => $params['date']]);
                            }
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response['message'] = 'Today Shift Updated Successfully';
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Shift Id Not Found';
                        }
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'User Not Found';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User Not Found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    /**
     * @return array
     */
    public function actionAddShiftWithAddress() {
        $fields = ApiFields::addShiftFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            if (isset($params['user_id']) && $params['user_id'] != '') {
                $user = User::findOne(['id' => $params['user_id'], 'groupid' => Groups::GROUP_DOCTOR]);
                if (!empty($user)) {
                    $user_ids = $params['user_id'];
                    $addAddress = new UserAddress();
                    $data = DrsPanel::loadAddressData($params);
                    $addAddress->load($data);

                    if ((isset($params['dayShifts']) && !empty($params['dayShifts']))) {
                        $shiftsarray = json_decode($params['dayShifts']);
                        $postmeta = array();
                        foreach ($shiftsarray as $key => $shift) {
                            $postmeta['weekday'][$key] = $shift->weekday;
                            $postmeta['start_time'][$key] = $shift->shiftList->start_time;
                            $postmeta['end_time'][$key] = $shift->shiftList->end_time;
                            $postmeta['appointment_time_duration'][$key] = $shift->shiftList->appointment_time_duration;
                            $postmeta['consultation_fees'][$key] = $shift->shiftList->consultation_fees;
                            $postmeta['emergency_fees'][$key] = $shift->shiftList->emergency_fees;
                            $postmeta['consultation_fees_discount'][$key] = $shift->shiftList->consultation_fees_discount;
                            $postmeta['emergency_fees_discount'][$key] = $shift->shiftList->emergency_fees_discount;
                        }

                        $shift = array();
                        $shiftcount = $postmeta['start_time'];
                        $canAddEdit = true;
                        $msg = ' invalid';
                        $errorIndex = 0;
                        $newInsertIndex = 0;
                        $errorShift = array();
                        $insertShift = array();
                        $newshiftInsert = 0;
                        $insertShift = array();
                        $addAddress->load(Yii::$app->request->post());
                        $addAddress->user_id = $user_ids;
                        $upload = UploadedFile::getInstance($addAddress, 'image');
                        $userAddressLastId = UserAddress::find()->orderBy(['id' => SORT_DESC])->one();
                        $countshift = count($shiftcount);
                        $newshiftcheck = array();
                        $errormsgloop = array();
                        $nsc = 0;
                        $error_msg = 0;

                        if (!empty($postmeta)) {
                            foreach ($postmeta['weekday'] as $keyClnt => $day_shift) {
                                foreach ($day_shift as $keydata => $value) {
                                    $dayShiftsFromDb = UserSchedule::find()->where(['user_id' => $user_ids])->andwhere(['weekday' => $value])->all();
                                    if (!empty($dayShiftsFromDb)) {
                                        foreach ($dayShiftsFromDb as $keydb => $dayshiftValuedb) {
                                            $dbstart_time = date('Y-m-d', $dayshiftValuedb->start_time);
                                            $dbend_time = date('Y-m-d', $dayshiftValuedb->end_time);
                                            $nstart_time = $dbstart_time . ' ' . $postmeta['start_time'][$keyClnt];
                                            $nend_time = $dbend_time . ' ' . $postmeta['end_time'][$keyClnt];
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
                                                $errormsgloop[$error_msg]['message'] = ' already exists';
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
                                            $insertShift[$newInsertIndex] = DrsPanel::loadShiftData($user_ids, $keyClnt, $postmeta, $value, $countshift = NULL);
                                            $newInsertIndex++;
                                        }
                                    } else {
                                        $dbstart_time = date('Y-m-d');
                                        $nstart_time = $dbstart_time . ' ' . $postmeta['start_time'][$keyClnt];
                                        $nend_time = $dbstart_time . ' ' . $postmeta['end_time'][$keyClnt];
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
                                            $newshiftcheck[$nsc_add]['keyclnt'] = $keyClnt;
                                            $newshiftcheck[$nsc_add]['weekday'] = $value;
                                            $insertShift[$newInsertIndex] = DrsPanel::loadShiftData($user_ids, $keyClnt, $postmeta, $value, $countshift = NULL);
                                            $newInsertIndex++;
                                        }
                                    }
                                }
                            }

                            if ($canAddEdit == false || !empty($errormsgloop)) {
                                if (!empty($errormsgloop)) {
                                    $html = array();
                                    $remove_duplicate = array();
                                    $weekdaysl = array();
                                    foreach ($errormsgloop as $msgloop) {
                                        $keyshifts = $msgloop['shift'];
                                        if (!in_array($keyshifts . '-' . $msgloop['weekday'], $remove_duplicate)) {
                                            $remove_duplicate[] = $keyshifts . '-' . $msgloop['weekday'];
                                            $weekdaysl[$keyshifts][] = $msgloop['weekday'];
                                            $html[$keyshifts] = 'Shift time ' . date('h:i a', $msgloop['start_time']) . ' - ' . date('h:i a', $msgloop['end_time']) . ' on ' . implode(',', $weekdaysl[$keyshifts]) . ' ' . $msgloop['message'];
                                        }
                                    }
                                    $error_msg = implode(" , ", $html);
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["data"] = '';
                                    $response['message'] = $error_msg;
                                    Yii::info($response, __METHOD__);
                                    \Yii::$app->response->format = Response::FORMAT_JSON;
                                    return $response;
                                } else {
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["data"] = '';
                                    $response['message'] = 'Shift time invalid';
                                    Yii::info($response, __METHOD__);
                                    \Yii::$app->response->format = Response::FORMAT_JSON;
                                    return $response;
                                }
                            } elseif ($canAddEdit == true) {
                                if ($addAddress->save()) {
                                    $imageUpload = '';
                                    if (isset($_FILES['image'])) {
                                        $imageUpload = DrsImageUpload::updateAddressImage($addAddress->id, $_FILES);
                                    }
                                    if (isset($_FILES['images'])) {
                                        $imageUpload = DrsImageUpload::updateAddressImageList($addAddress->id, $_FILES, 'images');
                                    }
                                    $response = DrsPanel::addShiftWithAddress($insertShift, $addAddress->id, $user_ids);
                                } else {
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["data"] = $addAddress->getErrors();
                                    $response['message'] = 'Please try again';
                                }
                            } else {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = 'Please try again';
                            }
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Please add atleast one shift';
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Please add atleast one shift';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'User not found';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionEditShiftWithAddress() {
        $fields = ApiFields::addShiftFields();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        Yii::info($_FILES, __METHOD__);
        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }

        if (empty($required)) {
            if (isset($params['user_id']) && $params['user_id'] != '') {
                $user = User::findOne(['id' => $params['user_id']]);
                if (!empty($user)) {
                    $user_ids = $params['user_id'];
                    $addAddress = UserAddress::findOne($params['address_id']);
                    if (empty($addAddress)) {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Invalid Address';
                        Yii::info($response, __METHOD__);
                        \Yii::$app->response->format = Response::FORMAT_JSON;
                        return $response;
                    }
                    $response["response_image"] = $_FILES;

                    if ($addAddress->user_id == $params['user_id']) {
                        $disable_field = 0;
                    } else {
                        $disable_field = 1;
                    }

                    if ($disable_field == 0) {
                        $data['UserAddress']['user_id'] = $params['user_id'];
                        $data['UserAddress']['name'] = $params['name'];
                        $data['UserAddress']['city'] = $params['city'];
                        $data['UserAddress']['city_id'] = DrsPanel::getCityId($params['city'], $params['state']);
                        $data['UserAddress']['state'] = $params['state'];
                        $data['UserAddress']['address'] = $params['address'];
                        $data['UserAddress']['area'] = $params['area'];
                        $data['UserAddress']['phone'] = $params['mobile'];
                        $data['UserAddress']['landline'] = isset($params['landline']) ? $params['landline'] : '';
                        $data['UserAddress']['lat'] = isset($params['latitude']) ? $params['latitude'] : '';
                        $data['UserAddress']['lng'] = isset($params['longitude']) ? $params['longitude'] : '';
                        $data['UserAddress']['is_request'] = 0;
                        $addAddress->load($data);
                    }

                    if ((isset($params['dayShifts']) && !empty($params['dayShifts']))) {
                        $shiftsarray = json_decode($params['dayShifts']);
                        $postmeta = array();
                        $postmeta_shift = array();
                        foreach ($shiftsarray as $key => $shift) {
                            $postmeta['weekday'][$key] = $shift->weekday;
                            $postmeta['start_time'][$key] = $shift->shiftList->start_time;
                            $postmeta['end_time'][$key] = $shift->shiftList->end_time;
                            $postmeta['appointment_time_duration'][$key] = $shift->shiftList->appointment_time_duration;
                            $postmeta['consultation_fees'][$key] = $shift->shiftList->consultation_fees;
                            $postmeta['emergency_fees'][$key] = $shift->shiftList->emergency_fees;
                            $postmeta['consultation_fees_discount'][$key] = $shift->shiftList->consultation_fees_discount;
                            $postmeta['emergency_fees_discount'][$key] = $shift->shiftList->emergency_fees_discount;
                            if (isset($shift->shiftList->id)) {
                                $postmeta_shift['shift_ids'][$key] = (array) $shift->shiftList->id;
                            }
                        }
                        $shift = array();
                        $shiftcount = $postmeta['start_time'];
                        $canAddEdit = true;
                        $msg = ' invalid';
                        $errorIndex = 0;
                        $newInsertIndex = 0;
                        $errorShift = array();
                        $insertShift = array();
                        $newshiftInsert = 0;
                        $insertShift = array();
                        $upload = UploadedFile::getInstance($addAddress, 'image');
                        $userAddressLastId = UserAddress::find()->orderBy(['id' => SORT_DESC])->one();
                        $countshift = count($shiftcount);
                        $newshiftcheck = array();
                        $errormsgloop = array();
                        $nsc = 0;
                        $error_msg = 0;

                        if (!empty($postmeta)) {
                            foreach ($postmeta['weekday'] as $keyClnt => $day_shift) {
                                if (!empty($day_shift)) {
                                    foreach ($day_shift as $keydata => $value) {

                                        if (isset($postmeta_shift['shift_ids']) && isset($postmeta_shift['shift_ids'][$keyClnt]) && isset($postmeta_shift['shift_ids'][$keyClnt][$value])) {
                                            $existing_shift = UserSchedule::findOne($postmeta_shift['shift_ids'][$keyClnt][$value]);
                                        } else {
                                            $existing_shift = array();
                                        }
                                        $dayShiftsFromDb = UserSchedule::find()->where(['user_id' => $user_ids])->andwhere(['weekday' => $value])->all();
                                        if (!empty($dayShiftsFromDb)) {
                                            foreach ($dayShiftsFromDb as $keydb => $dayshiftValuedb) {
                                                $dbstart_time = date('Y-m-d', $dayshiftValuedb->start_time);
                                                $dbend_time = date('Y-m-d', $dayshiftValuedb->end_time);
                                                $nstart_time = $dbstart_time . ' ' . $postmeta['start_time'][$keyClnt];
                                                $nend_time = $dbend_time . ' ' . $postmeta['end_time'][$keyClnt];
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
                                                              $errormsgloop[$error_msg]['message']= 'is invalid time';
                                                              $canAddEdit = false;
                                                              $errorIndex++;$error_msg++;
                                                              $msg = ' msg3';
                                                              }
                                                              elseif($endtime_check > $startTimeClnt && $endtime_check <= $endTimeClnt) {
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
                                                    } elseif ($endTimeClnt > $startTimeDb && $endTimeClnt <= $endTimeDb) {
                                                        $errormsgloop[$error_msg]['start_time'] = $startTimeClnt;
                                                        $errormsgloop[$error_msg]['end_time'] = $endTimeClnt;
                                                        $errormsgloop[$error_msg]['shift'] = $keyClnt;
                                                        $errormsgloop[$error_msg]['weekday'] = $value;
                                                        $errormsgloop[$error_msg]['message'] = 'is invalid time';
                                                        $canAddEdit = false;
                                                        $errorIndex++;
                                                        $error_msg++;
                                                        $msg = ' msg2';
                                                    } elseif ($startTimeDb >= $startTimeClnt && $startTimeDb < $endTimeClnt) {
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
                                            }
                                            if ($canAddEdit == true) {
                                                $insertShift[$newInsertIndex] = DrsPanel::loadShiftData($user_ids, $keyClnt, $postmeta, $value, $countshift = NULL);
                                                if (!empty($existing_shift)) {
                                                    $insertShift[$newInsertIndex]['AddScheduleForm']['id'] = $existing_shift->id;
                                                }
                                                $newInsertIndex++;
                                            }
                                        } else {
                                            $dbstart_time = date('Y-m-d');
                                            $nstart_time = $dbstart_time . ' ' . $postmeta['start_time'][$keyClnt];
                                            $nend_time = $dbstart_time . ' ' . $postmeta['end_time'][$keyClnt];
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


                                            if ($canAddEdit == true) {
                                                $nsc_add = $nsc++;
                                                $newshiftcheck[$nsc_add]['start_time'] = $startTimeClnt;
                                                $newshiftcheck[$nsc_add]['end_time'] = $endTimeClnt;
                                                $newshiftcheck[$nsc_add]['weekday'] = $value;
                                                $newshiftcheck[$nsc_add]['keyclnt'] = $keyClnt;
                                                $insertShift[$newInsertIndex] = DrsPanel::loadShiftData($user_ids, $keyClnt, $postmeta, $value, $countshift = NULL);
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
                                    $error_msg = implode(" , ", $html);
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["data"] = '';
                                    $response['message'] = $error_msg;
                                    Yii::info($response, __METHOD__);
                                    \Yii::$app->response->format = Response::FORMAT_JSON;
                                    return $response;
                                } else {
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["data"] = '';
                                    $response['message'] = 'Shift time invalid';
                                    Yii::info($response, __METHOD__);
                                    \Yii::$app->response->format = Response::FORMAT_JSON;
                                    return $response;
                                }
                            } elseif ($canAddEdit == true) {
                                $errores = array();
                                if ($addAddress->save()) {
                                    //delete images
                                    if (isset($params['deletedImages'])) {
                                        $deletedImages = json_decode($params['deletedImages']);
                                        foreach ($deletedImages as $key_del => $value_del) {
                                            $deleteAddressimg = UserAddressImages::findOne($value_del);
                                            if (!empty($deleteAddressimg)) {
                                                $deleteAddressimg->delete();
                                            }
                                        }
                                    }

                                    $imageUpload = '';
                                    if (isset($_FILES['image'])) {
                                        $imageUpload = DrsImageUpload::updateAddressImage($addAddress->id, $_FILES);
                                    }
                                    if (isset($_FILES['images'])) {
                                        $imageUpload = DrsImageUpload::updateAddressImageList($addAddress->id, $_FILES, 'images');
                                    }
                                    if (!empty($insertShift)) {
                                        $oldshift_ids = array();
                                        $currentshift_ids = array();
                                        if (isset($postmeta_shift['shift_ids'])) {
                                            foreach ($postmeta_shift['shift_ids'] as $keyids => $valueids) {
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
                                                $olddata['consultation_fees_discount'] = $saveScheduleData->consultation_fees_discount;
                                                $olddata['emergency_fees_discount'] = $saveScheduleData->emergency_fees_discount;
                                            } else {
                                                $saveScheduleData = new UserSchedule();
                                                $old_insert = 0;
                                            }
                                            $saveScheduleData->load(['UserSchedule' => $value['AddScheduleForm']]);
                                            $saveScheduleData->address_id = $addAddress->id;
                                            $saveScheduleData->start_time = strtotime($value['AddScheduleForm']['start_time']);
                                            $saveScheduleData->end_time = strtotime($value['AddScheduleForm']['end_time']);

                                            if ($addAddress->user_id == $params['user_id']) {
                                                
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
                                    $attender_shifts = DrsPanel::editShiftToAttender($user_ids);
                                    $shifts_keys = Drspanel::addUpdateShiftKeys($user_ids);
                                    $updateStatusShift = DrsPanel::userShiftsStatus($user_ids);
                                    $loadtodayshifts = DrsPanel::getScheduleShifts($user_ids, date('Y-m-d'));

                                    $response["status"] = 1;
                                    $response["error"] = false;
                                    $response["data"] = '';
                                    $response["response_image"] = $_FILES;
                                    $response['message'] = 'Shift Updated Successfully';
                                } else {
                                    $response["status"] = 0;
                                    $response["error"] = true;
                                    $response["data"] = $addAddress->getErrors();
                                    $response['message'] = 'Please try again';
                                }
                            } else {
                                $response["status"] = 0;
                                $response["error"] = true;
                                $response['message'] = 'Please try again';
                            }
                        } else {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Please add atleast one shift';
                        }
                    } else {
                        $response["status"] = 0;
                        $response["error"] = true;
                        $response['message'] = 'Please add atleast one shift';
                    }
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'User not found';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'User not found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDeleteShiftAddress() {
        $fields = ApiFields::deleteShiftAddress();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $address_id = $params['address_id'];
            $address_delete = DrsPanel::deleteAddresswithShifts($params['doctor_id'], $address_id);
            $response["status"] = $address_delete['status'];
            $response["error"] = $address_delete['error'];
            $response['message'] = $address_delete['message'];
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetPatientShifts() {
        $fields = ApiFields::getPatientShifts();
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        foreach ($fields as $field) {
            if (array_key_exists($field, $params)) {
                
            } else {
                $required[] = $field;
            }
        }
        if (empty($required)) {
            $doctor = User::findOne($params['doctor_id']);
            $d_id = $doctor->id;
            $current_login = $params['user_id'];
            if (isset($params['date']) && !empty($params['date'])) {
                $date = $params['date'];
                $appointments = DrsPanel::getBookingAddressShifts($d_id, $date, $current_login, 1);
            } else {
                $date = date('Y-m-d');
                $appointments = DrsPanel::getBookingAddressShifts($d_id, $date, $current_login);
            }

            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $appointments;
            $response['message'] = 'Shift Details';
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $fields_req = implode(',', $required);
            $response['message'] = $fields_req . ' required';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionCancelAppointment() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);

        if (isset($params['appointment_id']) && $params['appointment_id'] != '' && isset($params['user_id']) && $params['user_id'] != '') {

            $user_id = $params['user_id'];
            $checkUserGroup = Drspanel::getusergroupalias($user_id);
            $appointment_id = $params['appointment_id'];
            $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
            $appointment->status = UserAppointment::STATUS_CANCELLED;
            $appointment->is_deleted = 1;
            if ($checkUserGroup == 'patient') {
                $appointment->deleted_by = 'Patient';
            } else {
                $appointment->deleted_by = 'Doctor';
            }
            if ($appointment->save()) {
                $sendSMS = Notifications::appointmentSmsNotification($appointment->id, 'cancelled', $checkUserGroup);
                $addLog = Logs::appointmentLog($appointment->id, 'Appointment cancelled by ' . $checkUserGroup);
                $slot_id = $appointment->slot_id;
                $schedule_id = $appointment->schedule_id;
                $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
                $slot->status = 'available';
                $slot->save();
                $refunResponse = DrsPanel::getRefundAmount($appointment->id, $checkUserGroup);
                if ($refunResponse['status'] == 'success') {
                    $message = $refunResponse['message'];
                } else {
                    $message = $refunResponse['message'];
                }
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = $message;
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Please try again';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Missing required fields';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    function titletype($groupid) {
        if ($groupid == Groups::GROUP_PATIENT) {
            $lista = DrsPanel::prefixingList('patient');
            $list = array();
            $l = 0;
            foreach ($lista as $li) {
                $list[$l]['value'] = $li;
                $list[$l]['label'] = $li;
                $l++;
            }
        } elseif ($groupid == Groups::GROUP_DOCTOR) {
            $lista = DrsPanel::prefixingList('doctor');
            $list = array();
            $l = 0;
            foreach ($lista as $li) {
                $list[$l]['value'] = $li;
                $list[$l]['label'] = $li;
                $l++;
            }
        } else {
            $list = array();
        }
        return $list;
    }

    function getList($lists, $listtype = '', $current_login = 0) {
        $l = 0;
        $list_a = array();
        foreach ($lists as $list) {
            $user = User::findOne($list['user_id']);
            $profile = UserProfile::findOne($list['user_id']);
            $groupid = $profile->groupid;
            $date = date('Y-m-d');
            $list_a[$l]['user_id'] = $profile->user_id;
            $list_a[$l]['groupid'] = $groupid;
            $list_a[$l]['name'] = $profile->name;
            $list_a[$l]['user_verified'] = $user->admin_status;
            $getplan = DrsPanel::getUserPlan($profile->user_id);
            $list_a[$l]['current_plan'] = $getplan;
            $list_a[$l]['profile_image'] = Drspanel::getUserAvator($profile->user_id);
            $list_a[$l]['countrycode'] = $user->countrycode;
            $list_a[$l]['phone'] = $user->phone;
            $list_a[$l]['gender'] = $profile->gender;
            $list_a[$l]['blood_group'] = $profile->blood_group;
            $list_a[$l]['dob'] = $profile->dob;
            if (!empty($profile->dob)) {
                $list_a[$l]['age'] = Drspanel::getAge($profile->dob);
            } else {
                $list_a[$l]['age'] = '';
            }
            $list_a[$l]['degree'] = $profile->degree;

            if ($profile->groupid == Groups::GROUP_HOSPITAL) {
                $details = DrsPanel::getMyHospitalSpeciality($profile->user_id);
                $list_a[$l]['speciality'] = ($details['speciality']) ? explode(',', $details['speciality']) : [];
                $list_a[$l]['treatments'] = ($details['treatments']) ? explode(',', $details['treatments']) : [];
            } else {
                $list_a[$l]['speciality'] = $profile->speciality;
                $list_a[$l]['treatments'] = $profile->treatment;
            }
            $list_a[$l]['experience'] = $profile->experience;
            $list_a[$l]['description'] = strip_tags($profile->description);
            DrsPanel::getBookingShifts($list['user_id'], $date, $current_login);
            $list_a[$l]['address'] = DrsPanel::getBookingAddressShifts($profile->user_id, date('Y-m-d'));

            $list_a[$l]['fees'] = $profile->consultation_fees;
            $list_a[$l]['fees_discount'] = $profile->consultation_fees_discount;
            $list_a[$l]['address_short'] = DrsPanel::getAddressLine($profile->address_id);
            $list_a[$l]['address_show'] = DrsPanel::getAddressShow($profile->address_id);
            $list_a[$l]['address_full'] = $list_a[$l]['address_show'];

            $userAddress = UserAddress::findOne(['user_id' => $profile->user_id]);
            $listimages = array();
            if (!empty($userAddress)) {
                $list_a[$l]['hospital_images'] = DrsPanel::getAddressImageList($userAddress->id);
            } else {
                $list_a[$l]['hospital_images'] = array();
            }

            $list_a[$l]['show_fees'] = DrsPanel::getUserSetting($profile->user_id, 'show_fees');
            $rating = Drspanel::getRatingStatus($profile->user_id);
            $list_a[$l]['rating'] = $rating['rating'];

            $lat = Drspanel::getLatLong($profile->user_id);
            $list_a[$l]['lat'] = $lat['lat'];
            $list_a[$l]['lng'] = $lat['lng'];

            if ($listtype == 'hospital_doctors') {
                $list_a[$l]['status'] = DrsPanel::sendRequestCheck($current_login, $profile->user_id);
            } else {
                $list_a[$l]['status'] = DrsPanel::sendRequestCheck($profile->user_id, $current_login);
            }

            if ($current_login !== $profile->user_id) {
                $current_group = DrsPanel::getusergroupalias($current_login);
                if ($current_group == Groups::GROUP_HOSPITAL_LABEL) {
                    $firstAddress = DrsPanel::hospitalDoctorFees($current_login, $profile->user_id);

                    $list_a[$l]['fees'] = $firstAddress['consultation_fees'];
                    $list_a[$l]['fees_discount'] = $firstAddress['consultation_fees_discount'];
                }
            }


            $l++;
        }
        return $list_a;
    }

    function treatment($speciality, $user_id = '') {
        $arraytreat = array();
        $getId = DrsPanel::getIDOfMetaKey('speciality');
        $speciality_id = MetaValues::findOne(['key' => $getId, 'value' => $speciality]);
        if (!empty($speciality_id)) {
            $treatments = MetaValues::find()->where(['parent_key' => $speciality_id->id])->all();
            $t = 0;
            $all_active_values = array();
            foreach ($treatments as $treat) {
                $all_active_values[] = $treat->value;

                $arraytreat[$t]['id'] = $treat->id;
                $arraytreat[$t]['value'] = $treat->value;
                $arraytreat[$t]['label'] = $treat->label;
                $t++;
            }

            if (!empty($user_id)) {
                $profile = UserProfile::findOne($user_id);
                $treatments = $profile->treatment;
                if (!empty($treatments)) {
                    $treatments = explode(',', $treatments);
                    foreach ($treatments as $treatment) {
                        if (!in_array($treatment, $all_active_values)) {
                            $checkValue = MetaValues::find()->where(['parent_key' => $speciality_id->id, 'value' => $treatment])->one();
                            if (!empty($checkValue)) {
                                $arraytreat[$t]['id'] = $checkValue->id;
                                $arraytreat[$t]['value'] = $checkValue->value;
                                $arraytreat[$t]['label'] = $checkValue->label;
                                $t++;
                            }
                        }
                    }
                }
            }
            return $arraytreat;
        }
    }

    function getCurrentAffair($checkForCurrentShift, $doctor_id, $date, $shift_check = '', $slots = array()) {
        $response = array();
        if ($checkForCurrentShift['status'] == 'error') {
            $response["status"] = 1;
            $response["error"] = false;
            $response['message'] = 'No Shifts for today';
        } elseif ($checkForCurrentShift['status'] == 'success') {
            $response["status"] = 1;
            $response["error"] = false;
            $response['schedule_id'] = $checkForCurrentShift['shift_id'];
            $response['shift_label'] = $checkForCurrentShift['shift_label'];
            $response['date'] = $date;
            $response['is_started'] = false;
            $response['is_completed'] = false;
            $response['all_shifts'] = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift['shift_id'], $slots, $shift_check);
            $response['data'] = [];
            $response['message'] = 'Shift not started';
        } else {
            $shift = $checkForCurrentShift['shift_id'];
            if ($shift_check == '') {
                $getAppointments = DrsPanel::getCurrentAppointmentsAffairs($doctor_id, $date, $shift);
            } elseif ($shift_check == $shift) {
                $getAppointments = DrsPanel::getCurrentAppointmentsAffairs($doctor_id, $date, $shift);
            } else {
                $getAppointments = array();
            }
            $response["status"] = 1;
            $response["error"] = false;
            if ($checkForCurrentShift['status'] == 'success_appointment_completed') {
                $response['is_started'] = false;
                $response['is_completed'] = true;
                $response['all_shifts'] = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift['shift_id'], $slots, $shift_check);
                $response['data'] = [];
                $response['message'] = 'Success';
            } else {
                $response['schedule_id'] = $checkForCurrentShift['shift_id'];
                $response['shift_label'] = $checkForCurrentShift['shift_label'];
                $response['date'] = $date;
                $response['is_started'] = true;
                $response['is_completed'] = false;
                $response['all_shifts'] = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift['shift_id'], $slots, $shift_check);
                $response['data'] = $getAppointments;
                $response['message'] = 'Appointment List';
            }
        }
        return $response;
    }

    function checkProfileInactive($id, $type = '') {
        $user = User::findOne($id);
        $group = $user->groupid;

        if ($group == Groups::GROUP_DOCTOR || $group == Groups::GROUP_HOSPITAL) {
            if ($type == '') {
                if ($user->admin_status == User::STATUS_ADMIN_APPROVED || $user->admin_status == User::STATUS_ADMIN_LIVE_APPROVED) {
                    return false;
                }
            } else {
                if ($type == 'myshifts') {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    function getLocationUsersArray($params) {
        $userLocation = array();
        if (isset($params['lat']) && isset($params['lng']) && $params['lat'] != '' && $params['lng'] != '') {
            $latitude = $params['lat'];
            $longitude = $params['lng'];
            $userLocation = DrsPanel::getLocationUserList($latitude, $longitude);
        } elseif (isset($params['city_id']) && $params['city_id'] != '' && isset($params['city_name']) && $params['city_name'] != '') {
            $city_id = $params['city_id'];
            $latlong = DrsPanel::getCityLatLong($city_id);
            if (!empty($latlong)) {
                $latitude = $latlong['lat'];
                $longitude = $latlong['lng'];
                $userLocation = DrsPanel::getLocationUserList($latitude, $longitude);
            }
        } else {
            $city_id = 15098;
            $latlong = DrsPanel::getCityLatLong($city_id);
            if (!empty($latlong)) {
                $latitude = $latlong['lat'];
                $longitude = $latlong['lng'];
                $userLocation = DrsPanel::getLocationUserList($latitude, $longitude);
            }
        }
        return $userLocation;
    }

    public function actionGetRefundStatus($params) {
        $response = array();
        if (isset($params['appointment_id']) && $params['appointment_id'] != '') {
            $appointmentID = $params['appointment_id'];
            $getAppDetail = Transaction::find()->where(['appointment_id' => $appointmentID, 'type' => 'refund'])->one();
            $paytmResponse = json_decode($getAppDetail['paytm_response']);
            $refundResponse = Payment::get_refund_status($paytmResponse, $appointmentID);
            if ($refundResponse) {
                $html = $refundResponse['body']['resultInfo']['resultMsg'];
                if ($refundResponse['body']['resultInfo']['resultStatus'] == 'TXN_SUCCESS') {
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['message'] = $html;
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = $html;
                }
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Missing required fields';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionGetAppointmentReport() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        //print_r($params);die;
        Yii::info($params, __METHOD__);
        if (isset($params['dateFrom']) && $params['dateFrom'] != '' && isset($params['dateTo']) && $params['dateTo'] != '' && isset($params['shiftid']) && $params['shiftid'] != '') {
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
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'success';
                $response['data'] = Url::to('@frontendUrl') . '/statement.pdf';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Statement not available';
                $response['data'] = '';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Missing required fields';
            $response['data'] = '';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionDeleteAppointment() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        if (isset($params['dateFrom']) && $params['dateFrom'] != '' && isset($params['dateTo']) && $params['dateTo'] != '') {
            $deleteAppointment = DrsPanel::deleteAppointment($params);
            if ($deleteAppointment['status'] == 'success') {
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'success';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'error';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Missing required fields';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionMyPayments() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        Yii::info($params, __METHOD__);
        if (isset($params['user_id']) && $params['user_id'] != '') {
            $userAppointment = UserAppointment::getPaymentHistory($params['user_id']);
            foreach ($userAppointment as $paymentData) {
                $txnId = json_decode($paymentData['paytm_response']);
                $doctorAddress = UserAddress::findOne(['id' => $paymentData['doctor_address_id']]);
                $row['appointment_id'] = $paymentData['appointment_id'];
                $row['patientname'] = $paymentData['user_name'];
                $row['patientphone'] = $paymentData['user_phone'];
                $row['token'] = $paymentData['token'];
                $row['doctor_name'] = $paymentData['doctor_name'];
                $row['hospitalname'] = $doctorAddress['type'] . ' ' . $doctorAddress['name'];
                $row['hospitaladdress'] = $doctorAddress['address'];
                $row['shift_label'] = $paymentData['shift_label'];
                $row['shift_name'] = $paymentData['shift_name'];
                $row['amount'] = $paymentData['txn_amount'];
                $row['status'] = $paymentData['refund_by'] != '' ? 'Refund' : 'Completed';
                $row['bookdate'] = date('d M, Y', strtotime($paymentData['originate_date']));
                $data[] = $row;
            }
            $response["status"] = 1;
            $response["error"] = false;
            $response['message'] = 'success';
            $response['data'] = $data;
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Missing required fields';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

    public function actionPrintReceipt() {
        $response = $data = $required = array();
        $params = Yii::$app->request->post();
        //print_r($params);die;
        Yii::info($params, __METHOD__);
        if (isset($params['appointment_id']) && $params['appointment_id'] != '') {
            $appointMentID = $params['appointment_id'];
            $model = DrsPanel::getBookingData($appointMentID);

            if (!empty($model)) {
                $content = $this->renderPartial('//layouts/_printView', ['receiptData' => $model]);

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
                    // set mPDF properties on the fly
                    'options' => ['title' => 'Receipt'],
                    // call mPDF methods on the fly
                    'methods' => [
                        'SetTitle' => 'Payment Receipt - drspanel.in',
                        'SetSubject' => 'Payment Receipt',
                        'SetHeader' => ['DrsPanel Payment Receipt||Generated On: ' . date("r")],
                        'SetFooter' => ['|Page {PAGENO}|'],
                        'SetAuthor' => 'Drspanel',
                        'SetCreator' => 'Drspanel',
                        'SetKeywords' => 'Payment Receipt',
                    ]
                ]);

                $pdf->filename = 'receipt.pdf';

                // return the pdf output as per the destination setting
                $pdf->render();
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'success';
                $response['data'] = Url::to('@frontendUrl') . '/receipt.pdf';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Statement not available';
                $response['data'] = '';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Missing required fields';
            $response['data'] = '';
        }
        Yii::info($response, __METHOD__);
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return $response;
    }

}
