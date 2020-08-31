<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/14
 */

namespace crmeb\jobs;


use app\services\message\sms\SmsSendServices;
use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJob;

/**
 * 未支付10分钟后发送短信
 * Class UnpaidOrderSend
 * @package crmeb\jobs
 */
class UnpaidOrderSend extends BaseJob
{
    public function doJob($id)
    {
        /** @var StoreOrderServices $services */
        $services = app()->make(StoreOrderServices::class);
        $orderInfo = $services->get($id);
        if (!$orderInfo) {
            return true;
        }
        if ($orderInfo->paid) {
            return true;
        }
        if ($orderInfo->is_del) {
            return true;
        }
        /** @var SmsSendServices $smsServices */
        $smsServices = app()->make(SmsSendServices::class);
        $smsServices->send(true, $orderInfo['user_phone'], ['order_id' => $orderInfo['order_id']], 'ORDER_PAY_FALSE');
        return true;
    }

}
