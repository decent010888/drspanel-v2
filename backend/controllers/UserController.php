<?php

namespace backend\controllers;

use backend\models\ChangePasswordForm;
use common\components\DrsPanel;
use common\models\UserProfile;
use frontend\modules\user\models\AccountForm;
use Yii;
use common\models\User;
use common\models\Groups;
use backend\models\UserForm;
use backend\models\search\UserSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchrole=array();
        $roles=DrsPanel::adminRoles();
        foreach($roles as $rolek=>$rolev){
            $searchrole[]=$rolek;
        }
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams,
            $searchrole
        );
        $dataProvider->sort->defaultOrder = ['id' => SORT_DESC];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    
    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new UserForm();
        $model->setScenario('create');
        $roles=DrsPanel::adminRoles();

        if (Yii::$app->request->post()) {
            $post=Yii::$app->request->post();
            $model->load($post);
            if($model->save())
            return $this->redirect(['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'roles' => $roles,
        ]);
    }

    /**
     * Updates an existing User model.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {

        $auth = Yii::$app->authManager;
        $model = new UserForm();
        $model->setModel($this->findModel($id));
        $roles=DrsPanel::adminRoles();
        $model->type='edit';
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'roles' => $roles,
        ]);
    }

    public function actionChangePassword($id){
        $user_id=   $id;
        $profile = UserProfile::findOne(['user_id' => $user_id]);

        $model = new ChangePasswordForm();
        $model->setUser(User::findOne($user_id));

        if ($model->load(Yii::$app->request->post()) ){
            if($model->validate()){
                if($model->save()) {
                    $message_text=Yii::t('db','Your account details successfully updated.');
                    Yii::$app->session->setFlash( 'alert',['class'=>'alert-success','body'=>Yii::t('db', $message_text) ]);
                    return $this->refresh();
                }
            }
        }
        return $this->render('change-password', ['profile'=>$profile,'model'=>$model]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        Yii::$app->authManager->revokeAll($id);
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
