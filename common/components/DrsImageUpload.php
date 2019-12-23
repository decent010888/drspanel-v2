<?php

namespace common\components;

use common\models\Groups;
use common\models\PatientMemberRecords;
use Intervention\Image\ImageManagerStatic;
use Yii;
use yii\helpers\Url;
use yii\web\UploadedFile;
use common\models\PatientMemberFiles;
use common\models\UserAddressImages;
use common\models\UserAddress;
use common\models\UserProfile;

class DrsImageUpload {

    public static function updateProfileImageWeb($userType, $user_id, $upload) {
        if (!empty($upload)) {
            $userProfile = UserProfile::findOne(['user_id' => $user_id]);

            if (!is_dir("../../storage/web/source/" . $userType . "/"))
                mkdir("../../storage/web/source/" . $userType, 0775, true);

            $uploadDir = Yii::getAlias('@storage/web/source/' . $userType . '/');
            $waterMarkImgDir = Yii::getAlias('@frontend/web/images/watermark.png');
            $image_name = time() . rand() . '.' . $upload->extension;
            $userProfile->avatar = $image_name;
            $userProfile->avatar_path = '/storage/web/source/' . $userType . '/';
            $userProfile->avatar_base_url = Yii::getAlias('@frontendUrl');
            $upload->saveAs($uploadDir . $image_name);
            if ($userProfile->save()) {
                $geturl = DrsPanel::getUserAvator($user_id);
                $file_data = file_get_contents($geturl, false, stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]));
                $img = ImageManagerStatic::make($file_data)->insert($waterMarkImgDir, 'bottom-left', 10, 10);
                $img->save($uploadDir . $image_name);
                $img->fit(100, 100);
                $dir = $uploadDir . 'thumb/';
                if (!file_exists($dir)) {
                    mkdir($dir, 0777, true);
                }
                
                $img->save($uploadDir . 'thumb/' . $image_name);
            }
        }
        return true;
    }

    public static function updateProfileImageApp($user_id, $files) {
        $model = UserProfile::findOne(['user_id' => $user_id]);
        $avatar = $model->avatar_path;
        $response = $file_tmp = $file_name = array();
        $groupid = $model->groupid;
        $model->gender = ($model->gender) ? $model->gender : 0;
        if (isset($files['image']['tmp_name']) && isset($files['image']['name'])) {
            $file_tmp = $files['image']['tmp_name'];
            $file_name = $files['image']['name'];
            $dir = Url::to('@frontend');
            if ($groupid == Groups::GROUP_PATIENT) {
                $dirname = 'patients';
            } else if ($groupid == Groups::GROUP_DOCTOR) {
                $dirname = 'doctors';
            } else if ($groupid == Groups::GROUP_HOSPITAL) {
                $dirname = 'hospitals';
            } else {
                $dirname = 'attenders';
            }

            if (!is_dir("../../storage/web/source/" . $dirname . "/"))
                mkdir("../../storage/web/source/" . $dirname, 0775, true);

            $uploadDir = Yii::getAlias('@storage/web/source/' . $dirname . '/');
            $waterMarkImgDir = Yii::getAlias('@frontend/web/images/watermark.png');
            $model->avatar_path = '/storage/web/source/' . $dirname . '/';
            $model->avatar_base_url = Yii::getAlias('@frontendUrl');
            $extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $newimage = time() . '_' . $dirname . '.' . $extension;
            if (!move_uploaded_file($file_tmp, $uploadDir . $newimage)) {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Unable to upload image';
            }
            $model->avatar = $newimage;

            if ($model->save()) {
                $geturl = DrsPanel::getUserAvator($user_id);
                $file_data = file_get_contents($geturl, false, stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]));
                $img = ImageManagerStatic::make($file_data)->insert($waterMarkImgDir, 'bottom-left', 10, 10);
                $img->save($uploadDir . $newimage);
                $response["status"] = 1;
                $response["error"] = false;
                $response['data'] = DrsPanel::getUserAvator($user_id);
                $response['message'] = 'Profile image updated';
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response["data"] = $model->getErrors();
                $response['message'] = 'Profile image not updated';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Image Required';
        }

        return $response;
    }

    public static function updateAddressImageWeb($address_id, $upload) {
        $address = UserAddress::findOne(['id' => $address_id]);

        if (!is_dir("../../storage/web/source/hospitals/"))
            mkdir("../../storage/web/source/hospitals", 0775, true);

        if (!empty($upload)) {
            $uploadDir = Yii::getAlias('@storage/web/source/hospitals/');
            $image_name = time() . rand() . '.' . $upload->extension;
            $address->image = $image_name;
            $address->image_path = '/storage/web/source/hospitals/';
            $address->image_base_url = Yii::getAlias('@frontendUrl');
            $upload->saveAs($uploadDir . $image_name);
            $address->save();
        }
        return true;
    }

    public static function updateAddressImage($address_id, $files) {
        $address = UserAddress::findOne(['id' => $address_id]);
        $avatar = $address->image_path;
        $response = $file_tmp = $file_name = array();


        if (isset($files['image']['tmp_name']) && isset($files['image']['name'])) {

            if ($files['image']['error'] == 0) {
                $file_tmp = $files['image']['tmp_name'];
                $file_name = $files['image']['name'];
                $uploadDir = Yii::getAlias('@storage/web/source/user-address/');
                $waterMarkImgDir = Yii::getAlias('@frontend/web/images/watermark.png');
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $newimage = time() . rand() . '_add.' . $extension;

                if (!is_dir("../../storage/web/source/user-address/"))
                    mkdir("../../storage/web/source/user-address", 0775, true);

                if (!move_uploaded_file($file_tmp, $uploadDir . $newimage)) {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response['message'] = 'Unable to upload image';
                }
                $address->image_path = '/storage/web/source/user-address/';
                $address->image_base_url = Yii::getAlias('@frontendUrl');
                $address->image = $newimage;
                if ($address->save()) {
                    $addressImagesData = UserAddress::find()->where(['id' => $address_id])->one();
                    $geturl = $addressImagesData->image_base_url . $addressImagesData->image_path . $addressImagesData->image;
                    $file_data = file_get_contents($geturl, false, stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                        ],
                    ]));
                    $img = ImageManagerStatic::make($file_data)->insert($waterMarkImgDir, 'bottom-left', 10, 10);
                    $img->save($uploadDir . $addressImagesData->image);
                    $response["status"] = 1;
                    $response["error"] = false;
                    $response['data'] = DrsPanel::getAddressAvator($address_id);
                    $response['message'] = 'Address image updated';
                } else {
                    $response["status"] = 0;
                    $response["error"] = true;
                    $response["data"] = $address->getErrors();
                    $response['message'] = 'Address image not updated';
                }
            } else {
                $response["status"] = 0;
                $response["error"] = true;
                $response['message'] = 'Error file found';
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Image Required';
        }

        return $response;
    }

    public static function updateAddressImageListWeb($address_id, $uploads) {

        $address = UserAddress::findOne(['id' => $address_id]);
        if (!empty($uploads)) {
            if (!is_dir("../../storage/web/source/user-address/"))
                mkdir("../../storage/web/source/user-address", 0775, true);

            $uploadDir = Yii::getAlias('@storage/web/source/user-address/');
            $waterMarkImgDir = Yii::getAlias('@frontend/web/images/watermark.png');
            foreach ($uploads as $key => $file) {
                $addressImages = UserAddressImages::find()->where(['address_id' => $address_id])->all();
                if (count($addressImages) < 8) {
                    $file_name = $file->name;
                    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $filename = pathinfo($file_name, PATHINFO_FILENAME);
                    $image_name = time() . rand(1, 9999) . '_' . $key . '.' . $file->extension;
                    $size = $file->size;
                    $file->saveAs($uploadDir . $image_name);
                    $imgModelPatient = new UserAddressImages();
                    $imgModelPatient->address_id = $address_id;
                    $imgModelPatient->image_path = '/storage/web/source/user-address/';
                    $imgModelPatient->image_base_url = Yii::getAlias('@frontendUrl');
                    $imgModelPatient->image = $image_name;
                    $imgModelPatient->image_name = $filename;
                    $imgModelPatient->image_size = (string) $size;
                    if ($imgModelPatient->save()) {
                        $addressImagesData = UserAddressImages::find()->where(['id' => $imgModelPatient->id])->one();
                        $geturl = $addressImagesData->image_base_url . $addressImagesData->image_path . $addressImagesData->image;
                        $file_data = file_get_contents($geturl, false, stream_context_create([
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                            ],
                        ]));
                        $img = ImageManagerStatic::make($file_data)->insert($waterMarkImgDir, 'bottom-left', 10, 10);
                        $img->save($uploadDir . $addressImagesData->image);
                    } else {
                        /* echo '<pre>';
                          print_r($imgModelPatient->getErrors());die; */
                    }
                } else {
                    break;
                }
            }
        }
        return true;
    }

    public static function updateAddressImageList($address_id, $files, $typename = 'images', $type = 'api') {
        $address = UserAddress::findOne(['id' => $address_id]);
        $avatar = $address->image_path;
        $response = $file_tmp = $file_name = array();


        if (isset($files[$typename]['tmp_name']) && isset($files[$typename]['name'])) {
            if ($type == 'web') {
                $file_tmps = $files[$typename]['tmp_name']['image'];
                $file_names = $files[$typename]['name']['image'];
                $file_sizes = $files[$typename]['size']['image'];
                $file_error = $files[$typename]['error'];
            } else {
                $file_tmps = $files[$typename]['tmp_name'];
                $file_names = $files[$typename]['name'];
                $file_sizes = $files[$typename]['size'];
                $file_error = $files[$typename]['error'];
            }

            if (!is_dir("../../storage/web/source/user-address/"))
                mkdir("../../storage/web/source/user-address", 0775, true);
            $uploadDir = Yii::getAlias('@storage/web/source/user-address/');
            $waterMarkImgDir = Yii::getAlias('@frontend/web/images/watermark.png');

            foreach ($file_names as $key => $file_name) {
                if ($file_error[$key] == 0) {
                    if (!empty($file_name)) {
                        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                        $filename = pathinfo($file_name, PATHINFO_FILENAME);
                        $newimage = time() . rand() . '_add.' . $extension;
                        $file_tmp = $file_tmps[$key];
                        $size = $file_sizes[$key];

                        if (!move_uploaded_file($file_tmp, $uploadDir . $newimage)) {
                            $response["status"] = 0;
                            $response["error"] = true;
                            $response['message'] = 'Unable to upload image';
                        }
                        $address_image = new UserAddressImages();
                        $address_image->address_id = $address_id;
                        $address_image->image_path = '/storage/web/source/user-address/';
                        $address_image->image_base_url = Yii::getAlias('@frontendUrl');
                        $address_image->image = $newimage;
                        $address_image->image_name = $filename;
                        $address_image->image_size = (string) $size;

                        if ($address_image->save()) {
                            $addressImagesData = UserAddressImages::find()->where(['id' => $address_image->id])->one();
                            $geturl = $addressImagesData->image_base_url . $addressImagesData->image_path . $addressImagesData->image;
                            $file_data = file_get_contents($geturl, false, stream_context_create([
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false,
                                ],
                            ]));
                            $img = ImageManagerStatic::make($file_data)->insert($waterMarkImgDir, 'bottom-left', 10, 10);
                            $img->save($uploadDir . $addressImagesData->image);
                            $response[$key]["status"] = 1;
                            $response[$key]["error"] = false;
                            $response[$key]['data'] = DrsPanel::getAddressAvator($address_id);
                            $response[$key]['message'] = 'Address image updated';
                        } else {
                            $response[$key]["status"] = 0;
                            $response[$key]["error"] = true;
                            $response[$key]["data"] = $address_image->getErrors();
                            $response[$key]['message'] = 'Address image not updated';
                        }
                    } else {
                        $response[$key]["status"] = 0;
                        $response[$key]["error"] = true;
                        $response[$key]["data"] = '';
                        $response[$key]['message'] = 'Temp file not found';
                    }
                } else {
                    $response[$key]["status"] = 0;
                    $response[$key]["error"] = true;
                    $response[$key]["data"] = '';
                    $response[$key]['message'] = 'Error file found';
                }
            }
        } else {
            $response["status"] = 0;
            $response["error"] = true;
            $response['message'] = 'Image Required';
        }

        return $response;
    }

    public static function updateMemberImageWeb($member_id, $upload) {
        if (!empty($upload)) {
            if (!is_dir("../../storage/web/source/records/"))
                mkdir("../../storage/web/source/records", 0775, true);

            $uploadDir = Yii::getAlias('@storage/web/source/records/');
            $image_name = time() . rand() . '.' . $upload->extension;
        }
        return true;
    }

    public static function memberImages($model, $record_label, $files) {

        if (!is_dir("../../storage/web/source/records/"))
            mkdir("../../storage/web/source/records", 0775, true);

        if (count($files) > 0) {
            $file_count = count($files['file']['tmp_name']);

            for ($i = 0; $i < $file_count;) {
                $photos = new PatientMemberFiles();
                $photos->image_name = $record_label;
                $file_tmp = $files['file']['tmp_name'];
                $file_name = $files['file']['name'];
                $uploadDir = Yii::getAlias('@storage/web/source/records/');
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $newimage = time() . rand() . '.' . $extension;
                //$newimage = time() .rand(1,9999).'_'.$i.'.'. $extension;

                if (move_uploaded_file($file_tmp, $uploadDir . $newimage)) {
                    $photos->image_base_url = Yii::getAlias('@storageUrl');
                    $photos->image_path = '/source/records/';
                    $photos->image_type = $extension;
                    $photos->image = $newimage;
                    if ($photos->save()) {
                        $recordAdd = new PatientMemberRecords();
                        $recordAdd->member_id = $model->id;
                        $recordAdd->files_id = $photos->id;
                        $recordAdd->save();
                    }
                }
                $i++;
            }
            return true;
        }
        return false;
    }

}
