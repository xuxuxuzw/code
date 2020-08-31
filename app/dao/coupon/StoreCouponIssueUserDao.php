<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\dao\coupon;

use app\dao\BaseDao;
use app\model\coupon\StoreCouponIssueUser;

/**
 *
 * Class StoreCouponIssueUserDao
 * @package app\dao\coupon
 */
class StoreCouponIssueUserDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCouponIssueUser::class;
    }

    /**
     * 获取领取的优惠卷列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, int $page, int $limit)
    {
        return $this->search($where)->with('userInfo')->page($page, $limit)->select()->toArray();
    }
}
