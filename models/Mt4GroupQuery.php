<?php

namespace app\models;

use yii\db\ActiveQuery;

class Mt4GroupQuery extends ActiveQuery
{
    /**
     * Filter by active status
     *
     * @return $this
     */
    public function active()
    {
        return $this->andWhere(['status' => Mt4Group::STATUS_ACTIVE]);
    }

    /**
     * Filter by inactive status
     *
     * @return $this
     */
    public function inactive()
    {
        return $this->andWhere(['status' => Mt4Group::STATUS_INACTIVE]);
    }

    /**
     * Search by name
     *
     * @param string $name
     * @return $this
     */
    public function searchByName($name)
    {
        return $this->andWhere(['like', 'name', $name]);
    }
}