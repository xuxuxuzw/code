<?php
declare (strict_types = 1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserTaskFinish;

/**
 *
 * Class UserTaskFinishDao
 * @package app\dao\user
 */
class UserTaskFinishDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserTaskFinish::class;
    }

}