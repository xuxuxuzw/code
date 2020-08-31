<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/2
 */

namespace crmeb\jobs;

use crmeb\basic\BaseJob;
use crmeb\services\template\Template;

/**
 * 小程序模板消息消息队列
 * Class RoutineTemplateJob
 * @package crmeb\jobs
 */
class RoutineTemplateJob extends BaseJob
{
    /**
     * 确认收货
     * @param $openid
     * @param $order
     * @param $title
     * @return bool
     */
    public function sendOrderTakeOver($openid, $order, $title)
    {
        return $this->sendTemplate('OREDER_TAKEVER', $openid, [
            'thing1' => $order['order_id'],
            'thing2' => $title,
            'date5' => date('Y-m-d H:i:s', time()),
        ], '/pages/order_details/index?order_id=' . $order['order_id']);
    }

    /**
     * @param $openid
     * @param $order
     * @param $storeTitle
     * @param int $isGive 0 = 同城配送， 1 = 快递发货
     * @return bool
     */
    public function sendOrderPostage($openid, $order, $storeTitle, int $isGive = 0)
    {
        if ($isGive) {//快递发货
            return $this->sendTemplate('ORDER_DELIVER_SUCCESS', $openid, [
                'character_string2' => $order['delivery_id'],
                'thing1' => $order['delivery_name'],
                'time3' => date('Y-m-d H:i:s', time()),
                'thing5' => $storeTitle,
            ], '/pages/order_details/index?order_id=' . $order['order_id']);
        } else {//同城配送
            return $this->sendTemplate('ORDER_POSTAGE_SUCCESS', $openid, [
                'thing8' => $storeTitle,
                'character_string1' => $order['order_id'],
                'name4' => $order['delivery_name'],
                'phone_number10' => $order['delivery_id']
            ], '/pages/order_details/index?order_id=' . $order['order_id']);
        }
    }

    /**
     * 充值金额退款
     * @param $UserRecharge
     * @param $refund_price
     * @return bool
     */
    public function sendRechargeSuccess($openid, $UserRecharge, $refund_price)
    {
        return $this->sendTemplate('ORDER_REFUND', $openid, [
            'thing1' => '亲，您充值的金额已退款,本次退款' . $refund_price . '金额',
            'thing2' => '余额充值退款',
            'amount3' => $UserRecharge['price'],
            'character_string6' => $UserRecharge['order_id'],
        ], '/pages/user_bill/index?type=2');
    }

    /**
     * 订单退款成功发送消息
     * @param string $openid
     * @param array $order
     * @return bool
     */
    public function sendOrderRefundSuccess($openid, $order, $storeTitle)
    {
        return $this->sendTemplate('ORDER_REFUND', $openid, [
            'thing1' => '已成功退款',
            'thing2' => $storeTitle,
            'amount3' => $order['pay_price'],
            'character_string6' => $order['order_id']
        ], '/pages/order_details/index?order_id=' . $order['order_id'] . '&isReturen=1');
    }

    /**
     * 订单退款失败
     * @param string $openid
     * @param $order
     * @return bool
     */
    public function sendOrderRefundFail($openid, $order, $storeTitle)
    {
        return $this->sendTemplate('ORDER_REFUND', $openid, [
            'thing1' => '退款失败',
            'thing2' => $storeTitle,
            'amount3' => $order['pay_price'],
            'character_string6' => $order['order_id']
        ], '/pages/order_details/index?order_id=' . $order['order_id'] . '&isReturen=1');
    }

    /**
     * 用户申请退款给管理员发送消息
     * @param array $order
     * @param string $refundReasonWap
     * @param array $adminList
     */
    public function sendOrderRefundStatus($openid, $order)
    {
        $data['character_string4'] = $order['order_id'];
        $data['date5'] = date('Y-m-d H:i:s', time());
        $data['amount2'] = $order['pay_price'];
        $data['phrase7'] = '申请退款中';
        $data['thing8'] = '请及时处理';
        return $this->sendTemplate('ORDER_REFUND_STATUS', $openid, $data);
    }

    /**
     * 砍价成功通知
     * @param array $bargain
     * @param array $bargainUser
     * @param int $bargainUserId
     * @return bool
     */
    public function sendBargainSuccess($openid, $bargain = [], $bargainUser = [], $bargainUserId = 0)
    {
        $data['thing1'] = $bargain['title'];
        $data['amount2'] = $bargainUser['min_price'];
        $data['thing3'] = '恭喜您，已经砍到最低价了';
        return $this->sendTemplate('BARGAIN_SUCCESS', $openid, $data, '/pages/activity/user_goods_bargain_list/index');
    }

    /**
     * 订单支付成功发送模板消息
     * @param $openid
     * @param $pay_price
     * @param $orderId
     * @param $payTime
     * @return bool|void
     */
    public function sendOrderSuccess($openid, $pay_price, $orderId)
    {
        if ($orderId == '') return true;
        $data['character_string1'] = $orderId;
        $data['amount2'] = $pay_price . '元';
        $data['date3'] = date('Y-m-d H:i:s', time());
        return $this->sendTemplate('ORDER_PAY_SUCCESS', $openid, $data, '/pages/order_details/index?order_id=' . $orderId);
    }

    /**
     *提现失败
     * @param $openid
     * @param $msg
     * @param $extract_number
     * @param $extract_type
     * @return bool
     */
    public function sendExtractFail($openid, $msg, $extract_number, $nickname)
    {
        return $this->sendTemplate('USER_EXTRACT', $openid, [
            'thing1' => '提现失败：' . $msg,
            'amount2' => $extract_number . '元',
            'thing3' => $nickname,
            'date4' => date('Y-m-d H:i:s', time())
        ], '/pages/user_spread_money/index?type=2');
    }

    /**
     * 提现成功
     * @param $openid
     * @param $extract_number
     * @param $nickname
     * @return bool
     */
    public function sendExtractSuccess($openid, $extract_number, $nickname)
    {
        return $this->sendTemplate('USER_EXTRACT', $openid, [
            'thing1' => '提现成功',
            'amount2' => $extract_number . '元',
            'thing3' => $nickname,
            'date4' => date('Y-m-d H:i:s', time())
        ], '/pages/user_spread_money/index?type=2');
    }

    /**
     * 拼团成功通知
     * @param $uid
     * @param $pinkTitle
     * @param $nickname
     * @param $pinkTime
     * @param $count
     * @return bool
     */
    public function sendPinkSuccess($openid, $pinkTitle, $nickname, $pinkTime, $count, string $link = '')
    {
        return $this->sendTemplate('PINK_TRUE', $openid, [
            'thing1' => $pinkTitle,
            'name3' => $nickname,
            'date5' => date('Y-m-d H:i:s', $pinkTime),
            'number2' => $count
        ], $link);
    }

    /**
     * 拼团状态通知
     * @param $openid
     * @param $pinkTitle
     * @param $count
     * @param $remarks
     * @return bool
     */
    public function sendPinkFail($openid, $pinkTitle, $count, $remarks, $link)
    {
        return $this->sendTemplate('PINK_STATUS', $openid, [
            'thing2' => $pinkTitle,
            'thing1' => $count,
            'thing3' => $remarks
        ], $link);
    }

    /**
     * 赠送积分消息提醒
     * @param $openid
     * @param $order
     * @param $storeTitle
     * @param $gainIntegral
     * @param $integral
     * @return bool
     */
    public function sendUserIntegral($openid, $order, $storeTitle, $gainIntegral, $integral)
    {
        if (!$order) return true;
        if (is_string($order['cart_id']))
            $order['cart_id'] = json_decode($order['cart_id'], true);
        return $this->sendTemplate('INTEGRAL_ACCOUT', $openid, [
            'character_string2' => $order['order_id'],
            'thing3' => $storeTitle,
            'amount4' => $order['pay_price'],
            'number5' => $gainIntegral,
            'number6' => $integral
        ], '/pages/user_bill/index?type=2');
    }

    /**
     * 发送模板消息
     * @param string $TempCode 模板消息常量名称
     * @param int $openid 用户openid
     * @param array $data 模板内容
     * @param string $link 跳转链接
     * @return bool
     */
    public function sendTemplate(string $tempCode, $openid, array $data, string $link = '')
    {
        try {
            if (!$openid) return true;
            $template = new Template('subscribe');
            return $template->to($openid)->url($link)->send($tempCode, $data);
        } catch (\Exception $e) {
            return true;
        }
    }
}