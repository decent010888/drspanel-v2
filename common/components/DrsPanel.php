<?php

namespace common\components;

use backend\models\AddScheduleForm;
use Codeception\Platform\Group;
use common\models\AppointmentHistory;
use common\models\Areas;
use common\models\HospitalAttender;
use common\models\HospitalSpecialityTreatment;
use common\models\PatientMemberFiles;
use common\models\PatientMemberRecords;
use common\models\Transaction;
use common\models\UserAboutus;
use common\models\UserAddressImages;
use common\models\UserAppointmentTemp;
use common\models\UserRatingLogs;
use common\models\UserReminder;
use common\models\UserScheduleGroup;
use common\models\UserScheduleSlots;
use common\models\UserServiceCharge;
use yii\data\ActiveDataProvider;
use common\models\Advertisement;
use common\models\Article;
use common\models\Countries;
use common\models\Groups;
use common\models\UserSettings;
use common\models\UserFavorites;
use common\models\MetaKeys;
use common\models\MetaValues;
use common\models\Tempuser;
use common\models\UserAddress;
use common\models\SliderImage;
use common\models\UserAppointment;
use common\models\UserFeesPercent;
use common\models\PatientMembers;
use common\models\UserRating;
use common\models\UserSchedule;
use common\models\UserScheduleDay;
use common\models\Cities;
use common\models\States;
use common\models\UserRequest;
use common\models\PopularMeta;
use frontend\modules\user\models\SignupForm;
use Yii;
use yii\db\Query;
use yii\helpers\Url;
use yii\web\UploadedFile;
use common\models\User;
use common\models\UserProfile;
use common\models\UserEducations;
use common\models\UserExperience;
use yii\helpers\ArrayHelper;
use yii\data\Pagination;

class DrsPanel {

    public static function getGenderList() {
        $gender = array(UserProfile::GENDER_MALE => 'Male', UserProfile::GENDER_FEMALE => 'Female', UserProfile::GENDER_OTHER => 'Other');
        return $gender;
    }

    public static function setTimezone() {
        $ipsess = Yii::$app->session;
        $ipAdd = $ipsess->get('IP');

        if (isset($ipAdd) && $ipAdd != '') {
            $ip = $ipAdd;
        } else {
            $ip = UserIp::getRealIp();
        }

        $timeZone = $ipsess->get('timezone');

        if (isset($timeZone) && $timeZone != '') {
            $zone = $timeZone;
        } else {
            $zone = UserIp::getIpTimeZone($ip);
        }
        return $zone;
    }

    public static function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        // trim
        $text = trim($text, '-');
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // lowercase
        $text = strtolower($text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }

    public static function userslugify($title) {
        $slug = preg_replace("/-$/", "", preg_replace('/[^a-z0-9]+/i', "-", strtolower($title)));
        if (empty($slug)) {
            return 'n-a';
        }
        $query = new yii\db\Query;
        $query->select(['COUNT(*) AS NumHits'])
                ->from(['user_profile profile'])
                ->where('profile.slug LIKE "' . $slug . '%"');
        $command = $query->createCommand();
        $results = $command->queryAll();
        $numHits = $results[0]['NumHits'];

        $countFinal = $numHits;

        return ($countFinal > 0) ? ($slug . '-' . $countFinal) : $slug;
    }

    public static function metavalues_slugify($title) {

        $slug = preg_replace("/-$/", "", preg_replace('/[^a-z0-9]+/i', "-", strtolower($title)));
        if (empty($slug)) {
            return 'n-a';
        }
        $query = new yii\db\Query;
        $query->select(['COUNT(*) AS NumHits'])
                ->from(['meta_values meta_values'])
                ->where('meta_values.slug LIKE "' . $slug . '%"');
        $command = $query->createCommand();
        $results = $command->queryAll();
        $countFinal = $results[0]['NumHits'];
        return ($countFinal > 0) ? ($slug . '-' . $countFinal) : $slug;
    }

    public static function prefixingList($user_type = null, $type = NULL) {
        $list = ['patient' => ['Mr.' => 'Mr.', 'Ms.' => 'Ms.', 'Mrs.' => 'Mrs.'], 'doctor' => ['Dr.' => 'Dr.', 'D.O.' => 'D.O.', 'PH.D' => 'PH.D']];
        return ($user_type == 'patient' || $user_type == 'doctor') ? (($type == 'list') ? array_values($list[$user_type]) : $list[$user_type]) : $list;
    }

    public function adminAccessUrl($logined, $type = NULL) {
        if ($type == 'hospital') {
            return ['detail'];
        } else if ($type == 'doctor') {
            return ['detail', 'view', 'update'];
        } else if ($type == 'patient') {
            return ['detail'];
        }
        return [];
    }

    public static function getBloodGroups() {
        $groups = array();
        $key = MetaKeys::findOne(['key' => 'blood_group']);
        if (!empty($key)) {
            $metavalues = MetaValues::find()->where(['key' => $key->id])->all();
            foreach ($metavalues as $values) {
                $groups[$values->value] = $values->label;
            }
        }
        return $groups;
    }

    public static function getPatientHeight() {
        return ['1' => '1 Feet', '2' => '2 Feet', '3' => '3 Feet', '4' => '4 Feet', '5' => '5 Feet', '6' => '6 Feet', '7' => '7 Feet', '8' => '8 Feet', '9' => '9 Feet', '10' => '10 Feet'];
    }

    public static function getInch() {
        for ($i = 1; $i <= 11; $i++) {
            $j[$i] = $i . ' Inch';
        }
        return $j;
    }

    public static function getMaritalStatus() {
        return ['1' => 'Married', '0' => 'Unmarried'];
    }

    public static function tablesList($alias = NULL) {
        $tables = ['User' => 'user'];
        if ($alias) {
            return $tables[$alias];
        }
        return $tables;
    }

    public static function tableName($alias) {
        return Drspanel::tablesList($alias);
    }

    public static function getCountryCode($dialcode = NULL) {
        if ($dialcode)
            $countryData = Countries::find()->where("dialcode != '' ")->andWhere(['dialcode' => $dialcode])->all();
        else
            $countryData = Countries::find()->where("dialcode != '' ")->all();
        $cData = array();
        foreach ($countryData as $dataCou) {
            $cData[$dataCou['dialcode']] = $dataCou['dialcode'];
        }
        return $cData;
    }

    public static function getStateList($country_id = NULL, $status = []) {
        if ($country_id) {
            $result = Countries::find()->where("dialcode != '' ")->andWhere(['dialcode' => $country_id])->all();
        } else {
            if (empty($status)) {
                $result = States::find()->where("code != '' ")->andWhere(['status' => 1])->select(['code as id', 'name'])->all();
            } else {
                $result = States::find()->where("code != '' ")->andWhere(['status' => $status])->select(['code as id', 'name'])->all();
            }
        }
        return $result;
    }

    public static function getCitiesList($state_id = NULL, $listby = NULL) {
        if ($state_id) {

            $state_id = ($listby == 'name') ? States::getIdByName($state_id) : $state_id;
            $result = Cities::find()->where(['status' => 1])->andWhere(['state_id' => $state_id])->select(['id', 'name'])->all();
        } else
            $result = Cities::find()->where(['status' => 1])->select(['id', 'name'])->all();

        return $result;
    }

    public static function getCityName($city_id) {
        $result = Cities::find()->where(['id' => $city_id])->one();
        return $result->name;
    }

    public static function getCityId($city = NULL, $state = NULL) {
        $state_id = States::getIdByName($state);
        $city_id = Cities::getIdByNameState($city, $state_id);
        return $city_id;
    }

    public static function getCityAreasList($city_id = NULL) {
        $result = array();
        if ($city_id) {
            $result = Areas::find()->where(['status' => 1])->andWhere(['city_id' => $city_id])->all();
        }

        return $result;
    }

    public static function getUserName($user_id) {
        $profile = UserProfile::findOne(['user_id' => $user_id]);
        $name = '';
        if (!empty($profile)) {
            if ($profile->name != '') {
                $name = $profile->name;
            }
        }
        return $name;
    }

    public static function checkmobileUpdate($user_id, $countrycode, $mobile, $userType) {
        $checkUser = User::findOne(['countrycode' => $countrycode, 'phone' => $mobile, 'groupid' => $userType]);
        if (!empty($checkUser)) {
            if ($user_id == $checkUser->id) {
                $response['type'] = 'success';
                $response['message'] = 'old';
            } else {
                $response['type'] = 'error';
                $response['message'] = 'Mobile number already registered with other';
            }
        } else {
            $response['type'] = 'success';
            $response['message'] = 'new';
        }
        return $response;
    }

    public static function checkemailUpdate($user_id, $email) {
        $checkUser = User::findOne(['email' => $email]);
        if (!empty($checkUser)) {
            if ($user_id == $checkUser->id) {
                $response['type'] = 'success';
                $response['message'] = 'old';
            } else {
                $response['type'] = 'error';
                $response['message'] = 'Email already registered with other';
            }
        } else {
            $response['type'] = 'success';
            $response['message'] = 'new';
        }
        return $response;
    }

    public static function sendOtpStep($mobile, $countrycode, $userType) {
        $response = array();
        $newuser = 'new';
        $checkUser = User::findOne(['countrycode' => $countrycode, 'phone' => $mobile, 'groupid' => $userType]);
        if (!empty($checkUser)) {
            $groupid = $checkUser->groupid;
            if ($userType == $groupid) {
                $newuser = 'old';
                $otp = DrsPanel::randomOTP();
                $checkUser->otp = $otp;
                $checkUser->mobile_verified = 0;
                if ($checkUser->save()) {
//                    $message = Yii::$app->mailer->compose('@common/mail/newuser', [
//                                'name' => $checkUser->name,
//                                'sendtouser' => $checkUser->email,
//                                'otp' => $otp
//                            ])
//                            ->setFrom(['contact@drspanel.in' => 'Drspanel'])
//                            ->setTo($checkUser->email)
//                            ->setSubject('Email OTP for mobile verification');
//                    $message->send();

                    $message = $otp . ' is the OTP for accessing your DrsPanel account. PLS DO NOT SHARE IT WITH ANYONE.';
                    $sendSms = Notifications::send_sms($message, $mobile, 'No', $countrycode, 1);
                    $response['userType'] = $newuser;
                    $response['type'] = 'success';
                    $response['countrycode'] = $countrycode;
                    $response['mobile'] = $mobile;
                } else {
                    $response['type'] = 'error';
                    $response['data'] = $checkUser->getErrors();
                    $response['message'] = 'Validation Errors';
                }
            } else {
                $response['type'] = 'error';
                $response['data'] = [];
                $response['message'] = 'Mobile number already registered as other';
            }
        } else {
            if ($userType == Groups::GROUP_ATTENDER) {
                $response['type'] = 'error';
                $response['data'] = 'Not Registered';
                $response['message'] = 'Mobile number not registered as attender';
            } else {
                $newuser = 'new';
                $checktemp = Tempuser::findOne(['countrycode' => $countrycode, 'phone' => $mobile, 'groupid' => $userType]);
                $otp = DrsPanel::randomOTP();
                if (!empty($checktemp)) {
                    $checktemp->groupid = $userType;
                    $checktemp->otp = $otp;
                } else {
                    $checktemp = new Tempuser();
                    $checktemp->countrycode = $countrycode;
                    $checktemp->phone = $mobile;
                    $checktemp->groupid = $userType;
                    $checktemp->otp = $otp;
                }
                if ($checktemp->save()) {
                    $message = $otp . ' is the OTP for accessing your DrsPanel account. PLS DO NOT SHARE IT WITH ANYONE.';
                    $sendSms = Notifications::send_sms($message, $mobile, 'No', $countrycode, 1);

                    $response['userType'] = $newuser;
                    $response['type'] = 'success';
                    $response['countrycode'] = $countrycode;
                    $response['mobile'] = $mobile;
                } else {
                    $response['type'] = 'error';
                    $response['data'] = $checktemp->getErrors();
                    $response['message'] = 'Validation Errors';
                }
            }
        }
        return $response;
    }

    public static function verifyOtpStep($mobile, $countrycode, $otp, $userType, $token = NULL, $device_type = NULL, $device_id = NULL) {
        $response = array();
        $newuser = 'new';
        $checkUser = User::findOne(['countrycode' => $countrycode, 'phone' => $mobile, 'groupid' => $userType]);
        if (!empty($checkUser)) {
            if ($otp == $checkUser->otp) {
                $newuser = 'old';
                $checkUser->mobile_verified = 1;
                if ($token)
                    $checkUser->token = $token;
                if ($device_type)
                    $checkUser->device_type = $device_type;
                if ($device_id)
                    $checkUser->device_id = $device_id;
                if ($checkUser->save()) {
                    $profile = UserProfile::findOne(['user_id' => $checkUser->id]);
                    $data_array = DrsPanel::profiledetails($checkUser, $profile, $userType);
                    $response['userType'] = $newuser;
                    $response['type'] = 'success';
                    $response['data'] = $data_array;
                } else {
                    $response['type'] = 'error';
                    $response['message'] = 'Please try again';
                    $response['data'] = $checkUser->getErrors();
                }
            } else {
                $response['type'] = 'error';
                $response['message'] = 'Otp not matched';
                $response['data'] = [];
            }
        } else {
            $newuser = 'new';
            $checktemp = Tempuser::findOne(['countrycode' => $countrycode, 'phone' => $mobile, 'groupid' => $userType]);
            if (!empty($checktemp)) {
                if ($otp == $checktemp->otp) {
                    $checktemp->mobile_verified = 1;
                    if ($checktemp->save()) {
                        $response['userType'] = $newuser;
                        $response['type'] = 'success';
                        $response['countrycode'] = $countrycode;
                        $response['mobile'] = $mobile;
                    } else {
                        $response['message'] = 'Please try again';
                        $response['type'] = 'error';
                        $response['data'] = $checktemp->getErrors();
                    }
                } else {
                    $response['type'] = 'error';
                    $response['message'] = 'Otp not matched';
                    $response['data'] = [];
                }
            } else {
                $response['message'] = 'Resend otp please';
                $response['type'] = 'error';
                $response['data'] = [];
            }
        }
        return $response;
    }

    public static function checkOtpVerified($mobile, $countrycode, $userType) {
        $checkUser = User::findOne(['countrycode' => $countrycode, 'phone' => $mobile, 'groupid' => $userType]);
        if (empty($checkUser)) {
            $checktemp = Tempuser::findOne(['countrycode' => $countrycode, 'phone' => $mobile, 'groupid' => $userType]);
            if (!empty($checktemp)) {
                if ($checktemp->mobile_verified == 1) {
                    $response['type'] = 'success';
                    $response['otp'] = $checktemp->otp;
                } else {
                    $response['type'] = 'verification_error';
                }
            } else {
                $response['type'] = 'verification_error';
            }
        } else {
            $response['type'] = 'already_registered';
        }
        return $response;
    }

    public static function profiledetails($user, $profile, $user_type, $current_login) {

        $data_array = array();
        $data_array['user_verified'] = $user->admin_status;
        $data_array['user_id'] = $profile->user_id;
        $data_array['name'] = $profile->name;
        $data_array['email'] = $user->email;
        $data_array['countrycode'] = $user->countrycode;
        $data_array['mobile'] = $user->phone;
        $data_array['mobile_verfied'] = $user->mobile_verified;
        $data_array['gender'] = $profile->gender;
        $data_array['blood_group'] = $profile->blood_group;
        $data_array['dob'] = $profile->dob;
        $data_array['slug'] = $profile->slug;
        if (!empty($profile->dob)) {
            $data_array['age'] = DrsPanel::getAge($profile->dob);
        } else {
            $data_array['age'] = '';
        }
        $date = date('Y-m-d');
        if ($profile->groupid == Groups::GROUP_DOCTOR) {

            if (!empty($profile->degree)) {
                $data_array['degree'] = explode(',', $profile->degree);
            } else {
                $data_array['degree'] = array();
            }
            if (!empty($profile->speciality)) {
                $data_array['speciality'] = explode(',', $profile->speciality);
            } else {
                $data_array['speciality'] = array();
            }

            if (!empty($profile->treatment)) {
                $data_array['treatment'] = explode(',', $profile->treatment);
            } else {
                $data_array['treatment'] = array();
            }

            $data_array['experience'] = $profile->experience;
            $data_array['description'] = strip_tags($profile->description);
            $data_array['show_fees'] = DrsPanel::getUserSetting($profile->user_id, 'show_fees');


            $data_array['articles'] = DrsPanel::getMyArticles($profile->user_id);
            $list = DrsPanel::getDoctorExperience($profile->user_id);
            $data_array['experience_list'] = DrsPanel::listExperience($list);

            $list = DrsPanel::getDoctorEducation($profile->user_id);
            $data_array['education_list'] = DrsPanel::listEducation($list);
            $data_array['services'] = ($profile->services) ? explode(',', $profile->services) : [];

            $listarray = DrsPanel::getRatingList($profile->user_id, 0, 2);
            $data_array['reviews'] = $listarray;

            $data_array['fees'] = $profile->consultation_fees;
            $data_array['fees_discount'] = $profile->consultation_fees_discount;

            $rating = DrsPanel::getRatingStatus($profile->user_id);
            $data_array['rating'] = $rating['rating'];
            DrsPanel::getBookingShifts($profile->user_id, $date, $current_login);
            $data_array['address'] = DrsPanel::getBookingAddressShifts($profile->user_id, date('Y-m-d'));
            $data_array['address_show'] = DrsPanel::getAddressShow($profile->address_id);
        } elseif ($profile->groupid == Groups::GROUP_HOSPITAL) {

            $data_array['services'] = ($profile->services) ? explode(',', $profile->services) : [];
            $data_array['speciality'] = ($profile->speciality) ? explode(',', $profile->speciality) : [];
            $data_array['treatment'] = ($profile->treatment) ? explode(',', $profile->treatment) : [];
            $data_array['aboutus'] = DrsPanel::getAboutUs($profile->user_id);

            $rating = DrsPanel::getRatingStatus($profile->user_id);
            $data_array['rating'] = $rating['rating'];

            $data_array['fees'] = $profile->consultation_fees;
            $data_array['fees_discount'] = $profile->consultation_fees_discount;

            $userAddress = UserAddress::findOne(['user_id' => $profile->user_id]);

            $data_array['address'] = Drspanel::getAddress($profile->user_id);
            $listimages = array();
            if (!empty($userAddress)) {
                $data_array['address_show'] = DrsPanel::getAddressShow($userAddress->id);
                $data_array['hospital_images'] = DrsPanel::getAddressImageList($userAddress->id);
            } else {
                $data_array['address_show'] = '';
                $data_array['address'] = $userAddress;
                $data_array['hospital_images'] = array();
            }



            $path = Yii::getAlias('@frontendUrl');
            $default_image = 'hospitals_img1.jpg';
            $data_array['home_image'] = $path . '/images/' . $default_image;
        } elseif ($profile->groupid == Groups::GROUP_PATIENT) {
            $data_array['location'] = $profile->location;
        } elseif ($profile->groupid == Groups::GROUP_ATTENDER) {
            $data_array['created_by'] = $profile->created_by;
            $data_array['doctor_id'] = $user->parent_id;
        }
        $data_array['service_charge'] = 0; //DrsPanel::getMetaData('service_charge');
        $data_array['profile_image'] = DrsPanel::getUserAvator($profile->user_id);
        $data_array['groupid'] = $profile->groupid;
        $data_array['user_type'] = $user_type;
        $profilePercentage = DrsPanel::calculatePercentage($profile->user_id);
        $data_array['complete_percentage'] = $profilePercentage;
        $lat = Drspanel::getLatLong($profile->user_id);
        $data_array['lat'] = $lat['lat'];
        $data_array['lng'] = $lat['lng'];
        return $data_array;
    }

    public static function getRatingList($doctor_id, $offset, $recordlimit) {
        $totalpages = 0;
        $count_result = 0;
        $lists = new Query();
        $lists = UserRatingLogs::find();
        $lists->where(['doctor_id' => $doctor_id]);

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

        $list_a = DrsPanel::getReviews($lists);
        $data_array = array_values($list_a);

        return array('list' => $data_array, 'totallist' => $totallist);
    }

    public static function listExperience($list) {
        $lists = array();
        $k = 0;
        foreach ($list as $key => $value) {
            $value['start'] = date('Y-m-d', $value['start']);
            $value['end'] = date('Y-m-d', $value['end']);
            $lists[$k] = $value;
            $k++;
        }
        if (!empty($lists)) {
            usort($lists, function ($a, $b) {
                return $a['end'] - $b['end'];
            });
        }
        return $lists;
    }

    public static function listEducation($list) {
        $lists = array();
        $k = 0;
        foreach ($list as $key => $value) {
            $value['start'] = date('Y-m-d', $value['start']);
            $value['end'] = date('Y-m-d', $value['end']);
            $lists[] = $value;
            $k++;
        }
        if (!empty($lists)) {
            usort($lists, function ($a, $b) {
                return $a['end'] - $b['end'];
            });
        }
        return $lists;
    }

    public static function hospitalProfile($id = NULL) {
        $data_array = [];
        if ($user = User::find()->andWhere(['id' => $id])->andWhere(['groupid' => Groups::GROUP_HOSPITAL])->one()) {
            $userProfile = UserProfile::findOne($id);
            $data_array['user_verified'] = $user->admin_status;
            $data_array['id'] = $user['userProfile']['user_id'];
            $data_array['name'] = $user['userProfile']['name'];
            $data_array['email'] = $user->email;
            $data_array['countrycode'] = $user->countrycode;
            $data_array['mobile'] = $user->phone;
            $data_array['mobile_verfied'] = $user->mobile_verified;
            $data_array['gender'] = $user['userProfile']['gender'];
            $data_array['services'] = ($user['userProfile']['services']) ? explode(',', $user['userProfile']['services']) : [];
            $data_array['service_charge'] = DrsPanel::getMetaData('service_charge');
            $data_array['profile_image'] = DrsPanel::getUserAvator($user->id);
            $data_array['groupid'] = $user->groupid;
            $data_array['user_type'] = 'Hospital';
            $data_array['description'] = strip_tags($user['userProfile']['description']);
            $data_array['address'] = Drspanel::getAddress($user->id);
            $rating = Drspanel::getRatingStatus($user->id);
            $data_array['rating'] = $rating['rating'];
            $details = DrsPanel::getMyHospitalSpeciality($user->id);
            $data_array['speciality'] = $details['speciality'];
            $data_array['treatments'] = $details['treatments'];
            $data_array['address_show'] = DrsPanel::getAddressShow($userProfile->address_id);
        }
        return $data_array;
    }

    public static function calculatePercentage($user_id) {
        $userProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
        $user = User::find()->where(['id' => $user_id])->one();
        $maximumPoints = 100;
        $hasCompletedProfileImage = 0;
        $hasCompletedDegree = 0;
        $hasCompletedSpeciality = 0;
        $hasCompletedTreatment = 0;
        $hasCompletedExperience = 0;
        $hasCompletedServices = 0;
        $hasCompletedDescription = 0;
        $hasCompletedGender = 0;
        $hasCompletedDob = 0;
        $hasCompletedDefault = 0;
        $hasCompletedname = 0;
        $hasCompletedemail = 0;
        $hasFilledMobileNo = 0;

        if ($userProfile->groupid == Groups::GROUP_DOCTOR) {
            if ($userProfile->avatar != "" && $userProfile->avatar_path != "" && $userProfile->avatar_base_url != "") {
                $hasCompletedProfileImage = 10;
            }
            if ($userProfile->degree != "") {
                $hasCompletedDegree = 10;
            }
            if ($userProfile->speciality != "") {
                $hasCompletedSpeciality = 10;
            }
            if ($userProfile->treatment != "") {
                $hasCompletedTreatment = 10;
            }
            if ($userProfile->experience != "") {
                $hasCompletedExperience = 10;
            }
            if (!empty($userProfile->services != "")) {
                $hasCompletedServices = 10;
            }
            if ($userProfile->description != "") {
                $hasCompletedDescription = 10;
            }
            if ($userProfile->gender != "") {
                $hasCompletedGender = 10;
            }
            if ($userProfile->dob != "") {
                $hasCompletedDob = 10;
            }
            if ($userProfile->name != "") {
                $hasCompletedDefault = 10;
            }
            $profilepercentage = ($hasCompletedProfileImage + $hasCompletedDegree + $hasCompletedSpeciality + $hasCompletedTreatment + $hasCompletedExperience + $hasCompletedServices + $hasCompletedDescription + $hasCompletedGender + $hasCompletedDob + $hasCompletedDefault) * $maximumPoints / 100;
        } elseif ($userProfile->groupid == Groups::GROUP_HOSPITAL) {
            if ($userProfile->avatar != "" && $userProfile->avatar_path != "" && $userProfile->avatar_base_url != "") {
                $hasCompletedProfileImage = 10;
            }
            if ($userProfile->name != "") {
                $hasCompletedname = 10;
            }
            if ($user->email != "") {
                $hasCompletedemail = 10;
            }
            if ($user->phone != "") {
                $hasFilledMobileNo = 10;
            }
            if ($userProfile->dob != "") {
                $hasCompletedDob = 10;
            }
            if (!empty($userProfile->services != "")) {
                $hasCompletedServices = 10;
            }

            $hasFilledHospitalName = 0;
            $hasFilledAddress = 0;
            $hasFilledArea = 0;
            $hasFilledState = 0;
            $hasFilledCity = 0;
            $useraddressList = UserAddress::findOne(['user_id' => $user_id]);
            if (!empty($useraddressList)) {
                if (!empty($useraddressList->name != "")) {
                    $hasFilledHospitalName = 10;
                }
                if ($useraddressList->address != "") {
                    $hasFilledAddress = 5;
                }
                if ($useraddressList->area != "") {
                    $hasFilledArea = 10;
                }
                if ($useraddressList->city != "") {
                    $hasFilledCity = 10;
                }
                if ($useraddressList->state != "") {
                    $hasFilledState = 5;
                }
            }

            $profilepercentage = ($hasCompletedProfileImage + $hasCompletedDob + $hasCompletedname + $hasCompletedemail + $hasFilledMobileNo + $hasCompletedServices + $hasFilledHospitalName + $hasFilledAddress + $hasFilledArea + $hasFilledCity + $hasFilledState) * $maximumPoints / 100;
        } else {
            if ($userProfile->avatar != "" && $userProfile->avatar_path != "" && $userProfile->avatar_base_url != "") {
                $hasCompletedProfileImage = 30;
            }
            if ($userProfile->gender != "") {
                $hasCompletedGender = 20;
            }
            if ($userProfile->dob != "") {
                $hasCompletedDob = 20;
            }
            if ($userProfile->name != "") {
                $hasCompletedDefault = 30;
            }
            $profilepercentage = ($hasCompletedProfileImage + $hasCompletedGender + $hasCompletedDob + $hasCompletedDefault) * $maximumPoints / 100;
        }
        return round($profilepercentage);
    }

    public static function getWeekArray() {
        $weeks = array('Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday', 'Sunday' => 'Sunday');
        return $weeks;
    }

    public static function getWeekShortArray() {
        $weeks = array('Monday' => 'M', 'Tuesday' => 'T', 'Wednesday' => 'W',
            'Thursday' => 'T', 'Friday' => 'F', 'Saturday' => 'S', 'Sunday' => 'S');
        return $weeks;
    }

    public static function getShifts($user_id, $weekday = '') {
        $shifts = array();
        $getSchedule = UserSchedule::find()->where(['user_id' => $user_id])->all();
        if (!empty($getSchedule)) {
            $week_array = DrsPanel::getWeekArray();
            if ($weekday != '') {
                $week_array = array($weekday);
            }
            $slots = array();
            $w = 0;
            foreach ($week_array as $week) {
                $shifts[$w]['weekday'] = $week;
                $getDaySchedule = UserSchedule::find()->where(['user_id' => $user_id, 'weekday' => $week])->all();
                foreach ($getDaySchedule as $schedule) {

                    $getshiftDetail = DrsPanel::getShiftDetail($user_id, $week, $schedule->shift);
                    if ($getshiftDetail !== '' && !empty($getshiftDetail)) {
                        $shifts[$w]['shifts_type'][$schedule->shift] = $getshiftDetail['shift_type'];
                    } else {
                        $shifts[$w]['shifts_type'][$schedule->shift] = 'unavailable';
                    }
                    $shifts[$w]['shifts'][$schedule->shift] = $getshiftDetail;
                }
                $w++;
            }
            if ($weekday != '') {
                return $shifts[0];
            } else {
                return $shifts;
            }
        } else {
            if ($weekday != '') {
                $shifts = '';
            }
            return $shifts;
        }
    }

    public static function getShiftDetail($user_id, $week, $shift, $date = '') {
        if ($date != '') {
            $addSchedule = UserScheduleDay::find()->where(['user_id' => $user_id, 'weekday' => $week, 'date' => $date, 'shift' => $shift])->one();
            if (empty($addSchedule)) {
                $addSchedule = UserSchedule::find()->where(['user_id' => $user_id, 'weekday' => $week, 'shift' => $shift])->one();
            }
        } else {
            $addSchedule = UserSchedule::find()->where(['user_id' => $user_id, 'weekday' => $week, 'shift' => $shift])->one();
        }
        if (!empty($addSchedule)) {

            $schedule = UserSchedule::find()->where(['user_id' => $user_id, 'weekday' => $week, 'shift' => $shift])->one();

            $address = UserAddress::findOne($addSchedule->address_id);
            $stime = date('h:i a', $addSchedule->start_time);
            $etime = date('h:i a', $addSchedule->end_time);
            $res = array(
                'id' => $schedule['id'],
                'doctor_id' => $schedule['user_id'],
                'doctor_name' => DrsPanel::getUserName($schedule['user_id']),
                'schedule_id' => $schedule['id'],
                'time' => $stime . ' - ' . $etime,
                'shift_name' => 'Shift ' . $stime . ' - ' . $etime,
                'start_time' => $stime,
                'end_time' => $etime,
                'fees' => $addSchedule->consultation_fees,
                'consultation_fees' => $addSchedule->consultation_fees,
                'consultation_days' => $addSchedule->consultation_days,
                'consultation_price_show' => $addSchedule->consultation_show,
                'consultation_fees_discount' => $addSchedule->consultation_fees_discount,
                'emergency_fees' => $addSchedule->emergency_fees,
                'emergency_days' => $addSchedule->emergency_days,
                'emergency_price_show' => $addSchedule->emergency_show,
                'emergency_fees_discount' => $addSchedule->emergency_fees_discount,
                'patient_limit' => $addSchedule->patient_limit,
                'address_id' => $addSchedule->address_id,
                'address' => DrsPanel::getAddressLine($address),
                'city' => isset($address->city) ? $address->city : 0,
                'hospital_name' => $address['name'],
                'status' => $addSchedule->status,
                'booking_closed' => isset($addSchedule->booking_closed) ? $addSchedule->booking_closed : 0
            );
            if ($date != '') {
                $res['booked'] = DrsPanel::generateToken($user_id, $date) - 1;
            }
            return $res;
        } else {
            return '';
        }
    }

    public static function getAllShiftDetail($user_id, $week = '', $date = '') {
        $shifts = [];
        $week_array = DrsPanel::getWeekArray();
        $i = 0;
        foreach ($week_array as $key => $dayofweek) {

            $addSchedule = UserSchedule::find()->where(['user_id' => $user_id, 'weekday' => $dayofweek])->all();
            $shifts[$i]['weekday'] = $dayofweek;
            foreach ($addSchedule as $key => $value) {
                $shifts[$i]['shifts'][] = Drspanel::getShiftDetails($value->id);
            }
            $i++;
        }
        return $shifts;
    }

    public static function getShiftDetails($shift_id, $date = '') {
        $addSchedule = UserSchedule::findOne($shift_id);
        if (!empty($addSchedule)) {
            $address = UserAddress::findOne($addSchedule->address_id);
            $stime = date('h:i a', $addSchedule->start_time);
            $etime = date('h:i a', $addSchedule->end_time);
            $res = array(
                'id' => $addSchedule->id,
                'time' => $stime . ' - ' . $etime,
                'start_time' => $stime,
                'end_time' => $etime,
                'appointment_time_duration' => $addSchedule->appointment_time_duration,
                'consultation_fees' => $addSchedule->consultation_fees,
                'consultation_fees_discount' => $addSchedule->consultation_fees_discount,
                'emergency_fees' => $addSchedule->emergency_fees,
                'emergency_fees_discount' => $addSchedule->emergency_fees_discount,
                'patient_limit' => $addSchedule->patient_limit,
                'address_id' => $addSchedule->address_id,
                'address' => DrsPanel::getAddressLine($address),
                'hospital_name' => $address['name'],
                'status' => $addSchedule->status,
                'can_edit' => $addSchedule->is_edit
            );
            return $res;
        } else {
            return '';
        }
    }

    public static function getShiftTime($user_id, $week, $shift) {
        $addSchedule = UserSchedule::find()->where(['user_id' => $user_id, 'shift' => $shift, 'weekday' => $week])->one();
        if (!empty($addSchedule)) {
            return $addSchedule->start_time . ' - ' . $addSchedule->end_time;
        } else {
            return '';
        }
    }

    public static function getAllProfileStatus() {
        $status = array('pending' => 'Pending',
            'requested' => 'Requested for Live'
            , 'approved' => 'Profile Approved', 'live_approved' => 'Profile Live');
        return $status;
    }

    public static function getAllPlanStatus() {
        $status = array('sponsered' => 'Sponsered',
            'paid' => 'Paid'
            , 'other' => 'Other');
        return $status;
    }

    public static function getLiveStatus($user_id) {
        $user = User::findOne($user_id);
        $status = $user->admin_status;
        if ($status == 'pending') {
            return 'Pending';
        } elseif ($status == 'requested') {
            return 'Requested For Live';
        } elseif ($status == 'approved') {
            return 'Profile Approved';
        } elseif ($status == 'live_approved') {
            return 'Profile Live';
        } else {
            return "";
        }
    }

    public static function getRatingStatus($user_id) {
        $user = UserRating::find()->where(['user_id' => $user_id])->one();
        if (!empty($user)) {
            $status = $user->show_rating;
            if ($status == 'User') {
                return array('type' => 'User Rating', 'rating' => $user->users_rating);
            } elseif ($status == 'Admin') {
                return array('type' => 'Admin Rating', 'rating' => $user->admin_rating);
            } else {
                return array('type' => 'User Rating', 'rating' => 0);
            }
        } else {
            return array('type' => 'User Rating', 'rating' => 0);
        }
    }

    public static function getAdminCommission($user_id, $type) {
        $user = UserFeesPercent::find()->where(['user_id' => $user_id, 'type' => $type])->one();
        if (!empty($user)) {
            if ($type == 'booking') {
                return $user->admin;
            } else {
                return $user->admin;
            }
        } else {
            return 0;
        }
    }

    public static function getCommission($user_id, $type) {
        $user = UserFeesPercent::find()->where(['user_id' => $user_id, 'type' => $type])->one();
        if (!empty($user)) {
            if ($type == 'booking') {
                return array('admin' => $user->admin, 'user_provider' => $user->user_provider);
            } else {
                return array('admin' => $user->admin, 'user_provider' => $user->user_provider, 'user_patient' => $user->user_patient);
            }
        } else {
            return array('admin' => 0, 'user_provider' => 0, 'user_patient' => 0);
        }
    }

    public static function getMyArticles($user_id) {
        $articles = array();
        $lists = Article::find()->where(['user_id' => $user_id])->all();
        $l = 0;
        foreach ($lists as $list) {
            $articles[$l]['id'] = $list->id;
            $articles[$l]['user_id'] = $list->user_id;
            $articles[$l]['title'] = $list->title;
            $articles[$l]['body'] = $list->body;
            $articles[$l]['status'] = $list->status;
            $l++;
        }
        return $articles;
    }

    public static function getDateShifts($user_id, $date) {
        $shifts = array();
        $getScheduleDay = UserScheduleDay::find()->where(['user_id' => $user_id, 'date' => $date])->all();
        $getSchedule = UserSchedule::find()->where(['user_id' => $user_id])->all();
        if (!empty($getScheduleDay || !empty($getSchedule))) {
            $slots = array(UserSchedule::SHIFT_MORNING, UserSchedule::SHIFT_AFTERNOON, UserSchedule::SHIFT_EVENING);

            $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            $dayofweek = date('w', strtotime($date));
            $week = $days[$dayofweek];
            foreach ($slots as $slot) {
                $shifts['shifts'][$slot] = DrsPanel::getShiftDetail($user_id, $week, $slot, $date);
            }
            return $shifts;
        } else {
            return $shifts;
        }
    }

    public static function getCalendarShifts($user_id, $date) {
        $shifts = array();
        $getScheduleDay = UserScheduleDay::find()->where(['user_id' => $user_id, 'date' => $date])->all();
        $getSchedule = UserSchedule::find()->where(['user_id' => $user_id])->all();
        if (!empty($getScheduleDay || !empty($getSchedule))) {
            $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
            $dayofweek = date('w', strtotime($date));
            $week = $days[$dayofweek];
            $total_limit = 0;
            $total_booked = 0;
            foreach ($getScheduleDay as $slot) {
                $detailsadd = DrsPanel::getShiftDetail($user_id, $week, $date);
                $shifts['shifts'][] = $detailsadd;
                if (!empty($detailsadd)) {
                    $total_limit += $detailsadd['patient_limit'];
                    $total_booked += $detailsadd['booked'];
                }
            }
            $shifts['total_limit'] = $total_limit;
            $shifts['total_booked'] = $total_booked;
            return $shifts;
        } else {
            return $shifts;
        }
    }

    public static function getAge($date) {
        $age = 0;
        $from = new \DateTime($date);
        $to = new \DateTime('today');
        $age = $from->diff($to)->y;
        if ($age > 1) {
            return $age . ' yrs';
        } else {
            return $age . ' yr';
        }
    }

    public static function validationErrorMessage($getErrors) {
        $response = array();
        $errorkey_obj = array();
        foreach ($getErrors as $errorkey => $error) {
            $errorkey_obj[] = $errorkey;
        }
        $response["status"] = 0;
        $response["error"] = true;
        $fields_req = implode(',', $errorkey_obj);
        $response['data'] = $getErrors;
        $response['message'] = 'Validation error on field: ' . $fields_req;
        return $response;
    }

    public static function addAppointment($data, $type) {
        $appointment = new UserAppointment();
        $appointment->load($data);
        $shift_name = $data['UserAppointment']['shift_name'];
        $shift_date = $data['UserAppointment']['date'];
        $getTime = Drspanel::getAppointmentTime($shift_name, $shift_date);
        $appointment->appointment_time = $getTime['appointment_time'];
        $appointment->actual_time = $getTime['actual_time'];

        if ($appointment->save()) {

            if ($type == 'doctor') {
                $schedule_check = UserScheduleGroup::find()->where(['user_id' => $appointment->doctor_id, 'date' => $appointment->date, 'status' => 'current', 'schedule_id' => $appointment->schedule_id])->orderBy('shift asc')->one();
                if (!empty($schedule_check)) {
                    $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $appointment->doctor_id, 'date' => $appointment->date, 'schedule_id' => $appointment->schedule_id, 'status' => UserAppointment::STATUS_ACTIVE])->orderBy('token asc')->one();
                    if (empty($checkFirstAppointment)) {
                        $appointment->status = UserAppointment::STATUS_ACTIVE;
                        $appointment->save();
                    } else {
                        if ($checkFirstAppointment->token > $appointment->token) {
                            $checkFirstAppointment->status = UserAppointment::STATUS_AVAILABLE;
                            $checkFirstAppointment->save();

                            $appointment->status = UserAppointment::STATUS_ACTIVE;
                            $appointment->save();
                        }
                    }
                }
            }

            $schedule_id = $data['UserAppointment']['schedule_id'];
            $schedule = UserSchedule::findOne($schedule_id);
            $schedule->is_edit = 0;
            $schedule->save();

            $slot_id = $data['UserAppointment']['slot_id'];
            $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
            $slot->status = 'booked';
            $slot->save();

            if ($type == 'doctor') {
                $sendNotification = Notifications::appointmentSmsNotification($appointment->id, 'appointment', 'doctor');
                $addTransactionRow = Logs::addTransactionRow($appointment->id, 0, $type = 'pay', $txn_type = 'booking', $service_charge = 0);
                $addLog = Logs::appointmentLog($appointment->id, 'Appointment added by doctor');
            } else {
                $sendNotification = Notifications::appointmentSmsNotification($appointment->id, 'appointment', 'patient');
                $addLog = Logs::appointmentLog($appointment->id, 'Appointment added by patient');
            }

            $response["status"] = 1;
            $response["error"] = false;
            $response['type'] = 'success';
            $response['data'] = $appointment->id;
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['type'] = 'model_error';
            $response['data'] = $appointment->getErrors();
        }
        return $response;
    }

    public static function addTempAppointment($data, $type) {
        $appointment = new UserAppointmentTemp();
        $appointment->load($data);
        if ($appointment->save()) {
            $schedule_id = $data['UserAppointmentTemp']['schedule_id'];
            $schedule = UserSchedule::findOne($schedule_id);
            $schedule->is_edit = 0;
            $schedule->save();

            $slot_id = $data['UserAppointmentTemp']['slot_id'];
            $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
            $slot->status = 'booked';
            $slot->save();

            $addTransactionRow = Logs::addTransactionRow(0, $appointment->id, $type = 'pay', $txn_type = 'booking', $appointment->service_charge);

            $response['type'] = 'success';
            $response["status"] = 1;
            $response["error"] = false;
            $response['data'] = $appointment->id;
        } else {
            $response['type'] = 'model_error';
            $response["status"] = 0;
            $response["error"] = true;
            $response['data'] = $appointment->getErrors();
        }
        return $response;
    }

    public static function generateToken($doctor_id, $date) {
        $appointments = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date])->count();
        $newToken = $appointments + 1;
        return $newToken;
    }

    public static function getCurrentAppointmentsAffairs($doctor_id, $date, $shift = '') {
        if ($shift == '') {
            $active = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_ACTIVE])->one();

            $consultation = UserAppointment::find()->where([
                        'doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->all();

            $skipped = UserAppointment::find()->where([
                        'doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_SKIP])->orderBy('token asc')->all();

            $pending = UserAppointment::find()->where([
                        'doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_PENDING])->orderBy('token asc')->all();
        } else {
            $active = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_ACTIVE, 'schedule_id' => $shift])->one();

            $consultation = UserAppointment::find()->where([
                        'doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_AVAILABLE, 'schedule_id' => $shift])->orderBy('token asc')->all();

            $skipped = UserAppointment::find()->where([
                        'doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_SKIP, 'schedule_id' => $shift])->orderBy('token asc')->all();

            $pending = UserAppointment::find()->where([
                        'doctor_id' => $doctor_id, 'date' => $date, 'status' => UserAppointment::STATUS_PENDING, 'schedule_id' => $shift])->orderBy('token asc')->all();
        }

        $i = 0;
        $appointments = array();
        if (!empty($active)) {
            $appointments[$i] = DrsPanel::getappointmentarray($active);
            $i++;
        }

        if (!empty($consultation)) {
            foreach ($consultation as $c) {
                $appointments[$i] = DrsPanel::getappointmentarray($c);
                $i++;
            }
        }

        if (!empty($skipped)) {
            foreach ($skipped as $s) {
                $appointments[$i] = DrsPanel::getappointmentarray($s);
                $i++;
            }
        }

        if (!empty($pending)) {
            foreach ($pending as $p) {
                $appointments[$i] = DrsPanel::getappointmentarray($p);
                $i++;
            }
        }
        /* if(!empty($appointments)){
          $appointments = array_slice($appointments, 0, 2);
          } */
        return $appointments;
    }

    public static function getCurrentAppointments($doctor_id, $date, $current_selected, $shifts = [], $status = []) {
        $getShiftSlots = array();
        $bookings = array();
        if ($current_selected == 0) {
            $current = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date, 'status' => 'current'])->one();
            if (!empty($current)) {
                $current_selected = $current->schedule_id;
            } else {
                $search['status'] = 'pending';
                $current = UserScheduleGroup::find()->andWhere(['user_id' => $doctor_id, 'date' => $date, 'status' => 'pending'])->orderBy('shift asc')->one();
                if (!empty($current)) {
                    $current_selected = $current->schedule_id;
                }
            }
        }
        $sft = 0;
        $appointment_list_show = 0;
        foreach ($shifts as $shift) {
            $getShiftSlots[$sft]['doctor_id'] = $shift['doctor_id'];
            $getShiftSlots[$sft]['doctor_name'] = $shift['doctor_name'];
            $getShiftSlots[$sft]['shift_name'] = $shift['shift_name'];
            $getShiftSlots[$sft]['hospital_name'] = $shift['hospital_name'];
            $getShiftSlots[$sft]['schedule_id'] = $shift['schedule_id'];
            $getShiftSlots[$sft]['shift'] = $shift['shift'];
            $getShiftSlots[$sft]['date'] = $shift['date'];
            $getShiftSlots[$sft]['appointment_date'] = $shift['date'];
            $getShiftSlots[$sft]['status'] = $shift['status'];
            if ($current_selected == $shift['schedule_id']) {
                $getShiftSlots[$sft]['isChecked'] = true;
                $appointment_list_show = 1;
            } else {
                $getShiftSlots[$sft]['isChecked'] = false;
            }
            $sft++;
        }

        if (!empty($getShiftSlots) && $appointment_list_show == 0) {
            $current_selected = $getShiftSlots[0]['schedule_id'];
            $appointment_list_show = 1;
            $getShiftSlots[0]['isChecked'] = true;
        }

        $appointments = array();
        $completed = 0;
        $cancelled = 0;
        $notapp = 0;
        $online = 0;
        $offline = 0;
        $total_history['total_patient'] = 0;
        if ($appointment_list_show == 1) {
            if (isset($status) && !empty($status)) {
                $status = $status;
            } else {
                $status = [UserAppointment::STATUS_ACTIVE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_COMPLETED];
            }
            $lists = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $current_selected])->andWhere(['status' => $status])->orderBy('token asc')->all();

            $total_history['total_patient'] = count($lists);
            if (!empty($lists)) {
                $i = 0;
                foreach ($lists as $list) {

                    if ($list->booking_type == UserAppointment::BOOKING_TYPE_ONLINE) {
                        $online += 1;
                    } else {
                        $offline += 1;
                    }

                    if ($list->status == UserAppointment::STATUS_COMPLETED) {
                        $completed += 1;
                    } elseif ($list->status == UserAppointment::STATUS_CANCELLED) {
                        $cancelled += 1;
                    } else {
                        $notapp += 1;
                    }

                    $appointments[$i] = DrsPanel::getappointmentarray($list);
                    $i++;
                }
            }
        }
        $bookings = $appointments;

        $total_history['total_appointed'] = $completed;
        $total_history['total_offline'] = $offline;
        $total_history['total_online'] = $online;
        $total_history['total_cancelled'] = $cancelled;
        $total_history['total_not_appointed'] = $notapp;

        $type['online'] = $online;
        $type['offline'] = $offline;
        // echo '<pre>';
        // print_r($bookings);die;
        return array('shifts' => $getShiftSlots, 'bookings' => $bookings, 'current_selected' => $current_selected,
            'total_history' => $total_history, 'type' => $type);
    }

    public static function getappointmentarray($appointment) {
        $data = array();
        $data['id'] = $appointment->id;
        $data['booking_id'] = $appointment->booking_id;
        $data['token'] = $appointment->token;
        $data['user_id'] = $appointment->user_id;
        $data['doctor_id'] = $appointment->doctor_id;
        $data['booking_type'] = $appointment->booking_type;
        $data['type'] = $appointment->type;
        $data['name'] = ucfirst($appointment->user_name);
        $data['age'] = $appointment->user_age;
        $data['gender'] = $appointment->user_gender;
        $data['user_address'] = $appointment->user_address;
        $data['payment_type'] = $appointment->payment_type;
        $data['phone'] = $appointment->user_phone;
        $data['shift_name'] = $appointment->shift_name;
        $appointment_time = str_replace("Shift ", " ", "$appointment->shift_name");
        $data['appointment_time'] = ltrim($appointment_time);
        $appointment_time_approx = ltrim($appointment_time);
        $approx_time = explode('-', $appointment_time_approx);
        $data['approx_consulation_time'] = rtrim($approx_time[0]);
        $data['fees'] = $appointment->doctor_fees;
        $data['status'] = $appointment->status;
        $data['status_label'] = DrsPanel::statusLabelDoctor($appointment->status);
        $data['patient_image'] = DrsPanel::getUserAvator($appointment->user_id);
        return $data;
    }

    public static function patientgetappointmentarray($appointment) {
        $data = array();
        $data['id'] = $appointment->id;
        $data['doctor_id'] = $appointment->doctor_id;
        $data['doctor_name_old'] = $appointment->doctor_name;
        $data['doctor_name'] = DrsPanel::getUserName($appointment->doctor_id);
        $data['doctor_image'] = DrsPanel::getUserAvator($appointment->doctor_id);
        $data['doctor_speciality'] = DrsPanel::getDoctorSpeciality($appointment->doctor_id);
        $data['doctor_address'] = $appointment->doctor_address;
        $data['address'] = $appointment->doctor_address;
        $data['doctor_address_id'] = $appointment->doctor_address_id;

        $address = UserAddress::findOne($appointment->doctor_address_id);
        $address_user = User::findOne($address->user_id);
        if (!empty($address)) {
            if ($address_user->groupid == Groups::GROUP_HOSPITAL) {
                $data['is_hospital'] = 1;
            } else {
                $data['is_hospital'] = 0;
            }
            $data['hospital_name'] = $address->name;
        } else {
            $data['hospital_name'] = '';
        }
        $data['mobile'] = $appointment->doctor_phone;

        $data['user_id'] = $appointment->user_id;
        $data['schedule_id'] = $appointment->schedule_id;
        $data['patient_name'] = ucfirst($appointment->user_name);
        $data['patient_mobile'] = $appointment->user_phone;

        $data['appointment_date'] = $appointment->date;

        $shiftlabel = explode('Shift', $appointment->shift_name);
        $data['appointment_time'] = isset($shiftlabel[1]) ? $shiftlabel[1] : $appointment->shift_name;
        $data['shift_name'] = $appointment->shift_name;
        $data['token'] = $appointment->token;
        $data['booking_id'] = $appointment->booking_id;
        $data['fees'] = $appointment->doctor_fees;
        $data['service_fees'] = $appointment->service_charge;
        $data['txn_id'] = DrsPanel::getTransactionId($appointment->id);

        $approxTime = date('h:i a', $appointment->start_time);
        $data['appointment_approx_time'] = $approxTime;

        $data['payment_type'] = $appointment->payment_type;
        $data['booking_type'] = $appointment->booking_type;
        $data['type'] = $appointment->type;
        $data['status'] = $appointment->status;
        $data['status_label'] = DrsPanel::statusLabel($appointment->status);
        $data['payment_status'] = $appointment->payment_status;
        $data['reminder'] = DrsPanel::getReminderDetails($appointment->id);

        $upcoming_status = array(UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_ACTIVE);
        if (in_array($appointment->status, $upcoming_status)) {
            $data['appointment_type'] = 'upcoming';
        } else {
            $data['appointment_type'] = 'past';
        }

        $rating_logs = UserRatingLogs::find()->where(['appointment_id' => $appointment->id])->one();
        if (!empty($rating_logs)) {
            $data['rating_review']['rating'] = $rating_logs->rating;
            $data['rating_review']['review'] = $rating_logs->review;
        } else {
            $data['rating_review'] = '';
        }


        return $data;
    }

    public static function getDoctorSpeciality($doctor_id) {
        $profile = UserProfile::findOne(['user_id' => $doctor_id]);
        if (!empty($profile->speciality)) {
            return $profile->speciality;
        } else {
            return '';
        }
    }

    public static function getPatientAppointments($user_id, $type = 'all') {
        $lists = new Query();
        $lists = UserAppointment::find();
        $lists->where(['user_id' => $user_id]);
        if ($type == 'upcoming') {
            $lists->andWhere(['status' => array(UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_ACTIVE)]);
            $lists->orderBy('id desc');
        } elseif ($type == 'past') {
            $lists->andWhere(['status' => array(UserAppointment::STATUS_COMPLETED, UserAppointment::STATUS_CANCELLED)]);
            $lists->orderBy('id desc');
        } else {
            $lists->orderBy('id desc');
        }
        return $lists;
    }

    public static function getPatientAppointmentsList($appointments) {
        $l = 0;
        $list_a = array();
        foreach ($appointments as $appointment) {
            $list = UserAppointment::findOne($appointment['id']);
            $list_a[$l] = DrsPanel::patientgetappointmentarray($list);
            $l++;
        }
        return $list_a;
    }

    public static function getPatientReminders($user_id) {
        $i = 0;
        $reminders = array();
        $lists = UserReminder::find()->where(['user_id' => $user_id])->orderBy('id desc')->all();
        if (!empty($lists)) {
            foreach ($lists as $list) {
                $appointment = DrsPanel::getReminderDetails($list->appointment_id);
                if (!empty($appointment) && $appointment != '') {
                    $reminders[$i]['reminder_id'] = $list->id;
                    $reminders[$i]['reminder_date'] = $appointment['reminder_date'];
                    $reminders[$i]['reminder_time'] = $appointment['reminder_time'];
                    $reminders[$i]['token'] = $appointment['token'];
                    $reminders[$i]['doctor_id'] = $appointment['doctor_id'];
                    $reminders[$i]['doctor_name'] = $appointment['doctor_name'];
                    $reminders[$i]['doctor_image'] = DrsPanel::getUserAvator($appointment['doctor_id']);
                    $reminders[$i]['doctor_speciality'] = $appointment['doctor_speciality'];
                    $reminders[$i]['appointment_id'] = $appointment['appointment_id'];
                    $reminders[$i]['appointment_date'] = $appointment['appointment_date'];
                    $reminders[$i]['appointment_time'] = $appointment['appointment_time'];
                    $reminders[$i]['appointment_days'] = $appointment['appointment_days'];
                    $reminders[$i]['appointment_approx_time'] = $appointment['appointment_approx_time'];
                    $i++;
                }
            }
        }
        return $reminders;
    }

    public static function getReminderDetails($appointment_id) {
        $reminder = '';
        $appointment = UserAppointment::findOne($appointment_id);
        if (!empty($appointment)) {
            $reminder_data = UserReminder::find()->where(['appointment_id' => $appointment_id])->one();
            if (!empty($reminder_data)) {
                $reminder['reminder_id'] = $reminder_data->id;
                $reminder['reminder_date'] = $reminder_data->reminder_date;
                $reminder['reminder_time'] = $reminder_data->reminder_time;
                $reminder['token'] = $appointment->token;
                $reminder['doctor_name'] = $appointment->doctor_name;
                $reminder['doctor_fees'] = $appointment->doctor_fees;
                $reminder['doctor_address'] = $appointment->doctor_address;
                $reminder['doctor_address_id'] = $appointment->doctor_address_id;
                $reminder['doctor_id'] = $appointment->doctor_id;
                $reminder['doctor_speciality'] = DrsPanel::getDoctorSpeciality($appointment->doctor_id);
                $reminder['appointment_id'] = $appointment->id;
                $reminder['appointment_date'] = $appointment->date;
                $reminder['appointment_time'] = $appointment->shift_name;
                $reminder['appointment_days'] = DrsPanel::calculateDateDays($appointment->date);
                $approxTime = date('h:i a', $appointment->start_time);
                $reminder['appointment_approx_time'] = $approxTime;
            }
        }
        return $reminder;
    }

    public static function getReviews($reviews) {
        $l = 0;
        $list_a = array();
        foreach ($reviews as $reviews_t) {
            $review = UserRatingLogs::findOne($reviews_t['id']);
            $list_a[$l]['patient_id'] = $review->user_id;
            $list_a[$l]['patient_image'] = DrsPanel::getUserAvator($review->user_id);
            $list_a[$l]['patient_name'] = DrsPanel::getUserName($review->user_id);
            $list_a[$l]['rating'] = $review->rating;
            $list_a[$l]['review'] = $review->review;
            $list_a[$l]['created_at_day'] = DrsPanel::time_elapsed_string(date('Y-m-d H:i:s', $review->created_at));
            $list_a[$l]['created_at'] = date('Y-m-d', $review->created_at);
            $list_a[$l]['updated_at'] = date('Y-m-d', $review->updated_at);
            $l++;
        }
        return $list_a;
    }

    public static function calculateRatingAverage($doctor_id) {
        $totalReviewLogs = UserRatingLogs::find()->where(['doctor_id' => $doctor_id])->all();
        $average = 0;
        $max_rating = array();
        if (!empty($totalReviewLogs)) {
            foreach ($totalReviewLogs as $totalReviewLog) { // iterate through array
                $max_rating[] = $totalReviewLog->rating;
            }
            $average = ceil(array_sum($max_rating) / count($max_rating));
        }
        return $average;
    }

    public static function time_elapsed_string($datetime, $full = false) {
        $now = new \DateTime();
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->d > 1) {
            $v = $diff->d . ' days ago';
        } elseif ($diff->d == 1) {
            $v = $diff->d . ' day ago';
        } else {
            $v = DrsPanel::getDayName(strtotime($datetime));
        }

        return $v;
    }

    public static function getDayName($timestamp, $timezone = '') {
        if ($timezone == '') {
            $timezone = DrsPanel::setTimezone();
        }
        date_default_timezone_set($timezone);

        $date = date('d/m/Y', $timestamp);

        if ($date == date('d/m/Y')) {
            $date = 'Today';
        } else if ($date == date('d/m/Y', strtotime(date('Y-m-d H:i:s')) - (24 * 60 * 60))) {
            $date = 'Yesterday';
        } else {
            $date = date('M j', $timestamp);
        }
        return $date;
    }

    public static function ratingUpdateToProfile($doctor_id = 0) {
        if ($doctor_id > 0) {
            $doctor = UserProfile::find()->where(['user_id' => $doctor_id])->one();
            $rating = DrsPanel::getRatingStatus($doctor->user_id);
            $doctor->rating = $rating['rating'];
            $doctor->save();
            return true;
        } else {
            $doctors = User::find()->where(['groupid' => Groups::GROUP_DOCTOR])->all();
            foreach ($doctors as $doctorp) {
                $doctor = UserProfile::find()->where(['user_id' => $doctorp->id])->one();
                $rating = DrsPanel::getRatingStatus($doctor->user_id);
                $doctor->rating = $rating['rating'];
                $doctor->save();
            }
        }
        return true;
    }

    public static function calculateDateDays($date) {
        $now = time(); // or your date as well
        $date = strtotime($date);
        if ($now >= $date) {
            $datediff = $now - $date;
            return round($datediff / (60 * 60 * 24));
        } else {
            return '';
        }
    }

    public static function getMetaData($keyname, $user_id = '') {
        $key = MetaKeys::findOne(['key' => $keyname]);
        $groups_v = array();
        if (!empty($key)) {
            $metavalues = MetaValues::find()->where(['key' => $key->id, 'status' => 1])->all();
            $m = 0;
            $all_active_values = array();
            foreach ($metavalues as $values) {
                $all_active_values[] = $values->value;
                $groups_v[$m]['id'] = $values->id;
                $groups_v[$m]['value'] = $values->value;
                $groups_v[$m]['label'] = $values->label;
                $groups_v[$m]['count'] = 0;
                $groups_v[$m]['icon'] = ($values->icon) ? $values->base_path . $values->file_path . $values->icon : '';
                $m++;
            }

            if ($keyname == 'services' && !empty($user_id)) {
                $profile = UserProfile::findOne($user_id);
                $services = $profile->services;
                if (!empty($services)) {
                    $services = explode(',', $services);
                    foreach ($services as $service) {
                        $service = trim($service);
                        if (!in_array($service, $all_active_values)) {
                            $checkValue = MetaValues::find()->where(['key' => $key->id, 'value' => $service])->one();
                            if (!empty($checkValue)) {
                                $groups_v[$m]['id'] = $checkValue->id;
                                $groups_v[$m]['value'] = $checkValue->value;
                                $groups_v[$m]['label'] = $checkValue->label;
                                $groups_v[$m]['count'] = 0;
                                $groups_v[$m]['icon'] = ($checkValue->icon) ? $checkValue->base_path . $checkValue->file_path . $checkValue->icon : '';
                                $m++;
                            }
                        }
                    }
                }
            }
        }
        return $groups_v;
    }

    public static function getSpecialityWithCount($keyname, $count, $term = '') {

        $key = MetaKeys::findOne(['key' => $keyname]);
        $groups_v = array();
        if (!empty($key)) {
            if ($term == '') {
                $metavalues = MetaValues::find()->where(['key' => $key->id])->orderBy('value asc')->all();
            } else {
                $metavalues = MetaValues::find()->where(['key' => $key->id])->andWhere(['like', 'value', $term])->all();
            }
            $m = 0;

            foreach ($metavalues as $values) {

                if (isset($count[$values->value]) && $count > 0) {
                    $groups_v[$m]['type'] = $keyname;
                    $groups_v[$m]['id'] = $values->id;
                    $groups_v[$m]['value'] = $values->slug;
                    $groups_v[$m]['slug'] = $values->slug;
                    $groups_v[$m]['label'] = $values->value;
                    $groups_v[$m]['count'] = $count[$values->value];
                    $groups_v[$m]['icon'] = ($values->icon) ? $values->base_path . $values->file_path . $values->icon : '';
                    $m++;
                }
                // else{
                //     $groups_v[$m]['count']=0;
                // }
            }
        }
        return $groups_v;
    }

    public static function getAdvertisement() {
        $adds = Advertisement::find()->all();
        $adv = array();
        $i = 0;
        foreach ($adds as $add) {
            $adv[$i]['id'] = $add->id;
            $adv[$i]['title'] = $add->title;
            $adv[$i]['link'] = $add->link;
            $adv[$i]['start_date'] = $add->start_date;
            $adv[$i]['end_date'] = $add->end_date;
            $adv[$i]['show_for_seconds'] = $add->show_for_seconds;
            $adv[$i]['image'] = Yii::getAlias('@frontendUrl') . '/img/advertisement/advertisement.jpg';
            $i++;
        }
        return $adv;
    }

    public static function getDateWeekDay($date) {
        $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $dayofweek = date('w', strtotime($date));
        $week = $days[$dayofweek];
        return $week;
    }

    public static function getMonthRangeArray($month = 0, $year = 0) {
        $key_array = $details = $schedules = array();

        if (!($month < 13 and $month > 0)) {
            $month = date("m");  // Current month as default month
        }
        if ($year == 0) {
            $year = date("Y");  // Set current year as default year
        }
        $no_of_days = cal_days_in_month(CAL_GREGORIAN, $month, $year); //calculate number of days in a month

        $i = 1;
        $week_start_date = $year . '-' . $month . '-' . $i;
        $startnewdate = strtotime($week_start_date);
        $newdate = strtotime($week_start_date);
        $details['start'] = strtotime(date('Y-m-d 00:00:00', $newdate));
        for ($i = 1; $i <= $no_of_days; $i++) {
            $key_array[] = date("Y-m-d", $newdate);
            $details['end'] = strtotime(date('Y-m-d 23:59:59', $newdate));
            $newdate = strtotime(date("Y-m-d", $startnewdate) . " +" . $i . " days");
        }
        return $details;
    }

    public static function homeScreenData($currentcity = '', $city_id = 0) {

        $lists = new Query();
        $lists = User::find();
        $lists->joinWith('userProfile as uP');
        $lists->andwhere(['uP.groupid' => Groups::GROUP_HOSPITAL]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE]); //'user.admin_status'=>User::STATUS_ADMIN_APPROVED
        $lists->select(['uP.avatar as image', 'uP.avatar_base_url as base_path', 'uP.avatar_path as file_path', 'uP.name as label', 'uP.user_id as id']);
        $lists->all();
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        $slider_limit = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 13])->one();

        if ($currentcity != '') {
            $sliderImg = SliderImage::find()
                            ->where(['status' => 1])
                            ->andWhere('find_in_set(:key2, `city`)', [':key2' => $currentcity])
                            ->limit($slider_limit->value)->all();
        } else {
            $sliderImg = SliderImage::find()->andWhere(['status' => 1])->limit($slider_limit->value)->all();
        }

        $speciality = array();
        $hospitals = array();
        $treatment = array();
        $array_hospital = array();
        if ($city_id > 0) {

            $popularspeciality = PopularMeta::find()->andWhere(['status' => 1])->andWhere(['key' => 'speciality', 'city_id' => $city_id])->all();
            if (!empty($popularspeciality)) {
                $findSpeciality = explode(',', $popularspeciality[0]['value']);
                $speciality = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 5])->andwhere(['value' => $findSpeciality])->all();
            }



            $popularhospital = PopularMeta::find()->andWhere(['status' => 1])->andWhere(['key' => 'hospital', 'city_id' => $city_id])->all();

            if (!empty($popularhospital)) {
                $findHospital = explode(',', $popularhospital[0]['value']);
                $hospitals = UserProfile::find()->where(['groupid' => Groups::GROUP_HOSPITAL])->andwhere(['user_id' => $findHospital])->all();

                foreach ($hospitals as $hospital) {
                    $checkUser = User::findOne($hospital->user_id);
                    $profile = UserProfile::find()->where(['user_id' => $hospital->user_id])->one();

                    $array_hospital[] = DrsPanel::profiledetails($checkUser, $profile, Groups::GROUP_HOSPITAL);
                }
            }


            $populartreatment = PopularMeta::find()->andWhere(['status' => 1])->andWhere(['key' => 'treatment', 'city_id' => $city_id])->all();
            if (!empty($populartreatment)) {
                $findTreatment = explode(',', $populartreatment[0]['value']);
                $treatment = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 9])->andwhere(['value' => $findTreatment])->all();
            }
        }

        $services = MetaValues::find()->andWhere(['status' => 1])->andWhere(['Key' => 11])->all();

        return ['slider_images' => $sliderImg, 'speciality' => $speciality, 'treatment' => $treatment, 'hospitals' => $array_hospital, 'services' => $services, 'popularspeciality' => $popularspeciality];
    }

    public static function metaValuesByKeyNameOrId($identify) {
        return MetaValues::find()->where(['or', ['id' => $identify], ['key' => $identify]])->all();
    }

    public static function metaKeys($key_name, $selected = []) {
        if ($key_name) {
            return MetaKeys::find()->where(['key' => $key_name])->select($selected)->one();
        } else {
            return MetaKeys::find()->select($selected)->all();
        }
    }

    public static function updateShiftTiming($shift_id, $post, $schedule_id = NULL) {
        if (isset($post['AddScheduleForm']['date_dayschedule'])) {
            $date = $post['AddScheduleForm']['date_dayschedule'];
        } else {
            $date = $post['date_dayschedule'];
        }
        if (empty($schedule_id) || isset($post['schedule_id'])) {
            if (isset($post['schedule_id'])) {
                $schedule_id = $post['schedule_id'];
            }
        }

        $weekday = DrsPanel::getDateWeekDay($date);
        $schedule = UserSchedule::findOne($schedule_id);
        $canAddEdit = true;
        $msg = ' Invalid';
        $dayShiftsFromDb = UserScheduleGroup::find()->where(['user_id' => $post['AddScheduleForm']['user_id']])->andwhere(['address_id' => $post['AddScheduleForm']['address_id']])->andwhere(['!=', 'id', $post['AddScheduleForm']['id']])->andwhere(['date' => $date])->all();

        if (!empty($dayShiftsFromDb)) {
            foreach ($dayShiftsFromDb as $key => $dayshiftValuedb) {
                $dbstart_time = date('Y-m-d', $dayshiftValuedb->start_time);
                $dbend_time = date('Y-m-d', $dayshiftValuedb->end_time);
                $nstart_time = $dbstart_time . ' ' . $post['AddScheduleForm']['start_time'];
                $nend_time = $dbend_time . ' ' . $post['AddScheduleForm']['end_time'];
                $startTimeClnt = strtotime($nstart_time);
                $endTimeClnt = strtotime($nend_time);
                $startTimeDb = $dayshiftValuedb->start_time;
                $endTimeDb = $dayshiftValuedb->end_time;

                if ($startTimeClnt > $endTimeClnt) {
                    $canAddEdit = false;
                    $msg = ' (end time should be greater than start time)';
                } elseif ($startTimeClnt == $endTimeClnt) {
                    $canAddEdit = false;
                    $msg = ' (start time & end time should not be same)';
                } elseif ($startTimeClnt == $startTimeDb && $endTimeClnt == $endTimeDb) {
                    $canAddEdit = false;
                    $msg = ' already exists';
                } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                    $canAddEdit = false;
                } elseif ($endTimeClnt > $startTimeDb && $endTimeClnt <= $endTimeDb) {
                    $canAddEdit = false;
                } elseif ($startTimeDb >= $startTimeClnt && $startTimeDb < $endTimeClnt) {
                    $canAddEdit = false;
                } elseif ($endTimeDb > $startTimeClnt && $endTimeDb <= $endTimeClnt) {
                    $canAddEdit = false;
                } elseif ($startTimeClnt >= $startTimeDb && $startTimeClnt < $endTimeDb) {
                    $canAddEdit = false;
                }

                if ($canAddEdit == false) {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response["data"] = [];
                    $response['message'] = 'Shift ' . date('h:i a', $startTimeClnt) . ' - ' . date('h:i a', $endTimeClnt) . ' on ' . $dayshiftValuedb->weekday . $msg;
                    return $response;
                }
            }
        }

        $approxStartTime = date('h:i a', strtotime($post['AddScheduleForm']['start_time']));
        $approxEndTime = date('h:i a', strtotime($post['AddScheduleForm']['end_time']));
        $approxStartTime = $date . ' ' . $approxStartTime;
        $approxEndTime = $date . ' ' . $approxEndTime;
        $start_time = strtotime($approxStartTime);
        $end_time = strtotime($approxEndTime);
        $appointment_time_duration = $post['AddScheduleForm']['appointment_time_duration'];

        $difference = abs($end_time - $start_time) / 60;
        $patient_limit = $difference / $appointment_time_duration;

        $shiftday = UserScheduleDay::find()->where(['schedule_id' => $schedule->id, 'date' => $date, 'user_id' => $post['AddScheduleForm']['user_id']])->one();



        $DurationClnt = $appointment_time_duration;
        $consultationfeesClnt = (isset($post['AddScheduleForm']['consultation_fees']) && !empty($post['AddScheduleForm']['consultation_fees']) && ($post['AddScheduleForm']['consultation_fees'] > 0) ) ? $post['AddScheduleForm']['consultation_fees'] : 0;
        $emergencyfeesClnt = (isset($post['AddScheduleForm']['emergency_fees']) && !empty($post['AddScheduleForm']['emergency_fees']) && ($post['AddScheduleForm']['emergency_fees'] > 0)) ? $post['AddScheduleForm']['emergency_fees'] : 0;
        $consultationDiscountfeesClnt = (isset($post['AddScheduleForm']['consultation_fees_discount']) && !empty($post['AddScheduleForm']['consultation_fees_discount']) && ($post['AddScheduleForm']['consultation_fees_discount'] > 0) ) ? $post['AddScheduleForm']['consultation_fees_discount'] : 0;
        $emergencyDiscountfeesClnt = (isset($post['AddScheduleForm']['emergency_fees_discount']) && !empty($post['AddScheduleForm']['emergency_fees_discount']) && ($post['AddScheduleForm']['emergency_fees_discount'] > 0)) ? $post['AddScheduleForm']['emergency_fees_discount'] : 0;

        $sameAsOld = 0;
        if (!empty($shiftday)) {
            if (($shiftday->start_time == $start_time) && ($shiftday->end_time == $end_time) &&
                    ($shiftday->appointment_time_duration == $DurationClnt) && ($shiftday->consultation_fees == $consultationfeesClnt) &&
                    ($shiftday->emergency_fees == $emergencyfeesClnt) && ($shiftday->consultation_fees_discount == $consultationDiscountfeesClnt) &&
                    ($shiftday->emergency_fees_discount == $emergencyDiscountfeesClnt)) {
                $sameAsOld = 1;
            } else {
                $cancelAppointments = DrsPanel::cancelAppointmentsBySchedule($schedule_id, $shiftday->date, $post['AddScheduleForm']['user_id'], $by = 'Doctor');
            }
        } else {
            $shiftday = new UserScheduleDay();
        }

        if ($sameAsOld == 0) {
            $shiftday->schedule_id = $schedule->id;
            $shiftday->user_id = $schedule->user_id;
            $shiftday->shift_belongs_to = $schedule->shift_belongs_to;
            $shiftday->attender_id = $schedule->attender_id;
            $shiftday->hospital_id = $schedule->hospital_id;
            $shiftday->address_id = $schedule->address_id;
            $shiftday->type = 'available';
            $shiftday->shift = (string) $schedule->shift;
            $shiftday->date = $date;
            $shiftday->weekday = $schedule->weekday;
            $shiftday->start_time = $start_time;
            $shiftday->end_time = $end_time;
            $shiftday->patient_limit = (int) $patient_limit;
            $shiftday->appointment_time_duration = $appointment_time_duration;

            $shiftday->consultation_fees = $consultationfeesClnt;
            $shiftday->emergency_fees = $emergencyfeesClnt;
            $shiftday->consultation_fees_discount = $consultationDiscountfeesClnt;
            $shiftday->emergency_fees_discount = $emergencyDiscountfeesClnt;

            $shiftday->consultation_days = $schedule->consultation_days;
            $shiftday->consultation_show = $schedule->consultation_show;
            $shiftday->emergency_days = $schedule->emergency_days;
            $shiftday->emergency_show = $schedule->emergency_show;
            $shiftday->status = $schedule->status;
            $shiftday->is_edit = 1;
            $shiftday->booking_closed = 1;
            if ($shiftday->save()) {
                $schedulegroup = UserScheduleGroup::find()->where(['schedule_id' => $schedule->id, 'date' => $date, 'weekday' => $weekday, 'user_id' => $schedule->user_id])->one();
                if (empty($schedulegroup)) {
                    $schedulegroup = new UserScheduleGroup();
                }

                $approxStartTime = date('h:i a', strtotime($post['AddScheduleForm']['start_time']));
                $approxEndTime = date('h:i a', strtotime($post['AddScheduleForm']['end_time']));
                $schedulegroup->user_id = $schedule->user_id;
                $schedulegroup->schedule_id = $schedule->id;
                $schedulegroup->shift_belongs_to = $schedule->shift_belongs_to;
                $schedulegroup->attender_id = $schedule->attender_id;
                $schedulegroup->hospital_id = $schedule->hospital_id;
                $schedulegroup->address_id = $schedule->address_id;
                $schedulegroup->shift = (string) $schedule->shift;
                $schedulegroup->shift_label = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                $schedulegroup->start_time = strtotime($post['AddScheduleForm']['start_time']);
                $schedulegroup->end_time = strtotime($post['AddScheduleForm']['end_time']);
                $schedulegroup->date = $date;
                $schedulegroup->weekday = $weekday;
                $schedulegroup->status = 'pending';
                $schedulegroup->booking_closed = 1;
                $schedulegroup->save();
                UserScheduleSlots::deleteAll(['schedule_id' => $schedule_id, 'date' => $date, 'weekday' => $weekday]);
            } else {
                echo "<pre>";
                print_r($shiftday->getErrors());
                die;
            }
            $shifts_keys = Drspanel::addUpdateShiftKeys($schedule->user_id);
            $updateStatusShift = DrsPanel::userShiftsStatus($schedule->user_id);
        }
        return true;
    }

    public static function upsertShift($post, $id = NULL, $addresid) {

        $week_array = DrsPanel::getWeekArray();
        if (isset($post['AddScheduleForm']['id']) && !empty($id)) {
            $model_id = $post['AddScheduleForm']['id'];
            unset($post['AddScheduleForm']['id']);
        }
        $c_avil = $post['AddScheduleForm']['weekday'];

        $get_address_id = isset($post['AddScheduleForm']['address_id']) ? $post['AddScheduleForm']['address_id'] : '1';
        $checkforHospital = DrsPanel::checkRegHospitalAddress($get_address_id);

        $addSchedule = array();
        $addSchedule = UserSchedule::findOne($id);
        $address_ids = UserAddress::find()->orderBy('id desc')->one();
        if (!empty($addSchedule)) {
            $post['UserSchedule'] = $post['AddScheduleForm'];
            $addSchedule->load($post);
            $addSchedule->weekday = $c_avil[0];
            $addSchedule->address_id = $addresid;
            $addSchedule->start_time = strtotime($post['AddScheduleForm']['start_time']);
            $addSchedule->end_time = strtotime($post['AddScheduleForm']['end_time']);
            if (!empty($checkforHospital)) {
                $addSchedule->shift_belongs_to = 'hospital';
                $addSchedule->attender_id = 0;
                $addSchedule->hospital_id = $checkforHospital;
            } else {
                $addSchedule->shift_belongs_to = 'attender';
                $addSchedule->hospital_id = 0;
            }


            // $addSchedule->address_id = $addressID->id
            // echo '<pre>';

            if ($addSchedule->save()) {
                // delete shift in group & add slots
                $shiftgroup = UserScheduleGroup::find()->where(['schedule_id' => $id])->one();
                /* if(!empty($shiftgroup)){
                  $shiftgroup->delete();
                  UserScheduleSlots::deleteAll(['schedule_id' => $id]);
                  } */
            }
        } else {

            foreach ($c_avil as $key => $value) {

                if (in_array($value, array_keys($week_array))) {
                    // echo '<pre>';print_r($value);
                    $addSchedule = new UserSchedule();
                    $addSchedule->shift = UserSchedule::shiftNumberCount($post['AddScheduleForm']['user_id'], $value);
                    $addSchedule->load(['UserSchedule' => $post['AddScheduleForm']]);
                    if (isset($post['AddScheduleForm']['attender_id']) && !empty($post['AddScheduleForm']['attender_id'])) {
                        $addSchedule->attender_id = $post['AddScheduleForm']['attender_id'];
                    }
                    $addSchedule->start_time = strtotime($post['AddScheduleForm']['start_time']);
                    $addSchedule->end_time = strtotime($post['AddScheduleForm']['end_time']);
                    $addSchedule->weekday = $value;
                    $addSchedule->status = UserSchedule::STATUS_ACTIVE;
                    $addSchedule->address_id = $addresid;

                    if (!empty($checkforHospital)) {
                        $addSchedule->shift_belongs_to = 'hospital';
                        $addSchedule->attender_id = 0;
                        $addSchedule->hospital_id = $checkforHospital;
                    } else {
                        $addSchedule->shift_belongs_to = 'attender';
                        $addSchedule->hospital_id = 0;
                        $addSchedule->attender_id = 0;
                    }
                    // echo '<pre>';
                    // print_r($addSchedule);
                    if ($addSchedule->save()) {
                        
                    } else {
                        echo '<pre>';
                        print_r($addSchedule->getErrors());
                    }
                } else {
                    echo '<pre> week value';
                    print_r($value);
                }
            }
            // die;
        }
        return $addSchedule;
    }

    public static function upsdatetShiftWithAddress($post, $id = NULL, $addresid) {
        $week_array = DrsPanel::getWeekArray();
        if (isset($post['AddScheduleForm']['id']) && !empty($id)) {
            $model_id = $post['AddScheduleForm']['id'];
            unset($post['AddScheduleForm']['id']);
        }
        $c_avil = $post['AddScheduleForm']['weekday'];

        $get_address_id = isset($post['AddScheduleForm']['address_id']) ? $post['AddScheduleForm']['address_id'] : '1';
        $checkforHospital = DrsPanel::checkRegHospitalAddress($get_address_id);

        $addSchedule = array();
        $addSchedule = UserSchedule::findOne($id);

        $address_ids = UserAddress::find()->orderBy('id desc')->one();
        if (!empty($addSchedule)) {
            $post['UserSchedule'] = $post['AddScheduleForm'];
            $addSchedule->load($post);
            $addSchedule->weekday = $c_avil[0];
            $addSchedule->address_id = $addresid;
            $addSchedule->start_time = strtotime($post['AddScheduleForm']['start_time']);
            $addSchedule->end_time = strtotime($post['AddScheduleForm']['end_time']);
            if (!empty($checkforHospital)) {
                $addSchedule->shift_belongs_to = 'hospital';
                $addSchedule->attender_id = 0;
                $addSchedule->hospital_id = $checkforHospital;
            } else {
                $addSchedule->shift_belongs_to = 'attender';
                $addSchedule->hospital_id = 0;
            }

            if ($addSchedule->save()) {
                // delete shift in group & add slots
                $shiftgroup = UserScheduleGroup::find()->where(['schedule_id' => $id])->one();
                /* if(!empty($shiftgroup)){
                  $shiftgroup->delete();
                  UserScheduleSlots::deleteAll(['schedule_id' => $id]);
                  } */
            }
        } else {

            foreach ($c_avil as $key => $day) {

                $NotSchedule = UserSchedule::find()->where(['user_id' => $post['AddScheduleForm']['user_id']])->andwhere(['weekday' => $day])->andFilterWhere(['>=', 'start_time', strtotime($post['AddScheduleForm']['start_time'])])->andFilterWhere(['<=', 'end_time', strtotime($post['AddScheduleForm']['end_time'])])->all();

                if (!empty($NotSchedule)) {
                    // echo 'not valid schedules';die;
                    return $addSchedule = " ";
                } else {
                    echo 'valid schedules';
                }

                // $validAddSchedule2=UserSchedule::find()->where(['user_id' =>$post['AddScheduleForm']['user_id']])->andwhere(['weekday' => $day])->andFilterWhere(['>=', 'start_time',strtotime($post['AddScheduleForm']['start_time'])])->andFilterWhere(['<=', 'end_time',strtotime($post['AddScheduleForm']['end_time'])])->all();
                // echo '<pre>';
                // print_r($validAddSchedule);die;

                $addSchedule = new UserSchedule();
                $addSchedule->shift = UserSchedule::shiftNumberCount($post['AddScheduleForm']['user_id'], $day);
                $addSchedule->load(['UserSchedule' => $post['AddScheduleForm']]);
                if (isset($post['AddScheduleForm']['attender_id']) && !empty($post['AddScheduleForm']['attender_id'])) {
                    $addSchedule->attender_id = $post['AddScheduleForm']['attender_id'];
                }
                $addSchedule->start_time = strtotime($post['AddScheduleForm']['start_time']);
                $addSchedule->end_time = strtotime($post['AddScheduleForm']['end_time']);
                $addSchedule->weekday = $day;
                $addSchedule->status = UserSchedule::STATUS_ACTIVE;
                $addSchedule->address_id = $addresid;

                if (!empty($checkforHospital)) {
                    $addSchedule->shift_belongs_to = 'hospital';
                    $addSchedule->attender_id = 0;
                    $addSchedule->hospital_id = $checkforHospital;
                } else {
                    $addSchedule->shift_belongs_to = 'attender';
                    $addSchedule->hospital_id = 0;
                    $addSchedule->attender_id = 0;
                }
                if ($addSchedule->save()) {
                    
                } else {
                    echo '<pre>';
                    print_r($addSchedule->getErrors());
                }
            }
        }
        return $addSchedule;
    }

    public static function checkRegHospitalAddress($get_address_id) {
        $address = UserAddress::findOne($get_address_id);
        $address_user_id = $address->user_id;
        $user = User::findOne($address_user_id);
        if ($user->groupid == Groups::GROUP_HOSPITAL) {
            return $user->id;
        }
        return '';
    }

    public static function doctorHospitalList($id) {
        $reqUserSearch = ['status' => UserRequest::Request_Confirmed, 'request_to' => $id, 'groupid' => Groups::GROUP_HOSPITAL];
        $requested = UserRequest::requestedUser($reqUserSearch, 'request_to');
        $query = UserAddress::find()->andWhere(['user_id' => $id, 'status' => 1]);
        if (count($requested) > 0) {
            $query->orWhere(['and', ['user_id' => $requested]]); //['is_register'=>2]
        }

        $addressProvider = new ActiveDataProvider([
            'query' => $query]);
        $listaddress = $apiList = array();
        $addresslist = $query->orderBy('id desc')->all();
        if (!empty($addresslist)) {
            foreach ($addresslist as $list) {
                $listaddress[$list->id] = $list->address;
                $api['id'] = $list->id;
                $api['type'] = $list->type;
                $api['user_id'] = $list->user_id;
                $api['name'] = $list->name;
                $api['address'] = $list->address;
                $api['area'] = $list->area;
                $api['city'] = $list->city;
                $api['state'] = $list->state;
                $api['country'] = $list->country;
                $api['mobile'] = $list->phone;
                $api['landline'] = $list->landline;
                $api['lat'] = $list->lat;
                $api['lng'] = $list->lng;
                $api['image'] = Drspanel::getAddressAvator($list['id']);
                $api['images_list'] = Drspanel::getAddressImageList($list['id']);
                $api['address_line'] = Drspanel::getAddressLine($list['id']);
                if ($list->user_id == $id) {
                    $api['can_edit'] = 1;
                } else {
                    $api['can_edit'] = 0;
                }
                $apiList[] = $api;
            }
        }
        return ['listaddress' => $listaddress, 'addressProvider' => $addressProvider, 'apiList' => $apiList];
    }

    public static function addressShiftArray($address, $hospital_id) {
        $api['id'] = $address->id;
        $api['type'] = $address->type;
        $api['user_id'] = $address->user_id;
        $api['name'] = $address->name;
        $api['address'] = $address->address;
        $api['area'] = $address->area;
        $api['city'] = $address->city;
        $api['state'] = $address->state;
        $api['country'] = $address->country;
        $api['mobile'] = $address->phone;
        $api['landline'] = $address->landline;
        $api['lat'] = $address->lat;
        $api['lng'] = $address->lng;
        $api['image'] = Drspanel::getAddressAvator($address->id);
        $api['images_list'] = Drspanel::getAddressImageList($address->id);
        $api['address_line'] = Drspanel::getAddressLine($address->id);
        if ($address->user_id == $hospital_id) {
            $api['can_edit'] = 1;
        } else {
            $api['can_edit'] = 0;
        }
        $apiList = $api;
        return $apiList;
    }

    public static function attenderHospitalList($attender_id) {
        $list = new Query();
        $list = UserSchedule::find();
        $list->andWhere(['attender_id' => $attender_id]);
        //$lists->select($select);
        $list->all();
        $command = $list->createCommand();
        $list = $command->queryAll();
        return $list;
    }

    public static function doctorSearchList($search = array()) {
        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => Groups::GROUP_DOCTOR]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => User::STATUS_ADMIN_LIVE_APPROVED]);
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        return $lists;
    }

    public static function hospitalSearchList($search = array()) {
        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->andwhere(['user_profile.groupid' => Groups::GROUP_HOSPITAL]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => User::STATUS_ADMIN_APPROVED]);
        $lists->all();
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        return $lists;
    }

    public static function attenderList($serach_data = [], $type = NULL) {
        if (count($serach_data) > 0) {
            $list = User::find()->andWhere(['groupid' => Groups::GROUP_ATTENDER])->andWhere($serach_data)->orderBy('id desc')->all();
        } else {
            $list = User::find()->andWhere(['groupid' => Groups::GROUP_ATTENDER])->orderBy('id desc')->all();
        }
        if (count($list) > 0) {
            if ($type == 'list' || $type == 'apilist') {
                foreach ($list as $row) {
                    $attender[$row->id] = $row['userProfile']['name'];
                    $apiAttenderItem['id'] = $row['userProfile']['user_id'];
                    $apiAttenderItem['name'] = $row['userProfile']['name'];
                    $apiAttenderItem['phone'] = $row->phone;
                    $apiAttenderItem['email'] = $row->email;
                    $checkUserGroup = Drspanel::getusergroupalias($row->parent_id);
                    if ($checkUserGroup == 'doctor') {

                        $selectedShifts = Drspanel::shiftList(['user_id' => $row->parent_id, 'attender_id' => $row->id], 'list');
                        $addressList = DrsPanel::doctorHospitalList($row->parent_id);
                        $listadd = $addressList['apiList'];
                        $shift_array = array();
                        $s = 0;
                        $shift_value = array();
                        $sv = 0;
                        $selectedShiftsIds = array();
                        foreach ($listadd as $address) {
                            $shifts = DrsPanel::getShiftListByAddress($row->parent_id, $address['id']);
                            foreach ($shifts as $key => $shift) {
                                if ($shift['hospital_id'] == 0) {
                                    $shift_array[$s]['value'] = $shift['shifts_ids'];
                                    $shift_array[$s]['label'] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                                    $shift_value[$sv] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';

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


                        /* $selectedShifts=Drspanel::shiftList(['user_id'=>$row->parent_id,'attender_id'=>$row->id],'list');
                          $selectedShiftsIds=(count($selectedShifts)>0)?implode(',',array_keys($selectedShifts)):''; */
                        $selectedShiftsIdss = (count($selectedShiftsIds) > 0) ? implode(',', $selectedShiftsIds) : '';
                        $apiAttenderItem['shift_id'] = $selectedShiftsIdss;
                    } else {
                        $selectedDoctors = Drspanel::getAttenderDoctors($row->id);
                        $selectedDoctorsIds = (count($selectedDoctors) > 0) ? implode(',', $selectedDoctors) : '';
                        $apiAttenderItem['doctor_id'] = $selectedDoctorsIds;
                    }

                    $apiAttenderItem['image'] = DrsPanel::getUserAvator($row['userProfile']['user_id']);
                    $apiAttender[] = $apiAttenderItem;
                }
                return ($type == 'list') ? $attender : $apiAttender;
            } else {
                return $list;
            }
        } else {
            return [];
        }
    }

    public static function shiftList($serach_data = [], $type = NULL, $userType = []) {
        if (count($serach_data) > 0) {
            if ($userType) {
                $hospital_id = $serach_data['user_id'];
                $address = UserAddress::find()->andWhere(['user_id' => $hospital_id])->one();
                if ($address) {
                    $confirmDrSearch = ['status' => UserRequest::Request_Confirmed, 'request_from' => $hospital_id, 'groupid' => Groups::GROUP_HOSPITAL];
                    $confirmDr = UserRequest::requestedUser($confirmDrSearch, 'request_from');
                    $serach_data['user_id'] = $confirmDr;
                    $serach_data['address_id'] = $address->id;
                } else {
                    return [];
                }
            }

            if (isset($serach_data['attender_id'])) {
                $attender_id = $serach_data['attender_id'];
            }

            if (isset($serach_data['user_id'])) {
                $user_id = $serach_data['user_id'];
            }

            $list = UserSchedule::find()->where(['shift_belongs_to' => 'attender'])
                            ->andWhere('find_in_set(:key2, `attender_id`)', [':key2' => isset($attender_id) ? $attender_id : '0'])
                            ->andWhere(['user_id' => $user_id])->orderBy('weekday asc')->all();
        } else {
            $list = UserSchedule::find()->orderBy('weekday asc')->all();
        }

        if (count($list) > 0) {
            if ($type == 'list' || $type == 'apilist') {
                $shifts = array();
                foreach ($list as $row) {
                    $address = DrsPanel::getAddressDetails($row->address_id);
                    if (!empty($address->is_register) && ($address->is_register == 1)) {
                        
                    } else {
                        $shift_time = substr($row->weekday, 0, 3) . ' , ' . date('h:i a', $row->start_time) . ' - ' . date('h:i a', $row->end_time);
                        $shifts[$row->id] = isset($address->address) ? $address->address : '' . ' , ' . isset($shift_time) ? $shift_time : '';
                        $apiShiftItem['id'] = $row->id;
                        $apiShiftItem['user_id'] = $row->user_id;
                        $apiShiftItem['shift_time'] = $shift_time;
                        $apiShiftItem['address_name'] = isset($address->address) ? $address->address : '';
                        $apiShiftItem['address'] = DrsPanel::getAddressLine($address);
                        $apiShifts[] = $apiShiftItem;
                    }
                }
                return ($type == 'list') ? $shifts : $apiShifts;
            } else {
                return $list;
            }
        } else {
            return [];
        }
    }

    public static function getDoctorShifts($user_id, $address_id) {
        $list = [];
        $shifts = UserSchedule::find()
                ->andWhere(['user_id' => $user_id])
                ->andWhere(['address_id' => $address_id])
                ->groupBy(['start_time'])
                ->all();
        if ($shifts) {
            foreach ($shifts as $key => $row) {
                $item['shift_id'] = $row->id;
                $item['status'] = $row->status;
                $item['consultation_fees'] = $row->consultation_fees;
                $item['shift_time'] = date('h:i a', $row->start_time) . ' - ' . date('h:i a', $row->end_time);
                $item['booked'] = DrsPanel::userBookingCount($user_id);
                $item['day'] = UserSchedule::find()
                        ->andWhere(['user_id' => $user_id])
                        ->andWhere(['address_id' => $address_id])
                        ->andWhere(['start_time' => $row->start_time])
                        ->select(['SUBSTR(weekday,1,3) as weekday'])
                        ->groupBy(['weekday'])
                        ->asArray()
                        ->all();
                $list[] = $item;
            }
        }
        return $list;
    }

    public function attenderShiftUpdate($attenderModel, $post = []) {
        $shiftList = Drspanel::shiftList(['user_id' => $attenderModel->parent_id], 'list');
        $selectedShifts = Drspanel::shiftList(['user_id' => $attenderModel->parent_id, 'attender_id' => $attenderModel->id], 'list');
        $selectedShiftsIds = (count($selectedShifts) > 0) ? array_keys($selectedShifts) : [];
        if (count($post) > 0) {
            $postRemove = array_diff_assoc($selectedShiftsIds, $post);
            foreach ($post as $key => $shift_id) {
                $shift = UserSchedule::findOne($shift_id);
                if ($shift) {
                    $shift->attender_id = $attenderModel->id;
                    $shift->save();
                }
            }
            if ($postRemove) {
                foreach ($postRemove as $key => $value) {
                    $shift = UserSchedule::findOne($value);
                    if ($shift) {
                        $shift->attender_id = 0;
                        $shift->save();
                    }
                }
            }
        } else {
            if (!empty($selectedShiftsIds)) {
                foreach ($selectedShiftsIds as $key => $shift_id) {
                    $shift = UserSchedule::findOne($shift_id);
                    if ($shift) {
                        $shift->attender_id = 0;
                        $shift->save();
                    }
                }
            }
        }
        return true;
    }

    public function appointmentShedules($andwhere) {
        $schedules = UserSchedule::find()->andWhere($andwhere)->select(['user_id', 'start_time', 'end_time', 'id', 'weekday', 'appointment_time_duration'])->asArray()->all();
        if (count($schedules) > 0) {
            $schedules[0]['slots'] = Drspanel::getScheduleSlots($schedules[0]);
            return $schedules;
        } else {
            return [];
        }
    }

    public function getScheduleSlots($schedules, $loginid = NULL) {
        $b_id = 1;
        $timeSlotList = [];
        for ($i = $schedules['start_time']; $i <= $schedules['end_time'];) {
            $timeSlotListAdd = $schedules;
            $timeSlotListAdd['slot_id'] = $b_id;
            $timeSlotListAdd['slot_time'] = $i;
            $timeSlotListAdd['slot_status'] = UserAppointment::blockStatus($timeSlotListAdd, $loginid);
            $i = $i + ($schedules['appointment_time_duration'] * 60);
            $timeSlotList[] = $timeSlotListAdd;
            $b_id = $b_id + 1;
        }
        return $timeSlotList;
    }

    public static function getUserThumbAvator($user_id) {
        $profile = UserProfile::findOne(['user_id' => $user_id]);
        $path = Yii::getAlias('@frontendUrl');
        $default_image = 'doctor-profile-image.jpg';
        $avator_path = $path . '/images/' . $default_image;
        if (!empty($profile)) {
            if ($profile->avatar != '') {
                $filecheck = Yii::getAlias('@base') . '/' . $profile->avatar_path . 'thumb/' . $profile->avatar;
                if (file_exists($filecheck)) {
                    $avator_path = $profile->avatar_base_url . $profile->avatar_path . 'thumb/' . $profile->avatar;
                } else {
                    $avator_path = $profile->avatar_base_url . $profile->avatar_path . $profile->avatar;
                }
            } else {
                $avator_path = $path . '/images/' . $default_image;
            }
        }
        return $avator_path;
    }

    public static function getUserAvator($user_id, $type = '') {
        $profile = UserProfile::findOne(['user_id' => $user_id]);
        $path = Yii::getAlias('@frontendUrl');
        $default_image = 'doctor-profile-image.jpg';
        $avator_path = $path . '/images/' . $default_image;
        if (!empty($profile)) {
            if ($profile->avatar != '') {
                $avator_path = $profile->avatar_base_url . $profile->avatar_path . $profile->avatar;
                if ($type == 'thumb') {
                    $filecheck = Yii::getAlias('@base') . '/' . $profile->avatar_path . 'thumb/' . $profile->avatar;
                    if (file_exists($filecheck)) {
                        $avator_path = $profile->avatar_base_url . $profile->avatar_path . 'thumb/' . $profile->avatar;
                    }
                }
            } else {
                $avator_path = $path . '/images/' . $default_image;
            }
        }
        return $avator_path;
    }

    public static function getUserDefaultAvator($user_id, $type = '') {
        $profile = UserProfile::findOne(['user_id' => $user_id]);
        $path = Yii::getAlias('@frontendUrl');
        $default_image = 'dafault_img.jpeg';
        $avator_path = $path . '/images/' . $default_image;
        if (!empty($profile)) {
            if ($profile->avatar != '') {
                $avator_path = $profile->avatar_base_url . $profile->avatar_path . $profile->avatar;
                if ($type == 'thumb') {
                    $filecheck = Yii::getAlias('@base') . '/' . $profile->avatar_path . 'thumb/' . $profile->avatar;
                    if (file_exists($filecheck)) {
                        $avator_path = $profile->avatar_base_url . $profile->avatar_path . 'thumb/' . $profile->avatar;
                    }
                }
            }
        }
        return $avator_path;
    }

    public static function getUserSelected($user_id, $select = []) {
        return User::find()->where(['id' => $user_id])->select($select)->one();
    }

    public static function getAddress($user_id) {
        $profile = UserProfile::findOne($user_id);
        $user_idlist = $user_id;
        if ($profile->groupid == Groups::GROUP_DOCTOR) {
            $reqUserSearch = ['status' => UserRequest::Request_Confirmed, 'request_to' => $user_id, 'groupid' => Groups::GROUP_HOSPITAL];
            $requested = UserRequest::requestedUser($reqUserSearch, 'request_to');
            if (count($requested) > 0) {
                $user_idlist = array_merge(array($user_id), $requested);
            }
        }

        $address = UserAddress::find()->where(['user_id' => $user_idlist])->orderBy('id desc');
        $addresslist = $address->all();
        $listaddress = array();
        if (!empty($addresslist)) {
            $a = 0;
            foreach ($addresslist as $list) {
                $listaddress[$a]['id'] = $list['id'];
                $listaddress[$a]['type'] = $list['type'];
                $listaddress[$a]['name'] = $list['name'];
                $listaddress[$a]['address'] = $list['address'];
                $listaddress[$a]['city'] = $list['city'];
                $listaddress[$a]['area'] = $list['area'];
                $listaddress[$a]['state'] = $list['state'];
                $listaddress[$a]['country'] = $list['country'];
                $listaddress[$a]['mobile'] = $list['phone'];
                $listaddress[$a]['landline'] = $list['landline'];
                $listaddress[$a]['address_line'] = Drspanel::getAddressLine($list);
                $listaddress[$a]['image'] = Drspanel::getAddressAvator($list['id']);
                $listaddress[$a]['images'] = DrsPanel::getAddressImageList($list['id']);

                $listaddress[$a]['lat'] = ($list['lat'] != '') ? $list['lat'] : '26.943040';
                $listaddress[$a]['lng'] = ($list['lng'] != '') ? $list['lng'] : '75.757060';
                if ($list['user_id'] == $user_id) {
                    $listaddress[$a]['can_edit'] = 1;
                } else {
                    $listaddress[$a]['can_edit'] = 0;
                }
                $shifts = DrsPanel::getDoctorShifts($user_id, $list['id']);
                if ($shifts) {
                    $shifts[0]['day'] = implode(',', ArrayHelper::getColumn($shifts[0]['day'], "weekday"));
                    $listaddress[$a]['shifts'] = $shifts[0];
                } else {
                    $listaddress[$a]['shifts'] = [];
                }
                $a++;
            }
        }
        return $listaddress;
    }

    public static function getAddressLine($address) {
        if (is_array($address)) {
            $address = $address['id'];
        }
        $address = UserAddress::findOne($address);
        $addressline = '';
        if (!empty($address)) {
            if (!empty($address['area'])) {
                $addressline .= ucfirst($address['area']);
                if (!empty($address['city'])) {
                    $addressline .= ', ' . $address['city'];
                }
            } elseif (!empty($address['city'])) {
                $addressline .= $address['city'];
            }
        }
        return $addressline;
    }

    public static function getAddressShow($address) {
        $addressline = '';
        if (is_array($address)) {
            if (!empty($address)) {
                if (!empty($address['address'])) {
                    $addressline .= ucfirst($address['address']) . ', ';
                }
                if (!empty($address['area'])) {
                    $addressline .= ucfirst($address['area']);
                    if (!empty($address['city'])) {
                        $addressline .= ', ' . $address['city'];
                    }
                } elseif (!empty($address['city'])) {
                    $addressline .= $address['city'];
                }
            }
        } else {
            $address_l = UserAddress::findOne($address);
            if (!empty($address_l)) {
                if (!empty($address_l['address'])) {
                    $addressline .= ucfirst($address_l['address']) . ', ';
                }
                if (!empty($address_l['area'])) {
                    $addressline .= ucfirst($address_l['area']);
                    if (!empty($address_l['city'])) {
                        $addressline .= ', ' . $address_l['city'];
                    }
                } elseif (!empty($address_l['city'])) {
                    $addressline .= $address_l['city'];
                }
            }
        }
        return $addressline;
    }

    public static function getIDOfMetaKey($meta) {
        $metakey = MetaKeys::findOne(['key' => $meta]);
        if ($metakey) {
            return $metakey->id;
        } else {
            return 0;
        }
    }

    public static function getDoctorEducation($user_id) {
        $lists = new Query();
        $lists = UserEducations::find();
        $lists->andwhere(['user_id' => $user_id]);
        $lists->orderBy('start desc');

        $lists->all();
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        return $lists;
    }

    public static function getDoctorProfileMsg($user_id) {
        $profile = User::findOne(['id' => $user_id]);
        $msg = 'Profile updated';
        if (!empty($profile)) {
            if ($profile->admin_status == User::STATUS_ADMIN_PENDING) {
                $profile->admin_status = User::STATUS_ADMIN_REQUESTED;
                if ($profile->save()) {
                    $msg = 'Your profile is submitted.';
                }
            } elseif ($profile->admin_status == User::STATUS_ADMIN_REQUESTED) {
                $msg = 'Your have already submitted your profile.';
            } else {
                $msg = 'Profile updated';
            }
        }
        return $msg;
    }

    public static function getDoctorExperience($user_id) {
        $experience = UserExperience::find()->where(['user_id' => $user_id])->orderBy('id desc');
        $experiencelist = $experience->all();
        // print_r($educationlist);die;
        return $experiencelist;
    }

    public static function checkRequestSend($hospital_id, $doctor_id) {
        $check = UserRequest::find()->where(['request_from' => $hospital_id, 'request_to' => $doctor_id])->one();
        if (!empty($check)) {
            if ($check->status == UserRequest::Requested) {
                return true;
            }
        }
        return false;
    }

    public static function getBookingShifts($doctor_id, $date, $current_login) {
        $getShiftSlots = array();
        $weekday = DrsPanel::getDateWeekDay($date);
        $getScheduleShifts = DrsPanel::getScheduleShifts($doctor_id, $date);
        if ($getScheduleShifts) {
            $user = User::findOne($current_login);
            $groupid = $user->groupid;
            if ($groupid == Groups::GROUP_PATIENT) {
                $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date, 'booking_closed' => UserScheduleGroup::BOOKING_CLOSED_FALSE])->orderBy('shift asc')->all();
            } elseif ($groupid == Groups::GROUP_ATTENDER) {
                $attender_parent = DrsPanel::getAttenderParentType($current_login);
                if ($attender_parent == Groups::GROUP_DOCTOR) {
                    $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date, 'shift_belongs_to' => 'attender'])->andWhere('find_in_set(:key2, `attender_id`)', [':key2' => $current_login])->orderBy('shift asc')->all();
                } else {
                    $attender_address = HospitalAttender::find()->where(['doctor_id' => $doctor_id, 'attender_id' => $current_login])->all();
                    $addressids = array();
                    foreach ($attender_address as $attender) {
                        $addressids[] = $attender->hospital_id;
                    }
                    //hospital
                    $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date, 'hospital_id' => $addressids])->orderBy('shift asc')->all();
                }
            } elseif ($groupid == Groups::GROUP_HOSPITAL) {
                $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date, 'weekday' => $weekday, 'hospital_id' => $current_login])->all();
            } else {
                //doctor shifts
                $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date])->orderBy('shift asc')->all();
            }

            $s = 0;


            foreach ($shifts as $shift) {
                $addSlot = DrsPanel::getShiftSlots($doctor_id, $date, $shift->schedule_id, $shift->shift);

                $dateSchedule = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $date, 'shift' => $shift->shift])->one();

                if (empty($dateSchedule)) {
                    $dateSchedule = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'shift' => $shift->shift, 'id' => $shift->schedule_id])->one();
                }

                if (!empty($dateSchedule)) {

                    $getShiftSlots[$s]['doctor_id'] = $doctor_id;
                    $getShiftSlots[$s]['doctor_name'] = DrsPanel::getUserName($doctor_id);
                    $getShiftSlots[$s]['doctor_category'] = DrsPanel::getDoctorSpeciality($doctor_id);
                    $getShiftSlots[$s]['schedule_id'] = $shift->schedule_id;
                    $getShiftSlots[$s]['shift'] = $shift->shift;
                    $getShiftSlots[$s]['shift_id'] = $shift->id;
                    $getShiftSlots[$s]['shift_name'] = $shift->shift_label;
                    $getShiftSlots[$s]['shift_label'] = $shift->shift_label;
                    $getShiftSlots[$s]['date'] = $shift->date;
                    $getShiftSlots[$s]['status'] = $shift->status;
                    $getShiftSlots[$s]['booking_closed'] = $shift->booking_closed;

                    $address = UserAddress::find()->where(['id'=>$dateSchedule->address_id])->one();
                    $getShiftSlots[$s]['consultation_fees'] = $dateSchedule->consultation_fees;
                    $getShiftSlots[$s]['consultation_fees_discount'] = $dateSchedule->consultation_fees_discount;
                    $getShiftSlots[$s]['fees'] = $dateSchedule->consultation_fees;
                    $getShiftSlots[$s]['emergency_fees'] = $dateSchedule->emergency_fees;
                    $getShiftSlots[$s]['emergency_fees_discount'] = $dateSchedule->emergency_fees_discount;
                    $getShiftSlots[$s]['address_id'] = $dateSchedule->address_id;
                    $getShiftSlots[$s]['address_name'] = isset($address->name) ? $address->name : '';
                    $getShiftSlots[$s]['hospital_name'] = isset($address->name) ? $address->name : '';
                    $getShiftSlots[$s]['address'] = DrsPanel::getAddressLine($address->id);
                    $getShiftSlots[$s]['phone'] = isset($address->phone) ? $address->phone : '';
                    $getShiftSlots[$s]['duration'] = $dateSchedule->appointment_time_duration;
                    $getShiftSlots[$s]['patient_limit'] = $dateSchedule->patient_limit;
                    $getShiftSlots[$s]['start_time'] = date('h:i a', $dateSchedule->start_time);
                    $getShiftSlots[$s]['end_time'] = date('h:i a', $dateSchedule->end_time);
                    $getShiftSlots[$s]['time'] = date('h:i a', $dateSchedule->start_time) . ' - ' . date('h:i a', $dateSchedule->end_time);
                    $getShiftSlots[$s]['can_edit'] = $dateSchedule->is_edit;
                    $service_charge = DrsPanel::getServiceCharge($dateSchedule->address_id, $doctor_id);
                    $getShiftSlots[$s]['service_charge'] = $service_charge['charge'];
                    $getShiftSlots[$s]['service_charge_discount'] = $service_charge['charge_discount'];
                    $getShiftSlots[$s]['service_charge_show'] = $service_charge['final_charge'];

                    $s++;
                }
            }
        }
        return $getShiftSlots;
    }

    public static function getBookingShiftSlots($doctor_id, $date, $schedule_id, $status = array('available', 'booked')) {
        $getShiftSlots = array();
        $shift = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $doctor_id, 'schedule_id' => $schedule_id, 'status' => array('pending', 'current')])->orderBy('shift asc')->one();
        if (!empty($shift)) {
            $getShiftSlots = DrsPanel::getShiftSlots($doctor_id, $date, $shift->schedule_id, $shift->shift, $status);
        }

        return $getShiftSlots;
    }

    public static function getScheduleShifts($doctor_id, $date, $loginype = []) {
        $weekday = DrsPanel::getDateWeekDay($date);
        $allSchedules = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday])->all();
        // echo '<pre>';
        // print_r($allSchedules);
        // die;
        if (!empty($allSchedules)) {

            foreach ($allSchedules as $key => $schedule) {
                $approxStartTime = NULL;
                $approxEndTime = NULL;
                $dateSchedule = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $date, 'shift' => $schedule->shift])->one();
                // echo '<pre>';
                // print_r($dateSchedule);
                if (!empty($dateSchedule)) {
                    $shifts_g = UserScheduleGroup::find()->where(['schedule_id' => $schedule->id, 'date' => $date, 'user_id' => $doctor_id])->one();
                    if (empty($shifts_g)) {

                        $approxStartTime = date('h:i a', $schedule->start_time);
                        $approxEndTime = date('h:i a', $schedule->end_time);

                        $schedulegroup = new UserScheduleGroup();
                        $schedulegroup->user_id = $doctor_id;
                        $schedulegroup->schedule_id = $schedule->id;
                        $schedulegroup->shift_belongs_to = $dateSchedule->shift_belongs_to;
                        $schedulegroup->attender_id = $dateSchedule->attender_id;
                        $schedulegroup->hospital_id = $dateSchedule->hospital_id;
                        $schedulegroup->address_id = $dateSchedule->address_id;
                        $schedulegroup->shift = (string) $schedule->shift;
                        $schedulegroup->shift_label = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                        $schedulegroup->start_time = $schedule->start_time;
                        $schedulegroup->end_time = $schedule->end_time;
                        $schedulegroup->date = $date;
                        $schedulegroup->weekday = $weekday;
                        $schedulegroup->status = 'pending';
                        $schedulegroup->booking_closed = $dateSchedule->booking_closed;
                        $schedulegroup->save();
                    }
                } else {
                    $getSchedule = new UserScheduleDay();
                    $getSchedule->schedule_id = $schedule->id;
                    $getSchedule->user_id = $schedule->user_id;
                    $getSchedule->shift_belongs_to = $schedule->shift_belongs_to;
                    $getSchedule->attender_id = $schedule->attender_id;
                    $getSchedule->hospital_id = $schedule->hospital_id;
                    $getSchedule->address_id = $schedule->address_id;
                    $getSchedule->type = 'available';
                    $getSchedule->shift = (string) $schedule->shift;
                    $getSchedule->date = $date;
                    $getSchedule->weekday = $schedule->weekday;

                    $approxStartTime = date('h:i a', $schedule->start_time);
                    $approxEndTime = date('h:i a', $schedule->end_time);
                    $approxStartTime = $date . ' ' . $approxStartTime;
                    $approxEndTime = $date . ' ' . $approxEndTime;
                    $start_time = strtotime($approxStartTime);
                    $end_time = strtotime($approxEndTime);

                    $getSchedule->start_time = $start_time;
                    $getSchedule->end_time = $end_time;
                    $getSchedule->patient_limit = $schedule->patient_limit;
                    $getSchedule->appointment_time_duration = $schedule->appointment_time_duration;
                    $getSchedule->consultation_fees = $schedule->consultation_fees;
                    $getSchedule->consultation_days = $schedule->consultation_days;
                    $getSchedule->consultation_show = $schedule->consultation_show;
                    $getSchedule->consultation_fees_discount = ($schedule->consultation_fees_discount) ? $schedule->consultation_fees_discount : 0;
                    $getSchedule->emergency_fees = $schedule->emergency_fees;
                    $getSchedule->emergency_days = $schedule->emergency_days;
                    $getSchedule->emergency_show = $schedule->emergency_show;
                    $getSchedule->emergency_fees_discount = ($schedule->emergency_fees_discount) ? $schedule->emergency_fees_discount : 0;
                    $getSchedule->status = $schedule->status;
                    $getSchedule->is_edit = $schedule->is_edit;
                    $getSchedule->booking_closed = 1;
                    if ($getSchedule->save()) {
                        $shifts_g = UserScheduleGroup::find()->where(['schedule_id' => $schedule->id, 'date' => $date, 'user_id' => $doctor_id])->one();
                        if (empty($shifts_g)) {
                            $approxStartTime = date('h:i a', $schedule->start_time);
                            $approxEndTime = date('h:i a', $schedule->end_time);

                            $schedulegroup = new UserScheduleGroup();
                            $schedulegroup->user_id = $doctor_id;
                            $schedulegroup->schedule_id = $schedule->id;
                            $schedulegroup->shift_belongs_to = $schedule->shift_belongs_to;
                            $schedulegroup->attender_id = $schedule->attender_id;
                            $schedulegroup->hospital_id = $schedule->hospital_id;
                            $schedulegroup->address_id = $schedule->address_id;
                            $schedulegroup->shift = (string) $schedule->shift;
                            $schedulegroup->shift_label = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                            $schedulegroup->start_time = $schedule->start_time;
                            $schedulegroup->end_time = $schedule->end_time;
                            $schedulegroup->date = $date;
                            $schedulegroup->weekday = $weekday;
                            $schedulegroup->status = 'pending';
                            $schedulegroup->booking_closed = 1;
                            $schedulegroup->save();
                        } else {
                            $approxStartTime = date('h:i a', $schedule->start_time);
                            $approxEndTime = date('h:i a', $schedule->end_time);

                            $shifts_g->shift_belongs_to = $schedule->shift_belongs_to;
                            $shifts_g->hospital_id = $schedule->hospital_id;
                            $shifts_g->attender_id = $schedule->attender_id;
                            $shifts_g->address_id = $schedule->address_id;
                            $shifts_g->booking_closed = 1;
                            $shifts_g->shift_label = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                            $shifts_g->save();
                        }
                    }
                }
                //echo $key;
            }
            $shifts = UserScheduleGroup::find()->where(['date' => $date, 'user_id' => $doctor_id])->orderBy('schedule_id asc')->all();

            if (!empty($shifts)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function getShiftSlots($doctor_id, $date, $schedule_id, $shiftid, $status = NULL) {
        $weekday = DrsPanel::getDateWeekDay($date);

        $slots = UserScheduleSlots::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $date, 'schedule_id' => $schedule_id])->all();
        if (empty($slots)) {
            $schedule = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $date, 'schedule_id' => $schedule_id])->one();
            if (empty($schedule)) {
                $schedule = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'id' => $schedule_id])->one();
            }
            if (!empty($schedule)) {
                $approxStartTime = date('h:i a', $schedule->start_time);
                $approxEndTime = date('h:i a', $schedule->end_time);

                $starttime = strtotime($date . ' ' . $approxStartTime);
                $endtime = strtotime($date . ' ' . $approxEndTime);

                $b_id = 1;
                $timeSlotList = [];
                $endtime = $endtime - ($schedule->appointment_time_duration * 60);
                for ($i = $starttime; $i <= $endtime;) {
                    $newSlot = new UserScheduleSlots();
                    $newSlot->user_id = $doctor_id;
                    $newSlot->schedule_id = $schedule_id;
                    $newSlot->date = $date;
                    $newSlot->weekday = $weekday;
                    $newSlot->shift_label = $approxStartTime . ' - ' . $approxEndTime;
                    $newSlot->token = $b_id;

                    if ($b_id % 5 == 0) {
                        $newSlot->type = 'emergency';
                        $newSlot->fees = $schedule->emergency_fees;
                        $newSlot->fees_discount = $schedule->emergency_fees_discount;
                    } else {
                        $newSlot->type = 'consultation';
                        $newSlot->fees = $schedule->consultation_fees;
                        $newSlot->fees_discount = $schedule->consultation_fees_discount;
                    }

                    $newSlot->start_time = (int) $i;
                    $newSlot->end_time = $i + ($schedule->appointment_time_duration * 60);

                    $approxStart = date('h:i a', (int) $i);
                    $approxEnd = date('h:i a', $i + ($schedule->appointment_time_duration * 60));

                    $newSlot->shift_name = $approxStart . ' - ' . $approxEnd;
                    $newSlot->status = 'available';
                    $newSlot->save();
                    $i = $i + ($schedule->appointment_time_duration * 60);

                    $b_id++;
                }
                return $timeSlotList;
            }
            $slots = UserScheduleSlots::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $date, 'schedule_id' => $schedule_id])->all();
        } else {
            if ($status) {
                $slots = UserScheduleSlots::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $date, 'schedule_id' => $schedule_id, 'status' => $status])->all();
            }
        }
        return $slots;
    }

    public static function getMyDoctorList($params) {
        $response = $search = array();
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

            $lists->all();
            $command = $lists->createCommand();
            $lists = $command->queryAll();

            $list_a = Drspanel::getList($lists, 'hospital_doctors', $hospital_id);
            $data_array = array_values($list_a);

            $response['data'] = $data_array;

            $count_result = count($data_array);

            if ($count_result > 0) {
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
            $response['data'] = [];
        }
        return $response;
    }

    public static function getDoctorSliders($params) {
        $response = $search = array();
        $offset = 0;
        $recordlimit = 3;
        $totalpages = 0;
        $count_result = 0;
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

            $list_a = Drspanel::getList($lists, 'hospital_doctors', $hospital_id);
            $data_array = array_values($list_a);

            $response['pagination'] = $totallist;
            $response['data'] = $data_array;

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
            $response['data'] = [];
        }
        return $response;
    }

    public static function upsertEducation($post) {
        if (isset($post['id']) && !empty($post['id'])) {
            $model = UserEducations::findOne($post['id']);
        } else {
            $model = new UserEducations();
        }

        $model->load(['UserEducations' => $post]);
        $model->start = strtotime($post['start']);
        $endtill = date('Y-01-01', strtotime('+1 year', strtotime(date('Y-m-d'))));
        $model->end = $post['end'] == 'Till Now' ? strtotime($endtill) : strtotime($post['end']);

        if (date('Y', $model->end) > date('Y')) {
            $model->is_till_now = 1;
        } else {
            $model->is_till_now = 0;
        }
        $model->save();
        return $model;
    }

    public static function upsertExperience($post) {
        if (isset($post['id']) && !empty($post['id'])) {
            $model = UserExperience::findOne($post['id']);
        } else {
            $model = new UserExperience();
        }
        $model->load(['UserExperience' => $post]);
        $model->start = strtotime($post['start']);
        $endtill = date('Y-01-01', strtotime('+1 year', strtotime(date('Y-m-d'))));
        $model->end = $post['end'] == 'Till Now' ? strtotime($endtill) : strtotime($post['end']);


        if (date('Y', $model->end) > date('Y')) {
            $model->is_till_now = 1;
        } else {
            $model->is_till_now = 0;
        }
        $model->save();
        return $model;
    }

    public static function patientDoctorList($user_id) {

        return UserAppointment::find()->andWhere(['user_id' => $user_id])->select(['doctor_id', 'user_id', 'doctor_name', 'doctor_address', 'doctor_address_id', 'doctor_fees',])->groupBy('doctor_id')->all();
    }

    public static function patientMyDoctorsList($user_id) {
        $docotorsList = array();
        $favorites = UserFavorites::find()->where(['user_id' => $user_id])->all();
        $i = 0;
        foreach ($favorites as $favorite) {
            if ($favorite->status == 1) {
                $docotorsList[$favorite->profile_id] = $favorite->profile_id;
                $i++;
            }
        }
        return $docotorsList;
    }

    public static function patientDoctorFavoriteList($user_id) {

        return UserAppointment::find()->andWhere(['user_id' => $user_id])->select(['doctor_id', 'user_id', 'doctor_name', 'doctor_address', 'doctor_address_id', 'doctor_fees'])->groupBy('doctor_id')->all();
    }

    public static function doctorPatientList($user_id) {

        return UserAppointment::find()->andWhere(['doctor_id' => $user_id])->select(['doctor_id', 'user_id', 'user_name', 'user_age', 'user_phone', 'user_address', 'user_gender'])->all();
    }

    public static function patientAppoitmentList($user_id, $status_type = [], $type = 'all') {
        $datecheck = strtotime(date('Y-m-d 00:00:00'));

        $pastcheck = strtotime(date('Y-m-d 23:59:59'));
        $appList = new Query();
        $appList = UserAppointment::find();
        $appList->andWhere(['user_id' => $user_id]);
        if (count($status_type) > 0) {
            $appList->andWhere(['status' => $status_type]);
        }
        if ($type == 'upcoming') {
            $appList->andWhere(['>=', 'appointment_time', $datecheck]);
        } elseif ($type == 'past') {
            $appList->andWhere(['<=', 'appointment_time', $pastcheck]);
        } else {
            
        }
        $appList->orderBy('id desc');
        $appList->all();
        $command = $appList->createCommand();
        $appList = $command->queryAll();
        return $appList;
    }

    public static function getHospitalServices($model) {
        $meta_key = DrsPanel::metaKeys('services');
        $services = DrsPanel::metaValuesByKeyNameOrId($meta_key->id);
        return $services;

        // print_r($model['speciality']);die;
        $lists = new Query();
        $lists = MetaValues::find();
        $services = explode(',', $model['services']);
        foreach ($services as $service) {
            $lists->orWhere(['or', ['like', 'value', $service]]);
        }
        // $lists->select($select);
        $lists->all();
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        // pr($lists);die;
        return $lists;
    }

    public static function deleteShift($doctor_id, $address_id, $schedule_ids) {
        $response = array();
        if (is_array($schedule_ids)) {
            
        } else {
            $schedule_ids = array($schedule_ids);
        }

        foreach ($schedule_ids as $schedule_id) {
            $schedule = UserSchedule::findOne($schedule_id);
            if (!empty($schedule)) {
                $dbTransaction = Yii::$app->db->beginTransaction();
                try {
                    $appointments = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'schedule_id' => $schedule_id])->andWhere(['status' => [UserAppointment::STATUS_ACTIVE, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_PENDING]])->all();
                    foreach ($appointments as $appointment) {
                        $appointment->status = UserAppointment::STATUS_CANCELLED;
                        $appointment->is_deleted = 1;
                        $appointment->deleted_by = 'Doctor';
                        if ($appointment->save()) {
                            $addLog = Logs::appointmentLog($appointment->id, 'Appointment cancelled by doctor');
                        }
                    }
                    if ($schedule->delete()) {

                        $schedule_Group = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'schedule_id' => $schedule_id])->one();
                        if (!empty($schedule_Group)) {
                            $schedule_Group->delete();

                            $schedule_day = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'schedule_id' => $schedule_id])->one();
                            if (!empty($schedule_day)) {
                                $schedule_day->delete();

                                $schedule_slots = UserScheduleSlots::find()->where(['user_id' => $doctor_id, 'schedule_id' => $schedule_id])->all();
                                if (!empty($schedule_slots)) {
                                    $schedule_slots->deleteAll();
                                }
                            }
                        }
                    }

                    $dbTransaction->commit();
                    $response['type'] = 'success';
                    $response['message'] = 'Shift Deleted';
                } catch (\Exception $e) {
                    $dbTransaction->rollBack();
                    $response["type"] = "error";
                    $response["message"] = Yii::t("db, Exception! Please try again");
                }
            } else {
                $response['type'] = 'error';
                $response['message'] = 'Shift not found';
            }
        }
        return $response;
    }

    public static function getLatLong($user_id) {
        $address = UserAddress::find()->where(['user_id' => $user_id]);
        $addresslist = $address->all();
        $listaddress['lat'] = 26.912434;
        $listaddress['lng'] = 75.787270;
        if (!empty($addresslist)) {
            $a = 0;
            foreach ($addresslist as $list) {
                if (!empty($list['lat']) && $list['lat'] != '') {
                    if ($list['lat'] != 26.912434) {
                        $listaddress['lat'] = $list['lat'];
                        $listaddress['lng'] = $list['lng'];
                    }
                }
            }
        }
        return $listaddress;
    }

    public function hospitalDoctorList($id) {

        $searchNotIn = ['request_from' => $id, 'groupid' => Groups::GROUP_HOSPITAL];
        $notIn = UserRequest::requestedUser($searchNotIn, 'request_from');
        // pr($notIn);die;

        $lists = new Query();
        $lists = User::find();
        $lists->joinWith('userProfile as uP');
        $lists->andwhere(['uP.groupid' => Groups::GROUP_DOCTOR]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE]); //'user.admin_status'=>User::STATUS_ADMIN_APPROVED
        if (count($notIn) > 0) {
            $lists->andWhere(['user.admin_status' => 'approved']);
            $lists->andWhere(['not in', 'user.id', $notIn]);
        } else {
            $lists->andWhere(['user.admin_status' => 'approved']);
        }
        // $lists->andWhere(['user.id'=>$confirmDr]);
        $lists->select(['uP.name', 'uP.user_id']);
        $lists->all();
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        $listdoctor = array();
        if (!empty($lists)) {
            foreach ($lists as $value) {
                $listdoctor[$value['user_id']] = $value['name'];
            }
        }
        return $listdoctor;
    }

    public function requestedHospitalsList($id) {
        $reqUserSearch = ['status' => UserRequest::Requested, 'request_to' => $id, 'groupid' => Groups::GROUP_HOSPITAL];
        $requested = UserRequest::requestedUser($reqUserSearch, 'request_to');
        if (count($requested) > 0) {
            $lists = new Query();
            $lists = User::find();
            $lists->joinWith('userProfile as uP');
            $lists->andwhere(['uP.groupid' => Groups::GROUP_HOSPITAL]);

            $lists->andWhere(['user.status' => User::STATUS_ACTIVE, 'user.admin_status' => User::STATUS_ADMIN_APPROVED]);
            $lists->andWhere(['user.id' => $requested]);
            $lists->select(['uP.name', 'uP.user_id']);
            $lists->all();
            $command = $lists->createCommand();
            $lists = $command->queryAll();
            $listdoctor = array();
            if (!empty($lists)) {
                foreach ($lists as $value) {
                    $listdoctor[$value['user_id']] = $value['name'];
                }
            }
            return $listdoctor;
        }
        return [];
    }

    public static function userFavoriteUpsert($post) {
        $favorite = UserFavorites::find()->where(['user_id' => $post['user_id'], 'profile_id' => $post['profile_id']])->one();
        if (empty($favorite)) {
            $post['status'] = $post['status'];
            $favorite = new UserFavorites();
            $status = $post['status'];
        } else {
            $status = $post['status'];
        }
        $favorite->load(['UserFavorites' => $post]);
        if ($favorite->save()) {
            return $favorite->status;
        }
        return false;
    }

    public static function checkFavorite($from, $to) {
        return UserFavorites::find()->andwhere(['user_id' => $from, 'profile_id' => $to])->one();
    }

    public static function membersList($user_id) {
        return PatientMembers::find()->where(['user_id' => $user_id])->orderBy('id desc')->all();
    }

    public function membersListFiles($user_id) {
        $records_list = PatientMemberRecords::find()->andWhere(['member_id' => $user_id])->all();
        $files = array();
        foreach ($records_list as $record) {
            $files[] = $record->files_id;
        }

        $PatientMembersData = PatientMemberFiles::find()->where(['id' => $files])->all();
        $rowimgs = array();
        foreach ($PatientMembersData as $value) {
            /* $rowData['imgData'] = $value['image_base_url'].$value['image_path'].$value['image']; */
            $rowimgsData['id'] = $value['id'];
            $rowimgsData['image'] = $value['image_base_url'] . $value['image_path'] . $value['image'];
            $rowimgsData['image_type'] = $value['image_type'];
            $rowimgsData['record_label'] = $value['image_name'];
            $rowimgs[] = $rowimgsData;
        }
        return $rowimgs;
    }

    public static function doctorAddMember($post) {

        $member = PatientMembers::find()->where(['member_phone' => $post['member_phone']])->one();
        if (empty($member))
            $member = new PatientMembers();
        $member->load(['PatientMembers' => $post]);
        if ($member->save()) {
            return true;
        }
        return false;
    }

    public static function memberUpsert($post, $files) {
        if (isset($post['member_id']) && !empty($post['member_id'])) {
            $member = PatientMembers::findOne($post['member_id']);
        } else {
            $member = new PatientMembers();
        }
        $member->load(['PatientMembers' => $post]);
        if ($member->save()) {
            $detail = PatientMembers::find()->where(['id' => $member->id])->one();
            //DrsImageUpload::memberImages($detail,$files);
            return $member->id;
        }
        return false;
    }

    public static function userBookingCount($user_id) {

        if ($user = User::findOne($user_id)) {
            if ($user->groupid == 6) {
                $search['doctor_id'] = $user->parent_id;
                $search['attender_id'] = $user->id;
            } else if ($user->groupid == 4) {
                $search['doctor_id'] = $user->parent_id;
            } else if ($user->groupid == 3) {
                $search['user_id'] = $user->id;
            } else {
                $search['user_id'] = 0;
            }
            return UserAppointment::find()->andWhere($search)->count();
        }
        return 0;
    }

    public static function attenderDelete($id) {
        $attender = User::findOne($id);
        $userProfile = UserProfile::findOne($id);
        $addupdateAttender = DrsPanel::addUpdateAttenderToShifts(array(), $attender->id);
        $cond['id'] = $attender->id;
        $cond1['user_id'] = $userProfile->user_id;
        User::deleteAll($cond);
        UserProfile::deleteAll($cond1);
        return true;
    }

    public static function addressDelete($id) {

        return true;
    }

    public static function weekSchedules($id, $weekday = NULL) {
        if ($user = User::find()->where(['id' => $id])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one()) {
            $schedules = new Query();
            $schedules = UserSchedule::find();
            $schedules->andWhere(['user_id' => $id]);
            if ($weekday) {
                $schedules->andWhere(['weekday' => $weekday]);
                $schedules->all();
                $command = $schedules->createCommand();
                $schedules = $command->queryAll();
                return $schedules;
            }
            $week_array = DrsPanel::getWeekArray();
            $list = [];
            foreach ($week_array as $week) {

                $schedules->andWhere(['weekday' => $week]);
                $schedules->all();
                $command = $schedules->createCommand();
                $items = $command->queryAll();
                $list[$week] = $items;
            }
            return $list;
        }
        return [];
    }

    public function myPatients($params = NULL) {
        $offset = 0;
        $recordlimit = 3;
        $totalpages = 0;
        $count_result = 0;
        if (isset($params['offset']) && $params['offset'] != '') {
            $offset = $params['offset'];
        }
        $lists = new Query();
        $lists = UserAppointment::find();
        $lists->where(['doctor_id' => $params['doctor_id']]);
        $lists->select(['user_id', 'user_phone'])->distinct();

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

        $appointments = $lists;

        $userList = $data_array = array();
        $i = 0;
        foreach ($appointments as $appoint) {
            $appointment = UserAppointment::find()->where(['user_id' => $appoint['user_id'], 'user_phone' => $appoint['user_phone']])->orderBy('id desc')->one();
            if ($appointment->user_id != 0) {
                $check = $appointment->user_id . '-' . $appointment->user_phone;
                if (!in_array($check, $userList)) {
                    $userList[] = $check;
                    $data_array[$i]['patient_id'] = $appointment->user_id;
                    $data_array[$i]['patient_image'] = DrsPanel::getUserAvator($appointment->user_id);
                    $data_array[$i]['patient_name'] = ucfirst($appointment->user_name);
                    $data_array[$i]['patient_phone'] = $appointment->user_phone;
                    $data_array[$i]['patient_address'] = $appointment->user_address;
                    $data_array[$i]['patient_gender'] = $appointment->user_gender;
                    $data_array[$i]['patient_age'] = $appointment->user_age;
                    $i++;
                }
            } else {
                $data_array[$i]['patient_id'] = $appointment->user_id;
                $data_array[$i]['patient_image'] = '';
                $data_array[$i]['patient_name'] = ucfirst($appointment->user_name);
                $data_array[$i]['patient_phone'] = $appointment->user_phone;
                $data_array[$i]['patient_address'] = $appointment->user_address;
                $data_array[$i]['patient_gender'] = $appointment->user_gender;
                $data_array[$i]['patient_age'] = $appointment->user_age;
                $i++;
            }
        }

        $response['pagination'] = $totallist;
        $response['data'] = $data_array;
        return $response;
    }

    public static function appointmentHistory1($userModel, $date, $type = NULL, $booking_type = NULL) {
        $history = new Query();
        $history = UserAppointment::find();
        $history->where(['is_deleted' => 0, 'date' => $date]);
        if ($booking_type) {
            $history->andWhere(['booking_type' => $booking_type]);
        }
        if ($userModel->groupid == Groups::GROUP_ATTENDER) {
            $doctor_id = $userModel->parent_id;
            if ($type == Groups::GROUP_DOCTOR)
                $history->andWhere(['doctor_id' => $userModel->parent_id]);
            $history->andWhere(['attender_id' => $userModel->id]);
            $doctor_id = 0;
        } else if ($userModel->groupid == Groups::GROUP_HOSPITAL) {
            if ($type == Groups::GROUP_DOCTOR) {
                $history->andWhere(['doctor_id' => '']);
            } else if ($userModel->groupid == Groups::GROUP_ATTENDER) {
                $history->andWhere(['attender_id' => '']);
            }
            $doctor_id = 0;
            $history->andWhere(['doctor_id' => 0]);
        } else if ($userModel->groupid == Groups::GROUP_DOCTOR) {
            $history->andWhere(['doctor_id' => $userModel->id]);
            $doctor_id = $userModel->id;
        } else if ($userModel->groupid == Groups::GROUP_PATIENT) {
            $doctor_id = 0;
            $history->andWhere(['user_id' => $userModel->id]);
        }
        $current_shifts = 0;
        $patient = $total_appointed = $total_cancelled = $completed = $offline = $online = $history;
        $history->all();
        $command = $history->createCommand();
        $lists = $command->queryAll();
        $result['patient'] = DrsPanel::getTotalAppointment($patient);
        $appointment = DrsPanel::getCurrentAppointments($doctor_id, $date, $current_shifts);
        $result['total_appointed'] = DrsPanel::getTotalAppointment($total_appointed, 'total_appointed');
        $result['total_cancelled'] = DrsPanel::getTotalAppointment($total_cancelled, 'total_cancelled');
        $result['completed'] = DrsPanel::getTotalAppointment($completed, 'completed');
        $result['offline'] = DrsPanel::getTotalAppointment($offline, 'offline');
        $result['online'] = DrsPanel::getTotalAppointment($online, 'online');
        $result['shifts'] = $appointment['shifts'];
        $result['data'] = DrsPanel::appointmentListData($lists);
        return $result;
    }

    public static function appointmentHistory($doctor_id, $date, $current_selected, $shifts = [], $typewise = '') {
        $getShiftSlots = array();
        $bookings = array();

        foreach ($shifts as $shift) {
            $getShiftSlots[] = $shift['schedule_id'];
        }
        $total_history = $type = array();
        if ($typewise == '') {
            if ($current_selected == 0) {
                $getShiftSlots = $getShiftSlots;
            } else {
                $getShiftSlots = $current_selected;
            }
            $lists = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $getShiftSlots])->andWhere('`deleted_at` IS NULL')->orderBy('token asc')->all();
        } else {
            if ($current_selected == 0) {
                $getShiftSlots = $getShiftSlots;
            } else {
                $getShiftSlots = $current_selected;
            }
            $lists = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $getShiftSlots])->andWhere('`deleted_at` IS NULL')->orderBy('token asc')->all();
        }
        $total_history['total_patient'] = 0;
        $completed = 0;
        $cancelled = 0;
        $notapp = 0;
        $online = 0;
        $offline = 0;
        if (!empty($lists)) {
            foreach ($lists as $list) {

                if ($list->booking_type == UserAppointment::BOOKING_TYPE_ONLINE) {
                    $online += 1;
                } else {
                    $offline += 1;
                }

                if ($list->status == UserAppointment::STATUS_COMPLETED) {
                    $completed += 1;
                } elseif ($list->status == UserAppointment::STATUS_CANCELLED) {
                    $cancelled += 1;
                } else {
                    $notapp += 1;
                }
            }
        }

        $total_history['total_appointed'] = $completed;
        $total_history['total_offline'] = $offline;
        $total_history['total_online'] = $online;
        $total_history['total_cancelled'] = $cancelled;
        $total_history['total_not_appointed'] = $notapp;

        if ($typewise == '') {
            $booking_type = array(UserAppointment::BOOKING_TYPE_ONLINE, UserAppointment::BOOKING_TYPE_OFFLINE);
            $typeselected = '';
        } else {
            $booking_type = $typewise;
            $typeselected = $booking_type;
        }

        $currentShiftData = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $current_selected, 'booking_type' => $booking_type])->andWhere('deleted_at IS NULL')->orderBy('token asc')->all();
        foreach ($currentShiftData as $data) {
            $appointment = UserAppointment::findOne($data->id);
            $bookings[] = DrsPanel::getappointmentarray($appointment);
        }
        $type['online'] = $online;
        $type['offline'] = $offline;
        return array('schedule_id' => $current_selected, 'date' => $date, 'total_history' => $total_history, 'type' => $type, 'typeselected' => $typeselected, 'bookings' => $bookings);
    }

    public static function appointmentListData($lists) {
        $result = array();
        if (count($lists)) {
            foreach ($lists as $key => $appointment) {
                $data['id'] = $appointment['id'];
                $data['token'] = $appointment['token'];
                $data['name'] = ucfirst($appointment['user_name']);
                $data['age'] = $appointment['user_age'];
                $data['gender'] = $appointment['user_gender'];
                $data['user_address'] = $appointment['user_address'];
                $data['payment_type'] = $appointment['payment_type'];
                $data['phone'] = $appointment['user_phone'];
                $data['appointment_type'] = $appointment['type'];
                $data['appointment_date'] = $appointment['date'];
                $data['start_time'] = $appointment['start_time'];
                $selectedTime = $appointment['start_time'];
                if ($appointment['token'] > 1) {
                    $approxTime = date('h:i a', $appointment['end_time']);
                } else {
                    $approxTime = date('h:i a', $appointment['start_time']);
                }
                $data['appointment_approx_time'] = $approxTime;
                $data['fees'] = $appointment['doctor_fees'];
                $data['status'] = $appointment['status'];
                $data['shift_name'] = $appointment['shift_name'];
                $data['patient_image'] = '';
                if ($appointment['user_id'] > 0) {
                    $data['patient_image'] = DrsPanel::getUserAvator($appointment['user_id']);
                }
                $result[] = $data;
            }
        }
        return $result;
    }

    public static function getTotalAppointment($list, $count_type = NULL) {

        if ($count_type == 'total_appointed') {
            $list->andWhere(['status' => array(UserAppointment::STATUS_PENDING, UserAppointment::STATUS_SKIP)]);
        } else if ($count_type == 'total_cancelled') {
            $list->andWhere(['status' => UserAppointment::STATUS_CANCELLED]);
        } else if ($count_type == 'completed') {
            $list->andWhere(['status' => UserAppointment::STATUS_COMPLETED]);
        }

        return $list->count();
    }

    public static function getHospitalDoctors($hospital_id, $doctor_id = NULL, $attender_id = NULL) {
        $confirmDrSearch['status'] = UserRequest::Request_Confirmed;
        $confirmDrSearch['request_from'] = $hospital_id;
        $confirmDrSearch['groupid'] = Groups::GROUP_HOSPITAL;
        if ($doctor_id) {
            $confirmDrSearch['request_to'] = $doctor_id;
        }
        $confirmDr = UserRequest::requestedUser($confirmDrSearch, 'request_from');
        if ($attender_id) {
            
        } else {
            return $confirmDr;
        }
    }

    public static function getShiftLimit($user_id, $date, $shift) {
        $shifts = array();
        $week = DrsPanel::getDateWeekDay($date);
        $getScheduleDay = UserScheduleDay::find()->where(['user_id' => $user_id, 'date' => $date])->all();
        $getSchedule = UserSchedule::find()->where(['user_id' => $user_id, 'weekday' => $week])->all();
        if (!empty($getScheduleDay || !empty($getSchedule))) {
            $total_limit = 0;
            $total_booked = 0;
            $detailsadd = DrsPanel::getShiftDetail($user_id, $week, $date);
            $shifts['shifts'] = $detailsadd;
            if (!empty($detailsadd)) {
                $total_limit = $detailsadd['patient_limit'];
                $total_booked = $detailsadd['booked'];
            }

            $shifts['total_limit'] = $total_limit;
            $shifts['total_booked'] = $total_booked;
            return $shifts;
        } else {
            return $shifts;
        }
    }

    public static function getUserSetting($user_id, $key = NULL) {
        $search['user_id'] = $user_id;
        if ($key) {
            $search['key_name'] = $key;
        }
        $settings = UserSettings::find()->andWhere($search)->one();
        if ($settings) {

            if ($key) {
                if ($settings->key_name == $key) {
                    return $settings->key_value;
                } else {
                    return 0;
                }
            }
            return $settings;
        }
        return 0;
    }

    public static function dateWiseHospitalDoctors($hospitals_id, $date, $userType) {
        $confirmDrSearch = ['status' => UserRequest::Request_Confirmed, 'request_from' => $hospitals_id, 'groupid' => Groups::GROUP_HOSPITAL];
        $confirmDr = UserRequest::requestedUser($confirmDrSearch, 'request_from');
        $search_data['doctor_id'] = $confirmDr;
        if ($date) {
            $search_data['date'] = $date;
        }
        if ($userType->groupid == Groups::GROUP_ATTENDER) {
            $search_data['attender_id'] = $userType->id;
        }
        $doctors = UserAppointment::find()->where($search_data)->select(['doctor_id'])->all();
        $ids = implode(',', ArrayHelper::getColumn($doctors, "doctor_id"));
        if ($ids)
            return $ids = explode(',', $ids);
        else
            return 0;
    }

    /*
     * Function used to fetch attender parent type
     */

    public static function getAttenderParentType($id) {
        $groupid = 0;
        $attender = User::find()->where(['id' => $id])->one();
        if (!empty($attender)) {
            $parent = User::find()->where(['id' => $attender->parent_id])->one();
            $groupid = $parent->groupid;
        }
        return $groupid;
    }

    /*
     * Function used to fetch hospital doctor request status
     */

    public static function sendRequestCheck($hospital_id, $doctor_id) {
        $request = UserRequest::find()->where(['request_from' => $hospital_id, 'request_to' => $doctor_id])->one();
        if (!empty($request)) {
            if ($request->status == 1) {
                return 'requested';
            } else {
                return 'confirmed';
            }
        } else {
            return 'pending';
        }
    }

    /*
     * Function used to fetch doctors list for hospital & hospital attender
     */

    public static function doctorsHospitalList($hospital_id, $status, $usergroup = Groups::GROUP_HOSPITAL, $attender_id, $search = array()) {
        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => Groups::GROUP_DOCTOR]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);

        $user_list = array();
        if (!empty($search)) {
            if (isset($search['current']) && isset($search['shift'])) {
                $getAvailabilityUser = DrsPanel::getAllAvailibilityUser('today');
                if (!empty($user_list)) {
                    $user_list = array_intersect($user_list, $getAvailabilityUser);
                } else {
                    $user_list = $getAvailabilityUser;
                }
            } elseif (isset($search['shift'])) {
                $user_list = DrsPanel::getAllAvailibilityUser();
            }
        }

        if ($usergroup == Groups::GROUP_HOSPITAL) {
            if ($status == 'Confirm') {
                $confirmDrSearch = ['status' => UserRequest::Request_Confirmed, 'request_from' => $hospital_id, 'groupid' => Groups::GROUP_HOSPITAL];
                $confirmDr = UserRequest::requestedUser($confirmDrSearch, 'request_from');
                if (isset($search['current']) || isset($search['shift'])) {
                    $user_list = array_intersect($user_list, $confirmDr);
                } else {
                    $user_list = $confirmDr;
                }
                $lists->andWhere(['user.id' => $user_list]);
            } elseif ($status == 'Requested') {
                $requested = ['request_from' => $hospital_id, 'groupid' => Groups::GROUP_HOSPITAL, 'status' => UserRequest::Requested];
                $requested = UserRequest::requestedUser($requested, 'request_from');

                if (isset($search['current']) || isset($search['shift'])) {
                    $user_list = array_intersect($user_list, $requested);
                } else {
                    $user_list = $requested;
                }
                $lists->andWhere(['user.id' => $user_list]);
            } else {
                if (!empty($user_list)) {
                    $lists->andWhere(['user.id' => $user_list]);
                }
            }
        } else {
            if ($status == 'Confirm') {
                $result = [];
                $query = HospitalAttender::find()->where(['hospital_id' => $hospital_id, 'attender_id' => $attender_id])->all();
                if (count($query) > 0) {
                    foreach ($query as $key => $value) {
                        $result[] = $value->doctor_id;
                    }
                }
                $lists->andWhere(['user.id' => $result]);
            } else {
                
            }
        }

        return $lists;
    }

    public static function doctorsFavoriteList($hospital_id, $status, $usergroup = Groups::GROUP_HOSPITAL, $attender_id, $search = array()) {

        $query = new Query;
        $query->select([
                    'tbl_user.username AS name',
                    'tbl_category.categoryname as  Category',
                    'tbl_document.documentname']
                )
                ->from('tbl_user')
                ->join('LEFT OUTER JOIN', 'tbl_category', 'tbl_category.createdby =tbl_user.userid')
                ->join('LEFT OUTER JOIN', 'tbl_document', 'tbl_category.cid =tbl_document.did')
                ->LIMIT(5);

        $command = $query->createCommand();
        $data = $command->queryAll();
        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => Groups::GROUP_DOCTOR]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => User::STATUS_ADMIN_LIVE_APPROVED]);

        $user_list = array();
        if (!empty($search)) {
            if (isset($search['current']) && isset($search['shift'])) {
                $getAvailabilityUser = DrsPanel::getAllAvailibilityUser('today');
                if (!empty($user_list)) {
                    $user_list = array_intersect($user_list, $getAvailabilityUser);
                } else {
                    $user_list = $getAvailabilityUser;
                }
            } elseif (isset($search['shift'])) {
                $user_list = DrsPanel::getAllAvailibilityUser();
            }
        }

        if ($usergroup == Groups::GROUP_HOSPITAL) {
            if ($status == 'Confirm') {
                $confirmDrSearch = ['status' => UserRequest::Request_Confirmed, 'request_from' => $hospital_id, 'groupid' => Groups::GROUP_HOSPITAL];
                $confirmDr = UserRequest::requestedUser($confirmDrSearch, 'request_from');
                if (isset($search['current']) || isset($search['shift'])) {
                    $user_list = array_intersect($user_list, $confirmDr);
                } else {
                    $user_list = $confirmDr;
                }
                $lists->andWhere(['user.id' => $user_list]);
            } elseif ($status == 'Requested') {
                $requested = ['request_from' => $hospital_id, 'groupid' => Groups::GROUP_HOSPITAL];
                $requested = UserRequest::requestedUser($requested, 'request_from');

                if (isset($search['current']) || isset($search['shift'])) {
                    $user_list = array_intersect($user_list, $requested);
                } else {
                    $user_list = $requested;
                }
                $lists->andWhere(['user.id' => $user_list]);
            } else {
                if (!empty($user_list)) {
                    $lists->andWhere(['user.id' => $user_list]);
                }
            }
        } else {
            if ($status == 'Confirm') {
                $result = [];
                $query = HospitalAttender::find()->where(['hospital_id' => $hospital_id, 'attender_id' => $attender_id])->all();
                if (count($query) > 0) {
                    foreach ($query as $key => $value) {
                        $result[] = $value->doctor_id;
                    }
                }
                $lists->andWhere(['user.id' => $result]);
            } else {
                
            }
        }

        return $lists;
    }

    public static function hospitalsList($hospital_id, $status, $usergroup = Groups::GROUP_HOSPITAL, $attender_id, $search = array()) {
        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => Groups::GROUP_HOSPITAL]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => User::STATUS_ADMIN_LIVE_APPROVED]);

        $user_list = array();
        if (!empty($search)) {
            if (isset($search['current']) && isset($search['shift'])) {
                $getAvailabilityUser = DrsPanel::getAllAvailibilityUser('today');
                if (!empty($user_list)) {
                    $user_list = array_intersect($user_list, $getAvailabilityUser);
                } else {
                    $user_list = $getAvailabilityUser;
                }
            } elseif (isset($search['shift'])) {
                $user_list = DrsPanel::getAllAvailibilityUser();
            }
        }

        if ($usergroup == Groups::GROUP_HOSPITAL) {
            if ($status == 'Confirm') {
                $confirmDrSearch = ['status' => UserRequest::Request_Confirmed, 'request_to' => $hospital_id, 'groupid' => Groups::GROUP_DOCTOR];
                $confirmDr = UserRequest::requestedUser($confirmDrSearch, 'request_from');
                if (isset($search['current']) || isset($search['shift'])) {
                    $user_list = array_intersect($user_list, $confirmDr);
                } else {
                    $user_list = $confirmDr;
                }
                $lists->andWhere(['user.id' => $user_list]);
            } elseif ($status == 'Requested') {
                $requested = ['request_to' => $hospital_id, 'groupid' => Groups::GROUP_HOSPITAL];
                $requested = UserRequest::requestedUser($requested, 'request_to');

                if (isset($search['current']) || isset($search['shift'])) {
                    $user_list = array_intersect($user_list, $requested);
                } else {
                    $user_list = $requested;
                }
                $lists->andWhere(['user.id' => $user_list]);
            } else {
                if (!empty($user_list)) {
                    $lists->andWhere(['user.id' => $user_list]);
                }
            }
        } else {
            if ($status == 'Confirm') {
                $result = [];
                $query = HospitalAttender::find()->where(['hospital_id' => $hospital_id, 'attender_id' => $attender_id])->all();
                if (count($query) > 0) {
                    foreach ($query as $key => $value) {
                        $result[] = $value->doctor_id;
                    }
                }
                $lists->andWhere(['user.id' => $result]);
            } else {
                
            }
        }
        /* $command = $lists->createCommand();
          $lists = $command->queryAll();
          echo '<pre>';
          print_r($lists);die; */
        return $lists;
    }

    /*
     * Function used to fetch schedules availabilty user list
     */

    public static function getAllAvailibilityUser($type = 'all') {
        $user = array();
        if ($type == 'today') {
            $date = date('Y-m-d');
            $weekDay = DrsPanel::getDateWeekDay($date);
            $schedules = UserSchedule::find()->where(['weekday' => $weekDay])->all();
        } else {
            $schedules = UserSchedule::find()->all();
        }
        foreach ($schedules as $data) {
            $user[$data->user_id] = $data->user_id;
        }
        return $user;
    }

    /*
     * Function used to fetch hospital my doctors id's list
     */

    public static function myHospitalDoctors($hospital_id, $status) {
        $doctorslist = array();
        $lists = DrsPanel::doctorsHospitalList($hospital_id, $status, Groups::GROUP_HOSPITAL, $hospital_id);
        $command = $lists->createCommand();
        $requests = $command->queryAll();
        if (!empty($requests)) {
            foreach ($requests as $request) {
                $doctorslist[] = $request['user_id'];
            }
        }
        return $doctorslist;
    }

    /*
     * Function used to fetch hospital speciality with treatments on the basis of hospital doctors
     */

    public static function getMyHospitalSpeciality($hospital_id) {
        $speciality = '';
        $treatment = '';
        $mydoctors = DrsPanel::myHospitalDoctors($hospital_id, 'Confirm');
        if (!empty($mydoctors)) {
            $list_speciality = '';
            $list_treatments = '';
            $k = 0;
            foreach ($mydoctors as $id) {
                $doctor = UserProfile::findOne($id);
                if ($list_speciality == '') {
                    $list_speciality .= $doctor->speciality;
                } else {
                    if (!empty($doctor->speciality)) {
                        $list_speciality .= ',' . $doctor->speciality;
                    }
                }
                if ($list_treatments == '') {
                    $list_treatments .= $doctor->treatment;
                } else {
                    if (!empty($doctor->treatment)) {
                        $list_treatments .= ',' . $doctor->treatment;
                    }
                }
                $k++;
            }
            if ($list_speciality != '') {
                $special_list = explode(',', $list_speciality);
                foreach ($special_list as $s) {
                    $speciality[$s] = $s;
                }
                $speciality = implode(',', $speciality);
            }

            if ($list_treatments != '') {
                $treat_list = explode(',', $list_treatments);
                foreach ($treat_list as $t) {
                    $treatment[$t] = $t;
                }
                $treatment = implode(',', $treatment);
            }
        }
        return array('speciality' => $speciality, 'treatments' => $treatment);
    }

    public static function getAboutUs($user_id) {
        $data = '';
        $aboutus = UserAboutus::find()->where(['user_id' => 162])->one();
        return true;
        if (!empty($aboutus->description) && !empty($aboutus)) {
            $data['description'] = strip_tags($aboutus->description);
            $data['vision'] = strip_tags($aboutus->vision);
            $data['mission'] = strip_tags($aboutus->mission);
            $data['timing'] = strip_tags($aboutus->timing);
        }
        return $data;
    }

    /*
     * Function used to fetch doctors related to hospital attender
     */

    public static function getAttenderDoctors($attender_id) {
        $doctor_list = array();
        $lists = HospitalAttender::find()->where(['attender_id' => $attender_id])->all();
        foreach ($lists as $list) {
            $doctor_list[] = $list->doctor_id;
        }
        return $doctor_list;
    }

    public static function generateBookingID() {
        $appointments = UserAppointment::find()->orderBy('id desc')->one();
        if (!empty($appointments)) {
            $newToken = $appointments->booking_id + 1;
            $newToken = (string) $newToken;
        } else {
            $newToken = (string) 1001;
        }
        return $newToken;
    }

    public static function addUpdateAttenderToShifts($shifts, $attender_id) {
        if (!empty($shifts)) {

            $oldshifts = UserSchedule::find()->where(['shift_belongs_to' => 'attender'])
                            ->andWhere('find_in_set(:key2, `attender_id`)', [':key2' => $attender_id])->all();
            $oldshift_array = array();
            // remove shift
            foreach ($oldshifts as $oldshift) {
                $oldshift_array[] = $oldshift->id;
                if (!in_array($oldshift->id, $shifts)) {
                    $shift = UserSchedule::findOne($oldshift->id);
                    if (!empty($shift)) {
                        $old_attenders = $shift->attender_id;
                        $shift->shift_belongs_to = 'attender';
                        $explode_attender = explode(',', $old_attenders);
                        if (($keyc = array_search($attender_id, $explode_attender)) !== false) {
                            unset($explode_attender[$keyc]);
                        }
                        if (empty($explode_attender)) {
                            $shift->attender_id = 0;
                        } else {
                            $shift->attender_id = implode(',', $explode_attender);
                        }
                        $shift->hospital_id = 0;
                        if ($shift->save()) {
                            $shiftday = DrsPanel::attenderDaySchedule($shift->id);
                            $schedulegroup = DrsPanel::attenderGroupSchedule($shift->id);
                        }
                    }
                }
            }

            // add shift
            foreach ($shifts as $key => $shift_id) {
                $shift = UserSchedule::findOne($shift_id);
                $old_attenders = $shift->attender_id;
                if (!empty($shift)) {
                    $shift->shift_belongs_to = 'attender';
                    if (!empty($old_attenders) || $old_attenders != 0) {
                        $explode_attender = explode(',', $old_attenders);
                        if (!in_array($attender_id, $explode_attender)) {
                            array_push($explode_attender, $attender_id);
                            $shift->attender_id = implode(',', $explode_attender);
                        }
                    } else {
                        $shift->attender_id = $attender_id;
                    }
                    $shift->hospital_id = 0;
                    if ($shift->save()) {
                        $shiftday = DrsPanel::attenderDaySchedule($shift->id);
                        $schedulegroup = DrsPanel::attenderGroupSchedule($shift->id);
                    }
                }
            }
        } else {
            $shifts = UserSchedule::find()->where(['shift_belongs_to' => 'attender'])
                            ->andWhere('find_in_set(:key2, `attender_id`)', [':key2' => $attender_id])->all();
            if (!empty($shifts)) {
                foreach ($shifts as $key => $shiftlist) {
                    $shift = UserSchedule::findOne($shiftlist->id);
                    $old_attenders = $shift->attender_id;
                    if (!empty($shift)) {
                        if (!empty($old_attenders) || $old_attenders != 0) {
                            $shift->shift_belongs_to = 'attender';
                            $explode_attender = explode(',', $old_attenders);
                            if (in_array($attender_id, $explode_attender)) {
                                if (($key = array_search($attender_id, $explode_attender)) !== false) {
                                    unset($explode_attender[$key]);
                                }
                                $shift->attender_id = implode(',', $explode_attender);
                            } else {
                                $shift->attender_id = $old_attenders;
                            }
                            $shift->hospital_id = 0;
                        }
                        if ($shift->save()) {
                            $shiftday = DrsPanel::attenderDaySchedule($shift->id);
                            $schedulegroup = DrsPanel::attenderGroupSchedule($shift->id);
                        }
                    }
                }
            }
        }
        return true;
    }

    public static function attenderDaySchedule($shiftid) {
        $shift = UserSchedule::findOne($shiftid);
        $shiftday = UserScheduleDay::find()->where(['schedule_id' => $shift->id])->all();
        if (!empty($shiftday)) {
            foreach ($shiftday as $days) {
                $days->shift_belongs_to = $shift->shift_belongs_to;
                $days->attender_id = $shift->attender_id;
                $days->hospital_id = $shift->hospital_id;
                $days->save();
            }
        }
        return true;
    }

    public static function attenderGroupSchedule($shiftid) {
        $shift = UserSchedule::findOne($shiftid);
        $schedulegroup = UserScheduleGroup::find()->where(['schedule_id' => $shift->id])->all();
        if (!empty($schedulegroup)) {
            foreach ($schedulegroup as $groups) {
                $groups->shift_belongs_to = $shift->shift_belongs_to;
                $groups->attender_id = $shift->attender_id;
                $groups->hospital_id = $shift->hospital_id;
                $groups->save();
            }
        }
        return true;
    }

    public static function addUpdateDoctorsToHospitalAttender($doctors, $attender_id, $hospital_id) {
        $userAddress = UserAddress::find()->where(['user_id' => $hospital_id])->one();
        if (!empty($userAddress)) {
            $address_id = $userAddress->id;
        } else {
            $address_id = 0;
        }

        if (!empty($doctors)) {
            $hospital_attenders = HospitalAttender::find()
                            ->where(['attender_id' => $attender_id, 'hospital_id' => $hospital_id])->all();
            if (!empty($hospital_attenders)) {
                HospitalAttender::deleteAll(['attender_id' => $attender_id, 'hospital_id' => $hospital_id]);
            }
            foreach ($doctors as $key => $doctor_id) {
                $doctor = User::findOne($doctor_id);
                if ($doctor) {
                    $hospital_attender = new HospitalAttender();
                    $hospital_attender->attender_id = $attender_id;
                    $hospital_attender->hospital_id = $hospital_id;
                    $hospital_attender->address_id = $address_id;
                    $hospital_attender->doctor_id = $doctor_id;

                    $hospital_attender->save();
                    Yii::$app->session->setFlash('success', "Attender Added!");
                }
            }
        } else {
            $hospital_attenders = HospitalAttender::find()
                            ->where(['attender_id' => $attender_id, 'hospital_id' => $hospital_id])->all();
            if (!empty($hospital_attenders)) {
                HospitalAttender::deleteAll(['attender_id' => $attender_id, 'hospital_id' => $hospital_id]);
            }
        }
        return true;
    }

    public static function getDoctorAllShift($doctor_id, $date, $selected_shift, $schedule_check, $default_check) {
        $weekDay = DrsPanel::getDateWeekDay($date);
        $response = array();
        $s = 0;
        if (!empty($schedule_check)) {
            foreach ($schedule_check as $key => $schedule) {
                $shiftData = Drspanel::getShiftDetail($doctor_id, $weekDay, $schedule['shift'], $date);
                if (!empty($shiftData)) {
                    $response[$s] = $shiftData;
                    if ($default_check == '') {
                        if ($schedule['schedule_id'] == $selected_shift) {
                            $response[$s]['isChecked'] = true;
                        } else {
                            $response[$s]['isChecked'] = false;
                        }
                    } else {
                        if ($schedule['schedule_id'] == $default_check) {
                            $response[$s]['isChecked'] = true;
                        } else {
                            $response[$s]['isChecked'] = false;
                        }
                    }
                    if ($schedule['status'] == 'pending') {
                        $response[$s]['is_started'] = false;
                        $response[$s]['is_completed'] = false;
                        $response[$s]['is_cancelled'] = false;
                    } elseif ($schedule['status'] == 'current') {
                        $response[$s]['is_started'] = true;
                        $response[$s]['is_completed'] = false;
                        $response[$s]['is_cancelled'] = false;
                    } elseif ($schedule['status'] == 'cancelled') {
                        $response[$s]['is_started'] = false;
                        $response[$s]['is_completed'] = false;
                        $response[$s]['is_cancelled'] = true;
                    } elseif ($schedule['status'] == 'completed') {
                        $response[$s]['is_started'] = false;
                        $response[$s]['is_completed'] = true;
                        $response[$s]['is_cancelled'] = false;
                    }
                    $response[$s]['date'] = $date;
                    $s++;
                }
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'No Shifts for today';
        }
        return $response;
    }

    public static function getDoctorCurrentShift($shifts) {
        $response = array();
        if (!empty($shifts)) {
            foreach ($shifts as $schedule) {
                if ($schedule['status'] == 'completed') {
                    $response['status'] = 'success_appointment_completed';
                    $response['shift_id'] = $schedule['schedule_id'];
                    $response['shift_label'] = $schedule['shift_name'];
                    $response['message'] = 'Shift Completed';
                } elseif ($schedule['status'] == 'cancelled') {
                    $response['status'] = 'cancelled';
                    $response['shift_id'] = $schedule['schedule_id'];
                    $response['shift_label'] = $schedule['shift_name'];
                    $response['message'] = 'Shift Cancelled';
                } elseif ($schedule['status'] == 'current') {
                    $response['status'] = 'success_appointment';
                    $response['shift_id'] = $schedule['schedule_id'];
                    $response['shift_label'] = $schedule['shift_name'];
                    $response['message'] = 'Shift Started';
                    break;
                } elseif ($schedule['status'] == 'pending') {
                    $response['status'] = 'success';
                    $response['shift_id'] = $schedule['schedule_id'];
                    $response['shift_label'] = $schedule['shift_name'];
                    $response['message'] = 'Shift Not Started';
                    break;
                }
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'No Shifts for today';
        }
        return $response;
    }

    public static function fetchSpecialityCount($lists) {
        $category = array();
        $count = array();

        foreach ($lists as $list) {
            $profile = UserProfile::findOne($list['user_id']);
            if (!empty($profile->speciality) && $profile->speciality != '') {
                $speciality = explode(',', $profile->speciality);
                foreach ($speciality as $special) {
                    if (in_array($special, $category)) {
                        $count[$special] = $count[$special] + 1;
                    } else {
                        $category[] = $special;
                        $count[$special] = 1;
                    }
                }
            }
        }
        return $count;
    }

    public static function fetchHospitalSpecialityCount($lists) {
        $category = array();
        $count = array();
        foreach ($lists as $list) {
            $profile = UserProfile::findOne($list['user_id']);
            $specialities = DrsPanel::getMyHospitalSpeciality($profile->user_id);
            $specialities = $specialities['speciality'];
            if (!empty($specialities) && $specialities != '') {
                $speciality = explode(',', $specialities);
                foreach ($speciality as $special) {
                    $special = trim($special);
                    if (in_array($special, $category)) {
                        $count[$special] = $count[$special] + 1;
                    } else {
                        $category[] = $special;
                        $count[$special] = 1;
                    }
                }
            }
        }
        return $count;
    }

    public static function addHospitalSpecialityCount($lists) {
        foreach ($lists as $list) {
            $profile = UserProfile::findOne($list['user_id']);
            $listdata = DrsPanel::getMyHospitalSpeciality($profile->user_id);
            $specialities = $listdata['speciality'];
            $treatments = $listdata['treatments'];
            $checkList = HospitalSpecialityTreatment::find()->where(['hospital_id' => $profile->user_id])->one();
            if (empty($checkList)) {
                $checkList = new HospitalSpecialityTreatment();
            }
            $checkList->hospital_id = $profile->user_id;
            $checkList->speciality = $specialities;
            $checkList->treatment = $treatments;
            if ($checkList->save()) {
                $profile->speciality = $specialities;
                $profile->treatment = $treatments;
                $profile->save();
            }
        }
        return true;
    }

    public static function exportPatientHistoryExcel($doctor_id, $current_login, $from_date, $to_date) {

        $period = new \DatePeriod(
                new \DateTime($from_date), new \DateInterval('P1D'), new \DateTime($to_date)
        );

        $getShiftSlots = array();
        foreach ($period as $key => $value) {
            $date = $value->format('Y-m-d');
            $getSlots = DrsPanel::getBookingShifts($doctor_id, $date, $current_login);
            foreach ($getSlots as $shift) {
                $getShiftSlots[] = $shift['schedule_id'];
            }
        }

        $appointments = UserAppointment::find()->where(['between', 'date', $from_date, $to_date])->andWhere(['doctor_id' => $doctor_id, 'schedule_id' => $getShiftSlots, 'is_deleted' => 0])->orderBy('date asc', 'token asc')->all();

        $a = 0;
        $export_data = array();

        foreach ($appointments as $appointment) {
            $export_data[$a]['A'] = $a + 1;
            $export_data[$a]['B'] = $appointment->token;
            $export_data[$a]['C'] = $appointment->date;
            $export_data[$a]['D'] = $appointment->shift_label;
            $export_data[$a]['E'] = $appointment->shift_name;
            $export_data[$a]['F'] = $appointment->booking_type;
            $export_data[$a]['G'] = $appointment->payment_type;
            $export_data[$a]['H'] = $appointment->status;
            $export_data[$a]['I'] = $appointment->payment_status;
            $export_data[$a]['J'] = $appointment->user_id;
            $export_data[$a]['K'] = ucfirst($appointment->user_name);
            $export_data[$a]['L'] = $appointment->user_phone;
            $export_data[$a]['M'] = $appointment->user_address;
            $export_data[$a]['N'] = $appointment->user_gender;
            $export_data[$a]['O'] = $appointment->doctor_id;
            $export_data[$a]['P'] = $appointment->doctor_name;
            $export_data[$a]['Q'] = $appointment->doctor_phone;
            $export_data[$a]['R'] = $appointment->doctor_address;
            $a++;
        }

        $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        $template = Yii::getAlias('@frontend') . '/web/PatientHistory.xlsx';
        $objPHPExcel = $objReader->load($template);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_FOLIO);
        $baseRow = 2; // line 2

        foreach ($export_data as $export) {
            $keys = array_keys($export);
            foreach ($keys as $key) {
                $objPHPExcel->getActiveSheet()->setCellValue($key . $baseRow, $export[$key]);
            }
            $baseRow++;
        }


        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel2007");

        $path = Url::to('@frontendUrl');

        $dir = Url::to('@frontend');
        $dir = $dir . '/web/history/';
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $extension = "xlsx";
        $filename = time() . '.' . $extension;
        $filepath = $dir . $filename;
        $objWriter->save(str_replace(__FILE__, $filepath, __FILE__));

        $userInvoice = AppointmentHistory::find()->where(['user_id' => $current_login])->one();
        if (empty($userInvoice)) {
            $userInvoice = new AppointmentHistory();
        }
        $userInvoice->user_id = $current_login;
        $userInvoice->sheet_base_url = $path;
        $userInvoice->sheet_path = '/history/' . $filename;
        $userInvoice->save();

        $user = User::findOne(['id' => $current_login]);

        $message = \Yii::$app->mailer->compose()
                ->setFrom(['developer@brsoftech.com' => 'DrsPanel'])
                ->setTo($user->email)
                ->setSubject('Patient History')
                ->setTextBody('Patient History File');

        $message->attach($filepath);


        $message->send();

        return true;
    }

    public static function getShiftListByAddress($doctor_id, $address_id) {
        $getShiftSlots = array();
        $checkaddress_time = array();
        $days = DrsPanel::getWeekArray();


        foreach ($days as $day) {
            $shifts = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $day, 'address_id' => $address_id])
                            ->orderBy('shift asc')->all();


            $s = 0;
            foreach ($shifts as $shift) {
                // $userDaySchedule=UserScheduleDay::find()->where(['schedule_id'=>$shift->id,'user_id'=>$doctor_id,'weekday'=>$shift->weekday])->one();

                $address_id = $shift->address_id;
                $address_detail = UserAddress::find()->where(['id' => $address_id, /* 'status'=>1 */])->one();

                if (!empty($address_detail)) {
                    $approxStartTime = date('h:i a', $shift->start_time);
                    $approxEndTime = date('h:i a', $shift->end_time);
                    $patinet_limit_check = $shift->patient_limit;
                    $consultationfees = $shift->consultation_fees;
                    $consultationfeesdiscount = $shift->consultation_fees_discount;
                    $emergencyfees = $shift->emergency_fees;
                    $emergencyfeesdiscount = $shift->emergency_fees_discount;

                    $checkfees = $shift->consultation_fees . '-' . $consultationfeesdiscount . '-' . $emergencyfees . '-' . $emergencyfeesdiscount;


                    $line_shift = $address_id . '-' . $patinet_limit_check . '-' . $checkfees . '-' . $approxStartTime . '-' . $approxEndTime;
                    $line_shift = $address_id . '-' . $patinet_limit_check . '-' . $checkfees . '-' . $approxStartTime . '-' . $approxEndTime;
                    if (!in_array($line_shift, $checkaddress_time)) {
                        $checkaddress_time[] = $line_shift;
                        $getShiftSlots[$line_shift]['id'] = $address_id;
                        $getShiftSlots[$line_shift]['shift_id'] = $shift->id;
                        $getShiftSlots[$line_shift]['shiftno'] = $shift->shift;
                        $getShiftSlots[$line_shift]['address_id'] = $address_id;
                        $getShiftSlots[$line_shift]['hospital_id'] = $shift->hospital_id;
                        $getShiftSlots[$line_shift]['name'] = isset($address_detail->name) ? $address_detail->name : DrsPanel::getUserName($shift->hospital_id);
                        $getShiftSlots[$line_shift]['address'] = DrsPanel::getAddressLine($address_id);
                        $getShiftSlots[$line_shift]['address_line'] = DrsPanel::getAddressLine($address_id);
                        $getShiftSlots[$line_shift]['hospital_logo'] = DrsPanel::getAddressAvator($address_id);
                        $getShiftSlots[$line_shift]['hospital_images'] = DrsPanel::getAddressImageList($address_id);

                        $getShiftSlots[$line_shift]['start_time'] = $approxStartTime;
                        $getShiftSlots[$line_shift]['end_time'] = $approxEndTime;
                        $getShiftSlots[$line_shift]['start_time_original'] = $approxStartTime;
                        $getShiftSlots[$line_shift]['end_time_original'] = $approxEndTime;
                        $getShiftSlots[$line_shift]['shift_label'] = $approxStartTime . ' - ' . $approxEndTime;
                        $getShiftSlots[$line_shift]['shift_name'] = $approxStartTime . ' - ' . $approxEndTime;
                        $getShiftSlots[$line_shift]['consultation_fees'] = $shift->consultation_fees;
                        $getShiftSlots[$line_shift]['consultation_fees_discount'] = $shift->consultation_fees_discount;
                        $getShiftSlots[$line_shift]['emergency_fees'] = $shift->emergency_fees;
                        $getShiftSlots[$line_shift]['emergency_fees_discount'] = $shift->emergency_fees_discount;
                        $getShiftSlots[$line_shift]['appointment_time_duration'] = $shift->appointment_time_duration;
                        $getShiftSlots[$line_shift]['patient_limit'] = $shift->patient_limit;
                        $getShiftSlots[$line_shift]['lat'] = isset($address_detail->lat) ? $address_detail->lat : '';
                        $getShiftSlots[$line_shift]['lng'] = isset($address_detail->lng) ? $address_detail->lng : '';

                        $getShiftSlots[$line_shift]['shifts'][$shift->id] = $shift->weekday;
                        $getShiftSlots[$line_shift]['shifts_days_id'][$shift->weekday] = $shift->id;
                        $getShiftSlots[$line_shift]['shifts_ids'][$shift->id] = $shift->id;
                    } else {
                        $getShiftSlots[$line_shift]['shifts'][$shift->id] = $shift->weekday;
                        $getShiftSlots[$line_shift]['shifts_days_id'][$shift->weekday] = $shift->id;
                        $getShiftSlots[$line_shift]['shifts_ids'][$shift->id] = $shift->id;
                    }
                    $s++;
                }
            }
        }

        $getShiftSlots = array_values($getShiftSlots);
        $newshift = array();
        foreach ($getShiftSlots as $key => $getShiftSlot) {
            $newshift[$key] = $getShiftSlot;
            $shift_string = DrsPanel::weekArraySort($getShiftSlot['shifts']);

            $shift_days = array();
            foreach ($getShiftSlot['shifts'] as $keyu => $shifts_key) {
                $shift_days[] = $shifts_key;
                //$shiftDaysVal = ['day_id'=>$keyu,'day_name' => $shifts_key,$shifts_key => $keyu];
                // $newshift['shifts_key_value'][]=$shiftDaysVal;
            }
            $newshift[$key]['shifts_key'] = $shift_days;

            $shift_ids = array();
            foreach ($getShiftSlot['shifts_ids'] as $shifts_id) {
                $shift_ids[] = $shifts_id;
            }
            $newshift[$key]['shifts_ids'] = $shift_ids;

            $newshift[$key]['shifts_list'] = implode(',', $shift_string);
            if (count($shift_string) == 7) {
                $newshift[$key]['shifts_list'] = 'All Days';
            }
        }
        return $newshift;
    }
    
    public static function getShiftListByAddress2($doctor_id, $address_id) {
        $getShiftSlots = array();
        $checkaddress_time = array();
        $days = DrsPanel::getWeekArray();


        foreach ($days as $day) {
            $shifts = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $day, 'address_id' => $address_id])
                            ->orderBy('shift asc')->groupBy('address_id')->all();


            $s = 0;
            foreach ($shifts as $shift) {
                // $userDaySchedule=UserScheduleDay::find()->where(['schedule_id'=>$shift->id,'user_id'=>$doctor_id,'weekday'=>$shift->weekday])->one();

                $address_id = $shift->address_id;
                $address_detail = UserAddress::find()->where(['id' => $address_id, /* 'status'=>1 */])->one();

                if (!empty($address_detail)) {
                    $approxStartTime = date('h:i a', $shift->start_time);
                    $approxEndTime = date('h:i a', $shift->end_time);
                    $patinet_limit_check = $shift->patient_limit;
                    $consultationfees = $shift->consultation_fees;
                    $consultationfeesdiscount = $shift->consultation_fees_discount;
                    $emergencyfees = $shift->emergency_fees;
                    $emergencyfeesdiscount = $shift->emergency_fees_discount;

                    $checkfees = $shift->consultation_fees . '-' . $consultationfeesdiscount . '-' . $emergencyfees . '-' . $emergencyfeesdiscount;


                    $line_shift = $address_id . '-' . $patinet_limit_check . '-' . $checkfees . '-' . $approxStartTime . '-' . $approxEndTime;
                    $line_shift = $address_id . '-' . $patinet_limit_check . '-' . $checkfees . '-' . $approxStartTime . '-' . $approxEndTime;
                    if (!in_array($line_shift, $checkaddress_time)) {
                        $checkaddress_time[] = $line_shift;
                        $getShiftSlots[$line_shift]['id'] = $address_id;
                        $getShiftSlots[$line_shift]['shift_id'] = $shift->id;
                        $getShiftSlots[$line_shift]['shiftno'] = $shift->shift;
                        $getShiftSlots[$line_shift]['address_id'] = $address_id;
                        $getShiftSlots[$line_shift]['hospital_id'] = $shift->hospital_id;
                        $getShiftSlots[$line_shift]['name'] = isset($address_detail->name) ? $address_detail->name : DrsPanel::getUserName($shift->hospital_id);
                        $getShiftSlots[$line_shift]['address'] = DrsPanel::getAddressLine($address_id);
                        $getShiftSlots[$line_shift]['address_line'] = DrsPanel::getAddressLine($address_id);
                        $getShiftSlots[$line_shift]['hospital_logo'] = DrsPanel::getAddressAvator($address_id);
                        $getShiftSlots[$line_shift]['hospital_images'] = DrsPanel::getAddressImageList($address_id);

                        $getShiftSlots[$line_shift]['start_time'] = $approxStartTime;
                        $getShiftSlots[$line_shift]['end_time'] = $approxEndTime;
                        $getShiftSlots[$line_shift]['start_time_original'] = $approxStartTime;
                        $getShiftSlots[$line_shift]['end_time_original'] = $approxEndTime;
                        $getShiftSlots[$line_shift]['shift_label'] = $approxStartTime . ' - ' . $approxEndTime;
                        $getShiftSlots[$line_shift]['shift_name'] = $approxStartTime . ' - ' . $approxEndTime;
                        $getShiftSlots[$line_shift]['consultation_fees'] = $shift->consultation_fees;
                        $getShiftSlots[$line_shift]['consultation_fees_discount'] = $shift->consultation_fees_discount;
                        $getShiftSlots[$line_shift]['emergency_fees'] = $shift->emergency_fees;
                        $getShiftSlots[$line_shift]['emergency_fees_discount'] = $shift->emergency_fees_discount;
                        $getShiftSlots[$line_shift]['appointment_time_duration'] = $shift->appointment_time_duration;
                        $getShiftSlots[$line_shift]['patient_limit'] = $shift->patient_limit;
                        $getShiftSlots[$line_shift]['lat'] = isset($address_detail->lat) ? $address_detail->lat : '';
                        $getShiftSlots[$line_shift]['lng'] = isset($address_detail->lng) ? $address_detail->lng : '';

                        $getShiftSlots[$line_shift]['shifts'][$shift->id] = $shift->weekday;
                        $getShiftSlots[$line_shift]['shifts_days_id'][$shift->weekday] = $shift->id;
                        $getShiftSlots[$line_shift]['shifts_ids'][$shift->id] = $shift->id;
                    } else {
                        $getShiftSlots[$line_shift]['shifts'][$shift->id] = $shift->weekday;
                        $getShiftSlots[$line_shift]['shifts_days_id'][$shift->weekday] = $shift->id;
                        $getShiftSlots[$line_shift]['shifts_ids'][$shift->id] = $shift->id;
                    }
                    $s++;
                }
            }
        }

        $getShiftSlots = array_values($getShiftSlots);
        $newshift = array();
        foreach ($getShiftSlots as $key => $getShiftSlot) {
            $newshift[$key] = $getShiftSlot;
            $shift_string = DrsPanel::weekArraySort($getShiftSlot['shifts']);

            $shift_days = array();
            foreach ($getShiftSlot['shifts'] as $keyu => $shifts_key) {
                $shift_days[] = $shifts_key;
                //$shiftDaysVal = ['day_id'=>$keyu,'day_name' => $shifts_key,$shifts_key => $keyu];
                // $newshift['shifts_key_value'][]=$shiftDaysVal;
            }
            $newshift[$key]['shifts_key'] = $shift_days;

            $shift_ids = array();
            foreach ($getShiftSlot['shifts_ids'] as $shifts_id) {
                $shift_ids[] = $shifts_id;
            }
            $newshift[$key]['shifts_ids'] = $shift_ids;

            $newshift[$key]['shifts_list'] = implode(',', $shift_string);
            if (count($shift_string) == 7) {
                $newshift[$key]['shifts_list'] = 'All Days';
            }
        }
        return $newshift;
    }

    public static function getBookingAddressShifts($doctor_id, $date, $current_login = '', $datecheck = '') {
        $doctorDetail = User::findOne($doctor_id);
        if ($datecheck == '') {
            $shifts = DrsPanel::shiftForAllDates($doctor_id, $date, $current_login = '');
            return $shifts;
        } else {
            if ($current_login == '') {
                $userType = 'patient';
            } else {
                $userProfile = UserProfile::find()->where(['user_id' => $current_login])->one();
                if ($userProfile->groupid == Groups::GROUP_PATIENT) {
                    $userType = 'patient';
                } else {
                    $userType = 'all';
                }
            }
            $getShiftSlots = array();
            $checkaddress_time = array();
            $current_weekday = DrsPanel::getDateWeekDay($date);
            $timestamp = strtotime($date);
            $enddate = date('Y-m-d', strtotime('+15 days', $timestamp));


            $shifts = UserScheduleDay::find()->where(['user_id' => $doctor_id,
                        'weekday' => $current_weekday, 'date' => $date])->orderBy('shift asc')->all();
            foreach ($shifts as $key => $shift) {
                $userDaySchedule = UserScheduleDay::findOne($shift->id);
                $address_id = $userDaySchedule->address_id;
                $shift_id = $userDaySchedule->schedule_id;
                $address_detail = UserAddress::findOne($address_id);
                if ($userType == 'patient') {
                    if ($userDaySchedule->booking_closed == 0) {
                        $shift = $userDaySchedule;
                        $app_list = 1;
                    } else {
                        $app_list = 0;
                    }
                    //$app_list=1;
                } else {
                    $app_list = 1;
                }

                if ($app_list == 1) {
                    $approxStartTime = date('h:i a', $userDaySchedule->start_time);
                    $approxEndTime = date('h:i a', $userDaySchedule->end_time);
                    $line_shift = $address_id . '-' . $approxStartTime . '-' . $approxEndTime;

                    $getShiftSlots[$line_shift]['id'] = $address_id;
                    $getShiftSlots[$line_shift]['address_id'] = $address_id;
                    $getShiftSlots[$line_shift]['name'] = isset($address_detail->name) ? $address_detail->name : '';
                    $getShiftSlots[$line_shift]['address'] = DrsPanel::getAddressLine($address_id);
                    $getShiftSlots[$line_shift]['area'] = isset($address_detail->area) ? $address_detail->area : '';
                    $getShiftSlots[$line_shift]['address_line'] = DrsPanel::getAddressLine($address_id);
                    $getShiftSlots[$line_shift]['address_show'] = DrsPanel::getAddressShow($address_id);
                    $getShiftSlots[$line_shift]['address_short'] = $getShiftSlots[$line_shift]['address_line'];
                    $getShiftSlots[$line_shift]['address_full'] = $getShiftSlots[$line_shift]['address_show'];
                    $getShiftSlots[$line_shift]['hospital_logo'] = DrsPanel::getAddressAvator($address_id);
                    $getShiftSlots[$line_shift]['hospital_images'] = DrsPanel::getAddressImageList($address_id);
                    $getShiftSlots[$line_shift]['start_time'] = $approxStartTime;
                    $getShiftSlots[$line_shift]['end_time'] = $approxEndTime;
                    $getShiftSlots[$line_shift]['shift_label'] = $approxStartTime . ' - ' . $approxEndTime;
                    $getShiftSlots[$line_shift]['shift_name'] = $approxStartTime . ' - ' . $approxEndTime;
                    $getShiftSlots[$line_shift]['consultation_fees'] = $userDaySchedule->consultation_fees;
                    $getShiftSlots[$line_shift]['consultation_fees_discount'] = $userDaySchedule->consultation_fees_discount;
                    $getShiftSlots[$line_shift]['lat'] = isset($address_detail->lat) ? $address_detail->lat : '';
                    $getShiftSlots[$line_shift]['lng'] = isset($address_detail->lng) ? $address_detail->lng : '';
                    $getShiftSlots[$line_shift]['hospital_id'] = $userDaySchedule->hospital_id;
                    $getShiftSlots[$line_shift]['schedule_id'] = $shift_id;
                    $getShiftSlots[$line_shift]['shifts_id'][$shift_id] = $shift_id;
                    $getShiftSlots[$line_shift]['shifts'][$shift_id] = $userDaySchedule->weekday;
                    $getShiftSlots[$line_shift]['booking_closed'] = $userDaySchedule->booking_closed;

                    $shift_doctor_id = $userDaySchedule->user_id;
                    $user_doctor = User::findOne($shift_doctor_id);
                    if ($user_doctor->admin_status == User::STATUS_ADMIN_LIVE_APPROVED) {
                        $getShiftSlots[$line_shift]['doctor_toggle'] = 'show';
                    } else {
                        $getShiftSlots[$line_shift]['doctor_toggle'] = 'hide';
                    }

                    $service_charge = DrsPanel::getServiceCharge($address_id, $doctor_id);
                    $getShiftSlots[$line_shift]['service_charge'] = $service_charge['charge'];
                    $getShiftSlots[$line_shift]['service_charge_discount'] = $service_charge['charge_discount'];
                    $getShiftSlots[$line_shift]['service_charge_show'] = $service_charge['final_charge'];

                    if ($doctorDetail->admin_status == User::STATUS_ADMIN_LIVE_APPROVED) {
                        if ($userDaySchedule->end_time >= time()) {
                            if ($date == date('Y-m-d')) {
                                $getShiftSlots[$line_shift]['next_availablity'] = 'Available Today';
                            } else {
                                $getShiftSlots[$line_shift]['next_availablity'] = 'Available';
                            }
                            $getShiftSlots[$line_shift]['next_date'] = $date;
                        } else {
                            if ($date == date('Y-m-d')) {
                                $getShiftSlots[$line_shift]['next_availablity'] = "Today's Booking Closed";
                            } else {
                                $getShiftSlots[$line_shift]['next_availablity'] = 'Booking Closed';
                            }
                            $getShiftSlots[$line_shift]['next_date'] = $date;
                            $getShiftSlots[$line_shift]['booking_closed'] = 1;
                        }
                    } else {
                        $getShiftSlots[$line_shift]['next_availablity'] = "Online Booking Closed";
                        $getShiftSlots[$line_shift]['next_date'] = $date;
                        $getShiftSlots[$line_shift]['booking_closed'] = 1;
                    }
                }
            }
            $getShiftSlots = array_values($getShiftSlots);
            $newshift = array();
            foreach ($getShiftSlots as $key => $getShiftSlot) {
                $newshift[$key] = $getShiftSlot;
                $shift_string = DrsPanel::weekArraySort($getShiftSlot['shifts']);
                $newshift[$key]['shifts_list'] = implode(',', $shift_string);
                if (count($shift_string) == 7) {
                    $newshift[$key]['shifts_list'] = 'All Days';
                }
            }
            return $newshift;
        }
    }

    public static function shiftForAllDates($doctor_id, $date, $current_login = '') {
        $doctorDetail = User::findOne($doctor_id);
        if ($current_login == '') {
            $userType = 'patient';
        } else {
            $userProfile = UserProfile::find()->where(['user_id' => $current_login])->one();
            if ($userProfile->groupid == Groups::GROUP_PATIENT) {
                $userType = 'patient';
            } else {
                $userType = 'all';
            }
        }
        $getShiftSlots = array();
        $checkaddress_time = array();
        $current_weekday = DrsPanel::getDateWeekDay($date);
        $timestamp = strtotime($date);
        $enddate = date('Y-m-d', strtotime('+15 days', $timestamp));
        $days = array();
        for ($i = 0; $i < 7; $i++) {
            $days[] = strftime('%A', $timestamp);

            $timestamp = strtotime('+1 day', $timestamp);
        }

        foreach ($days as $kday => $day) {
            $shifts = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $day])
                            ->orderBy('shift asc')->all();

            $s = 0;
            foreach ($shifts as $shift) {
                $address_id = $shift->address_id;
                $shift_id = $shift->id;
                $address_detail = UserAddress::findOne($address_id);

                $userDaySchedule = UserScheduleDay::find()->where(['schedule_id' => $shift_id, 'user_id' => $doctor_id,
                            'weekday' => $day])->andWhere(['between', 'date', $date, $enddate])->one();
                $app_list = 0;
                if (!empty($userDaySchedule)) {
                    if ($userType == 'patient') {
                        if ($userDaySchedule->booking_closed == 0) {
                            $shift = $userDaySchedule;
                            $app_list = 1;
                        } else {
                            $app_list = 2;
                        }
                    } else {
                        $app_list = 1;
                    }
                } else {
                    if ($userType == 'patient') {
                        $app_list = 0;
                    } else {
                        $app_list = 1;
                    }
                }

                if ($app_list == 1 || $app_list == 2) {
                    $approxStartTime = date('h:i a', $shift->start_time);
                    $approxEndTime = date('h:i a', $shift->end_time);
                    $line_shift = $address_id . '-' . $approxStartTime . '-' . $approxEndTime;
                    $line_shift = $address_id . '-' . $approxStartTime . '-' . $approxEndTime;
                    if (!in_array($line_shift, $checkaddress_time)) {
                        $checkaddress_time[] = $line_shift;
                        $getShiftSlots[$line_shift]['id'] = $address_id;
                        $getShiftSlots[$line_shift]['address_id'] = $address_id;
                        $getShiftSlots[$line_shift]['name'] = isset($address_detail->name) ? $address_detail->name : '';
                        $getShiftSlots[$line_shift]['address'] = DrsPanel::getAddressLine($address_id);
                        $getShiftSlots[$line_shift]['area'] = isset($address_detail->area) ? $address_detail->area : '';
                        $getShiftSlots[$line_shift]['address_line'] = DrsPanel::getAddressLine($address_id);
                        $getShiftSlots[$line_shift]['address_show'] = DrsPanel::getAddressShow($address_id);
                        $getShiftSlots[$line_shift]['address_short'] = $getShiftSlots[$line_shift]['address_line'];
                        $getShiftSlots[$line_shift]['address_full'] = $getShiftSlots[$line_shift]['address_show'];
                        $getShiftSlots[$line_shift]['hospital_logo'] = DrsPanel::getAddressAvator($address_id);
                        $getShiftSlots[$line_shift]['hospital_images'] = DrsPanel::getAddressImageList($address_id);
                        $getShiftSlots[$line_shift]['start_time'] = $approxStartTime;
                        $getShiftSlots[$line_shift]['end_time'] = $approxEndTime;
                        $getShiftSlots[$line_shift]['shift_label'] = $approxStartTime . ' - ' . $approxEndTime;
                        $getShiftSlots[$line_shift]['shift_name'] = $approxStartTime . ' - ' . $approxEndTime;
                        $getShiftSlots[$line_shift]['consultation_fees'] = $shift->consultation_fees;
                        $getShiftSlots[$line_shift]['consultation_fees_discount'] = $shift->consultation_fees_discount;
                        $getShiftSlots[$line_shift]['lat'] = isset($address_detail->lat) ? $address_detail->lat : '';
                        $getShiftSlots[$line_shift]['lng'] = isset($address_detail->lng) ? $address_detail->lng : '';
                        $getShiftSlots[$line_shift]['hospital_id'] = $shift->hospital_id;

                        $shift_doctor_id = $userDaySchedule->user_id;
                        $user_doctor = User::findOne($shift_doctor_id);
                        if ($user_doctor->admin_status == User::STATUS_ADMIN_LIVE_APPROVED) {
                            $getShiftSlots[$line_shift]['doctor_toggle'] = 'show';
                        } else {
                            $getShiftSlots[$line_shift]['doctor_toggle'] = 'hide';
                        }

                        $service_charge = DrsPanel::getServiceCharge($address_id, $doctor_id);
                        $getShiftSlots[$line_shift]['service_charge'] = $service_charge['charge'];
                        $getShiftSlots[$line_shift]['service_charge_discount'] = $service_charge['charge_discount'];
                        $getShiftSlots[$line_shift]['service_charge_show'] = $service_charge['final_charge'];


                        if ($doctorDetail->admin_status == User::STATUS_ADMIN_LIVE_APPROVED) {
                            if (!empty($userDaySchedule)) {
                                $getShiftSlots[$line_shift]['booking_closed'] = $userDaySchedule->booking_closed;
                                if ($userDaySchedule->booking_closed == 0) {
                                    if ($current_weekday == $day) {
                                        if ($shift->end_time >= time()) {
                                            $getShiftSlots[$line_shift]['next_availablity'] = 'Available Today';
                                            $getShiftSlots[$line_shift]['next_date'] = $date;
                                            $getShiftSlots[$line_shift]['schedule_id'] = $shift_id;
                                        } else {
                                            $getShiftSlots[$line_shift]['next_availablity'] = "Today's Booking Closed";
                                            $getShiftSlots[$line_shift]['next_date'] = $date;
                                            $getShiftSlots[$line_shift]['schedule_id'] = $shift_id;
                                            // $getShiftSlots[$line_shift]['booking_closed']=1;

                                            $nexttimestamp = strtotime(date('Y-m-d'));
                                            $nextdate = date('Y-m-d', strtotime('1 days', $nexttimestamp));
                                            $getnextAvailability = DrsPanel::getNextAvailability($doctor_id, $address_id, $shift->start_time, $shift->end_time, $shift_id, $nextdate);
                                            $getShiftSlots[$line_shift]['next_availablity'] = $getnextAvailability['next_availablity'];
                                            $getShiftSlots[$line_shift]['next_date'] = $getnextAvailability['next_date'];
                                            $getShiftSlots[$line_shift]['schedule_id'] = $getnextAvailability['schedule_id'];
                                            $getShiftSlots[$line_shift]['booking_closed'] = $getnextAvailability['booking_closed'];
                                        }
                                    } else {
                                        $getShiftSlots[$line_shift]['next_availablity'] = 'Available on ' . $day;
                                        $nextdate = date('Y-m-d', strtotime('next ' . $day));
                                        $getShiftSlots[$line_shift]['next_date'] = $nextdate;
                                        $getShiftSlots[$line_shift]['schedule_id'] = $shift_id;
                                    }
                                } else {
                                    //check for next availability
                                    $getnextAvailability = DrsPanel::getNextAvailability($doctor_id, $address_id, $shift->start_time, $shift->end_time, $shift_id);
                                    $getShiftSlots[$line_shift]['next_availablity'] = $getnextAvailability['next_availablity'];
                                    $getShiftSlots[$line_shift]['next_date'] = $getnextAvailability['next_date'];
                                    $getShiftSlots[$line_shift]['schedule_id'] = $getnextAvailability['schedule_id'];
                                    $getShiftSlots[$line_shift]['booking_closed'] = $getnextAvailability['booking_closed'];
                                }
                            } else {
                                $getShiftSlots[$line_shift]['booking_closed'] = 0;

                                if ($current_weekday == $day) {
                                    $getShiftSlots[$line_shift]['next_availablity'] = 'Available Today';
                                    $getShiftSlots[$line_shift]['next_date'] = $date;
                                    $getShiftSlots[$line_shift]['schedule_id'] = $shift_id;
                                } else {
                                    $getShiftSlots[$line_shift]['next_availablity'] = 'Available on ' . $day;
                                    $nextdate = date('Y-m-d', strtotime('next ' . $day));
                                    $getShiftSlots[$line_shift]['next_date'] = $nextdate;
                                    $getShiftSlots[$line_shift]['schedule_id'] = $shift_id;
                                }
                            }
                        } else {
                            $getShiftSlots[$line_shift]['next_availablity'] = "Online Booking Closed";
                            $getShiftSlots[$line_shift]['next_date'] = $date;
                            $getShiftSlots[$line_shift]['schedule_id'] = $shift_id;
                            $getShiftSlots[$line_shift]['booking_closed'] = 1;
                        }

                        $getShiftSlots[$line_shift]['shifts'][$shift_id] = $shift->weekday;
                        $getShiftSlots[$line_shift]['shifts_id'][$shift_id] = $shift_id;
                    } else {
                        $getShiftSlots[$line_shift]['shifts'][$shift_id] = $shift->weekday;
                        $getShiftSlots[$line_shift]['shifts_id'][$shift_id] = $shift_id;
                    }
                }
                $s++;
            }
        }
        $getShiftSlots = array_values($getShiftSlots);
        $newshift = array();
        foreach ($getShiftSlots as $key => $getShiftSlot) {
            $newshift[$key] = $getShiftSlot;
            $shift_string = DrsPanel::weekArraySort($getShiftSlot['shifts']);
            $newshift[$key]['shifts_list'] = implode(',', $shift_string);
            if (count($shift_string) == 7) {
                $newshift[$key]['shifts_list'] = 'All Days';
            }
        }
        return $newshift;
    }

    public static function getNextAvailability($doctor_id, $address_id, $starttime, $endtime, $shift_id, $date = '') {
        $today = date('Y-m-d');
        $checkdate = $date;
        $checkbreak = 0;
        if ($date == '') {
            $date = date('Y-m-d', strtotime('+1 days', strtotime(date('Y-m-d'))));
            $timestamp = strtotime($date);
            $enddate = date('Y-m-d', strtotime('+14 days', $timestamp));
        } else {
            $date = $date;
            $timestamp = strtotime($date);
            $enddate = date('Y-m-d', strtotime('+14 days', $timestamp));
        }
        $avail = array();

        $dates = DrsPanel::date_range($date, $enddate);
        $i = 0;
        foreach ($dates as $d) {
            $getScheduleShifts = DrsPanel::getScheduleShifts($doctor_id, $d);
            $day = DrsPanel::getDateWeekDay($d);

            $dbstart_time = date('h:i a', $starttime);
            $dbend_time = date('h:i a', $endtime);
            $nstart_time = $d . ' ' . $dbstart_time;
            $nend_time = $d . ' ' . $dbend_time;
            $starttime = strtotime($nstart_time);
            $endtime = strtotime($nend_time);

            // $schedule=UserSchedule::find()->where(['user_id'=>$doctor_id,'address_id'=>$address_id,'start_time'=>$starttime,'end_time'=>$endtime,'weekday'=>$day])->one();
            $schedules = UserSchedule::find()->where(['user_id' => $doctor_id, 'address_id' => $address_id, 'weekday' => $day])->all();
            if (!empty($schedules)) {
                foreach ($schedules as $schedule) {
                    $schedule_id = $schedule->id;
                    $doctor_id = $schedule->user_id;
                    $userDaySchedule = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'date' => $d, 'schedule_id' => $schedule_id, 'start_time' => $starttime, 'end_time' => $endtime])->one();
                    if (!empty($userDaySchedule)) {
                        if ($userDaySchedule->booking_closed == 0) {
                            if ($checkdate == $d) {
                                $avail['booking_closed'] = 0;
                                if ($today == $checkdate) {
                                    $avail['next_availablity'] = 'Available Today';
                                } else {
                                    $avail['next_availablity'] = 'Available on ' . $day;
                                }
                                $avail['next_date'] = $date;
                                $avail['next_date_d'] = $d;
                                $avail['schedule_id'] = $schedule_id;
                            } else {
                                $avail['booking_closed'] = 0;
                                $avail['next_availablity'] = 'Available on ' . $day;
                                $nextdate = date('Y-m-d', strtotime('next ' . $day));
                                $avail['next_date'] = $nextdate;
                                $avail['schedule_id'] = $schedule_id;
                            }
                            $checkbreak = 1;
                            break;
                        } else {
                            $avail['booking_closed'] = 1;
                            $avail['next_availablity'] = 'Online Booking Closed';
                            $avail['next_date'] = $date;
                            $avail['schedule_id'] = $schedule_id;
                        }
                    }
                }
                if ($checkbreak == 1) {
                    break;
                }
            } else {
                $avail['booking_closed'] = 1;
                $avail['next_availablity'] = 'Online Booking Closed';
                $avail['next_date'] = $date;
                $avail['schedule_id'] = $shift_id;
            }

            $i++;
        }

        return $avail;
    }

    public function getAddressShiftsDays($params) {
        $date = $params['next_date'];
        $doctor_id = $params['doctor_id'];
        $start_time = $params['start_time'];
        $end_time = $params['end_time'];

        $stime = date('h:i a', strtotime($start_time));
        $etime = date('h:i a', strtotime($end_time));

        $shift_label = 'Shift ' . $stime . ' - ' . $etime;
        $address_id = $params['address_id'];
        $slots = array();
        $getDatesArray = DrsPanel::getDatesFromToday(15, strtotime(date('Y-m-d')));
        $d = 0;
        $slots_array = array();
        foreach ($getDatesArray as $listdate) {
            $week_check = DrsPanel::getDateWeekDay($listdate);
            $getSlots = DrsPanel::getBookingShifts($doctor_id, $listdate, $doctor_id);
            $slots[$d]['date'] = $listdate;
            $slots[$d]['weekday'] = $week_check;
            $slots[$d]['isChecked'] = false;
            if (!empty($getSlots)) {
                $checkShift = UserScheduleGroup::find()->where([
                            'user_id' => $doctor_id, 'address_id' => $address_id, 'weekday' => $week_check, 'shift_label' => $shift_label, 'date' => $listdate])->one();
                if (!empty($checkShift)) {
                    $schedule_id = $checkShift->schedule_id;
                    /*                $slots[$d]['schedule_id']=$schedule_id; */
                    if ($checkShift->booking_closed == 0) {
                        /* $slots[$d]['shifts_available']=1; */
                        if ($listdate == $date) {
                            $slots[$d]['isChecked'] = true;
                            $bookingSchedule = DrsPanel::getScheduleShifts($doctor_id, $date);
                            $getSlots = DrsPanel::getBookingShiftSlots($doctor_id, $date, $schedule_id, '');
                            $slots_array = $getSlots;
                        }
                    } else {
                        if ($listdate == $date) {
                            $slots[$d]['isChecked'] = true;
                        }
                        /*                    $slots[$d]['shifts_available']=0; */
                    }
                } else {
                    if ($listdate == $date) {
                        $slots[$d]['isChecked'] = true;
                    }
                    /* $slots[$d]['shifts_available']=0; */
                }
            } else {
                if ($listdate == $date) {
                    $slots[$d]['isChecked'] = true;
                }
                /* $slots[$d]['shifts_available']=0; */
            }

            $d++;
        }

        return array('list' => $slots, 'slots' => $slots_array);
    }

    public function getPtientDoctor($doctor_id = NULL) {
        $doctorUserData = UserProfile::find()->andWhere(['user_id' => $doctor_id])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one();
        return $doctorUserData;
    }

    public function getPtientDoctorSlug($doctor_id = NULL) {
        $userSlug = UserProfile::find()->andWhere(['user_id' => $doctor_id])->andWhere(['groupid' => Groups::GROUP_DOCTOR])->one();
        return $userSlug;
    }

    public static function weekArraySort($input_array) {
        $input_array1 = array_values($input_array);
        $input_array = array();
        foreach ($input_array1 as $input) {
            $input_array[] = date('D', strtotime($input));
        }
        $day_map = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        uasort($input_array, function($a, $b) use ($day_map) {
            $c = explode(',', $a);
            $d = explode(',', $b);
            for ($i = 0, $len = count($c); $i < $len; $i++) {
                $d1 = @array_search($c[$i], $day_map);
                $d2 = @array_search($d[$i], $day_map);
                if ($d1 == $d2) {
                    continue; // skip, check the next column
                }
                return $d1 - $d2;
            }
        });
        return $input_array;
    }

    public static function getDatesFromToday($count, $start) {
        $dates = array();
        $dates[] = date('Y-m-d', $start);
        for ($i = 1; $i < ($count); $i++) {
            $start = strtotime("+1 day", $start);
            $dates[] = date('Y-m-d', $start);
        }
        return $dates;
    }

    public static function addTemporaryAppointment($params, $userType, $merchanttype = 'web') {
        $response = array();
        if (isset($params['doctor_id']) && !empty($params['doctor_id']) && isset($params['user_id']) && !empty($params['user_id'])) {
            $doctor_id = $params['doctor_id'];
            $user_id = $params['user_id'];
        } else {
            $doctor_id = $params['user_id'];
            $user_id = 0;
        }
        $slot_id = $params['slot_id'];
        $schedule_id = $params['schedule_id'];
        $doctorProfile = UserProfile::findOne(['user_id' => $doctor_id]);
        if (!empty($doctorProfile)) {
            $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
            if (!empty($slot)) {
                if ($slot->status == 'available' || $slot->status == 'blocked') {
                    $schedule = UserSchedule::findOne($schedule_id);
                    $address = UserAddress::findOne($schedule->address_id);
                    if ($userType == 'patient') {
                        $booking_type = UserAppointment::BOOKING_TYPE_ONLINE;
                        $payment_type = UserAppointment::PAYMENT_TYPE_PAYTM;
                        $status = UserAppointment::STATUS_PENDING;
                        $payment_status = UserAppointment::PAYMENT_PENDING;
                        $modelname = 'UserAppointmentTemp';

                        $data[$modelname]['created_by'] = UserAppointment::GROUP_PATIENT;
                        $data[$modelname]['created_by_id'] = $user_id;

                        /* $booking_type=UserAppointment::BOOKING_TYPE_ONLINE;
                          $payment_type=UserAppointment::PAYMENT_TYPE_CASH;
                          $status=UserAppointment::STATUS_PENDING;
                          $payment_status=UserAppointment::PAYMENT_COMPLETED; */

                        $service_charge = DrsPanel::getServiceCharge($schedule->address_id, $doctor_id);
                        $service_charge = $service_charge['final_charge'];

                        //$modelname='UserAppointment';

                        $data[$modelname]['created_by'] = UserAppointment::GROUP_PATIENT;
                        $data[$modelname]['created_by_id'] = $user_id;
                    } else {
                        $booking_type = UserAppointment::BOOKING_TYPE_OFFLINE;
                        $payment_type = UserAppointment::PAYMENT_TYPE_CASH;
                        $status = UserAppointment::STATUS_AVAILABLE;
                        $payment_status = UserAppointment::PAYMENT_COMPLETED;
                        $service_charge = 0;
                        $modelname = 'UserAppointment';
                        $userLogin = User::findOne($doctor_id);
                        $groupid = $userLogin->groupid;
                        if ($groupid == Groups::GROUP_DOCTOR) {
                            $data[$modelname]['created_by'] = UserAppointment::GROUP_DOCTOR;
                        } elseif ($groupid == Groups::GROUP_HOSPITAL) {
                            $data[$modelname]['created_by'] = UserAppointment::GROUP_HOSPITAL;
                        } elseif ($groupid == Groups::GROUP_ATTENDER) {
                            $parentGroup = DrsPanel::getAttenderParentType($doctor_id);
                            if ($parentGroup == Groups::GROUP_DOCTOR) {
                                $data[$modelname]['created_by'] = UserAppointment::GROUP_ATTENDER;
                            } elseif ($parentGroup == Groups::GROUP_HOSPITAL) {
                                $data[$modelname]['created_by'] = UserAppointment::GROUP_HOSPITAL_ATTENDER;
                            }
                        }
                        $data[$modelname]['created_by_id'] = $doctor_id;
                    }

                    $data[$modelname]['booking_type'] = $booking_type;
                    $data[$modelname]['booking_id'] = DrsPanel::generateBookingID();
                    $data[$modelname]['type'] = $slot->type;
                    $data[$modelname]['token'] = $slot->token;
                    $data[$modelname]['user_id'] = $user_id;
                    $data[$modelname]['user_name'] = ucfirst($params['name']);
                    $data[$modelname]['user_age'] = isset($params['age']) ? $params['age'] : "0";
                    $data[$modelname]['user_phone'] = $params['mobile'];
                    $data[$modelname]['user_address'] = isset($params['address']) ? $params['address'] : '';
                    $data[$modelname]['user_gender'] = $params['gender'];


                    $data[$modelname]['doctor_id'] = $doctor_id;
                    $data[$modelname]['doctor_name'] = $doctorProfile->name;
                    $data[$modelname]['doctor_address'] = DrsPanel::getAddressShow($address->id);
                    $data[$modelname]['doctor_address_id'] = $schedule->address_id;
                    $data[$modelname]['doctor_phone'] = ($address->phone != '') ? $address->phone : $address->landline;


                    if (isset($slot->fees_discount) && $slot->fees_discount < $slot->fees && $slot->fees_discount > 0) {
                        $data[$modelname]['doctor_fees'] = $slot->fees_discount;
                    } else {
                        $data[$modelname]['doctor_fees'] = $slot->fees;
                    }

                    $data[$modelname]['date'] = $params['date'];
                    $data[$modelname]['weekday'] = $slot->weekday;
                    $data[$modelname]['shift_label'] = $slot->shift_label;
                    $data[$modelname]['start_time'] = $slot->start_time;
                    $data[$modelname]['end_time'] = $slot->end_time;

                    $approxStartTime = date('h:i a', $slot->start_time);
                    $approxEndTime = date('h:i a', $slot->end_time);

                    $data[$modelname]['shift_name'] = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                    $data[$modelname]['schedule_id'] = $schedule_id;
                    $data[$modelname]['slot_id'] = $slot_id;
                    $data[$modelname]['book_for'] = UserAppointment::BOOK_FOR_SELF;
                    $data[$modelname]['payment_type'] = $payment_type;
                    $data[$modelname]['status'] = $status;
                    $data[$modelname]['payment_status'] = $payment_status;
                    $data[$modelname]['service_charge'] = $service_charge;

                    if ($userType == 'patient') {
                        $addAppointment = DrsPanel::addTempAppointment($data, 'patient');
                        if ($addAppointment['type'] == 'model_error') {
                            $response = DrsPanel::validationErrorMessage($addAppointment['data']);
                        } else {
                            $appointment_id = $addAppointment['data'];
                            $payment = Payment::walletRechargePaytm($user_id, $appointment_id, $service_charge, $merchanttype);
                            $response["paytmdata"] = $payment;
                            $response["status"] = 1;
                            $response["error"] = false;
                            $response['message'] = 'Paytm Details';
                        }

                        //$addAppointment = DrsPanel::addAppointment($data, 'patient');
                        /* if ($addAppointment['type'] == 'model_error') {
                          $response = DrsPanel::validationErrorMessage($addAppointment['data']);
                          } else {

                          //add appointment member to patient record list
                          $member=PatientMembers::find()->where(['user_id'=>$user_id,
                          'name'=>$params['name'],'phone'=>$params['mobile']])->one();
                          if(empty($member)){
                          $memberdata=array();
                          $memberdata['user_id']=$user_id;
                          $memberdata['name']=$params['name'];
                          $memberdata['phone']=$params['mobile'];
                          $memberdata['gender']=$params['gender'];
                          $memberInsert=DrsPanel::memberUpsert($memberdata,array());
                          }
                          $sendNotification=Notifications::appointmentSmsNotification($addAppointment['data'],'appointment','patient');
                          $response["status"] = 1;
                          $response["error"] = false;
                          $app_detail = UserAppointment::findOne($addAppointment['data']);
                          $response["data"] = DrsPanel::patientgetappointmentarray($app_detail);
                          $response['message'] = 'Appointment added successfully';
                          } */
                    } else {
                        $addAppointment = DrsPanel::addAppointment($data, 'doctor');
                        if ($addAppointment['type'] == 'model_error') {
                            $response = DrsPanel::validationErrorMessage($addAppointment['data']);
                        } else {
                            //  $sendNotification=Notifications::appointmentSmsNotification($addAppointment['data'],'appointment','doctor');
                            $response["status"] = 1;
                            $response["error"] = false;
                            $app_detail = UserAppointment::findOne($addAppointment['data']);
                            $response["data"] = DrsPanel::patientgetappointmentarray($app_detail);
                            $response['message'] = 'Appointment added successfully';
                        }
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
        return $response;
    }

    public static function getTypeDefaultListArray() {
        $query = new Query();
        $query->select(['group.name'])
                ->from(['groups group'])
                ->andFilterWhere(['group.show' => 1, 'group.search' => 1])
                ->orderBy('group.id')
                ->limit(20);
        $command = $query->createCommand();
        $data = $command->queryAll();
        return $data;
    }

    public static function search_permute($items, $perms = array()) {
        $back = array();
        if (empty($items)) {
            $back[] = join(' ', $perms);
        } else {
            for ($i = count($items) - 1; $i >= 0; --$i) {
                $newitems = $items;
                $newperms = $perms;
                list($foo) = array_splice($newitems, $i, 1);
                array_unshift($newperms, $foo);
                $back = array_merge($back, DrsPanel::search_permute($newitems, $newperms));
            }
        }
        return $back;
    }

    public static function getUserSearchListArray($words, $groupcheck = '') {
        if ($groupcheck == '') {
            $groupcheck = array(Groups::GROUP_HOSPITAL, Groups::GROUP_DOCTOR);
        }
        $query = new Query();
        $usersearch = array();
        foreach ($words as $word) {
            $usersearch[] = "profile.name LIKE '%" . $word . "%'";
        }
        $v1 = implode(' or ', $usersearch);


        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => array(Groups::GROUP_HOSPITAL)]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
        $lists->andWhere(['user_profile.shifts' => 1]);
        $addSpeciality = Drspanel::addHospitalSpecialityCount($lists->createCommand()->queryAll());



        $query->select(['profile.user_id as id', 'group.name as category', 'profile.slug as query',
                    'profile.name as label', 'CONCAT(profile.avatar_base_url,"",profile.avatar_path) as avator', 'profile.speciality as speciality'])
                ->from(['user_profile profile', 'groups group', 'user user'])
                ->where('profile.groupid = group.id')
                ->andWhere('profile.user_id = user.id')
                ->andWhere(['user.status' => User::STATUS_ACTIVE,
                    'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]])
                ->andWhere(['profile.shifts' => 1])
                ->andWhere(['profile.groupid' => $groupcheck])
                ->andFilterWhere(['group.show' => 1])
                ->andFilterWhere(['or', $v1])
                ->orderBy('group.id')
                ->limit(20);

        $command = $query->createCommand();
        $results = $command->queryAll();
        return $results;
    }

    public static function getusergroupalias($userid) {
        $profile = UserProfile::findOne(['user_id' => $userid]);
        if (!empty($profile)) {
            $groupid = $profile->groupid;
            $groupalias = Groups::findOne([$groupid]);
            if (!empty($groupalias)) {
                return $groupalias['alias'];
            }
        }
        return false;
    }

    public static function getDisplayAddress($user_id) {
        $address = UserAddress::findOne(['user_id' => $user_id]);
        $addressline = '';
        if (!empty($address)) {
            $addressline .= $address['address'];
            $addressline .= ' ' . $address['city'];
        }
        return $addressline;
    }

    public static function getTitleDisplayAddress($user_id) {
        $address = UserAddress::findOne(['user_id' => $user_id]);
        $addressline = '';
        if (!empty($address)) {
            $addressline .= $address['name'];
            $addressline .= ' (' . $address['city'] . ')';
        }
        return $addressline;
    }

    public static function getlastPagination($total, $limit) {
        $last = ceil($total / $limit);
        return $last;
    }

    public static function getSpecialitySearchListArray($words) {
        $catsearch = array();
        foreach ($words as $word) {
            $catsearch[] = "meta_values.value LIKE '%" . $word . "%'";
            $catsearch[] = "meta_values.slug LIKE '%" . $word . "%'";
        }
        $v2 = implode(' or ', $catsearch);

        $query = new Query;

        $query->select(['meta_values.id as id', 'meta_values.label as label', 'meta_values.slug as query'])
                ->from(['meta_values meta_values'])
                ->where(['meta_values.status' => 1])
                ->andWhere(['meta_values.key' => 5])
                ->andFilterWhere(['or', $v2])
                ->orderBy('meta_values.id')
                ->limit(20);
        $command = $query->createCommand();
        $categories = $command->queryAll();
        return $categories;
    }

    public static function getTreatmentSearchListArray($words) {
        $catsearch = array();
        foreach ($words as $word) {
            $catsearch[] = "meta_values.value LIKE '%" . $word . "%'";
            $catsearch[] = "meta_values.slug LIKE '%" . $word . "%'";
        }
        $v2 = implode(' or ', $catsearch);

        $query = new Query;

        $query->select(['meta_values.id as id', 'meta_values.label as label', 'meta_values.slug as query'])
                ->from(['meta_values meta_values'])
                ->where(['meta_values.status' => 1])
                ->andWhere(['meta_values.key' => 9])
                ->andFilterWhere(['or', $v2])
                ->orderBy('meta_values.id')
                ->limit(20);
        $command = $query->createCommand();
        $categories = $command->queryAll();
        return $categories;
    }

    public static function getAddressAvator($address_id, $default = false) {
        $address = UserAddress::findOne(['id' => $address_id]);
        if ($default == true) {
            $path = Yii::getAlias('@frontendUrl');
            $default_image = 'hospital_default.png';
            $avator_path = $path . '/images/' . $default_image;
        } else {
            $avator_path = '';
        }
        if (!empty($address)) {
            if ($address->image != '') {
                $avator_path = $address->image_base_url . $address->image_path . $address->image;
            }
        }
        return $avator_path;
    }

    public static function getAddressImageList($address_id) {
        $address = UserAddress::findOne(['id' => $address_id]);
        $images = UserAddressImages::find()->where(['address_id' => $address_id])->all();
        $list = array();
        $i = 0;
        foreach ($images as $image) {
            $list[$i]['id'] = $image->id;
            if ($image->image != '') {
                $list[$i]['image'] = $image->image_base_url . $image->image_path . $image->image;
                $list[$i]['uri'] = $image->image_base_url . $image->image_path . $image->image;
            } else {
                $list[$i]['image'] = '';
                $list[$i]['uri'] = '';
            }
            $i++;
        }
        return $list;
    }

    public static function getUserAddress($id) {
        if ($address = UserAddress::find()->where(['user_id' => $id])->one()) {
            return Drspanel::getAddressLine($address);
        }
        return null;
    }

    public static function getHospitalName($id) {
        $address = UserAddress::findOne($id);
        $name = '';
        if (!empty($address)) {
            if ($address->name != '') {
                $name = $address->name;
            }
        }
        return $name;
    }

//
    public static function getAllHospital() {
        $hospitalList = UserProfile::find()->where(['!=', 'user_id', 1])->andwhere(['groupid' => Groups::GROUP_HOSPITAL])->all();

        return $hospitalList;
    }

    public static function getAddressDetails($id) {
        return UserAddress::findOne($id);
    }

    public static function getUserAddressMeta($user_id) {
        $address = UserAddress::find()->where(['user_id' => $user_id]);
        $addresslist = $address->all();
        $listaddress = array();
        if (!empty($addresslist)) {
            $a = 0;
            foreach ($addresslist as $list) {
                $listaddress[$a]['id'] = $list['id'];
                $listaddress[$a]['type'] = $list['type'];
                $listaddress[$a]['name'] = $list['address'];
                $listaddress[$a]['address_line'] = DrsPanel::getAddressLine($list);
                $a++;
            }
        }
        return $listaddress;
    }

    public static function getCurrentLocation() {
        $ipsess = Yii::$app->session;
        $ipAdd = $ipsess->get('IP');

        if (isset($ipAdd) && $ipAdd != '') {
            $ip = $ipAdd;
        } else {
            $ip = UserIp::getRealIp();
        }
        //$ip = UserIp::getRealIp();
        $url = 'http://ip-api.com/json/' . $ip;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        $data = curl_exec($ch);

        curl_close($ch);

        $query = json_decode($data);


        if ($query && $query->status == 'success') {
            $country = $query->country;
            $city = $query->city;
            $latitude = $query->lat;
            $longitude = $query->lon;
            return array('type' => 'success', 'country' => $country, 'city' => $city, 'latitude' => $latitude, 'longitude' => $longitude);
        }
        return array('type' => 'error');
    }

    public static function setCurrentLocation($lat, $lng) {
        $ipsess = Yii::$app->session;
        $ipAdd = $ipsess->get('IP');

        if (isset($ipAdd) && $ipAdd != '') {
            $ip = $ipAdd;
        } else {
            $ip = UserIp::getRealIp();
        }

        $url = 'http://ip-api.com/json/' . $ip;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

        $data = curl_exec($ch);

        curl_close($ch);

        $query = json_decode($data);

        if ($query && $query->status == 'success') {
            $country = $query->country;
            $city = $query->city;
            $latitude = $query->lat;
            $longitude = $query->lon;

            $baseurl = $_SERVER['HTTP_HOST'];
            $codearray = [];
            $codearray['city'] = $city;
            $codearray['address'] = $city . ', ' . $country;
            $codearray['name'] = $city;
            $codearray['lat'] = $lat;
            $codearray['lng'] = $lng;

            $json = json_encode($codearray, true);
            setcookie('location_filter', $json, time() + 60 * 60, '/', $baseurl, false);
            return array('type' => 'success', 'country' => $country, 'city' => $city, 'latitude' => $latitude, 'longitude' => $longitude);
        }
        return array('type' => 'error');
    }

    public static function getList($lists, $listtype = '', $current_login = 0) {
        $l = 0;
        $list_a = array();
        foreach ($lists as $list) {
            $user = User::findOne($list['user_id']);
            $profile = UserProfile::findOne($list['user_id']);
            $groupid = $profile->groupid;

            $list_a[$l]['user_id'] = $profile->user_id;
            $list_a[$l]['slug'] = $profile->slug;
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
            $list_a[$l]['address'] = DrsPanel::getBookingAddressShifts($profile->user_id, date('Y-m-d'));

            $list_a[$l]['fees'] = $profile->consultation_fees;
            $list_a[$l]['fees_discount'] = $profile->consultation_fees_discount;

            $list_a[$l]['show_fees'] = DrsPanel::getUserSetting($profile->user_id, 'show_fees');
            $rating = Drspanel::getRatingStatus($profile->user_id);
            $list_a[$l]['rating'] = $rating['rating'];

            $lat = Drspanel::getLatLong($profile->user_id);
            $list_a[$l]['lat'] = $lat['lat'];
            $list_a[$l]['lng'] = $lat['lng'];

            if ($listtype == 'hospital_doctors') {
                $list_a[$l]['status'] = DrsPanel::sendRequestCheck($current_login, $profile->user_id);
            }

            $l++;
        }
        return $list_a;
    }

    public static function updateShiftStatus($params) {
        $response = array();
        $doctor_id = $params['doctor_id'];
        $doctorUser = User::findOne($doctor_id);
        if ($doctorUser->admin_status == User::STATUS_ADMIN_LIVE_APPROVED) {
            $date = $params['date'];
            $weekday = DrsPanel::getDateWeekDay($date);
            $schedule_id = $params['schedule_id'];

            $schedule = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'id' => $schedule_id])->one();

            if (!empty($schedule)) {
                $dateScheduleDay = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $date, 'schedule_id' => $schedule_id])->one();
                if (empty($dateScheduleDay)) {
                    $getSchedule = new UserScheduleDay();
                    $getSchedule->schedule_id = $schedule->id;
                    $getSchedule->user_id = $schedule->user_id;
                    $getSchedule->shift_belongs_to = $schedule->shift_belongs_to;
                    $getSchedule->attender_id = $schedule->attender_id;
                    $getSchedule->hospital_id = $schedule->hospital_id;
                    $getSchedule->address_id = $schedule->address_id;
                    $getSchedule->type = 'available';
                    $getSchedule->shift = (string) $schedule->shift;
                    $getSchedule->date = $date;
                    $getSchedule->weekday = $schedule->weekday;

                    $approxStartTime = date('h:i a', $schedule->start_time);
                    $approxEndTime = date('h:i a', $schedule->end_time);
                    $approxStartTime = $date . ' ' . $approxStartTime;
                    $approxEndTime = $date . ' ' . $approxEndTime;
                    $start_time = strtotime($approxStartTime);
                    $end_time = strtotime($approxEndTime);

                    $getSchedule->start_time = $start_time;
                    $getSchedule->end_time = $end_time;
                    $getSchedule->patient_limit = $schedule->patient_limit;
                    $getSchedule->appointment_time_duration = $schedule->appointment_time_duration;
                    $getSchedule->consultation_fees = $schedule->consultation_fees;
                    $getSchedule->consultation_days = $schedule->consultation_days;
                    $getSchedule->consultation_show = $schedule->consultation_show;
                    $getSchedule->consultation_fees_discount = ($schedule->consultation_fees_discount) ? $schedule->consultation_fees_discount : 0;
                    $getSchedule->emergency_fees = $schedule->emergency_fees;
                    $getSchedule->emergency_days = $schedule->emergency_days;
                    $getSchedule->emergency_show = $schedule->emergency_show;
                    $getSchedule->emergency_fees_discount = ($schedule->emergency_fees_discount) ? $schedule->emergency_fees_discount : 0;
                    $getSchedule->status = $schedule->status;
                    $getSchedule->is_edit = $schedule->is_edit;
                    $getSchedule->booking_closed = $params['booking_closed'];
                    if ($getSchedule->save()) {
                        $group_shift = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date, 'weekday' => $weekday, 'schedule_id' => $schedule_id])->one();
                        if (!empty($group_shift)) {
                            $group_shift->booking_closed = $params['booking_closed'];
                            $group_shift->save();
                        }
                    } else {
                        $response["data"] = $getSchedule->getErrors();
                    }
                } else {
                    $dateScheduleDay->booking_closed = $params['booking_closed'];
                    if ($dateScheduleDay->save()) {
                        $group_shift = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $date, 'weekday' => $weekday, 'schedule_id' => $schedule_id])->one();
                        if (!empty($group_shift)) {
                            $group_shift->booking_closed = $params['booking_closed'];
                            $group_shift->save();
                        }
                    }
                }
                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Shift updated';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Date Shift not available';
            }
        } else {
            $response["status"] = 1;
            $response["error"] = false;
            $response['message'] = 'You do not have permission';
        }
        return $response;
    }

    public static function getSliderDates($first = '', $total = 1, $timezone = '') {
        if ($timezone == '') {
            $timezone = DrsPanel::setTimezone();
        }
        date_default_timezone_set($timezone);

        if ($first == '') {
            $first = date('Y-m-d');
            $last = $first;
        } else {
            if ($total > 1) {
                $check = strtotime($first);
                $last = date('Y-m-d', strtotime('+' . $total . ' days', $check));
            } else {
                $last = $first;
            }
        }
        $dates = DrsPanel::date_range($first, $last);
        return $dates;
    }

    public static function date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d') {

        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            $dates[] = date($output_format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }

    public static function checkProfileFavorite($user_id, $profile_id) {
        $favorite = UserFavorites::find()->where(['user_id' => $user_id, 'profile_id' => $profile_id])->one();
        if (!empty($favorite)) {
            if ($favorite->status == 0) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    public static function getCurrentAffair($checkForCurrentShift, $doctor_id, $date, $shift_check = '', $slots = array()) {
        $response = array();
        if ($checkForCurrentShift['status'] == 'error') {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'No Shifts for today';
        } elseif ($checkForCurrentShift['status'] == 'success') {
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
            $response['schedule_id'] = $checkForCurrentShift['shift_id'];
            $response['shift_label'] = $checkForCurrentShift['shift_label'];
            $response['date'] = $date;
            $response['is_started'] = false;
            $response['is_completed'] = false;
            $response['is_cancelled'] = false;
            $response['all_shifts'] = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift['shift_id'], $slots, $shift_check);
            $response['data'] = $getAppointments;
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
                $response['schedule_id'] = $checkForCurrentShift['shift_id'];
                $response['shift_label'] = $checkForCurrentShift['shift_label'];
                $response['is_started'] = false;
                $response['is_completed'] = true;
                $response['is_cancelled'] = false;
                $response['all_shifts'] = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift['shift_id'], $slots, $shift_check);
                $response['data'] = [];
                $response['message'] = 'Success';
            } elseif ($checkForCurrentShift['status'] == 'cancelled') {
                $response['schedule_id'] = $checkForCurrentShift['shift_id'];
                $response['shift_label'] = $checkForCurrentShift['shift_label'];
                $response['is_started'] = false;
                $response['is_completed'] = false;
                $response['is_cancelled'] = true;
                $response['all_shifts'] = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift['shift_id'], $slots, $shift_check);
                $response['data'] = [];
                $response['message'] = 'Success';
            } else {
                $response['schedule_id'] = $checkForCurrentShift['shift_id'];
                $response['shift_label'] = $checkForCurrentShift['shift_label'];
                $response['date'] = $date;
                $response['is_started'] = true;
                $response['is_completed'] = false;
                $response['is_cancelled'] = false;
                $response['all_shifts'] = DrsPanel::getDoctorAllShift($doctor_id, $date, $checkForCurrentShift['shift_id'], $slots, $shift_check);
                $response['data'] = $getAppointments;
                $response['message'] = 'Appointment List';
            }
        }
        return $response;
    }

    public static function getParentDetails($user_id) {
        $parentGroup = DrsPanel::getAttenderParentType($user_id);
        $userModel = User::findOne($user_id);
        $userProfile = UserProfile::findOne(['user_id' => $user_id]);
        if ($parentGroup == Groups::GROUP_HOSPITAL) {
            $parent_id = $userModel->parent_id;
            $parentModel = User::findOne($parent_id);
            $parentProfile = UserProfile::findOne(['user_id' => $parent_id]);
        } else {
            $parent_id = $userModel->parent_id;
            $parentModel = User::findOne($parent_id);
            $parentProfile = UserProfile::findOne(['user_id' => $parent_id]);
        }

        return array('parentGroup' => $parentGroup, 'userModel' => $userModel,
            'userProfile' => $userProfile, 'parent_id' => $parent_id,
            'parentModel' => $parentModel, 'parentProfile' => $parentProfile);
    }

    public static function getNextDaysCount($date) {
        $from = $date;
        $today = time();
        $todayTimeStamp = date('Y-m-d');
        if (strtotime($date) < strtotime($todayTimeStamp)) {
            return '';
        } else {
            $difference = $today - strtotime($from);
            $numberDays = floor($difference / 86400);
            if ($numberDays == 0) {
                return 'Today';
            } elseif ($numberDays == 1) {
                return 'in ' . $numberDays . ' Day';
            } else {
                return 'in ' . $numberDays . ' Days';
            }
        }
        return '';
    }

    public static function getRatingArray() {
        return ['0-1' => '0-1', '1-2' => '1-2', '2-3' => '2-3', '3-4' => '3-4', '4-5' => '4-5'];
    }

    public static function appointmentUsers($doctor_id, $schedule_id, $date = NULL) {

        $lists = new Query();
        $lists = User::find();
        $lists->alias('u');
        $lists->innerJoin('user_appointment as ua', 'ua.user_id = u.id');
        $lists->andWhere(['ua.doctor_id' => $doctor_id, 'ua.schedule_id' => $schedule_id]);
        if ($date)
            $lists->andWhere(['ua.date' => $date]);
        $lists->select(['u.token as device_token', 'ua.status', 'ua.start_time', 'ua.end_time',
            'ua.token', 'ua.slot_id', 'ua.user_gender', 'ua.id']);
        $lists->all();
        $command = $lists->createCommand();
        $lists = $command->queryAll();
        return $lists;
    }

    public static function liveStatusData($doctor_id, $schedule_id, $date, $appointment_id) {
        $userAppointment = UserAppointment::findOne($appointment_id);
        $token = $userAppointment->token;

        $status = array(UserAppointment::STATUS_ACTIVE, UserAppointment::STATUS_AVAILABLE, UserAppointment::STATUS_PENDING, UserAppointment::STATUS_SKIP, UserAppointment::STATUS_COMPLETED);
        $appointments = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $schedule_id, 'status' => $status])->andWhere(['<=', 'token', $token])->orderBy('token asc')->all();
        $lists = array();
        $a = 0;
        foreach ($appointments as $appointment) {
            $lists[$a]['id'] = $appointment->id;
            $lists[$a]['user_gender'] = $appointment->user_gender;
            $lists[$a]['slot_id'] = $appointment->slot_id;
            $lists[$a]['token'] = $appointment->token;
            $shiftlabel = explode('Shift', $appointment->shift_name);
            $lists[$a]['appointment_time'] = date('h:i a', $appointment->appointment_time);
            if ($a == 0) {
                $time = $appointment->actual_time;
            } else {
                $time = $time;
            }
            if ($appointment->status == UserAppointment::STATUS_COMPLETED) {
                $lists[$a]['time_label'] = 'Actual Time';
                $lists[$a]['status'] = 'Completed';
                $lists[$a]['status_color'] = 'green';
                $lists[$a]['status_class'] = 'green-text';
                $lists[$a]['time'] = date('h:i a', $appointment->actual_time);
                $lists[$a]['time_color'] = 'green';
                $lists[$a]['time_class'] = 'green-text';
            } elseif ($appointment->status == UserAppointment::STATUS_ACTIVE) {
                $lists[$a]['time'] = date('h:i a', $appointment->actual_time);
                $time = $appointment->actual_time;
                $lists[$a]['time_label'] = 'Actual Time';
                $lists[$a]['status'] = 'Consulting';
                $lists[$a]['status_color'] = 'orange';
                $lists[$a]['status_class'] = 'orange-text';
                $lists[$a]['time_color'] = 'orange';
                $lists[$a]['time_class'] = 'orange-text';
            } elseif ($appointment->status == UserAppointment::STATUS_PENDING) {
                $lists[$a]['time'] = '---';
                $lists[$a]['time_label'] = 'Estimated Time';
                $lists[$a]['status'] = 'Hold';
                $lists[$a]['status_color'] = 'red';
                $lists[$a]['status_class'] = 'red-text';
                $lists[$a]['time_color'] = 'red';
                $lists[$a]['time_class'] = 'red-text';
            } elseif ($appointment->status == UserAppointment::STATUS_SKIP) {
                $lists[$a]['time'] = '---';
                $lists[$a]['time_label'] = 'Estimated Time';
                $lists[$a]['status'] = 'Hold';
                $lists[$a]['status_color'] = 'red';
                $lists[$a]['status_class'] = 'red-text';
                $lists[$a]['time_color'] = 'red';
                $lists[$a]['time_class'] = 'red-text';
            } else {
                $schedule = UserScheduleDay::find()->where(
                                ['schedule_id' => $appointment->schedule_id,
                                    'user_id' => $appointment->doctor_id, 'date' => $appointment->date])->one();

                if (!empty($schedule)) {
                    $duration = $schedule->appointment_time_duration;
                    if ($lists[$a]['appointment_time'] < $time) {
                        $time = strtotime('+' . $duration . ' minute', $time);
                    } else {
                        $time = strtotime('-' . $duration . ' minute', $time);
                    }
                }

                $lists[$a]['time'] = date('h:i a', $time);
                $lists[$a]['time_label'] = 'Estimated Time';
                $lists[$a]['status'] = 'Waiting';
                $lists[$a]['status_color'] = 'red';
                $lists[$a]['status_class'] = 'red-text';
                $lists[$a]['time_color'] = 'red';
                $lists[$a]['time_class'] = 'red-text';
            }

            /* if(is_int($lists[$a]['time'])){
              if($lists[$a]['time'] > $lists[$a]['appointment_time']){
              $lists[$a]['time_color']='red';
              $lists[$a]['time_class']='red-text';
              }
              else{
              $lists[$a]['time_color']='orange';
              $lists[$a]['time_class']='orange-text';
              }
              }
              else{
              $lists[$a]['time_color']='red';
              $lists[$a]['time_class']='red-text';
              }

              if($lists[$a]['status'] == 'Waiting'){
              $lists[$a]['time_color']='red';
              $lists[$a]['time_class']='red-text';
              } */


            $a++;
        }
        return $lists;
    }

    public function deleteShiftForDays($doctor_id, $schedule_ids = [], $address_id = NULL) {
        $cond['user_id'] = $doctor_id;
        if ($schedule_ids)
            $cond['id'] = $schedule_ids;
        if ($address_id)
            $cond['address_id'] = $address_id;
        $lists = UserSchedule::deleteAll($cond);
        if ($lists) {
            unset($cond['id'], $cond['address_id']);
            if ($schedule_ids)
                $cond['schedule_id'] = $schedule_ids;
            UserScheduleGroup::deleteAll($cond);
            UserScheduleDay::deleteAll($cond);
            UserScheduleSlots::deleteAll($cond);
        }
        return true;
    }

    public static function deleteAddresswithShifts($doctor_id, $address_id) {
        $schedule_ids = array();
        $useraddress = UserAddress::findOne($address_id);
        if (!empty($useraddress)) {
            $getSchedules = UserSchedule::find()->where(['user_id' => $doctor_id, 'address_id' => $address_id])->all();
            foreach ($getSchedules as $schedules) {
                $schedule_ids[] = $schedules->id;
            }
            $useraddress->status = 0;
            if ($useraddress->save()) {
                $cancelAppointments = DrsPanel::cancelAppointmentsBySchedule($schedule_ids);


                $cond['user_id'] = $doctor_id;
                if ($schedule_ids)
                    $cond['id'] = $schedule_ids;
                if ($address_id)
                    $cond['address_id'] = $address_id;
                $lists = UserSchedule::deleteAll($cond);
                if ($lists) {
                    unset($cond['id'], $cond['address_id']);
                    if ($schedule_ids)
                        $cond['schedule_id'] = $schedule_ids;
                    UserScheduleGroup::deleteAll($cond);
                    UserScheduleDay::deleteAll($cond);
                    UserScheduleSlots::deleteAll($cond);
                }

                $attender_shifts = DrsPanel::editShiftToAttender($doctor_id);
                $shifts_keys = Drspanel::addUpdateShiftKeys($doctor_id);
                $updateStatusShift = DrsPanel::userShiftsStatus($doctor_id);
                $loadtodayshifts = DrsPanel::getScheduleShifts($doctor_id, date('Y-m-d'));

                $response["status"] = 1;
                $response["error"] = false;
                $response['message'] = 'Address Deleted Successfully';
            }
            else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Please try again';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Something went wrong,Please try again';
        }
        return $response;
    }

    public function loadShiftData($ids, $keyClnt, $post, $day_shift, $shiftcount) {
        if (isset($post['AddScheduleForm'])) {
            
        } else {
            $post['AddScheduleForm'] = $post;
        }
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

    public function errorMessageObject($startTimeClnt, $endTimeClnt, $keyClnt, $value, $message) {
        $errormsgloop['start_time'] = $startTimeClnt;
        $errormsgloop['end_time'] = $endTimeClnt;
        $errormsgloop['shift'] = $keyClnt;
        $errormsgloop['weekday'] = $value;
        $errormsgloop['shift_week'] = $keyClnt . '_' . $value;
        $errormsgloop['message'] = $message;
        return $errormsgloop;
    }

    public static function addUpdateShiftKeys($doctor_id) {
        $list_schedule = array();
        $list_week_day = array();
        $schedules = UserSchedule::find()->where(['user_id' => $doctor_id])->orderBy('weekday')->all();
        $k = 0;
        foreach ($schedules as $schedule) {
            if (isset($list_schedule[$schedule->weekday])) {
                $k++;
            } else {
                $k = 0;
            }
            $dbstart_time = date('Y-m-d');

            $nstart_time = $dbstart_time . ' ' . date('h:i a', $schedule->start_time);
            $nend_time = $dbstart_time . ' ' . date('h:i a', $schedule->end_time);
            $startTimeClnt = strtotime($nstart_time);
            $endTimeClnt = strtotime($nend_time);

            $list_schedule[$schedule->weekday][$k]['id'] = $schedule->id;
            $list_schedule[$schedule->weekday][$k]['start_time'] = $startTimeClnt;
            $list_schedule[$schedule->weekday][$k]['end_time'] = $endTimeClnt;
        }
        if (!empty($list_schedule)) {
            $getWeekArray = DrsPanel::getWeekArray();
            foreach ($getWeekArray as $weekarray) {
                if (isset($list_schedule[$weekarray])) {
                    usort($list_schedule[$weekarray], function ($a, $b) {
                        return $a['start_time'] - $b['start_time'];
                    });
                }
            }

            foreach ($list_schedule as $key => $value) {
                foreach ($value as $keylist => $shiftvalue) {
                    $schedulefind = UserSchedule::findOne($shiftvalue['id']);
                    $schedulefind->shift = $keylist + 1;
                    if ($schedulefind->save()) {
                        //checkscheduleDay
                        $scheduleDays = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'schedule_id' => $shiftvalue['id']])->all();
                        foreach ($scheduleDays as $scheduleDay) {
                            $single_day_schedule = UserScheduleDay::findOne($scheduleDay->id);
                            $single_day_schedule->shift = $schedulefind->shift;
                            $single_day_schedule->save();
                        }

                        //checkscheduleGroup
                        $scheduleGroups = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'schedule_id' => $shiftvalue['id']])->all();
                        foreach ($scheduleGroups as $scheduleGroup) {
                            $single_group_schedule = UserScheduleGroup::findOne($scheduleGroup->id);
                            $single_group_schedule->shift = $schedulefind->shift;
                            $single_group_schedule->save();
                        }
                    }
                }
            }
        }
        return true;
    }

    public static function oldShiftsDataUpdate($olddata, $newdata) {
        $id_check = $olddata['id'];
        $schedule = UserSchedule::findOne($id_check);

        $dbstart_time = date('Y-m-d');
        $ostart_time = $dbstart_time . ' ' . date('h:i a', $olddata['start_time']);
        $oend_time = $dbstart_time . ' ' . date('h:i a', $olddata['end_time']);
        $startTimeDb = strtotime($ostart_time);
        $endTimeDb = strtotime($oend_time);
        $DurationDb = $olddata['appointment_time_duration'];
        $consultationfeesDb = $olddata['consultation_fees'];
        $emergencyfeesDb = $olddata['emergency_fees'];
        $consultationDiscountfeesDb = $olddata['consultation_fees_discount'];
        $emergencyDiscountfeesDb = $olddata['emergency_fees_discount'];

        if (isset($newdata['AddScheduleForm'])) {
            $nstart_time = $dbstart_time . ' ' . date('h:i a', strtotime($newdata['AddScheduleForm']['start_time']));
            $nend_time = $dbstart_time . ' ' . date('h:i a', strtotime($newdata['AddScheduleForm']['end_time']));
            $startTimeClnt = strtotime($nstart_time);
            $endTimeClnt = strtotime($nend_time);
            $DurationClnt = $newdata['AddScheduleForm']['appointment_time_duration'];
            $consultationfeesClnt = $newdata['AddScheduleForm']['consultation_fees'];
            $emergencyfeesClnt = $newdata['AddScheduleForm']['emergency_fees'];
            $consultationDiscountfeesClnt = $newdata['AddScheduleForm']['consultation_fees_discount'];
            $emergencyDiscountfeesClnt = $newdata['AddScheduleForm']['emergency_fees_discount'];

            if (($startTimeDb == $startTimeClnt) && ($endTimeDb == $endTimeClnt) &&
                    ($DurationDb == $DurationClnt) && ($consultationfeesDb == $consultationfeesClnt) &&
                    ($emergencyfeesDb == $emergencyfeesClnt) && ($consultationDiscountfeesDb == $consultationDiscountfeesClnt) &&
                    ($emergencyDiscountfeesDb == $emergencyDiscountfeesClnt)) {
                
            } else {
                $statusarray = array('pending', 'available', 'active', 'deactivate', 'skip', 'booked');
                $appointments = UserAppointment::find()->where(['schedule_id' => $id_check, 'status' => $statusarray])->all();
                if (!empty($appointments)) {
                    foreach ($appointments as $appointment) {
                        $appointment->status = UserAppointment::STATUS_CANCELLED;
                        $appointment->is_deleted = 1;
                        $appointment->deleted_by = 'Doctor';
                        if ($appointment->save()) {
                            $addLog = Logs::appointmentLog($appointment->id, 'Appointment cancelled by doctor');
                        }
                    }
                }
                $deleteDateSchedule = UserScheduleDay::deleteAll(['schedule_id' => $id_check]);
                $deleteGroupSchedule = UserScheduleGroup::deleteAll(['schedule_id' => $id_check]);
                $deleteSlotsSchedule = UserScheduleSlots::deleteAll(['schedule_id' => $id_check]);
            }
        }
        return true;
    }

    public static function editShiftToAttender($doctor_id) {
        $addressList = DrsPanel::doctorHospitalList($doctor_id);
        $listadd = $addressList['apiList'];
        $shift_array = array();
        $shift_value = array();
        $shift_value_detail = array();
        $selectedShiftsIds = array();

        $list = DrsPanel::attenderList(['parent_id' => $doctor_id], 'apilist');
        foreach ($list as $key => $item) {
            $s = 0;
            $sv = 0;
            $attender_id = $item['id'];
            $selectedShifts = Drspanel::shiftList(['user_id' => $doctor_id, 'attender_id' => $attender_id], 'list');

            foreach ($listadd as $address) {
                $shifts = DrsPanel::getShiftListByAddress($doctor_id, $address['id']);
                foreach ($shifts as $key => $shift) {
                    if ($shift['hospital_id'] == 0) {
                        $shift_value[$attender_id][$sv] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';

                        $shift_value_detail[$attender_id][$sv]['label'] = $shift['address_line'] . ' (' . $shift['shift_label'] . ') - (' . $shift['shifts_list'] . ')';
                        $shift_value_detail[$attender_id][$sv]['shifts'] = $shift['shifts_ids'];

                        $shift_id_list = $shift['shifts_ids'];
                        foreach ($selectedShifts as $select => $valuesel) {
                            if (in_array($select, $shift_id_list)) {
                                $selectedShiftsIds[$attender_id][$sv] = $sv;
                            }
                        }
                        $s++;
                        $sv++;
                    }
                }
            }
        }

        foreach ($selectedShiftsIds as $key_attender => $shiftselect) {
            $shift_val = array();
            foreach ($shiftselect as $k => $shiftk) {
                if (isset($shift_value_detail[$key_attender]) && !empty($shift_value_detail[$key_attender]) && isset($shift_value_detail[$key_attender][$k]) && !empty($shift_value_detail[$key_attender][$k])) {
                    $shiftslist = $shift_value_detail[$key_attender][$k]['shifts'];
                    foreach ($shiftslist as $vallist) {
                        $shift_val[] = $vallist;
                    }
                }
            }
            $addupdateAttender = DrsPanel::addUpdateAttenderToShifts($shift_val, $key_attender);
        }
        return true;
    }

    public static function getAppointmentTime($shift_name, $date) {
        $shiftlabel = explode('Shift', $shift_name);

        $label = isset($shiftlabel[1]) ? $shiftlabel[1] : $shift_name;

        $time = explode('-', $label);
        $unixtime = strtotime($time[0]);

        $dbstart_time = date('Y-m-d', strtotime($date));

        $nstart_time = $dbstart_time . ' ' . date('h:i a', $unixtime);
        $startTimeClnt = strtotime($nstart_time);

        return array('appointment_time' => $startTimeClnt, 'actual_time' => $startTimeClnt);
    }

    public static function blockSlot($slot_id, $current_login_id) {
        $slot = UserScheduleSlots::find()->andWhere(['id' => $slot_id, 'status' => 'available'])->one();
        if (!empty($slot)) {
            $slot->status = 'blocked';
            $slot->blocked_by = $current_login_id;
            $blocked_time = strtotime('+2 minutes', strtotime(date('Y-m-d H:i:s')));
            $slot->blocked_till = $blocked_time;
            if ($slot->save()) {

                $response = 'success';
            } else {
                $response = 'error';
            }
        } else {
            $response = 'error';
        }


        return $response;
    }

    public static function checkBlockedSlots() {
        $time = time();
        $slots = UserScheduleSlots::find()->where(['status' => 'blocked'])
                        ->andWhere('blocked_till <= "' . $time . '"')->all();
        foreach ($slots as $slot) {
            $slot->status = 'available';
            $slot->blocked_till = 0;
            $slot->blocked_by = 0;
            $slot->save();
        }

        $temp_bookings = UserAppointmentTemp::find()->where(['payment_status' => UserAppointment::PAYMENT_PENDING])
                ->all();
        foreach ($temp_bookings as $temp_booking) {
            $slot = UserScheduleSlots::find()->where(['status' => 'booked', 'id' => $temp_booking->slot_id])
                            ->andWhere('blocked_till <= "' . $time . '"')->one();
            if (!empty($slot)) {
                //echo $temp_booking->slot_id;
                $slot->status = 'available';
                $slot->blocked_till = 0;
                $slot->blocked_by = 0;
                $slot->save();
            }
        }
        //die;
        return true;
    }

    public static function statusLabel($status) {
        $array = array('pending' => 'Confirmed', 'available' => 'Confirmed', 'active' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'skip' => 'Hold');
        if (isset($array[$status])) {
            return $array[$status];
        } else {
            return $status;
        }
    }

    public static function statusLabelDoctor($status) {
        $array = array('pending' => 'Pending', 'available' => 'Available', 'active' => 'Consulting', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'skip' => 'Hold');
        if (isset($array[$status])) {
            return $array[$status];
        } else {
            return $status;
        }
    }

    public static function userShiftsStatus($user_id = 0) {
        if ($user_id > 0) {
            $users = User::find()->where(['id' => $user_id])->all();
        } else {
            $users = User::find()->where(['groupid' => array(Groups::GROUP_DOCTOR, Groups::GROUP_HOSPITAL)])->all();
        }
        foreach ($users as $user) {
            $userProfile = UserProfile::find()->where(['user_id' => $user->id])->one();
            if ($user->groupid == Groups::GROUP_DOCTOR) {
                $schedules = UserSchedule::find()->where(['user_id' => $user->id])->all();
                if (!empty($schedules)) {
                    $userProfile->shifts = 1;
                    $getSchedule = UserSchedule::find()->where(['user_id' => $user->id])->andWhere(['>', 'consultation_fees', 0])->orderBy('consultation_fees asc')->one();
                    if (!empty($getSchedule)) {
                        $userProfile->address_id = $getSchedule->address_id;
                        $userProfile->consultation_fees = $getSchedule->consultation_fees;
                        if ($getSchedule->consultation_fees_discount == '' || empty($getSchedule->consultation_fees_discount) || is_null($getSchedule->consultation_fees_discount)) {
                            $userProfile->consultation_fees_discount = 0;
                        } else {
                            $userProfile->consultation_fees_discount = $getSchedule->consultation_fees_discount;
                        }
                    } else {
                        $userProfile->consultation_fees = 0;
                        $userProfile->consultation_fees_discount = 0;
                        $userProfile->address_id = 0;
                    }
                } else {
                    $userProfile->shifts = 0;
                    $userProfile->consultation_fees = 0;
                    $userProfile->consultation_fees_discount = 0;
                    $userProfile->address_id = 0;
                }
                $userProfile->save();
            } else {
                $hospitalAddress = UserAddress::find()->where(['user_id' => $user->id])->one();
                if (!empty($hospitalAddress)) {
                    $lists = DrsPanel::doctorsHospitalList($user->id, 'Confirm', Groups::GROUP_HOSPITAL, $user->id);
                    $command = $lists->createCommand();
                    $lists = $command->queryAll();
                    if (!empty($lists)) {
                        $scheduleget = array();
                        $consultation_fees = array();
                        $consultation_fees_discount = array();
                        foreach ($lists as $list) {
                            $getSchedule = UserSchedule::find()->where(['user_id' => $list['user_id'], 'address_id' => $hospitalAddress->id])->orderBy('consultation_fees asc')->one();
                            if (!empty($getSchedule)) {
                                $consultation_fees[] = $getSchedule->consultation_fees;
                                if ($getSchedule->consultation_fees_discount == '' || empty($getSchedule->consultation_fees_discount) || is_null($getSchedule->consultation_fees_discount)) {
                                    
                                } else {
                                    $consultation_fees_discount[] = $getSchedule->consultation_fees_discount;
                                }
                                $scheduleget[] = 1;
                            } else {
                                $scheduleget[] = 0;
                            }
                        }
                        if (in_array(1, $scheduleget)) {
                            $userProfile->shifts = 1;
                            if (!empty($consultation_fees)) {
                                $userProfile->consultation_fees = min($consultation_fees);
                            } else {
                                $userProfile->consultation_fees = 0;
                            }

                            if (!empty($consultation_fees_discount)) {
                                $userProfile->consultation_fees_discount = min($consultation_fees_discount);
                            } else {
                                $userProfile->consultation_fees_discount = 0;
                            }

                            $userProfile->address_id = $hospitalAddress->id;
                        } else {
                            $userProfile->shifts = 0;
                            $userProfile->consultation_fees = 0;
                            $userProfile->consultation_fees_discount = 0;
                            $userProfile->address_id = 0;
                        }
                    } else {
                        $userProfile->shifts = 0;
                        $userProfile->consultation_fees = 0;
                        $userProfile->consultation_fees_discount = 0;
                        $userProfile->address_id = 0;
                    }
                } else {
                    $userProfile->shifts = 0;
                    $userProfile->consultation_fees = 0;
                    $userProfile->consultation_fees_discount = 0;
                    $userProfile->address_id = 0;
                }
                $userProfile->save();
            }
        }
        return true;
    }

    public static function hospitalDoctorFees($hospital_id, $user_id) {
        $users = User::find()->where(['id' => $user_id])->one();
        $hospitalAddress = UserAddress::find()->where(['user_id' => $hospital_id])->one();
        $consultation_fees = 0;
        $consultation_fees_discount = 0;
        if (!empty($hospitalAddress)) {
            $getSchedule = UserSchedule::find()->where(['user_id' => $user_id, 'address_id' => $hospitalAddress->id])->orderBy('consultation_fees asc')->one();
            if (!empty($getSchedule)) {
                $consultation_fees = $getSchedule->consultation_fees;
                if ($getSchedule->consultation_fees_discount == '' || empty($getSchedule->consultation_fees_discount) || is_null($getSchedule->consultation_fees_discount)) {
                    $consultation_fees_discount = 0;
                } else {
                    $consultation_fees_discount = $getSchedule->consultation_fees_discount;
                }
            }
        }
        return array('consultation_fees' => $consultation_fees, 'consultation_fees_discount' => $consultation_fees_discount);
    }

    public static function getPopularCities() {
        $list = array();
        $list = Cities::find()->where(['status' => 1])->all();
        $city_list = array();
        foreach ($list as $a) {
            $city_list[$a->name] = $a->name;
        }
        return $city_list;
    }

    public static function updateCurrentStatus($status, $doctor_id, $date, $shift) {
        $res = ['status' => false, 'message' => 'Please try again'];
        if ($status == 'next' || $status == 'skip') {
            $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_ACTIVE])->orderBy('token asc')->one();
            if (!empty($checkFirstAppointment)) {
                $appointment_id = $checkFirstAppointment->id;
                $status_update = ($status == 'next') ? UserAppointment::STATUS_COMPLETED : UserAppointment::STATUS_SKIP;
                $checkFirstAppointment->status = $status_update;
                if ($checkFirstAppointment->save()) {
                    if ($status == 'next') {
                        $thankuNotification = Notifications::thankyou($checkFirstAppointment->user_id);
                    }
                    $res = ['status' => true];
                    $secondAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                    if (!empty($secondAppointment)) {
                        $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                        $secondAppointment->actual_time = time();
                        $secondAppointment->save();
                    } else {
                        $secondAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_SKIP])->andWhere(['!=', 'id', $appointment_id])->orderBy('token asc')->one();
                        if (!empty($secondAppointment)) {
                            $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                            $secondAppointment->actual_time = time();
                            $secondAppointment->save();
                        }
                    }
                }
            } else {
                $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                if (!empty($checkFirstAppointment)) {
                    $appointment_id = $checkFirstAppointment->id;
                    $status_update = ($status == 'next') ? UserAppointment::STATUS_COMPLETED : UserAppointment::STATUS_SKIP;
                    $checkFirstAppointment->status = $status_update;
                    if ($checkFirstAppointment->save()) {
                        $res = ['status' => true];
                        $secondAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_AVAILABLE])->orderBy('token asc')->one();
                        if (!empty($secondAppointment)) {
                            $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                            $secondAppointment->actual_time = time();
                            $secondAppointment->save();
                        } else {
                            $secondAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_SKIP])->andWhere(['!=', 'id', $appointment_id])->orderBy('token asc')->one();
                            if (!empty($secondAppointment)) {
                                $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                                $secondAppointment->actual_time = time();
                                $secondAppointment->save();
                            }
                        }
                    }
                } else {
                    $checkFirstAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_SKIP])->orderBy('token asc')->one();
                    if (!empty($checkFirstAppointment)) {
                        $appointment_id = $checkFirstAppointment->id;
                        $status_update = ($status == 'next') ? UserAppointment::STATUS_COMPLETED : UserAppointment::STATUS_SKIP;
                        $checkFirstAppointment->status = $status_update;
                        if ($checkFirstAppointment->save()) {
                            $res = ['status' => true];
                            $secondAppointment = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'date' => $date, 'schedule_id' => $shift, 'status' => UserAppointment::STATUS_SKIP])->andWhere(['!=', 'id', $appointment_id])->orderBy('token asc')->one();
                            if (!empty($secondAppointment)) {
                                $secondAppointment->status = UserAppointment::STATUS_ACTIVE;
                                $secondAppointment->actual_time = time();
                                $secondAppointment->save();
                            } else {
                                
                            }
                        } else {
                            $res = ['status' => false, 'message' => 'Appointments not updated'];
                        }
                    } else {
                        $res = ['status' => false, 'message' => 'Appointments not available or may be not paid.'];
                    }
                }
            }
        } else {
            
        }
        return $res;
    }

    public static function cancelAppointmentById($appointment_id, $by = 'Doctor') {
        $response = array('status' => 'error', 'message' => 'Please try again!');
        $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
        $appointment->status = UserAppointment::STATUS_CANCELLED;
        $appointment->is_deleted = 1;
        if ($by == 'Patient' || $by == 'patient') {
            $appointment->deleted_by = 'Patient';
        } else {
            $appointment->deleted_by = 'Doctor';
        }
        if ($appointment->save()) {
            $sendSMS = Notifications::appointmentSmsNotification($appointment->id, 'cancelled', strtolower($by));
            $addLog = Logs::appointmentLog($appointment->id, 'Appointment cancelled by ' . $by);
            $slot_id = $appointment->slot_id;
            $schedule_id = $appointment->schedule_id;
            $slot = UserScheduleSlots::find()->where(['id' => $slot_id, 'schedule_id' => $schedule_id])->one();
            $slot->status = 'available';
            $slot->save();
            $refunResponse = self::getRefundAmount($appointment_id, $by);

            if ($refunResponse['status'] == 'success') {
                $response = array('status' => 'success', 'message' => $refunResponse['message']);
            } else {
                $response = array('status' => 'error', 'message' => $refunResponse['message']);
            }
        } else {
            $response = array('status' => 'success', 'message' => $appointment->getErrors());
        }
        return $response;
    }

    public static function getRefundAmount($appointment_id, $by) {
        if ($appointment_id != '') {
            //$doctor = User::findOne($id);
            $appointmentId = $appointment_id;
            $getAppointmentDetail = UserAppointment::find()->where(['id' => $appointmentId])->one();
            $timestamp = $getAppointmentDetail['appointment_time'] - 60 * 60 * 2; // appointment_time - 2 hours
            if ($timestamp >= time()) {
                $checkTransaction = Transaction::find()->where(['appointment_id' => $appointmentId, 'type' => 'refund'])->count();
                if ($checkTransaction == 0) {
                    $addTransactionRow = Logs::addTransactionRow($appointmentId, 0, $type = 'refund', $txn_type = 'booking', $service_charge = 0);
                }

                $getTransactionDetail = Transaction::find()->where(['appointment_id' => $appointmentId, 'type' => 'pay'])->one();
                $paytmResponse = json_decode($getTransactionDetail['paytm_response']);
                $refundResponce = Payment::paytm_refund_api($paytmResponse, $appointmentId);

                if (isset($refundResponce['body']['orderId']) && $refundResponce['body']['orderId'] != '') {

                    $refundData = array("ORDERID" => $refundResponce['body']['orderId'], "MID" => $refundResponce['body']['mid'], "REFID" => isset($refundResponce['body']['refId']) ? $refundResponce['body']['refId'] : 0, "STATUS" => $refundResponce['body']['resultInfo']['resultStatus'], "RESULTMSG" => $refundResponce['body']['resultInfo']['resultMsg'], "REFUNDID" => $refundResponce['body']['refundId'], "TXNID" => $refundResponce['body']['txnId'], "REFUNDAMOUNT" => $refundResponce['body']['refundAmount']);

                    $transactionModel = Transaction::find()->where(['appointment_id' => $appointmentId, 'type' => 'refund'])->one();
                    $transactionModel->paytm_response = json_encode($refundData);
                    $transactionModel->status = strtolower($refundResponce['body']['resultInfo']['resultStatus']);
                    if ($by == 'Patient' || $by == 'patient') {
                        $transactionModel->refund_by = 'Patient';
                    } else {
                        $transactionModel->refund_by = 'Doctor';
                    }
                    if ($transactionModel->save()) {
                        return array('status' => 'success', 'message' => 'Appointment Cancelled! Refund Will Be Initiated Shortly.');
                    } else {
                        return array('status' => 'success', 'message' => 'Appointment Cancelled! ' . $transactionModel->getErrors());
                    }
                } else {
                    return array('status' => 'success', 'message' => 'Appointment Cancelled! Refund request failed');
                }
            } else {
                return array('status' => 'success', 'message' => 'Appointment Cancelled! Refund request not complete.');
            }
        }
    }

    public static function deleteShiftWithAppointments($schedule_id) {
        $cancelAppointments = DrsPanel::cancelAppointmentsBySchedule($schedule_id);
        $deleteschedule = UserSchedule::findOne($schedule_id);
        if (!empty($deleteschedule)) {
            $deleteschedule->delete();
        }
        $deleteDateSchedule = UserScheduleDay::deleteAll(['schedule_id' => $schedule_id]);
        $deleteGroupSchedule = UserScheduleGroup::deleteAll(['schedule_id' => $schedule_id]);
        $deleteSlotsSchedule = UserScheduleSlots::deleteAll(['schedule_id' => $schedule_id]);
        return true;
    }

    public static function cancelAppointmentsBySchedule($schedule_id, $date = '', $doctor_id = '', $by = 'Doctor') {
        $statusarray = array('pending', 'available', 'active', 'deactivate', 'skip', 'booked');
        if ($date == '' && $doctor_id == '') {
            $appointments = UserAppointment::find()->where(['schedule_id' => $schedule_id, 'status' => $statusarray])->all();
        } else {
            $appointments = UserAppointment::find()->where(['schedule_id' => $schedule_id, 'doctor_id' => $doctor_id, 'date' => $date, 'status' => $statusarray])->all();
        }

        if (!empty($appointments)) {
            foreach ($appointments as $appointment) {
                $appointment->status = UserAppointment::STATUS_CANCELLED;
                $appointment->is_deleted = 1;
                if ($by == 'Patient' || $by == 'patient') {
                    $appointment->deleted_by = 'Patient';
                } else {
                    $appointment->deleted_by = 'Doctor';
                }
                if ($appointment->save()) {
                    $refunResponse = self::getRefundAmount($appointment->id, $by);
                    $sendSMS = Notifications::appointmentSmsNotification($appointment->id, 'cancelled', strtolower($by));
                    $addLog = Logs::appointmentLog($appointment->id, 'Appointment cancelled by ' . $by);
                }
            }
        }
        return true;
    }

    public static function getProfileStatus($user_id) {
        $user = User::findOne($user_id);
        return $user->admin_status;
    }

    public static function loadAddressData($params) {
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
        return $data;
    }

    public static function addShiftWithAddress($insertShift, $address_id, $user_id) {
        if (!empty($insertShift)) {
            foreach ($insertShift as $key => $value) {
                $saveScheduleData = new UserSchedule();
                $saveScheduleData->load(['UserSchedule' => $value['AddScheduleForm']]);
                $saveScheduleData->address_id = $address_id;
                $saveScheduleData->start_time = strtotime($value['AddScheduleForm']['start_time']);
                $saveScheduleData->end_time = strtotime($value['AddScheduleForm']['end_time']);
                $saveScheduleData->save();
            }
        }
        //add shift keys to user_schedule table
        $attender_shifts = DrsPanel::editShiftToAttender($user_id);
        $shifts_keys = Drspanel::addUpdateShiftKeys($user_id);
        $updateStatusShift = DrsPanel::userShiftsStatus($user_id);
        $loadtodayshifts = DrsPanel::getScheduleShifts($user_id, date('Y-m-d'));

        $response["status"] = 1;
        $response["error"] = false;
        $response["data"] = '';
        $response['message'] = 'Shift Added Successfully';
        return $response;
    }

    public static function responseArray($status, $code, $message, $data = '', $other = array()) {
        $response = array();
        $response["status"] = $status;
        $response["error"] = $code;
        if (!empty($data) || $data != '') {
            $response["data"] = $data;
        }
        if (!empty($other)) {
            foreach ($other as $key => $val) {
                $response[$key] = $val;
            }
        }
        $response['message'] = $message;
        return $response;
    }

    public static function getFilterArray($type = 'doctor', $currentcity = '', $app = 0, $type2 = '') {
        $groups_v = array();
        $l = 0;

        if ($type != 'specialization') {
            $key = MetaKeys::findOne(['key' => 'speciality']);
            if (!empty($key)) {
                $metavalues = MetaValues::find()->where(['key' => $key->id])->all();
                $m = 0;
                $groups_v[$l]['type'] = $key->key;
                $groups_v[$l]['label'] = 'Speciality';
                $groups_v[$l]['select_type'] = 'multiple';
                $groups_v[$l]['list'] = array();
                foreach ($metavalues as $values) {
                    $groups_v[$l]['list'][$m]['id'] = $values->id;
                    $groups_v[$l]['list'][$m]['label'] = $values->label;
                    $groups_v[$l]['list'][$m]['value'] = $values->slug;
                    $groups_v[$l]['list'][$m]['select_type'] = 'multiple';
                    $m++;
                }
                $l++;
            }
        }

        if ($currentcity == '') {
            $currentcity = DrsPanel::getCitySelected();
        }
        $cityid = Drspanel::getCityId($currentcity, 'Rajasthan');
        $areas = Areas::find()->orderBy('id asc')->where(['city_id' => $cityid])->all();
        if (!empty($areas)) {
            $m = 0;
            $groups_v[$l]['type'] = 'areas';
            $groups_v[$l]['label'] = 'Area';
            $groups_v[$l]['select_type'] = 'multiple';
            $groups_v[$l]['list'] = array();
            foreach ($areas as $values) {
                $groups_v[$l]['list'][$m]['id'] = $values->id;
                $groups_v[$l]['list'][$m]['label'] = $values->name;
                $groups_v[$l]['list'][$m]['value'] = $values->code;
                $groups_v[$l]['list'][$m]['select_type'] = 'multiple';
                $m++;
            }
            $l++;
        }

        if (($type == 'doctor' || $type == 'specialization' || $type == 'treatment') && $type2 != 'hospital') {
            $groups_v[$l]['type'] = 'gender';
            $groups_v[$l]['label'] = 'Gender';
            $groups_v[$l]['select_type'] = 'single';
            $groups_v[$l]['list'] = array();
            $m = 0;
            $gender[0] = array('id' => UserProfile::GENDER_MALE, 'label' => 'Male');
            $gender[1] = array('id' => UserProfile::GENDER_FEMALE, 'label' => 'Female');
            $gender[2] = array('id' => UserProfile::GENDER_OTHER, 'label' => 'Other');
            foreach ($gender as $values) {
                $groups_v[$l]['list'][$m]['id'] = $values['id'];
                $groups_v[$l]['list'][$m]['label'] = $values['label'];
                $groups_v[$l]['list'][$m]['value'] = $values['id'];
                $groups_v[$l]['list'][$m]['select_type'] = 'single';
                $m++;
            }
            $l++;

            $groups_v[$l]['type'] = 'availability';
            $groups_v[$l]['label'] = 'Availability';
            $groups_v[$l]['select_type'] = 'single';
            $groups_v[$l]['list'] = array();

            $shift[0] = array('id' => '1', 'value' => 'today', 'label' => 'Today');
            $shift[1] = array('id' => '3', 'value' => 'next3days', 'label' => 'In next three days');
            $shift[2] = array('id' => '2', 'value' => 'anyday', 'label' => 'Any Day');

            $m = 0;
            foreach ($shift as $values) {
                $groups_v[$l]['list'][$m]['id'] = $values['id'];
                $groups_v[$l]['list'][$m]['label'] = $values['label'];
                $groups_v[$l]['list'][$m]['value'] = $values['value'];
                $groups_v[$l]['list'][$m]['select_type'] = 'single';
                $m++;
            }
            $l++;
        }


        if ($app == 1) {
            $groups_v[$l]['type'] = 'sort';
            $groups_v[$l]['label'] = 'Sort';
            $groups_v[$l]['select_type'] = 'single';
            $groups_v[$l]['list'] = array();

            if ($type == 'doctor') {
                $sortarray = array('price_highttolow' => 'Price, High to Low', 'price_lowtohigh' => 'Price, Low to High', 'rating_highttolow' => 'Rating, High to Low', 'rating_lowtohigh' => 'Rating, Low to High');
            } else {
                $sortarray = array('rating_highttolow' => 'Rating, High to Low', 'rating_lowtohigh' => 'Rating, Low to High');
            }
            $m = 0;
            foreach ($sortarray as $keysort => $sortarr) {
                $groups_v[$l]['list'][$m]['id'] = $keysort;
                $groups_v[$l]['list'][$m]['label'] = $sortarr;
                $groups_v[$l]['list'][$m]['value'] = $keysort;
                $groups_v[$l]['list'][$m]['select_type'] = 'single';
                $m++;
            }
        }
        return $groups_v;
    }

    public static function getSortArray() {
        $groups_v = array();
        $groups_v[0]['type'] = 'price';
        $groups_v[0]['label'] = 'Price, High to Low';
        $groups_v[0]['value'] = 'price_highttolow';
        $groups_v[1]['type'] = 'price';
        $groups_v[1]['label'] = 'Price, Low to High';
        $groups_v[1]['value'] = 'price_lowtohigh';

        $groups_v[2]['type'] = 'rating';
        $groups_v[2]['label'] = 'Rating, High to Low';
        $groups_v[2]['value'] = 'rating_highttolow';
        $groups_v[3]['type'] = 'rating';
        $groups_v[3]['label'] = 'Rating, Low to High';
        $groups_v[3]['value'] = 'rating_lowtohigh';

        return $groups_v;
    }

    public static function getAreaUser($areas) {
        $user = array();
        $area = Areas::find()->where(['code' => $areas])->all();
        foreach ($area as $area) {
            $addresss = UserAddress::find()->where(['area' => $area->name])->all();
            foreach ($addresss as $address) {
                $user[$address->user_id] = $address->user_id;
            }
        }
        return $user;
    }

    public static function getAvailibilityUser($type) {
        $key_array = array();
        $user = array();
        if ($type == 'today') {
            $today = date('Y-m-d');
            $key_array[] = $today;
        } elseif ($type == 'next3days') {
            $today = date('Y-m-d');
            $startnewdate = strtotime($today);
            $newdate = strtotime($today);

            $details['start'] = strtotime(date('Y-m-d 00:00:00', $newdate));
            for ($i = 1; $i <= 3; $i++) {
                $key_array[] = date("Y-m-d", $newdate);
                $details['end'] = strtotime(date('Y-m-d 23:59:59', $newdate));
                $newdate = strtotime(date("Y-m-d", $startnewdate) . " +" . $i . " days");
            }
        } elseif ($type == 'anyday') {
            $key_array = array();
            $schedules = UserScheduleDay::find()->where(['booking_closed' => 0])->andWhere(['>=', 'date', date('Y-m-d')])->all();
            foreach ($schedules as $schedule) {
                $user[$schedule->user_id] = $schedule->user_id;
            }
        } else {
            $key_array = array();
        }

        if (!empty($key_array)) {
            $schedules = UserScheduleDay::find()->where(['date' => $key_array])->all();
            foreach ($schedules as $schedule) {
                if ($schedule->booking_closed == 0) {
                    $user[$schedule->user_id] = $schedule->user_id;
                }
            }
        }
        return $user;
    }

    public static function addupdateServiceChange() {
        
    }

    public static function getServiceCharge($address_id, $doctor_id) {
        $discount = 0;
        $charge = array('charge' => 50, 'charge_discount' => '', 'discount' => 0, 'final_charge' => 50);
        $service_log = UserServiceCharge::find()->where(['user_id' => $doctor_id, 'address_id' => $address_id])->one();
        if (!empty($service_log)) {
            if (is_null($service_log->charge_discount)) {
                $final_charge = $service_log->charge;
                $discount = 0;
            } elseif ($service_log->charge_discount < $service_log->charge) {
                $discount = 1;
                $final_charge = $service_log->charge_discount;
            } else {
                $final_charge = $service_log->charge;
            }
            $charge = array('charge' => $service_log->charge, 'charge_discount' => $service_log->charge_discount, 'discount' => $discount, 'final_charge' => $final_charge);
        }
        return $charge;
    }

    public static function getCitySelected() {
        $cities = DrsPanel::getPopularCities();
        if (isset($_COOKIE['location_filter'])) {
            $cookie = $_COOKIE['location_filter'];
            // $cookie = stripslashes($cookie);
            $location = json_decode($cookie, true);
            $city = $location['city'];
            if (!in_array($city, $cities)) {
                $city = reset($cities);
            }
        } else {
            $city = reset($cities);
            $city = 'Jaipur';
        }
        DrsPanel::setLocationToCookie($city);
        return $city;
    }

    public static function getCookieData() {
        $data = array();
        if (isset($_COOKIE['location_filter'])) {
            $cookie = $_COOKIE['location_filter'];
            // $cookie = stripslashes($cookie);
            $location = json_decode($cookie, true);
            $data['city'] = $location['city'];
            $data['lat'] = $location['lat'];
            $data['lng'] = $location['lng'];
        }
        return $data;
    }

    public static function setLocationToCookie($city_sel) {
        $out = array();
        $out['city'] = $city_sel;
        $city_id = Drspanel::getCityId($city_sel, 'Rajasthan');
        $city = Cities::findOne($city_id);
        if (!empty($city)) {
            $out['address'] = $city_sel;
            $out['name'] = $city_sel;
            $out['lat'] = $city->lat;
            $out['lng'] = $city->lng;
        }
        $baseurl = $_SERVER['HTTP_HOST'];
        $json = json_encode($out, true);
        setcookie('location_filter', $json, time() + 60 * 60, '/', $baseurl, false);
    }

    public static function getLocationUserList($latitude, $longitude) {
        $user = array();
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
                        SELECT * ,( 3959 * acos( 
                            cos( radians($latitude) ) 
                            * cos( radians( user_address.lat ) ) 
                            * cos( radians( user_address.lng )  - radians($longitude) ) 
                            + sin( radians($latitude) ) 
                            * sin( radians( user_address.lat ) ) 
                            ) 
                        ) 
                       AS distance from user_address 
                       having distance < 65 ORDER BY distance ASC");
        $result = $command->queryAll();
        foreach ($result as $re) {
            $user[] = $re['user_id'];
        }
        return $user;
    }

    public static function getCityLatLong($city_id) {
        $out = array();
        $city = Cities::findOne($city_id);
        if (!empty($city)) {
            $out['lat'] = $city->lat;
            $out['lng'] = $city->lng;
        }
        return $out;
    }

    public static function getCurrentLocationLatLong() {
        $location = array('lat' => '', 'lng' => '');
        $user_id = Yii::$app->user->id;
        if ($user_id > 0) {
            //$data = DrsPanel::getCurrentLocation();
            $data = UserProfile::find()->where(['user_id' => $user_id])->one();
            if (!empty($data)) {
                $latitude = $data['lat'];
                $longitude = $data['lng'];
                $location = array('lat' => $latitude, 'lng' => $longitude);
            }
        }
        return $location;
    }

    public static function getKilometers($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo) {
        //Calculate distance from latitude and longitude
        //echo $latitudeFrom.'---'.$longitudeFrom.'---'.$latitudeTo.'---'.$longitudeTo; die;
        if ($latitudeFrom != '' && $longitudeFrom != '' && $latitudeTo != '' && $longitudeTo != '') {
            $theta = $longitudeFrom - $longitudeTo;
            $dist = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) + cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;

            $distance = $miles * 1.609344;
            $distance = number_format($distance, 2);
            $distance = ($distance) . ' km';
            return $distance;
        }
        return '';
    }

    public static function commaSeperatedWithSpace($list) {
        $new = array();
        $list_l = explode(',', $list);
        foreach ($list_l as $t) {
            $new[$t] = $t;
        }
        $new = implode(', ', $new);
        return $new;
    }

    public static function currentUserVerified($current_login_id, $doctor_id = 0) {
        if ($doctor_id > 0) {
            $doctor = User::findOne(['id' => $doctor_id]);
            $user = User::findOne(['id' => $current_login_id]);

            if ($user->groupid == Groups::GROUP_DOCTOR) {
                $userCurrentLogin = User::find()->where(['id' => $current_login_id])->one();
                return $userCurrentLogin->admin_status;
            } elseif ($user->groupid == Groups::GROUP_ATTENDER) {
                $attender_id = $current_login_id;
                $getParentDetails = DrsPanel::getParentDetails($attender_id);
                $parentGroup = $getParentDetails['parentGroup'];
                $attender_parent_id = $getParentDetails['parent_id'];
                if ($parentGroup == Groups::GROUP_HOSPITAL) {
                    $userCurrentLogin = User::find()->where(['id' => $attender_parent_id])->one();
                    return $userCurrentLogin->admin_status;
                } else {
                    $userCurrentLogin = User::find()->where(['id' => $attender_parent_id])->one();
                    return $userCurrentLogin->admin_status;
                }
            } else {
                $userCurrentLogin = User::find()->where(['id' => $current_login_id])->one();
                return $userCurrentLogin->admin_status;
            }
        } else {
            $userCurrentLogin = User::find()->where(['id' => $current_login_id])->one();
            return $userCurrentLogin->admin_status;
        }
    }

    public static function adminRoles() {
        $roles = array(Groups::GROUP_ADMIN => 'Admin',
            Groups::GROUP_MANAGER => 'Manager', Groups::GROUP_SUBADMIN1 => 'SubAdmin-1',
            Groups::GROUP_SUBADMIN2 => 'SubAdmin-2', Groups::GROUP_SUBADMIN3 => 'SubAdmin-3');
        return $roles;
    }

    public static function getUserPlan($user_id) {
        $user = User::findOne($user_id);
        return $user->user_plan;
    }

    public static function getTransactionId($appointment_id) {
        $transaction = Transaction::find()->where(['appointment_id' => $appointment_id])->one();
        if (!empty($transaction)) {
            if (!empty($transaction->paytm_response)) {
                $response = json_decode($transaction->paytm_response);
                return $response->TXNID;
            } else {
                return '';
            }
        }
        return '';
    }

    public static function randomOTP() {
        //$code = Yii::$app->security->generateRandomString(4);
        $n = 4;
        $generator = "1357902468";
        $code = "";
        for ($i = 1; $i <= $n; $i++) {
            $code .= substr($generator, (rand() % (strlen($generator))), 1);
        }

        //$code='1234';
        return $code;
    }

    public static function getBookingHistory($params) {
        $dataArr = array();
        if (isset($params['dateFrom']) && $params['dateFrom'] != '' && isset($params['dateTo']) && $params['dateTo'] != '') {
            $dateFrom = date('Y-m-d', strtotime($params['dateFrom']));
            $dateTo = date('Y-m-d', strtotime($params['dateTo']));
            $user_id = $params['user_id'];
            $shiftid = $params['shiftid'];
            $hospitalId = 0;
            if (isset($params['hospitalid']) && $params['hospitalid'] > 0) {
                $hospitalAddressId = UserAddress::find()->where(['user_id' => $params['hospitalid']])->one();
                $hospitalId = $hospitalAddressId->id;
            }
            $doctor = UserProfile::find()->where(['user_id' => $user_id])->one();

            //$appointments = $typeCount = $history = [];

            $getAppointments = DrsPanel::appointmentHistoryPeriod($user_id, $hospitalId, $dateFrom, $dateTo, $shiftid);
            $appointments = $getAppointments['bookings'];
            $history = $getAppointments['total_history'];
            $typeCount = $getAppointments['type'];

            $dataArr = array('appointments' => $appointments, 'history' => $history, 'typeCount' => $typeCount, 'doctor' => $doctor, 'hospital' => $hospitalId);
        }
        return $dataArr;
    }

    public static function getBookingShiftsPeriod($doctor_id, $reportPeriod, $current_login) {
        $getShiftSlots = array();
        $weekday = DrsPanel::getDateWeekDay($reportPeriod);
        $getScheduleShifts = DrsPanel::getScheduleShiftsPeriod($doctor_id, $reportPeriod);
        if ($getScheduleShifts) {
            $user = User::findOne($current_login);
            $groupid = $user->groupid;
            if ($groupid == Groups::GROUP_PATIENT) {
                $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $reportPeriod, 'booking_closed' => UserScheduleGroup::BOOKING_CLOSED_FALSE])->orderBy('shift asc')->all();
            } elseif ($groupid == Groups::GROUP_ATTENDER) {
                $attender_parent = DrsPanel::getAttenderParentType($current_login);
                if ($attender_parent == Groups::GROUP_DOCTOR) {
                    $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $reportPeriod, 'shift_belongs_to' => 'attender'])->andWhere('find_in_set(:key2, `attender_id`)', [':key2' => $current_login])->orderBy('shift asc')->all();
                } else {
                    $attender_address = HospitalAttender::find()->where(['doctor_id' => $doctor_id, 'attender_id' => $current_login])->all();
                    $addressids = array();
                    foreach ($attender_address as $attender) {
                        $addressids[] = $attender->hospital_id;
                    }
                    //hospital
                    $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $reportPeriod, 'hospital_id' => $addressids])->orderBy('shift asc')->all();
                }
            } elseif ($groupid == Groups::GROUP_HOSPITAL) {
                $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $reportPeriod, 'weekday' => $weekday, 'hospital_id' => $current_login])->all();
            } else {
                //doctor shifts
                $shifts = UserScheduleGroup::find()->where(['user_id' => $doctor_id, 'date' => $reportPeriod])->orderBy('shift asc')->all();
            }

            $s = 0;


            foreach ($shifts as $shift) {
                $addSlot = DrsPanel::getShiftSlots($doctor_id, $reportPeriod, $shift->schedule_id, $shift->shift);

                $dateSchedule = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $reportPeriod, 'shift' => $shift->shift])->one();

                if (empty($dateSchedule)) {
                    $dateSchedule = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'shift' => $shift->shift, 'id' => $shift->schedule_id])->one();
                }

                if (!empty($dateSchedule)) {

                    $getShiftSlots[$s]['doctor_id'] = $doctor_id;
                    $getShiftSlots[$s]['doctor_name'] = DrsPanel::getUserName($doctor_id);
                    $getShiftSlots[$s]['doctor_category'] = DrsPanel::getDoctorSpeciality($doctor_id);
                    $getShiftSlots[$s]['schedule_id'] = $shift->schedule_id;
                    $getShiftSlots[$s]['shift'] = $shift->shift;
                    $getShiftSlots[$s]['shift_id'] = $shift->id;
                    $getShiftSlots[$s]['shift_name'] = $shift->shift_label;
                    $getShiftSlots[$s]['shift_label'] = $shift->shift_label;
                    $getShiftSlots[$s]['date'] = $shift->date;
                    $getShiftSlots[$s]['status'] = $shift->status;
                    $getShiftSlots[$s]['booking_closed'] = $shift->booking_closed;

                    $address = UserAddress::findOne($dateSchedule->address_id);
                    $getShiftSlots[$s]['consultation_fees'] = $dateSchedule->consultation_fees;
                    $getShiftSlots[$s]['consultation_fees_discount'] = $dateSchedule->consultation_fees_discount;
                    $getShiftSlots[$s]['fees'] = $dateSchedule->consultation_fees;
                    $getShiftSlots[$s]['emergency_fees'] = $dateSchedule->emergency_fees;
                    $getShiftSlots[$s]['emergency_fees_discount'] = $dateSchedule->emergency_fees_discount;
                    $getShiftSlots[$s]['address_id'] = $dateSchedule->address_id;
                    $getShiftSlots[$s]['address_name'] = isset($address->name) ? $address->name : '';
                    $getShiftSlots[$s]['hospital_name'] = isset($address->name) ? $address->name : '';
                    $getShiftSlots[$s]['address'] = DrsPanel::getAddressLine($address->id);
                    $getShiftSlots[$s]['phone'] = isset($address->phone) ? $address->phone : '';
                    $getShiftSlots[$s]['duration'] = $dateSchedule->appointment_time_duration;
                    $getShiftSlots[$s]['patient_limit'] = $dateSchedule->patient_limit;
                    $getShiftSlots[$s]['start_time'] = date('h:i a', $dateSchedule->start_time);
                    $getShiftSlots[$s]['end_time'] = date('h:i a', $dateSchedule->end_time);
                    $getShiftSlots[$s]['time'] = date('h:i a', $dateSchedule->start_time) . ' - ' . date('h:i a', $dateSchedule->end_time);
                    $getShiftSlots[$s]['can_edit'] = $dateSchedule->is_edit;
                    $service_charge = DrsPanel::getServiceCharge($dateSchedule->address_id, $doctor_id);
                    $getShiftSlots[$s]['service_charge'] = $service_charge['charge'];
                    $getShiftSlots[$s]['service_charge_discount'] = $service_charge['charge_discount'];
                    $getShiftSlots[$s]['service_charge_show'] = $service_charge['final_charge'];

                    $s++;
                }
            }
        }
        return $getShiftSlots;
    }

    public static function getScheduleShiftsPeriod($doctor_id, $reportPeriod, $loginype = []) {
        $weekday = DrsPanel::getDateWeekDay($reportPeriod);
        $allSchedules = UserSchedule::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday])->all();
        // echo '<pre>';
        // print_r($allSchedules);
        // die;
        if (!empty($allSchedules)) {

            foreach ($allSchedules as $key => $schedule) {
                $approxStartTime = NULL;
                $approxEndTime = NULL;
                $dateSchedule = UserScheduleDay::find()->where(['user_id' => $doctor_id, 'weekday' => $weekday, 'date' => $reportPeriod, 'shift' => $schedule->shift])->one();
                // echo '<pre>';
                // print_r($dateSchedule);
                if (!empty($dateSchedule)) {
                    $shifts_g = UserScheduleGroup::find()->where(['schedule_id' => $schedule->id, 'date' => $reportPeriod, 'user_id' => $doctor_id])->one();
                    if (empty($shifts_g)) {

                        $approxStartTime = date('h:i a', $schedule->start_time);
                        $approxEndTime = date('h:i a', $schedule->end_time);

                        $schedulegroup = new UserScheduleGroup();
                        $schedulegroup->user_id = $doctor_id;
                        $schedulegroup->schedule_id = $schedule->id;
                        $schedulegroup->shift_belongs_to = $dateSchedule->shift_belongs_to;
                        $schedulegroup->attender_id = $dateSchedule->attender_id;
                        $schedulegroup->hospital_id = $dateSchedule->hospital_id;
                        $schedulegroup->address_id = $dateSchedule->address_id;
                        $schedulegroup->shift = (string) $schedule->shift;
                        $schedulegroup->shift_label = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                        $schedulegroup->start_time = $schedule->start_time;
                        $schedulegroup->end_time = $schedule->end_time;
                        $schedulegroup->date = $date;
                        $schedulegroup->weekday = $weekday;
                        $schedulegroup->status = 'pending';
                        $schedulegroup->booking_closed = $dateSchedule->booking_closed;
                        $schedulegroup->save();
                    }
                } else {
                    $getSchedule = new UserScheduleDay();
                    $getSchedule->schedule_id = $schedule->id;
                    $getSchedule->user_id = $schedule->user_id;
                    $getSchedule->shift_belongs_to = $schedule->shift_belongs_to;
                    $getSchedule->attender_id = $schedule->attender_id;
                    $getSchedule->hospital_id = $schedule->hospital_id;
                    $getSchedule->address_id = $schedule->address_id;
                    $getSchedule->type = 'available';
                    $getSchedule->shift = (string) $schedule->shift;
                    $getSchedule->date = $date;
                    $getSchedule->weekday = $schedule->weekday;

                    $approxStartTime = date('h:i a', $schedule->start_time);
                    $approxEndTime = date('h:i a', $schedule->end_time);
                    $approxStartTime = $date . ' ' . $approxStartTime;
                    $approxEndTime = $date . ' ' . $approxEndTime;
                    $start_time = strtotime($approxStartTime);
                    $end_time = strtotime($approxEndTime);

                    $getSchedule->start_time = $start_time;
                    $getSchedule->end_time = $end_time;
                    $getSchedule->patient_limit = $schedule->patient_limit;
                    $getSchedule->appointment_time_duration = $schedule->appointment_time_duration;
                    $getSchedule->consultation_fees = $schedule->consultation_fees;
                    $getSchedule->consultation_days = $schedule->consultation_days;
                    $getSchedule->consultation_show = $schedule->consultation_show;
                    $getSchedule->consultation_fees_discount = ($schedule->consultation_fees_discount) ? $schedule->consultation_fees_discount : 0;
                    $getSchedule->emergency_fees = $schedule->emergency_fees;
                    $getSchedule->emergency_days = $schedule->emergency_days;
                    $getSchedule->emergency_show = $schedule->emergency_show;
                    $getSchedule->emergency_fees_discount = ($schedule->emergency_fees_discount) ? $schedule->emergency_fees_discount : 0;
                    $getSchedule->status = $schedule->status;
                    $getSchedule->is_edit = $schedule->is_edit;
                    $getSchedule->booking_closed = 1;
                    if ($getSchedule->save()) {
                        $shifts_g = UserScheduleGroup::find()->where(['schedule_id' => $schedule->id, 'date' => $date, 'user_id' => $doctor_id])->one();
                        if (empty($shifts_g)) {
                            $approxStartTime = date('h:i a', $schedule->start_time);
                            $approxEndTime = date('h:i a', $schedule->end_time);

                            $schedulegroup = new UserScheduleGroup();
                            $schedulegroup->user_id = $doctor_id;
                            $schedulegroup->schedule_id = $schedule->id;
                            $schedulegroup->shift_belongs_to = $schedule->shift_belongs_to;
                            $schedulegroup->attender_id = $schedule->attender_id;
                            $schedulegroup->hospital_id = $schedule->hospital_id;
                            $schedulegroup->address_id = $schedule->address_id;
                            $schedulegroup->shift = (string) $schedule->shift;
                            $schedulegroup->shift_label = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                            $schedulegroup->start_time = $schedule->start_time;
                            $schedulegroup->end_time = $schedule->end_time;
                            $schedulegroup->date = $date;
                            $schedulegroup->weekday = $weekday;
                            $schedulegroup->status = 'pending';
                            $schedulegroup->booking_closed = 1;
                            $schedulegroup->save();
                        } else {
                            $approxStartTime = date('h:i a', $schedule->start_time);
                            $approxEndTime = date('h:i a', $schedule->end_time);

                            $shifts_g->shift_belongs_to = $schedule->shift_belongs_to;
                            $shifts_g->hospital_id = $schedule->hospital_id;
                            $shifts_g->attender_id = $schedule->attender_id;
                            $shifts_g->address_id = $schedule->address_id;
                            $shifts_g->booking_closed = 1;
                            $shifts_g->shift_label = 'Shift ' . $approxStartTime . ' - ' . $approxEndTime;
                            $shifts_g->save();
                        }
                    }
                }
                //echo $key;
            }
            $shifts = UserScheduleGroup::find()->where(['date' => $reportPeriod, 'user_id' => $doctor_id])->orderBy('schedule_id asc')->all();

            if (!empty($shifts)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function appointmentHistoryPeriod($doctor_id, $hospitalId, $dateFrom, $dateTo, $shiftid) {
        $getShiftSlots = array();
        $bookings = array();
        $total_history = $type = array();
        if ($hospitalId > 0) {
            $lists = UserAppointment::find()->where(['doctor_id' => $doctor_id, 'doctor_address_id' => $hospitalId])->andWhere(['between', 'date', '' . $dateFrom . '', '' . $dateTo . ''])->andWhere('`deleted_at` IS NULL')->orderBy('date asc')->all();
        } else {
            $lists = UserAppointment::find()->where(['doctor_id' => $doctor_id])->andWhere(['between', 'date', '' . $dateFrom . '', '' . $dateTo . ''])->andWhere('`deleted_at` IS NULL')->orderBy('date asc')->all();
        }
        $total_history['total_patient'] = 0;
        $completed = 0;
        $cancelled = 0;
        $notapp = 0;
        $online = 0;
        $offline = 0;
        if (!empty($lists)) {
            foreach ($lists as $list) {

                $appointment = UserAppointment::findOne($list->id);
                $bookings[] = DrsPanel::patientgetappointmentarray($appointment);

                if ($list->booking_type == UserAppointment::BOOKING_TYPE_ONLINE) {
                    $online += 1;
                } else {
                    $offline += 1;
                }

                if ($list->status == UserAppointment::STATUS_COMPLETED) {
                    $completed += 1;
                } elseif ($list->status == UserAppointment::STATUS_CANCELLED) {
                    $cancelled += 1;
                } else {
                    $notapp += 1;
                }
            }
        }

        $total_history['total_appointed'] = $completed;
        $total_history['total_offline'] = $offline;
        $total_history['total_online'] = $online;
        $total_history['total_cancelled'] = $cancelled;
        $total_history['total_not_appointed'] = $notapp;

//        if ($typewise == '') {
//            $booking_type = array(UserAppointment::BOOKING_TYPE_ONLINE, UserAppointment::BOOKING_TYPE_OFFLINE);
//            $typeselected = '';
//        } else {
//            $booking_type = $typewise;
//            $typeselected = $booking_type;
//        }

        $type['online'] = $online;
        $type['offline'] = $offline;
        return array('total_history' => $total_history, 'type' => $type, 'bookings' => $bookings);
    }

    public static function deleteAppointment($params) {
        if (isset($params['dateFrom']) && $params['dateFrom'] != '' && isset($params['dateTo']) && $params['dateTo'] != '') {
            $dateFrom = date('Y-m-d', strtotime($params['dateFrom']));
            $dateTo = date('Y-m-d', strtotime($params['dateTo']));
            $user_id = $params['user_id'];

            $deleted = UserAppointment::find()->where(['doctor_id' => $user_id])->andWhere(['between', 'date', '' . $dateFrom . '', '' . $dateTo . ''])->all();
            foreach ($deleted as $deletdata) {
                $deletModel = UserAppointment::findOne($deletdata->id);
                $deletModel->deleted_at = time();
                $deletModel->save();
            }
            $dataArr = array('status' => 'success');
        } else {
            $dataArr = array('status' => 'error');
        }
        return $dataArr;
    }

}
