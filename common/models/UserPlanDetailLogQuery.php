<?php

namespace common\models;

/**
 * This is the ActiveQuery class for [[UserPlanDetailLog]].
 *
 * @see UserPlanDetailLog
 */
class UserPlanDetailLogQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return UserPlanDetailLog[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return UserPlanDetailLog|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
