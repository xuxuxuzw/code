<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types = 1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserVisitDao;

/**
 *
 * Class UserVisitServices
 * @package app\services\user
 */
class UserVisitServices extends BaseServices
{

    /**
     * UserVisitServices constructor.
     * @param UserVisitDao $dao
     */
    public function __construct(UserVisitDao $dao)
    {
        $this->dao = $dao;
    }

}