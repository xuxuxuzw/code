<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\services\coupon;

use app\services\BaseServices;
use app\dao\coupon\StoreCouponIssueUserDao;

/**
 *
 * Class StoreCouponIssueUserServices
 * @package app\services\coupon
 * @method  getColumn(array $where, string $field, ?string $key = '')
 */
class StoreCouponIssueUserServices extends BaseServices
{

    /**
     * StoreCouponIssueUserServices constructor.
     * @param StoreCouponIssueUserDao $dao
     */
    public function __construct(StoreCouponIssueUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取列表
     * @param array $where
     * @return array
     */
    public function issueLog(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }
}
