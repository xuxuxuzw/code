<?php

namespace app\api\controller\v2\store;

use app\services\coupon\StoreCouponIssueServices;
use app\services\product\product\StoreProductCouponServices;
use think\Request;

class StoreCouponsController
{
    /**
     * 获取新人券
     * @return mixed
     */
    public function getNewCoupon(Request $request)
    {
        $userInfo = $request->user();
        $data = [];
        /** @var StoreCouponIssueServices $couponService */
        $couponService = app()->make(StoreCouponIssueServices::class);
        $data['list'] = $couponService->getNewCoupon();
        $data['image'] = sys_config('coupon_img');
        if ($userInfo->add_time === $userInfo->last_time) {
            $data['show'] = 1;
        } else {
            $data['show'] = 0;
        }
        return app('json')->success($data);
    }

    /**
     * 赠送下单之后订单中 关联优惠劵
     * @param Request $request
     * @param $orderId
     * @return mixed
     */
    public function getOrderProductCoupon(Request $request,$orderId)
    {

        $uid = (int)$request->uid();
        if(!$orderId){
            return app('json')->fail('参数错误');
        }
        /** @var StoreProductCouponServices $storeProductCoupon */
        $storeProductCoupon = app()->make(StoreProductCouponServices::class);
        $list = $storeProductCoupon->getOrderProductCoupon($uid,$orderId);
        return app('json')->success($list);
    }
}
