<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\services\coupon;

use app\services\BaseServices;
use app\dao\coupon\StoreCouponUserUserDao;

/**
 *
 * Class StoreCouponUserUserServices
 * @package app\services\coupon
 */
class StoreCouponUserUserServices extends BaseServices
{

    /**
     * StoreCouponUserUserServices constructor.
     * @param StoreCouponUserUserDao $dao
     */
    public function __construct(StoreCouponUserUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->sysPage($where, $page, $limit);
        $count = $this->dao->sysCount($where);
        return compact('list', 'count');
    }
}
