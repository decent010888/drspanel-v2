<?php

namespace common\models\query;

/**
 * This is the ActiveQuery class for [[UserServiceCharge]].
 *
 * @see UserServiceCharge
 */
class UserServiceChargeQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return UserServiceCharge[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return UserServiceCharge|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
