<?php

namespace common\models;

/**
 * This is the ActiveQuery class for [[PatientMembersRecord]].
 *
 * @see PatientMembersRecord
 */
class PatientMemberRecordsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return PatientMemberRecords[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return PatientMemberRecords|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
