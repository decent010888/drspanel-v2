<?php

namespace frontend\controllers;

use common\components\Payment;
use common\models\Areas;
use common\models\Cities;
use common\models\MetaValues;
use common\models\PatientMembers;
use common\models\UserAddress;
use common\models\UserAddressImages;
use common\models\UserAppointment;
use common\models\UserSchedule;
use common\models\UserFavorites;
use common\models\UserScheduleDay;
use common\models\UserScheduleSlots;
use frontend\models\AppointmentForm;
use Yii;
use common\models\User;
use common\models\UserProfile;
use yii\db\Expression;
use yii\helpers\Url;
use yii\web\Controller;
use yii\db\Query;
use frontend\modules\user\models\LoginForm;
use yii\data\Pagination;
use common\models\Groups;
use common\components\DrsPanel;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Search controller
 */
class SearchController extends Controller {

    /**
     * @inheritdoc
     */
    public function actions() {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction'
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null
            ],
            'set-locale' => [
                'class' => 'common\actions\SetLocaleAction',
                'locales' => array_keys(Yii::$app->params['availableLocales'])
            ]
        ];
    }

    public function actionIndex() {

        DrsPanel::checkBlockedSlots();

        $string = Yii::$app->request->queryParams;
        $baseurl = Yii::$app->getUrlManager()->getBaseUrl();
        $type = '';
        $recordlimit = 50;
        $count_result = 0;
        $count_result1 = 0;
        $getlastPagination = $getlastPagination1 = 0;
        $cityslug = 0;
        if (isset($string['results_type']) && !empty($string['results_type'])) {
            $type = strtolower($string['results_type']);
        }

        if (isset($string['page']) && !empty($string['page'])) {
            $pagination = $string['page'];
            $page = $string['page'] - 1;
            $offset = $recordlimit * $page;
        } else {
            $pagination = 1;
            $offset = 0;
        }
        $lists = $lists2 = array();
        $user = array();
        $v1 = '';
        $city = '';
        $q = '';

        if (isset($string['city']) && !empty($string['city'])) {
            $city = Appelavocat::slugifyCity($string['city']);
            if (isset($_COOKIE['location_filter'])) {
                $cookie = $_COOKIE['location_filter'];
                //$cookie = stripslashes($cookie);
                $location = json_decode($cookie, true);
                $locationname = Appelavocat::slugifyCity($location['name']);
                if ($locationname == $city) {
                    $user = Appelavocat::getLocationUserList($location['lat'], $location['lng']);
                } else {
                    $fetchlatlong = Appelavocat::get_lat_long($city);
                    if (!empty($fetchlatlong)) {
                        $user = Appelavocat::getLocationUserList($fetchlatlong['lat'], $fetchlatlong['lng']);
                        $setlatlong = $this->setLocationCookieFilter($fetchlatlong);
                    }
                }
            } else {
                $fetchlatlong = Appelavocat::get_lat_long($city);
                if (!empty($fetchlatlong)) {
                    $user = Appelavocat::getLocationUserList($fetchlatlong['lat'], $fetchlatlong['lng']);
                    $setlatlong = $this->setLocationCookieFilter($fetchlatlong);
                }
            }
            $cityslug = 1;
        }

        if ($type == Groups::GROUP_DOCTOR_LABEL || $type == Groups::GROUP_HOSPITAL_LABEL) {
            $typeSlug = $type;
            if (isset($string['q']) && !empty($string['q'])) {
                if ($city != '') {
                    return $this->redirect($baseurl . '/' . $typeSlug . '/' . $city . '?q=' . $string['q']);
                } else {
                    return $this->redirect($baseurl . '/' . $typeSlug . '?q=' . $string['q']);
                }
            } else {
                if ($city != '') {
                    return $this->redirect($baseurl . '/' . $typeSlug . '/' . $city);
                } else {
                    return $this->redirect($baseurl . '/' . $typeSlug);
                }
            }
        } elseif ($type == Groups::GROUP_SPECIALIZATION_LABEL) {
            $q = $string['q'];
            return $this->redirect($baseurl . '/' . $type . '/' . $string['q']);
        } elseif ($type == Groups::GROUP_TREATMENT_LABEL) {
            $type = 'treatment';
            $q = $string['q'];
            return $this->redirect($baseurl . '/' . $type . '/' . $string['q']);
        } else {
            $lists = new Query();
            $lists = UserProfile::find();
            $lists->joinWith('user');
            $lists->where(['user_profile.groupid' => array(Groups::GROUP_DOCTOR, Groups::GROUP_HOSPITAL)]);
            if ((isset($string['city']) && !empty($string['city']))) {
                $lists->andWhere(['user.id' => $user]);
            }

            $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
                'user.admin_status' => User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]);

            $countQuery = clone $lists;
            $totalpages = new Pagination(['totalCount' => $countQuery->count()]);

            $lists->limit($recordlimit);
            $lists->offset($offset);
            if (!empty($user)) {
                $lists->orderBy([new \yii\db\Expression('FIELD (user.id, ' . implode(',', (array_values($user))) . ')')]);
            }
            $command = $lists->createCommand();
            $lists = $command->queryAll();
        }

        if (!\Yii::$app->user->isGuest) {
            $userid = Yii::$app->user->identity->id;
        } else {
            $userid = 0;
        }


        if (isset($totalpages)) {
            $count_result = $totalpages->totalCount;
        }
        $getlastPagination = DrsPanel::getlastPagination($count_result, $recordlimit);
        return $this->render('search', ['lists' => $lists, 'userid' => $userid, 'city' => $city, 'result_type' => $type, 'query_slug' => $q, 'count_result' => $count_result, 'page' => $pagination, 'offset' => $offset, 'recordlimit' => $recordlimit, 'getlastPagination' => $getlastPagination, 'string' => $string, 'lists2' => $lists2, 'getlastPagination1' => $getlastPagination1, 'cityslug' => $cityslug, 'selected_filter' => '']);
    }

    /* Doctor Profile or all doctors list search results */

    public function actionDoctor($slug = '') {
        $loginID = isset(Yii::$app->user->identity->id) ? Yii::$app->user->identity->id : '';
        $profile = array();
        if (!empty($slug)) {
            $profile = UserProfile::findOne(['slug' => $slug]);
        }
        if (!empty($profile)) {
            $groupid = $profile->groupid;
            $user = User::find()->where(['id' => $profile->user_id, 'admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]])->one();
            if (!empty($user)) {
                $date = date('Y-m-d');
                DrsPanel::getBookingShifts($profile->user_id, $date, $profile->user_id);
                return $this->render('details', [
                            'profile' => $profile, 'user' => $user, 'groupid' => $groupid,
                            'loginid' => $loginID]);
            } else {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        } else {
            $string = Yii::$app->request->queryParams;
            $groupid = Groups::GROUP_DOCTOR;
            $lists = $this->getSearchResults('doctor', $slug, $groupid, $string);

            $out = array('result' => 'success', 'fullpath' => 0, 'filter' => 'Doctor', 'slug' => '', 'path' => '/search?results_type=doctor&q=');
            $this->setSearchCookie($out);

            if (!\Yii::$app->user->isGuest) {
                $userid = Yii::$app->user->identity->id;
            } else {
                $userid = 0;
            }
            $loginform = new LoginForm();
            if (Yii::$app->request->post()) {
                $post = Yii::$app->request->post();
                $loginP = $this->searchResultLogin($loginform, $post);
                if ($loginP['type'] == 'success') {
                    $userid = $loginP['userid'];
                    $groupid = $loginP['groupid'];
                    return $this->refresh();
                } elseif ($loginP['type'] == 'redirect') {
                    return $this->redirect([$loginP['group'] . '/dashboard']);
                } else {
                    return $this->redirect(['/login']);
                }
            }

            $totalpages = $lists['totalpages'];
            if (isset($totalpages)) {
                $count_result = $totalpages->totalCount;
            } else {
                $count_result = 0;
            }

            $getlastPagination = DrsPanel::getlastPagination($count_result, $lists['recordlimit']);

            $model = new UserProfile();
            $specialities = MetaValues::find()->orderBy('id asc')
                            ->where(['key' => 5])->all();

            $treatments = MetaValues::find()->orderBy('id asc')
                            ->where(['key' => 9])->all();
            return $this->render('search', ['lists' => $lists['lists'], 'loginform' => $loginform, 'userid' => $userid, 'city' => $lists['city'], 'result_type' => $lists['type'], 'query_slug' => $lists['q'], 'count_result' => $count_result, 'page' => $lists['pagination'], 'offset' => $lists['offset'], 'recordlimit' => $lists['recordlimit'], 'string' => $string, 'cityslug' => 1, 'getlastPagination' => $getlastPagination, 'model' => $model, 'specialities' => $specialities, 'treatments' => $treatments, 'selected_filter' => '']);
        }
    }

    /* Hospital Profile or all doctors list search results */

    public function actionHospital($slug = '') {
        $loginID = isset(Yii::$app->user->identity->id) ? Yii::$app->user->identity->id : '';
        $profile = array();
        if (!empty($slug)) {
            $profile = UserProfile::findOne(['slug' => $slug]);
        }
        if (!empty($profile)) {
            $string = Yii::$app->request->queryParams;
            $groupid = $profile->groupid;
            $user = User::find()->where(['id' => $profile->user_id, 'admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]])->one();
            if (!empty($user)) {

                $hospital_id = $profile->user_id;
                $getspecialities = DrsPanel::getMyHospitalSpeciality($hospital_id);
                $userAddress = UserAddress::findOne(['user_id' => $hospital_id]);
                if (!empty($userAddress)) {
                    $addressImages = UserAddressImages::find()->where(['address_id' => $userAddress->id])->all();
                } else {
                    $addressImages = array();
                }

                if (isset($string['speciality']) && !empty($string['speciality'])) {
                    $selected_speciality = $string['speciality'];
                } else {
                    $selected_speciality = 0;
                }

                return $this->render('details', [
                            'profile' => $profile, 'user' => $user, 'groupid' => $groupid, 'getspecialities' => $getspecialities, 'selected_speciality' => $selected_speciality, 'loginID' => $loginID, 'addressImages' => $addressImages
                ]);
            } else {
                throw new NotFoundHttpException('The requested page does not exist.');
            }
        } else {
            $string = Yii::$app->request->queryParams;
            $groupid = Groups::GROUP_HOSPITAL;
            $lists = $this->getSearchResults('hospital', $slug, $groupid, $string);

            $out = array('result' => 'success', 'fullpath' => 0, 'filter' => 'Lawyer', 'slug' => '', 'path' => '/search?results_type=hospital&q=');
            $this->setSearchCookie($out);

            if (!\Yii::$app->user->isGuest) {
                $userid = Yii::$app->user->identity->id;
            } else {
                $userid = 0;
            }
            $loginform = new LoginForm();
            if (Yii::$app->request->post()) {
                $post = Yii::$app->request->post();
                $loginP = $this->searchResultLogin($loginform, $post);
                if ($loginP['type'] == 'success') {
                    $userid = $loginP['userid'];
                    $groupid = $loginP['groupid'];
                    return $this->refresh();
                } elseif ($loginP['type'] == 'redirect') {
                    return $this->redirect([$loginP['group'] . '/dashboard']);
                } else {
                    return $this->redirect(['/login']);
                }
            }

            $totalpages = $lists['totalpages'];
            if (isset($totalpages)) {
                $count_result = $totalpages->totalCount;
            } else {
                $count_result = 0;
            }
            $getlastPagination = DrsPanel::getlastPagination($count_result, $lists['recordlimit']);


            return $this->render('search', ['lists' => $lists['lists'], 'loginform' => $loginform, 'userid' => $userid, 'city' => $lists['city'], 'result_type' => $lists['type'], 'query_slug' => $lists['q'], 'count_result' => $count_result, 'page' => $lists['pagination'], 'getlastPagination' => $getlastPagination, 'offset' => $lists['offset'], 'recordlimit' => $lists['recordlimit'], 'string' => $string, 'cityslug' => 1]);
        }
    }

    public function actionSpecialization($slug = '') {
        if (!empty($slug)) {
            $string = Yii::$app->request->queryParams;
            // pr($string);die;
            $string['speciality'][0] = $slug;
            if (isset($string['type']) && $string['type'] == 'hospital') {
                $typestring = 'hospital';
                $groupid = Groups::GROUP_HOSPITAL;
            } else {
                $typestring = 'doctor';
                $groupid = Groups::GROUP_DOCTOR;
            }
            $lists = $this->getSearchResults($typestring, $slug, $groupid, $string);


            $out = array('result' => 'success', 'fullpath' => 0, 'filter' => 'Specialization', 'slug' => '', 'path' => '/search?results_type=specialization&q=');
            $this->setSearchCookie($out);

            if (!\Yii::$app->user->isGuest) {
                $userid = Yii::$app->user->identity->id;
            } else {
                $userid = 0;
            }
            $loginform = new LoginForm();
            if (Yii::$app->request->post()) {
                $post = Yii::$app->request->post();
                $loginP = $this->searchResultLogin($loginform, $post);
                if ($loginP['type'] == 'success') {
                    $userid = $loginP['userid'];
                    $groupid = $loginP['groupid'];
                    return $this->refresh();
                } elseif ($loginP['type'] == 'redirect') {
                    return $this->redirect([$loginP['group'] . '/dashboard']);
                } else {
                    return $this->redirect(['/login']);
                }
            }

            $totalpages = $lists['totalpages'];
            if (isset($totalpages)) {
                $count_result = $totalpages->totalCount;
            } else {
                $count_result = 0;
            }
            $getlastPagination = DrsPanel::getlastPagination($count_result, $lists['recordlimit']);

            return $this->render('search', ['lists' => $lists['lists'], 'loginform' => $loginform, 'userid' => $userid, 'city' => $lists['city'], 'result_type' => $lists['type'], 'query_slug' => $lists['q'], 'count_result' => $count_result, 'page' => $lists['pagination'], 'offset' => $lists['offset'], 'recordlimit' => $lists['recordlimit'], 'string' => $string, 'cityslug' => 1, 'getlastPagination' => $getlastPagination]);
        } else {
            $string = Yii::$app->request->queryParams;
            $type = '';
            if (isset($string['type']) && $string['type'] != '') {
                $type = $string['type'];
                if ($type == 'doctor') {
                    $groupid = Groups::GROUP_DOCTOR;
                } elseif ($type == 'hospital') {
                    $groupid = Groups::GROUP_HOSPITAL;
                } else {
                    $type = 'doctor';
                    $groupid = Groups::GROUP_DOCTOR;
                }
            } else {
                $type = 'doctor';
                $groupid = Groups::GROUP_DOCTOR;
            }

            $lists = $this->getSearchResults($type, $slug = '', $groupid, $string, $limit = false);

            /* $lists= new Query();
              $lists=UserProfile::find();
              $lists->joinWith('user');
              $lists->where(['user_profile.groupid'=>$groupid]);
              $lists->andWhere(['user.status'=>User::STATUS_ACTIVE,
              'user.admin_status'=>[User::STATUS_ADMIN_LIVE_APPROVED,User::STATUS_ADMIN_APPROVED]]);
              $lists->andWhere(['user_profile.shifts'=>1]);
              $command = $lists->createCommand();
              $countQuery = clone $lists;
              $countTotal=$countQuery->count(); */
            if ($groupid == Groups::GROUP_HOSPITAL) {
                $fetchCount = DrsPanel::fetchHospitalSpecialityCount($lists['lists']);
            } else {
                $fetchCount = Drspanel::fetchSpecialityCount($lists['lists']);
            }

            $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);


            return $this->render('specialization', ['lists' => $s_list, 'type' => $type]);
        }
    }

    public function actionTreatment($slug = '') {
        if (!empty($slug)) {
            $string = Yii::$app->request->queryParams;
            // pr($string);die;
            $string['treatment'][0] = $slug;
            if (isset($string['type']) && $string['type'] == 'hospital') {
                $typestring = 'hospital';
                $groupid = Groups::GROUP_HOSPITAL;
            } else {
                $typestring = 'doctor';
                $groupid = Groups::GROUP_DOCTOR;
            }
            $lists = $this->getSearchResults($typestring, $slug, $groupid, $string);


            $out = array('result' => 'success', 'fullpath' => 0, 'filter' => 'Specialization', 'slug' => '', 'path' => '/search?results_type=specialization&q=');
            $this->setSearchCookie($out);

            if (!\Yii::$app->user->isGuest) {
                $userid = Yii::$app->user->identity->id;
            } else {
                $userid = 0;
            }
            $loginform = new LoginForm();
            if (Yii::$app->request->post()) {
                $post = Yii::$app->request->post();
                $loginP = $this->searchResultLogin($loginform, $post);
                if ($loginP['type'] == 'success') {
                    $userid = $loginP['userid'];
                    $groupid = $loginP['groupid'];
                    return $this->refresh();
                } elseif ($loginP['type'] == 'redirect') {
                    return $this->redirect([$loginP['group'] . '/profile']);
                } else {
                    //return $this->redirect(['/login']);
                }
            }

            $totalpages = $lists['totalpages'];
            if (isset($totalpages)) {
                $count_result = $totalpages->totalCount;
            } else {
                $count_result = 0;
            }
            $getlastPagination = DrsPanel::getlastPagination($count_result, $lists['recordlimit']);

            return $this->render('search', ['lists' => $lists['lists'], 'loginform' => $loginform, 'userid' => $userid, 'city' => $lists['city'], 'result_type' => $lists['type'], 'query_slug' => $lists['q'], 'count_result' => $count_result, 'page' => $lists['pagination'], 'offset' => $lists['offset'], 'recordlimit' => $lists['recordlimit'], 'string' => $string, 'cityslug' => 1, 'getlastPagination' => $getlastPagination]);
        } else {
            $string = Yii::$app->request->queryParams;
            $type = '';
            if (isset($string['type']) && $string['type'] != '') {
                $type = $string['type'];
                if ($type == 'doctor') {
                    $groupid = Groups::GROUP_DOCTOR;
                } elseif ($type == 'hospital') {
                    $groupid = Groups::GROUP_HOSPITAL;
                } else {
                    $type = 'doctor';
                    $groupid = Groups::GROUP_DOCTOR;
                }
            } else {
                $type = 'doctor';
                $groupid = Groups::GROUP_DOCTOR;
            }
            $lists = $this->getSearchResults($type, $slug = '', $groupid, $string);
            if ($groupid == Groups::GROUP_HOSPITAL) {
                $fetchCount = DrsPanel::fetchHospitalSpecialityCount($lists['lists']);
            } else {
                $fetchCount = Drspanel::fetchSpecialityCount($lists['lists']);
            }
            $s_list = DrsPanel::getSpecialityWithCount('speciality', $fetchCount);
            return $this->render('specialization', ['lists' => $s_list, 'type' => $type]);
        }
    }

    public function actionFavorite() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $data['user_id'] = $post['user_id'];
            $data['profile_id'] = $post['profile_id'];
            if ($post['status'] == 0) {
                $data['status'] = UserFavorites::STATUS_FAVORITE;
            } else {
                $data['status'] = UserFavorites::STATUS_UNFAVORITE;
            }
            $status = DrsPanel::userFavoriteUpsert($data);
            echo $this->renderAjax('details/_favorite_status', ['status' => $status]);
            exit;
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
                    if ($result['speciality'] == NULL || $result['speciality'] == '' || empty($result['speciality'])) {
                        $result['speciality'] = '';
                    }
                    $avator = DrsPanel::getUserThumbAvator($result['id']);
                    $data[] = array('id' => $result['id'], 'category_check' => $result['category'],
                        'category' => Yii::t('db', $result['category']),
                        'query' => $result['query'], 'label' => $result['label'],
                        'avator' => $avator,
                        'speciality' => $result['speciality']);
                }

                /* Category List search */
                $categories = DrsPanel::getSpecialitySearchListArray($words);
                if (!empty($categories)) {
                    foreach ($categories as $cat) {
                        $data[] = array('id' => '', 'category_check' => 'Specialization', 'category' => Yii::t('db', 'Specialization'), 'query' => $cat['query'], 'label' => Yii::t('db', $cat['label']), 'filters' => 'Specialization', 'avator' => '', 'speciality' => '');
                    }
                }

                /* Treatment List search */
                $treatments = DrsPanel::getTreatmentSearchListArray($words);
                if (!empty($treatments)) {
                    foreach ($treatments as $treatment) {
                        $data[] = array('id' => '', 'category_check' => 'Treatments', 'category' => Yii::t('db', 'Treatments'), 'query' => $treatment['query'], 'label' => Yii::t('db', $treatment['label']), 'filters' => 'Treatments', 'avator' => '', 'speciality' => '');
                    }
                }

                $data[] = array('id' => '', 'category_check' => 'Search', 'category' => Yii::t('db', 'Search'), 'query' => $q, 'label' => Yii::t('db', 'Doctor') . ' ' . Yii::t('db', 'named') . ' ' . $q, 'filters' => 'Doctor', 'avator' => '', 'speciality' => '');

                $data[] = array('id' => '', 'category_check' => 'Search', 'category' => Yii::t('db', 'Search'), 'query' => $q, 'label' => Yii::t('db', 'Hospital') . ' ' . Yii::t('db', 'named') . ' ' . $q, 'filters' => 'Hospital', 'avator' => '', 'speciality' => '');
                $out = array_values($data);
            } else {
                $data = DrsPanel::getTypeDefaultListArray();
                foreach ($data as $group) {
                    $out[] = array('id' => '', 'category_check' => 'Groups', 'category' => 'Groups', 'query' => '', 'label' => Yii::t('db', $group['name']), 'filters' => $group['name'], 'avator' => '', 'speciality' => '');
                }
            }
            return $out;
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
                                $path = '/search?results_type=' . strtolower($post['filter']) . '&q=';
                                $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $post['filter'], 'slug' => $post['slug'], 'path' => $path);
                            } else {
                                $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $post['filter'], 'path' => '/search?results_type=' . $post['filter']);
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
                    $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $restype, 'slug' => $post['slug'], 'path' => '/search?results_type=' . $restype . '&q=');
                } else {
                    $out = array('result' => 'success', 'fullpath' => 0, 'filter' => $restype, 'path' => '/search?results_type=' . $restype);
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

    public function getSearchResults($type, $slug, $groupid, $string, $limitcheck = true) {
        $recordlimit = 20;
        $user = array();
        $lists = array();
        $userLocation = array();
        $v1 = '';
        $city = '';
        $q = '';
        $specialization = '';
        $treatment = '';

        if (isset($string['page']) && !empty($string['page'])) {
            $pagination = $string['page'];
            $page = $string['page'] - 1;
            $offset = $recordlimit * $page;
        } else {
            $pagination = 1;
            $offset = 0;
        }

        if (isset($string['q'])) {
            $q = strtolower($string['q']);
        }
        if ($q != '') {
            $q_explode = explode(' ', $q);
            $usersearch = array();
            foreach ($q_explode as $word) {
                $usersearch[] = "user_profile.name LIKE '%" . $word . "%'";
            }
            $v1 = implode(' or ', $usersearch);
        }
        $lists = new Query();
        $lists = UserProfile::find();
        $lists->joinWith('user');
        $lists->where(['user_profile.groupid' => $groupid]);
        $lists->andWhere(['user.status' => User::STATUS_ACTIVE,
            'user.admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]]);
        //$lists->andWhere(['user_profile.shifts' => 1]);
        if ($v1 != '') {
            $lists->andFilterWhere(['or', $v1]);
        }

        if (isset($_COOKIE['location_filter'])) {
            $cookie = $_COOKIE['location_filter'];
            $location = json_decode($cookie, true);
            $data['city'] = $location['city'];
            $latitude = $location['lat'];
            $longitude = $location['lng'];
            $userLocation = DrsPanel::getLocationUserList($latitude, $longitude);
        }

        if ($groupid == Groups::GROUP_HOSPITAL) {
            $addSpeciality = DrsPanel::addHospitalSpecialityCount($lists->createCommand()->queryAll());
            $lists->joinWith('hospitalSpecialityTreatment');

            $listcat = array();
            $listareas = array();
            if (isset($string['speciality'])) {
                $listcat = $string['speciality'];
            }
            if (isset($string['areas'])) {
                $listareas = $string['areas'];
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
                    $query_array[] = sprintf('FIND_IN_SET("%s",`hospital_speciality_treatment`.`speciality`)', $needle);
                }
                $query_str = implode(' OR ', $query_array);
                $lists->andWhere(new \yii\db\Expression($query_str));
            }
            if (!empty($listareas)) {
                $getAreaUser = DrsPanel::getAreaUser($listareas);
                $userLocation = array_intersect($userLocation, $getAreaUser);
                $lists->andWhere(['user.id' => $userLocation]);
            } else {
                $lists->andWhere((['user.id' => $userLocation]));
            }
            
        } else {
            $listcat = $listtreatment = array();
            $listareas = array();
            $gender = '';
            $availability = '';
            if (isset($string['speciality'])) {
                $listcat = $string['speciality'];
            }
            if (isset($string['treatment'])) {
                $listtreatment = $string['treatment'];
            }
            if (isset($string['areas'])) {
                $listareas = $string['areas'];
            }
            if (isset($string['gender'])) {
                $gender = $string['gender'][0];
            }
            if (isset($string['availability'])) {
                $availability = $string['availability'][0];
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
                $valuecat = [];
                foreach ($listtreatment as $treatval) {
                    $metavalues = MetaValues::find()->where(['slug' => $treatval])->one();
                    if ($metavalues)
                        $valuecat[] = $metavalues->value;
                }

                $query_array = array();
                foreach ($valuecat as $needle) {
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
                $lists->andWhere(['user.id' => $userLocation]);
            } else {
                $lists->andWhere((['user.id' => $userLocation]));
            }
        }

        $countQuery = clone $lists;
        $totalpages = new Pagination(['totalCount' => $countQuery->count()]);

        if ($limitcheck == true) {
            $lists->limit($recordlimit);
            $lists->offset($offset);
        }


        if (isset($string['sort']) && !empty($string['sort'])) {
            $sort = $string['sort'][0];
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
        $command = $lists->createCommand();



        $lists = $command->queryAll();

        

        return array('lists' => $lists, 'city' => $city, 'type' => $type, 'q' => $q, 'pagination' => $pagination,
            'offset' => $offset, 'recordlimit' => $recordlimit, 'totalpages' => $totalpages);
    }

    public function searchResultLogin($loginform, $post) {
        $loginform->load($post);
        if ($loginform->login()) {
            $userid = Yii::$app->user->identity->id;
            $groupid = Yii::$app->user->identity->userProfile->groupid;
            if ($groupid == Groups::GROUP_LAWYER) {
                return array('type' => 'redirect', 'group' => 'lawyer');
            } elseif ($groupid == Groups::GROUP_NOTARY) {
                return array('type' => 'redirect', 'group' => 'notary');
            } elseif ($groupid == Groups::GROUP_FIRM) {
                return array('type' => 'redirect', 'group' => 'firm');
            } elseif ($groupid == Groups::GROUP_NOTARY_FIRM) {
                return array('type' => 'redirect', 'group' => 'firm');
            } else {
                return array('type' => 'success', 'userid' => $userid, 'groupid' => $groupid);
            }
        } else {
            return array('type' => 'redirect_login');
        }
    }

    public function actionGetLocationList() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = array();
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $searchTerm = trim($post['term']);
            $key = Yii::$app->params['googleApiKey'];
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $searchTerm;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            curl_close($ch);
            $array = json_decode($response, true);
            $array = $array['results'];
            echo "<pre>";
            print_r($array);
            exit;
        }
    }

    public function actionGetSearchurl() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $field_search = $post['field_search'];
            $location_search = $post['location_search'];
            $out = array();

            $baseurl = $_SERVER['HTTP_HOST'];

            $cookielat = '';
            $cookielng = '';
            if ($location_search != '') {
                if (isset($_COOKIE['location_filter'])) {
                    $cookie = $_COOKIE['location_filter'];
                    //  $cookie = stripslashes($cookie);
                    $location = json_decode($cookie, true);
                    $cookiename = $location['name'];
                    $cookieadd = $location['address'];
                    if ($cookiename == $location_search || $cookieadd == $location_search) {
                        $cookielat = $location['lat'];
                        $cookielng = $location['lng'];
                    } else {
                        setcookie("location_filter", "", time() - 3600, '/', $baseurl, false);
                    }
                }
            }
            if ($field_search != '') {
                if (isset($_COOKIE['search_filter'])) {
                    $cookie = $_COOKIE['search_filter'];
                    //$cookie = stripslashes($cookie);
                    $search = json_decode($cookie, true);

                    if ($search['filter'] == 'Category') {
                        $path = $search['path'] . $search['slug'];
                        if ($cookielat != '' && $cookielng != '') {
                            $cookiename = Appelavocat::slugifyCity($cookiename);
                            $path = $path . '/' . $cookiename;
                        } else {
                            setcookie("location_filter", "", time() - 3600, '/', $baseurl, false);
                        }
                    } elseif ($search['filter'] == 'SubCategory') {
                        $path = $search['path'] . $search['slug'];
                        if ($cookielat != '' && $cookielng != '') {
                            $cookiename = Appelavocat::slugifyCity($cookiename);
                            $path = $path . '/' . $cookiename;
                        } else {
                            setcookie("location_filter", "", time() - 3600, '/', $baseurl, false);
                        }
                    } else {
                        $path = $search['path'] . $search['slug'];
                        if ($cookielat != '' && $cookielng != '') {
                            $cookiename = Appelavocat::slugifyCity($cookiename);
                            $path = $path . '&city=' . $cookiename;
                        }
                    }
                    $out = array('result' => 'success', 'fullpath' => 1, 'path' => $path);
                    return $out;
                    exit();
                }
            } else {
                setcookie("search_filter", "", time() - 3600, '/', $baseurl, false);
                $path = '/search';
                if ($cookielat != '' && $cookielng != '') {
                    $cookiename = Appelavocat::slugifyCity($cookiename);
                    $path = $path . '?city=' . $cookiename;
                }
                $out = array('result' => 'success', 'fullpath' => 1, 'path' => $path);
                return $out;
                exit();
            }
        }
        return array('result' => 'fail');
        exit();
    }

    public function actionDoctorAddressList() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $date = (isset($post['date'])) ? date('Y-m-d', strtotime($post['date'])) : date('Y-m-d');
            $slug = $post['slug'];
            $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::find()->where(['id' => $doctorProfile->user_id, 'admin_status' => [User::STATUS_ADMIN_LIVE_APPROVED, User::STATUS_ADMIN_APPROVED]])->one();
                if (!empty($doctor)) {
                    $d_id = $doctor->id;
                    $current_login = Yii::$app->user->id;
                    $getSlots = DrsPanel::getBookingShifts($d_id, $date, $current_login);
                    $appointments = DrsPanel::getBookingAddressShifts($d_id, $date, $current_login);

                    return $this->renderAjax('_doctor_address_list', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile]);
                } else {
                    throw new NotFoundHttpException('The requested page does not exist.');
                }
            }
        }
        return null;
    }

    public function actionAppointmentTime($slug) {
        if (!\Yii::$app->user->isGuest) {
            if (Yii::$app->request->post()) {
                $post = Yii::$app->request->post();
                // echo "<pre>"; print_r($post);die;
                $current_login = Yii::$app->user->identity->id;
                $doctorProfile = UserProfile::find()->where(['slug' => $slug])->one();
                if (!empty($doctorProfile)) {
                    $doctor = User::findOne($doctorProfile->user_id);
                    $d_id = $doctor->id;
                    $schedule_id = $post['schedule_id'];
                    $date = $post['nextdate'];
                    $getSlots = array();
                    $scheduleData = UserSchedule::findOne($schedule_id);
                    if (!empty($scheduleData)) {
                        $scheduleDay = UserScheduleDay::find()->where(['user_id' => $d_id, 'date' => $date, 'schedule_id' => $schedule_id])->one();
                        if (!empty($scheduleDay)) {
                            if ($scheduleDay->booking_closed == 0) {
                                $getSlots = DrsPanel::getBookingShiftSlots($d_id, $date, $schedule_id, array('available', 'booked', 'blocked'));
                            } else {
                                $getSlots = array();
                            }
                        }
                    }
                    return $this->render('_doctor_appointment_time', ['date' => $date, 'doctor' => $doctor, 'scheduleDay' => $scheduleDay, 'schedule' => $scheduleData, 'slots' => $getSlots, 'doctorProfile' => $doctorProfile]);
                } else {
                    throw new NotFoundHttpException('The requested page does not exist.');
                }
            } else {
                $this->redirect(array('search/doctor', 'slug' => $slug));
            }
        } else {
            Yii::$app->session->setFlash('error', "You are not logged in.");
            return $this->goHome();
        }
    }

    public function actionGetDateTokens() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $current_login = Yii::$app->user->identity->id;
            $d_id = $post['doctor_id'];
            $date = $post['nextdate'];
            $appointments = DrsPanel::getBookingAddressShifts($d_id, $date, $current_login, 1);
            $doctor = User::findOne($d_id);
            $doctorProfile = UserProfile::find()->where(['user_id' => $d_id])->one();
            return $this->renderAjax('_doctor_address_list', ['defaultCurrrentDay' => strtotime($date), 'appointments' => $appointments, 'doctor' => $doctor, 'doctorProfile' => $doctorProfile]);
        }
    }

    public function actionGetShiftBookingDays() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $doctorProfile = UserProfile::find()->where(['user_id' => $post['doctor_id']])->one();
            $date = $post['next_date'];

            $shiftData = Drspanel::getAddressShiftsDays($post);


            return $this->renderAjax('_doctor_appointment_time', ['getshiftaddressdays' => $shiftData, 'doctorProfile' => $doctorProfile]);
            exit;
        }
    }

    public function actionBookingConfirm() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $slot_id = explode('-', $post['slot_id']);
            $date = $post['date'];
            $doctorProfile = UserProfile::find()->where(['slug' => $post['slug']])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::findOne($doctorProfile->user_id);
                $slot = UserScheduleSlots::find()->andWhere(['user_id' => $doctor->id, 'id' => $slot_id[1]])->one();
                if ($slot) {
                    $schedule = UserSchedule::findOne($slot->schedule_id);
                    $model = new AppointmentForm();
                    $model->doctor_id = $doctor->id;
                    $model->slot_id = $slot->id;
                    $model->schedule_id = $slot->schedule_id;
                    return $this->renderAjax('booking-confirm', ['doctor' => $doctor,
                                'slot' => $slot,
                                'schedule' => $schedule,
                                'address' => UserAddress::findOne($schedule->address_id),
                                'model' => $model,
                                'user_type' => 'patient',
                    ]);
                }
            }
        }
        return NULL;
    }

    public function actionBookingConfirmStep2() {
        if (Yii::$app->request->isAjax && Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $slot_id = $post['slot_id'];
            $id = $post['doctor_id'];
            $doctorProfile = UserProfile::find()->where(['user_id' => $id])->one();
            if (!empty($doctorProfile)) {
                $doctor = User::findOne($doctorProfile->user_id);
                $slot = UserScheduleSlots::find()->andWhere(['user_id' => $doctor->id, 'id' => $slot_id])->one();
                if ($slot) {
                    $schedule = UserSchedule::findOne($slot->schedule_id);
                    $model = new AppointmentForm();
                    $model->doctor_id = $doctor->id;
                    $model->slot_id = $slot->id;
                    $model->schedule_id = $slot->schedule_id;
                    $model->user_name = ucfirst($post['name']);
                    $model->user_phone = $post['phone'];
                    $model->user_gender = $post['gender'];
                    return $this->renderAjax('_booking_confirm_step2.php', ['doctor' => $doctor, 'slot' => $slot, 'schedule' => $schedule, 'address' => UserAddress::findOne($schedule->address_id), 'model' => $model, 'userType' => 'patient'
                    ]);
                }
            }
        }
        return NULL;
    }

    public function actionAppointmentBooked() {
        $user_id = Yii::$app->user->id;
        $userDetail = UserProfile::find()->where(['user_id' => $user_id])->one();
        $response["status"] = 0;
        $response["error"] = true;
        $response['message'] = 'Does not match require parameters';
        if (Yii::$app->request->post() && Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
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
                    if ($slot->status == 'available') {
                        $check = DrsPanel::blockSlot($slot_id, Yii::$app->user->id);
                        if ($check == 'success') {

                            $data['doctor_id'] = $doctor->id;
                            $data['user_id'] = $user_id;
                            $data['slot_id'] = $slot_id;
                            $data['schedule_id'] = $schedule_id;
                            $data['date'] = $slot->date;

                            $data['name'] = ucfirst($post['AppointmentForm']['user_name']);
                            $data['age'] = '2002-03-13';
                            $data['mobile'] = $post['AppointmentForm']['user_phone'];
                            $data['address'] = isset($post['address']) ? $post['address'] : '';
                            $data['gender'] = $post['AppointmentForm']['user_gender'];

                            $data['booking_type'] = UserAppointment::BOOKING_TYPE_ONLINE;
                            $data['booking_id'] = DrsPanel::generateBookingID();
                            $data['type'] = $slot->type;
                            $data['token'] = $slot->token;

                            //$addAppointment=DrsPanel::addAppointment($data,'patient');
                            $response = DrsPanel::addTemporaryAppointment($data, 'patient', 'web');
                            if ($response['status'] == 1) {

                                $response["status"] = 1;
                                $response["error"] = false;
                                $response['type'] = 'success_pay';

                                $out['paramList'] = $response['paytmdata']['list'];
                                $out['txn_url'] = $response['paytmdata']['txn_url'];
                                $out['checkSum'] = $response['paytmdata']['checkSum'];
                                $baseurl = $_SERVER['HTTP_HOST'];
                                $json = json_encode($out, true);
                                setcookie('paytm_params', $json, time() + 60 * 60, '/', $baseurl, false);
                                return $this->redirect(['search/paytm-redirect']);
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

    public function actionPaytmRedirect() {
        if (isset($_COOKIE['paytm_params'])) {
            $cookie = $_COOKIE['paytm_params'];
            $paytm_params = json_decode($cookie, true);
            $this->layout = 'paytm_base';
            return $this->render('paytm_view_submit', [
                        'paramList' => $paytm_params['paramList'], 'url' => $paytm_params['txn_url'],
                        'checkSum' => $paytm_params['checkSum']
            ]);
        }
    }

    public function actionGetAppointmentDetail() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $appointment_id = $post['appointment_id'];
            $appointment = UserAppointment::find()->where(['id' => $appointment_id])->one();
            $booking = DrsPanel::patientgetappointmentarray($appointment);
            echo $this->renderAjax('_booking_detail', ['booking' => $booking, 'doctor_id' => $appointment->doctor_id, 'userType' => 'patient']);
            exit();
        }
    }

    public function actionGetNextDates() {
        if (Yii::$app->request->post()) {
            $post = Yii::$app->request->post();
            $user_id = $post['user_id'];
            $days_plus = $post['plus'];
            $operator = $post['operator'];
            $type = $post['type'];
            $first = date('Y-m-d', strtotime($operator . $days_plus . ' days', $post['key']));
            $dates_range = DrsPanel::getSliderDates($first, 4);
            $doctorProfile = UserProfile::find()->where(['user_id' => $user_id])->one();
            $doctor = User::findOne($doctorProfile->user_id);
            $schedule_id = $post['schedule_id'];
            $scheduleData = UserSchedule::findOne($schedule_id);
            $date = date('Y-m-d', $post['date_selected']);
            if (!empty($scheduleData)) {
                $scheduleDay = UserScheduleDay::find()->where(['user_id' => $user_id, 'date' => $date, 'schedule_id' => $schedule_id])->one();
            } else {
                $scheduleDay = array();
            }

            $result['status'] = true;
            $result['result'] = $this->renderAjax('_appointment_time_calender', ['dates_range' => $dates_range,
                'doctor_id' => $user_id, 'type' => 'appointment', 'userType' => 'patient', 'slug' => $doctor['userProfile']['slug'], 'schedule_id' => $scheduleData['id'], 'date_filter' => $post['date_selected'], 'schedule' => $scheduleData, 'scheduleDay' => $scheduleDay]);
            $result['date'] = $first;
            echo json_encode($result);
            exit;
        }
        echo 'error';
        exit;
    }

    public function actionSearchFilter() {
        $selected_filter = array();
        if (Yii::$app->request->post() && Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();

            $specialities = isset($post['UserProfile']['speciality']) ? $post['UserProfile']['speciality'] : '';

            $treatments = isset($post['UserProfile']['treatment']) ? $post['UserProfile']['treatment'] : '';

            $gender_list = isset($post['UserProfile']['gender']) ? $post['UserProfile']['gender'] : '';

            $rating = isset($post['UserProfile']['rating']) ? $post['UserProfile']['rating'] : '';

            $lists = new Query();
            $lists = UserProfile::find();
            $lists->joinWith('user');
            $lists->where(['user_profile.groupid' => Groups::GROUP_DOCTOR]);
            if (!empty($specialities)) {
                $lists->andWhere('find_in_set(:key2, `user_profile`.`speciality`)', [':key2' => $specialities]);
            }
            if (!empty($treatments)) {
                $t = 3;
                foreach ($treatments as $key => $treatment) {
                    if ($t == 3) {
                        $lists->andWhere('find_in_set(:key' . $t . ', `user_profile`.`treatment`)', [':key' . $t => $treatment]);
                    } else {
                        $lists->orWhere('find_in_set(:key' . $t . ', `user_profile`.`treatment`)', [':key' . $t => $treatment]);
                    }
                    $t++;
                }
            }
            if (!empty($gender_list)) {
                $lists->andWhere('find_in_set(:key4, `user_profile`.`gender`)', [':key4' => $gender_list]);
            }
            if ($rating != '') {
                $rating = explode('-', $rating);
                $lists->andFilterWhere(['between', 'rating', $rating[0], $rating[1]]);
            }
            $command = $lists->createCommand();
            $lists = $command->queryAll();
            return $this->render('search', ['lists' => $lists, 'selected_filter' => $post]);
        }
    }

    public function actionShareProfile() {
        if (Yii::$app->request->post() && Yii::$app->request->isAjax) {
            $post = Yii::$app->request->post();
            $doctor_id = $post['doctor_id'];
            $type = $post['type'];

            $doctorProfile = UserProfile::find()->where(['user_id' => $doctor_id])->one();

            $url = Url::to(['search/doctor', 'slug' => $doctorProfile->slug], true);

            if ($type == 'google') {
                $baseurl = 'https://plus.google.com/share';
            } elseif ($type == 'twitter') {
                $baseurl = 'https://twitter.com/share';
            } elseif ($type == 'facebook') {
                $baseurl = 'http://www.facebook.com/sharer.php';
            } elseif ($type == 'linkedin') {
                $baseurl = 'http://www.linkedin.com/shareArticle';
            } elseif ($type == 'pinterest') {
                $baseurl = 'http://pinterest.com/pin/create/button';
            } else {
                $result = array('status' => 'error');
                echo json_encode($result);
                exit;
            }
            $result = array('status' => 'success', 'url' => $url, 'baseurl' => $baseurl);
            echo json_encode($result);
            exit;
        }
    }

    public function actionSetLocationCookie() {
        $post = Yii::$app->request->post();
        $out = array();
        $out['city'] = $post['city'];
        $city_id = Drspanel::getCityId($post['city'], 'Rajasthan');
        $city = Cities::findOne($city_id);
        if (!empty($city)) {
            $out['address'] = $post['city'];
            $out['name'] = $post['city'];
            $out['lat'] = $city->lat;
            $out['lng'] = $city->lng;
        }
        $baseurl = $_SERVER['HTTP_HOST'];
        $json = json_encode($out, true);
        setcookie('location_filter', $json, time() + 60 * 60, '/', $baseurl, false);


        echo json_encode($out);
        exit();
    }

    public function actionSetLocationCookieNavigation() {
        $post = Yii::$app->request->post();
        $lat = $post['lat'];
        $lng = $post['lng'];
        $location = DrsPanel::setCurrentLocation($lat, $lng);
        echo json_encode($location);
        exit();
    }

    public function setTypeCookie($type) {
        $baseurl = $_SERVER['HTTP_HOST'];
        $codearray = $type;
        $json = json_encode($codearray, true);
        setcookie('booking_type', $json, time() + 60 * 60, '/', $baseurl, false);
        return $type;
    }

    public function setSearchCookie($codearray) {
        $baseurl = $_SERVER['HTTP_HOST'];
        $json = json_encode($codearray, true);
        setcookie('search_filter', $json, time() + 60 * 60, '/', $baseurl, false);
        return $codearray;
    }

    public function actionPaytmWalletCallback() {
        $params = Yii::$app->request->post();
        $request = Yii::$app->request->queryParams;

        Yii::info($params, __METHOD__);
        Yii::info($request, __METHOD__);

        $callback = Payment::paytm_wallet_callback($params, $request);
        if (!empty($callback) && isset($callback['STATUS'])) {
            if ($callback['STATUS'] != 'TXN_SUCCESS') {
                Yii::$app->session->setFlash('error', "'" . $callback["RESPMSG"] . "'");
                return $this->redirect(['/patient/appointments']);
            } else {
                //\common\components\Notifications::bookingNotification($notifyData);
                Yii::$app->session->setFlash('success', "'Appointment booked successfully!'");
                return $this->redirect(['/patient/appointment-details/' . $callback['appointment_id']]);
            }
        } else {
            Yii::$app->session->setFlash('error', 'Callback failed');
            return $this->redirect(['/patient/appointments']);
        }
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        if (!\Yii::$app->user->isGuest) {
            $groupid = Yii::$app->user->identity->userProfile->groupid;

            if ($groupid == Groups::GROUP_DOCTOR) {
                return $this->redirect(['doctor/dashboard']);
            } elseif ($groupid == Groups::GROUP_HOSPITAL) {
                return $this->redirect(['hospital/dashboard']);
            } elseif ($groupid == Groups::GROUP_ATTENDER) {
                return $this->redirect(['attender/dashboard']);
            } else {
                
            }
        }
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

}
