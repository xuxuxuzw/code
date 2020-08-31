<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/10
 */
declare (strict_types=1);

namespace app\services\pay;

use crmeb\services\MiniProgramService;
use crmeb\services\WechatService;
use think\exception\ValidateException;

/**
 * 支付统一入口
 * Class PayServices
 * @package app\services\pay
 */
class PayServices
{
    /**
     * 发起支付
     * @param string $payType
     * @param string $openid
     * @param string $orderId
     * @param string $price
     * @param string $successAction
     * @param string $body
     * @return array|string
     */
    public function pay(string $payType, string $openid, string $orderId, string $price, string $successAction, string $body)
    {
        switch ($payType) {
            case 'routine':
                return MiniProgramService::jsPay($openid, $orderId, $price, $successAction, $body);
                break;
            case 'weixinh5':
                return WechatService::paymentPrepare(null, $orderId, $price, $successAction, $body, '', 'MWEB');
                break;
            case 'weixin':
                return WechatService::jsPay($openid, $orderId, $price, $successAction, $body);
                break;
            default:
                throw new ValidateException('支付方式不存在');
                break;
        }
    }
}
