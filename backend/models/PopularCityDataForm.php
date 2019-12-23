<?php
namespace backend\models;

use Yii;
use yii\base\Model;

/**
 * Create user form
 */
class PopularCityDataForm extends Model{

    public $speciality;
    public $treatment;
    public $hospital;
    public $city;



    /**
     * @inheritdoc
     */
    public function rules(){
        return [
            ['city','required'],
            [['speciality','treatment','hospital'],'safe'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'speciality' => Yii::t('common', 'Popular Speciality'),
            'treatment' => Yii::t('common', 'Popular Treatment'),
            'hospital' => Yii::t('common', 'Popular Hospital'),
            'city' => Yii::t('common', 'City'),
        ];
    }
}