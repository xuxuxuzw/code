<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\dao\coupon;

use app\dao\BaseDao;
use app\model\coupon\StoreCouponUser;

/**
 *
 * Class StoreCouponUserDao
 * @package app\dao\coupon
 */
class StoreCouponUserDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCouponUser::class;
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
        return $this->search($where)->with('issue')->page($page, $limit)->order('id desc')->select()->toArray();
    }

    /**
     * 使用优惠券修改优惠券状态
     * @param $id
     * @return \think\Model|null
     */
    public function useCoupon(int $id)
    {
        return $this->getModel()->where('id', $id)->update(['status' => 1, 'use_time' => time()]);
    }

    /**
     * 获取指定商品id下的优惠卷
     * @param array $productIds
     * @param int $uid
     * @param string $price
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productIdsByCoupon(array $productIds, int $uid, string $price)
    {
        return $this->getModel()->whereIn('cid', function ($query) use ($productIds) {
            $query->name('store_coupon_issue')->whereIn('id', function ($q) use ($productIds) {
                $q->name('store_coupon_product')->whereIn('product_id', $productIds)->field('coupon_id')->select();
            })->field(['id'])->select();
        })->with('issue')->where(['uid' => $uid, 'is_fail' => 0, 'status' => 0])->order('coupon_price DESC')
            ->where('use_min_price', '<=', $price)->select()->hidden(['status', 'is_fail'])->toArray();
    }

    /**
     * 根据商品id获取
     * @param array $cateIds
     * @param int $uid
     * @param string $price
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cateIdsByCoupon(array $cateIds, int $uid, string $price)
    {
        return $this->getModel()->whereIn('cid', function ($query) use ($cateIds) {
            $query->name('store_coupon_issue')->whereIn('category_id', $cateIds)->where('type', 1)->field('id')->select();
        })->where(['uid' => $uid, 'is_fail' => 0, 'status' => 0])->where('use_min_price', '<=', $price)
            ->order('coupon_price DESC')->with('issue')->select()->hidden([
                'status', 'is_fail'
            ])->toArray();
    }

    /**
     * 获取当前用户可用的优惠卷
     * @param array $ids
     * @param int $uid
     * @param string $price
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCoupon(array $ids, int $uid, string $price)
    {
        return $this->getModel()->where(['uid' => $uid, 'status' => 0])->whereNotIn('id', $ids)->whereIn('cid', function ($query) {
            $query->name('store_coupon_issue')->where('type', 0)->field(['id'])->select();
        })->where('use_min_price', '<=', $price)->order('coupon_price DESC')->with('issue')->select()->hidden([
            'status', 'is_fail'
        ])->toArray();;
    }

    /**
     * 获取列表带排序
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponListByOrder(array $where, $order, int $page, int $limit)
    {
        return $this->search($where)->with('issue')->page($page, $limit)->when($order != '', function ($query) use ($order) {
            $query->order($order);
        })->select()->toArray();
    }
}
