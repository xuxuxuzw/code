<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\services\product\product;

use app\services\BaseServices;
use app\dao\product\product\StoreProductCouponDao;
use app\services\coupon\StoreCouponIssueServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use think\exception\ValidateException;

/**
 *
 * Class StoreProductCouponServices
 * @package app\services\coupon
 */
class StoreProductCouponServices extends BaseServices
{

    /**
     * StoreProductCouponServices constructor.
     * @param StoreProductCouponDao $dao
     */
    public function __construct(StoreProductCouponDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 商品关联优惠券
     * @param int $id
     * @param array $coupon_ids
     * @return bool
     */
    public function setCoupon(int $id, array $coupon_ids)
    {
        $this->dao->whereDelete(['product_id' => $id]);
        if ($coupon_ids) {
            $data = $data_all = [];
            $data['product_id'] = $id;
            $data['add_time'] = time();
            foreach ($coupon_ids as $cid) {
                $data['issue_coupon_id'] = $cid;
                $data_all[] = $data;
            }
            $res = $this->dao->saveAll($data_all);
            if (!$res) throw new AdminException('关联优惠券失败！');
        }
        return true;
    }

    /**
     * 下单赠送优惠劵
     * @param int $uid
     * @param $orderId
     * @return array
     */
    public function getOrderProductCoupon(int $uid, $orderId)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        $order = $storeOrder->getOne(['order_id' => $orderId]);
        if (!$order || $order['uid'] != $uid) {
            throw new ValidateException('订单不存在');
        }
        /** @var StoreOrderCartInfoServices $storeOrderCartInfo */
        $storeOrderCartInfo = app()->make(StoreOrderCartInfoServices::class);
        $productIds = $storeOrderCartInfo->getColumn(['oid'=>$order['id']],'product_id');
        $list = [];
        if ($productIds) {
            $couponList = $this->dao->getProductCoupon($productIds);
            if ($couponList) {
                /** @var StoreCouponIssueServices $storeCoupon */
                $storeCoupon = app()->make(StoreCouponIssueServices::class);
                $list = $storeCoupon->orderPayGiveCoupon($uid, array_column($couponList, 'issue_coupon_id'));
                foreach ($list as &$item) {
                    $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
                    $item['end_time'] = date('Y-m-d H:i:s', $item['end_time']);
                }
            }
        }
        return $list;
    }
}
