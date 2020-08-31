<?php

namespace app\api\controller\v1\store;

use app\Request;
use app\services\coupon\StoreCouponIssueServices;
use app\services\coupon\StoreCouponService;

/**
 * 优惠券类
 * Class StoreCouponsController
 * @package app\api\controller\store
 */
class StoreCouponsController
{
    protected $services;

    public function __construct(StoreCouponIssueServices $services)
    {
        $this->services = $services;
    }

    /**
     * 可领取优惠券列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['type', 0],
            ['product_id', 0]
        ]);
        return app('json')->successful($this->services->getIssueCouponList($request->uid(), $where));
    }

    /**
     * 领取优惠券
     *
     * @param Request $request
     * @return mixed
     */
    public function receive(Request $request)
    {
        list($couponId) = $request->getMore([
            ['couponId', 0]
        ], true);
        if (!$couponId || !is_numeric($couponId)) return app('json')->fail('参数错误!');

        /** @var StoreCouponIssueServices $couponIssueService */
        $couponIssueService = app()->make(StoreCouponIssueServices::class);
        $couponIssueService->issueUserCoupon($couponId, $request->uid());

        return app('json')->success('领取成功');
    }

    /**
     * 用户已领取优惠券
     * @param Request $request
     * @param $types
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user(Request $request, $types)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getUserCouponList($uid, $types));
    }

    /**
     * 优惠券 订单获取
     * @param Request $request
     * @param $price
     * @return mixed
     */
    public function order(Request $request, StoreCouponService $service, $cartId, $new)
    {
        return app('json')->successful($service->beUsableCouponList((int)$request->uid(), $cartId, !!$new));
    }
}
