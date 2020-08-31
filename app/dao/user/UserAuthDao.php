<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/
 */
declare (strict_types = 1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\User;

/**
 *
 * Class UserAuthDao
 * @package app\dao\user
 */
class UserAuthDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return User::class;
    }

}
