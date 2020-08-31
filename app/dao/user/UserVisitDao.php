<?php
declare (strict_types = 1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserVisit;
/**
 *
 * Class UserVisitDao
 * @package app\dao\user
 */
class UserVisitDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserVisit::class;
    }

}