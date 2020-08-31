<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/9
 */

namespace app\services\pay;


use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use think\exception\ValidateException;

/**
 * 线下支付
 * Class OrderOfflineServices
 * @package app\services\pay
 */
class OrderOfflineServices extends BaseServices
{

    /**
     * 线下支付
     * @param int $id
     * @return mixed
     */
    public function orderOffline(int $id)
    {
        /** @var StoreOrderServices $orderSerives */
        $orderSerives = app()->make(StoreOrderServices::class);
        $orderInfo = $orderSerives->get($id);
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }

        if ($orderInfo->paid) {
            throw new ValidateException('订单已支付');
        }
        $orderInfo->paid = 1;
        $orderInfo->pay_time = time();
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $res = $statusService->save([
            'oid' => $id,
            'change_type' => 'offline',
            'change_message' => '线下付款',
            'change_time' => time()
        ]);
        return $res && $orderInfo->save();
    }
}