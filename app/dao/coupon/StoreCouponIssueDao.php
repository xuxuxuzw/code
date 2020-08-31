<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\dao\coupon;

use app\dao\BaseDao;
use app\model\coupon\StoreCouponIssue;

/**
 *
 * Class StoreCouponIssueDao
 * @package app\dao\coupon
 */
class StoreCouponIssueDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCouponIssue::class;
    }

    /**
     * 获取列表
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
        return $this->search($where)->with('coupon')->page($page, $limit)->order('id desc')->select()->toArray();
    }

    /**
     * 获取优惠券列表
     * @param int $uid 用户ID
     * @param int $type 0通用，1分类，2商品
     * @param int $typeId 分类ID或商品ID
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getIssueCouponList(int $uid, int $type, $typeId, int $page, int $limit)
    {
        return $this->getModel()->where('status', 1)
            ->where('is_del', 0)
            ->where('remain_count > 0 OR is_permanent = 1')
            ->where('is_give_subscribe', 0)
            ->where('is_full_give', 0)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', time())->where('end_time', '>', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })
            ->with(['used' => function ($query) use ($uid) {
                $query->where('uid', $uid);
            }])
            ->when($type == 1, function ($query) use ($typeId) {
                $query->where('category_id', 'in', $typeId);
            })
            ->when($type == 2, function ($query) use ($typeId) {
                $query->whereFindinSet('product_id', $typeId);
            })
            ->when($page != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->select()->toArray();
    }

    /**
     * 获取优惠卷详情
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        return $this->getModel()->where('status', 1)
            ->where('id', $id)
            ->where('is_del', 0)
            ->where('remain_count > 0 OR is_permanent = 1')
            ->where('is_give_subscribe', 0)
            ->where('is_full_give', 0)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', time())->where('end_time', '>', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })->find();
    }

    /**
     * 获取金大于额的优惠卷金额
     * @param string $totalPrice
     * @return float
     */
    public function getUserIssuePrice(string $totalPrice)
    {
        return $this->search(['status' => 1, 'is_full_give' => 1, 'is_del' => 0])
            ->where('full_reduction', '<=', $totalPrice)
            ->sum('coupon_price');
    }

    /**
     * 获取新人券
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewCoupon()
    {
        return $this->getModel()->where('status', 1)
            ->where('is_del', 0)
            ->where('remain_count > 0 OR is_permanent = 1')
            ->where('is_give_subscribe', 1)
            ->where('is_full_give', 0)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', time())->where('end_time', '>', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })->select()->toArray();
    }

    /**
     * 获取一条优惠券信息
     * @param int $id
     * @return mixed
     */
    public function getCouponInfo(int $id)
    {
        return $this->getModel()->alias('i')->join('StoreCoupon c', 'c.id = i.cid')->where('i.id', $id)->where('i.status', 1)->where('i.is_del', 0)->where('c.is_del', 0)->where('c.status', 1)->field('i.*')->find();
    }

    /**
     * 获取满赠、下单、关注赠送优惠券
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGiveCoupon(array $where, string $field = '*')
    {
        return $this->getModel()->field($field)
            ->where('status', 1)
            ->where('is_del', 0)
            ->where('remain_count > 0 OR is_permanent = 1')
            ->where($where)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<', time())->where('end_time', '>', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('end_time', 0);
                });
            })->select()->toArray();
    }

    /**
     * 获取商品优惠卷列表
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productCouponList($where, $field)
    {
        return $this->getModel()->where($where)->field($field)->select()->toArray();
    }
}
