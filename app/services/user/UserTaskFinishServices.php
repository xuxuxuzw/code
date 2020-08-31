<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types = 1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserTaskFinishDao;

/**
 *
 * Class UserTaskFinishServices
 * @package app\services\user
 */
class UserTaskFinishServices extends BaseServices
{

    /**
     * UserTaskFinishServices constructor.
     * @param UserTaskFinishDao $dao
     */
    public function __construct(UserTaskFinishDao $dao)
    {
        $this->dao = $dao;
    }

}