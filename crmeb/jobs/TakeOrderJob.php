<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/14
 */

namespace crmeb\jobs;


use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use app\services\order\StoreOrderTakeServices;
use crmeb\basic\BaseJob;
use think\facade\Log;

/**
 * 自动收货消息队列
 * Class TakeOrderJob
 * @package crmeb\jobs
 */
class TakeOrderJob extends BaseJob
{

    public function doJob($id)
    {
        /** @var StoreOrderTakeServices $services */
        $services = app()->make(StoreOrderTakeServices::class);
        $orderInfo = $services->get($id);
        if (!$orderInfo) {
            return true;
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->tidyOrder($orderInfo);
        if ($order['_status']['_type'] != 2) {
            return true;
        }
        $orderInfo->status = 2;
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $res = $orderInfo->save() && $statusService->save([
                'oid' => $orderInfo['id'],
                'change_type' => 'take_delivery',
                'change_message' => '已收货[自动收货]',
                'change_time' => time()
            ]);
        try {
            $services->storeProductOrderUserTakeDelivery($order);
        } catch (\Throwable $e) {
            Log::error('自动收货消息队列执行成功,收货后置动作失败,失败原因:' . $e->getMessage());
        }
        return $res;
    }

}