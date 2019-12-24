<?php

namespace frontend\modules\user\controllers;

use common\components\AppHelper;
use common\models\BlogPost;
use common\models\MetaCategories;
use common\models\MetaSubcategories;
use common\models\UserCategories;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use common\models\SharePages;
use yii\web\NotFoundHttpException;

class DefaultController extends Controller {

    /**
     * @return array
     */
    public function actions() {
        return [
        ];
    }

    /**
     * @return array
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['detail'],
                        'allow' => true,
                        'roles' => ['?']
                    ],
                    [
                        'actions' => ['get-category-type', 'get-sub-category'],
                        'allow' => true,
                        'roles' => ['@']
                    ]
                ]
            ]
        ];
    }

    public function actionGetCategoryType($id) {
        $typearray = array();
        $userId = Yii::$app->user->id;
        $addSharePage = 0;
        $statusArr = array('draft', 'pending', 'active', 'denied', 'modification_required', 'paused');
        $countRegularSharePage = SharePages::find()->where(['user_id' => $userId])->andWhere(['status' => $statusArr])->count();
        $userCurrentPaln = \common\models\UserPlan::find()->where(['user_id' => $userId])->one();
       
        $userPoints = \common\models\UserPoints::find()->where(['user_id' => $userId])->sum('amount');
        $palnFetaure = \common\models\PlanFeatures::find()->where(['plan_idss' => $userCurrentPaln['plan_id']])->where(['label' => 'share_pages'])->one();
        echo '<pre>';print_r($palnFetaure);die;
        $defultSharepagesCount = 0;
        if(is_numeric($palnFetaure['value']) && $palnFetaure['value'] > 0 ){
            $defultSharepagesCount = $palnFetaure['value'];
        }
        if ($userPoints > 0) {
            $addSharePage = $userPoints / 2;
        }
        $countSharePages = $defultSharepagesCount + $addSharePage;
        echo $palnFetaure['value'].''.$countRegularSharePage;die;
        if ($userCurrentPaln['plan_id'] == 4 && $countSharePages >= $countRegularSharePage) {
            return json_encode('no');
        }

        $categories = MetaSubcategories::find()->where(['cat_id' => $id])->all();
        $i = 0;
        foreach ($categories as $category) {
            $type = $category->type;
            if ($type == 1) {

                $typearray[$type] = '<li class="aro-se-g js-service-option" data-provider=' . SharePages::TYPE_SERVICE . '>
                                        <a href="javascript:void(0);">
                                            <img src="images/product-step/sell-tol-1.svg" width="80px" alt="">
                                            <p>Sell a <br>
                                            service</p>
                                            <i data-provider=' . SharePages::TYPE_SERVICE . '>Learn more</i>
                                        </a>
                                </li>';
            }
            if ($type == 2) {

                $typearray[$type] = '<li class="aro-se-g bl js-finished-option" data-provider=' . SharePages::TYPE_FINISHED_PRODUCT . '>
                                    <a href="javascript:void(0);">
                                        <img src="images/product-step/sell-tol-2.svg" width="70px" alt="">
                                        <p>Sell a finished<br>
                                        product</p>
                                        <i data-provider=' . SharePages::TYPE_FINISHED_PRODUCT . '>Learn more</i>
                                    </a>
                                </li>';
            }
            if ($type == 3) {

                $typearray[$type] = '<li class="aro-se-g gr js-pre-sales-option" data-provider=' . SharePages::TYPE_PRESALE_PRODUCT . '>
                                    <a href="javascript:void(0);">
                                        <img src="images/product-step/sell-tol-3.svg" width="70px" alt="">
                                        <p>Pre sell<br>
                                        something</p>
                                        <i data-provider=' . SharePages::TYPE_PRESALE_PRODUCT . '>Learn more</i>
                                    </a>
                                </li>';
            }
            if ($type == 4) {

                $typearray[$type] = '<li class="aro-se-g bl js-subscriptions-option" data-provider=' . SharePages::TYPE_SUBSCRIPTIONS . '>
                                    <a href="javascript:void(0);">
                                        <img src="images/product-step/sell-tol-4.svg" width="70px" alt="">
                                        <p>Sell a<br>
                                        subscription</p>
                                        <i data-provider=' . SharePages::TYPE_SUBSCRIPTIONS . '>Learn more</i>
                                    </a>
                                </li>';
            }

            $i++;
        }
        echo json_encode($typearray);
        die;
    }

    public function actionGetSubCategory($id, $type) {
        $providerarray = array('service' => 1, 'product' => 2, 'pre-sales' => 3, 'subscriptions' => 4);
        $ptype = $providerarray[$type];

        $sub_cat = array();
        $categories = MetaSubcategories::find()->where(['cat_id' => $id, 'type' => $ptype])->orderBy('name asc')->all();

        $i = 0;
        foreach ($categories as $category) {
            $sub_cat[$i]['id'] = $category->id;
            $sub_cat[$i]['name'] = $category->name;
            $sub_cat[$i]['slug'] = $category->slug;
            $i++;
        }
        if (!empty($sub_cat)) {
            $price_array = AppHelper::SearchArrayIndex($sub_cat, 'Other');
            if (!empty($price_array)) {
                $array_copy = $sub_cat[$price_array[0]];
                unset($sub_cat[$price_array[0]]); // remove **other**
                array_push($sub_cat, $array_copy);
            }
        }
        echo json_encode($sub_cat);
        die;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action) {
        if (!\Yii::$app->user->isGuest) {
            $user_id = Yii::$app->user->identity->id;
            $user_categories = UserCategories::find()->where(['user_id' => $user_id])->all();
            if (empty($user_categories)) {
                if ($action->id !== 'marketplace-interest') {
                    return $this->redirect('marketplace-interest');
                }
            }
        }
        return parent::beforeAction($action);
    }

}
