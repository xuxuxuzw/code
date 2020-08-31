<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/8
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserUserBillDao;

/**
 *
 * Class UserUserBillServices
 * @package app\services\user
 */
class UserUserBillServices extends BaseServices
{

    /**
     * UserUserBillServices constructor.
     * @param UserUserBillDao $dao
     */
    public function __construct(UserUserBillDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $where
     * @return array
     */
    public function getBrokerageList(array $where, string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->getCount($where);
        return [$count, $list];
    }
}
