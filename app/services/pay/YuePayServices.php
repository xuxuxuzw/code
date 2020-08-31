<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/10
 */

namespace app\services\pay;

use app\services\BaseServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\user\UserBillServices;
use app\services\user\UserServices;
use think\exception\ValidateException;

/**
 * 余额支付
 * Class YuePayServices
 * @package app\services\pay
 */
class YuePayServices extends BaseServices
{

    /**
     * 订单余额支付
     * @param $order_id
     * @param $uid
     * @return bool
     */
    public function yueOrderPay(array $orderInfo, $uid)
    {
        if (!$orderInfo) {
            throw new ValidateException('订单不存在!');
        }
        if ($orderInfo['paid']) {
            throw new ValidateException('该订单已支付!');
        }
        /** @var UserServices $services */
        $services = app()->make(UserServices::class);
        $userInfo = $services->getUserInfo($uid);
        if ($userInfo['now_money'] < $orderInfo['pay_price'])
            return ['status' => 'pay_deficiency', 'msg' => '余额不足' . floatval($orderInfo['pay_price'])];

        $this->transaction(function () use ($services, $orderInfo, $userInfo) {
            $res = false !== $services->bcDec($userInfo['uid'], 'now_money', $orderInfo['pay_price'], 'uid');
            /** @var UserBillServices $userBillServices */
            $userBillServices = app()->make(UserBillServices::class);
            $res = $res && $userBillServices->income('pay_product', $userInfo['uid'], $orderInfo['pay_price'], $userInfo['now_money'], $orderInfo['id']);
            /** @var StoreOrderSuccessServices $orderServices */
            $orderServices = app()->make(StoreOrderSuccessServices::class);
            $res = $res && $orderServices->paySuccess($orderInfo, 'yue');//余额支付成功
            if (!$res) {
                throw new ValidateException('余额支付失败!');
            }
        });
        return ['status' => true];
    }
}
