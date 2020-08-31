<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-14
 */

namespace crmeb\jobs;


use app\services\activity\StorePinkServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJob;

class PinkJob extends BaseJob
{
    public function doJob($pinkId)
    {
        /** @var StorePinkServices $pinkService */
        $pinkService = app()->make(StorePinkServices::class);
        $people = $pinkService->value(['id' => $pinkId], 'people');
        $count = $pinkService->count(['k_id' => $pinkId, 'is_refund' => 0]) + 1;
        $orderIds = $pinkService->getColumn([['id|k_id', '=', $pinkId]], 'order_id_key', 'uid');
        if ($people > $count) {
            foreach ($orderIds as $key => $item) {
                /** @var StoreOrderServices $orderService */
                $orderService = app()->make(StoreOrderServices::class);
                $order = $orderService->get($item);

                /** @var StoreOrderRefundServices $orderRefundService */
                $orderRefundService = app()->make(StoreOrderRefundServices::class);
                $orderRefundService->orderApplyRefund($order, '拼团时间超时');

                $pinkService->whereUpdate([['id|k_id', '=', $pinkId]], ['status' => 3]);
                $pinkService->orderPinkAfterNo($key, $pinkId, false, $order->is_channel);
            }
            return true;
        }
        return true;
    }
}
