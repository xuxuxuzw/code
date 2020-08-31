<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/8
 */

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\services\BaseServices;
use app\services\coupon\StoreCouponIssueServices;
use app\services\message\service\StoreServiceServices;
use app\services\message\sms\SmsSendServices;
use app\services\user\UserBillServices;
use app\services\user\UserBrokerageFrozenServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use crmeb\jobs\RoutineTemplateJob;
use crmeb\jobs\SmsAdminJob;
use crmeb\jobs\WechatTemplateJob as TemplateJob;
use crmeb\utils\Queue;
use crmeb\utils\Str;
use think\exception\ValidateException;

/**
 * 订单收货
 * Class StoreOrderTakeServices
 * @package app\services\order
 * @method get(int $id, ?array $field = []) 获取一条
 */
class StoreOrderTakeServices extends BaseServices
{
    /**
     * 构造方法
     * StoreOrderTakeServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 用户订单收货
     * @param $uni
     * @param $uid
     * @return bool
     */
    public function takeOrder(string $uni, int $uid)
    {
        $order = $this->dao->getUserOrderDetail($uni, $uid);
        if (!$order) {
            throw new ValidateException('订单不存在!');
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->tidyOrder($order);
        if ($order['_status']['_type'] != 2) {
            throw new ValidateException('订单状态错误!');
        }
        $order->status = 2;
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $res = $order->save() && $statusService->save([
                'oid' => $order['id'],
                'change_type' => 'user_take_delivery',
                'change_message' => '用户已收货',
                'change_time' => time()
            ]);
        $res = $res && $this->storeProductOrderUserTakeDelivery($order);
        if (!$res) {
            throw new ValidateException('收货失败');
        }
        $this->smsSendTake($order);
        return $order;
    }

    /**
     * 订单确认收货
     * @param $order
     * @return bool
     */
    public function storeProductOrderUserTakeDelivery($order)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->get((int)$order['uid']);
        //获取购物车内的商品标题
        /** @var StoreOrderCartInfoServices $orderInfoServices */
        $orderInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $storeName = $orderInfoServices->getCarIdByProductTitle($order['cart_id']);
        $storeTitle = Str::substrUTf8($storeName, 20);

        $res = $this->transaction(function () use ($order, $userInfo, $storeTitle) {
            //赠送积分
            $res1 = $this->gainUserIntegral($order, $userInfo, $storeTitle);
            //返佣
            $res2 = $this->backOrderBrokerage($order, $userInfo);
            //经验
            $res3 = $this->gainUserExp($order, $userInfo);
            if (!($res1 && $res2 && $res3)) {
                throw new ValidateException('收货失败!');
            }
            return true;
        });

        if ($res) {
            try {
                //满赠优惠券
                /** @var StoreCouponIssueServices $couponServices */
                $couponServices = app()->make(StoreCouponIssueServices::class);
                $couponServices->userTakeOrderGiveCoupon($order['uid'], $order['total_price']);
                //修改收货状态
                /** @var UserBillServices $userBillServices */
                $userBillServices = app()->make(UserBillServices::class);
                $userBillServices->takeUpdate((int)$order['uid'], (int)$order['id']);
                //增加收货订单状态
                /** @var StoreOrderStatusServices $statusService */
                $statusService = app()->make(StoreOrderStatusServices::class);
                $statusService->save([
                    'oid' => $order['id'],
                    'change_type' => 'take_delivery',
                    'change_message' => '已收货',
                    'change_time' => time()
                ]);
                //发送模板消息
                $this->orderTakeSendAfter($order, $userInfo, $storeTitle);
                //发送短信
                $this->smsSend($order, $storeTitle);
            } catch (\Throwable $e) {
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 赠送积分
     * @param $order
     * @param $userInfo
     * @param $storeTitle
     * @return bool
     */
    public function gainUserIntegral($order, $userInfo, $storeTitle)
    {
        $res2 = true;
        if (!$userInfo) {
            return true;
        }
        // 营销产品送积分
        if (isset($order['combination_id']) && $order['combination_id']) {
            return true;
        }
        if (isset($order['seckill_id']) && $order['seckill_id']) {
            return true;
        }
        if (isset($order['bargain_id']) && $order['bargain_id']) {
            return true;
        }
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        if ($order['gain_integral'] > 0) {
            $res2 = false != $userBillServices->income('pay_give_integral', $order['uid'], $order['gain_integral'], $userInfo['integral'], $order['id']);
        }
        $order_integral = 0;
        $res3 = true;
        $order_give_integral = sys_config('order_give_integral');
        if ($order['pay_price'] && $order_give_integral) {
            $order_integral = bcmul($order_give_integral, (string)$order['pay_price'], 2);
            $res3 = false != $userBillServices->income('order_give_integral', $order['uid'], $order_integral, $userInfo['integral'], $order['id']);
        }
        $give_integral = $order_integral + $order['gain_integral'];
        if ($give_integral > 0) {
            $integral = $userInfo['integral'] + $give_integral;
            $userInfo->integral = $integral;
            $res1 = false != $userInfo->save();
            $res = $res1 && $res2 && $res3;
            $this->sendUserIntegral($order, $give_integral, $integral, $storeTitle);
            return $res;
        }
        return true;
    }

    /**
     * 一级返佣
     * @param $orderInfo
     * @param $userInfo
     * @return bool
     */
    public function backOrderBrokerage($orderInfo, $userInfo)
    {
        //商城分销功能是否开启 0关闭1开启
        if (!sys_config('brokerage_func_status')) return true;

        // 营销产品不返佣金
        if (isset($orderInfo['combination_id']) && $orderInfo['combination_id']) {
            return true;
        }
        if (isset($orderInfo['seckill_id']) && $orderInfo['seckill_id']) {
            return true;
        }
        if (isset($orderInfo['bargain_id']) && $orderInfo['bargain_id']) {
            return true;
        }

        // 当前用户不存在 没有上级 或者 当用用户上级时自己  直接返回
        if (!$userInfo || !$userInfo['spread_uid'] || $userInfo['spread_uid'] == $orderInfo['uid']) {
            return true;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->checkUserPromoter($userInfo['spread_uid'])) {
            return $this->backOrderBrokerageTwo($orderInfo, $userInfo);
        }
        $cartId = is_string($orderInfo['cart_id']) ? json_decode($orderInfo['cart_id'], true) : $orderInfo['cart_id'];
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $brokeragePrice = $cartServices->getProductBrokerage($cartId);
        // 返佣金额小于等于0 直接返回不返佣金
        if ($brokeragePrice <= 0) {
            return true;
        }
        //TODO 获取上级推广员信息
        $spreadPrice = $userServices->value(['uid' => $userInfo['spread_uid']], 'brokerage_price');
        // 上级推广员返佣之后的金额
        $balance = bcadd($spreadPrice, $brokeragePrice, 2);
        // 添加推广记录
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $res1 = $userBillServices->income('get_brokerage', $userInfo['spread_uid'], [
            'nickname' => $userInfo['nickname'],
            'pay_price' => floatval($orderInfo['pay_price']),
            'number' => floatval($brokeragePrice)
        ], $balance, $orderInfo['id']);
        // 添加用户余额
        $res2 = $userServices->bcInc($userInfo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        if ($res2) {
            /** @var UserBrokerageFrozenServices $frozenService */
            $frozenService = app()->make(UserBrokerageFrozenServices::class);
            $res2 = $frozenService->saveBrokage($userInfo['spread_uid'], $brokeragePrice, $res1->id, $orderInfo['order_id']);
        }
        // 一级返佣成功 跳转二级返佣
        $res = $res1 && $res2 && $this->backOrderBrokerageTwo($orderInfo, $userInfo);
        return $res;
    }


    /**
     * 二级推广返佣
     * @param $orderInfo
     * @param $userInfo
     * @return bool
     */
    public function backOrderBrokerageTwo($orderInfo, $userInfo)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        // 获取上推广人
        $userInfoTwo = $userServices->get((int)$userInfo['spread_uid']);
        // 上推广人不存在 或者 上推广人没有上级  或者 当用用户上上级时自己  直接返回
        if (!$userInfoTwo || !$userInfoTwo['spread_uid'] || $userInfoTwo['spread_uid'] == $orderInfo['uid']) {
            return true;
        }
        // 获取后台分销类型  1 指定分销 2 人人分销
        if (!$userServices->checkUserPromoter($userInfoTwo['spread_uid'])) {
            return true;
        }
        $cartId = is_string($orderInfo['cart_id']) ? json_decode($orderInfo['cart_id'], true) : $orderInfo['cart_id'];
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $brokeragePrice = $cartServices->getProductBrokerage($cartId, false);
        // 返佣金额小于等于0 直接返回不返佣金
        if ($brokeragePrice <= 0) {
            return true;
        }
        // 获取上上级推广员信息
        $spreadPrice = $userServices->value(['uid' => $userInfoTwo['spread_uid']], 'brokerage_price');
        // 获取上上级推广员返佣之后余额
        $balance = bcadd($spreadPrice, $brokeragePrice, 2);
        // 添加返佣记录
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $res1 = $userBillServices->income('get_two_brokerage', $userInfoTwo['spread_uid'], [
            'nickname' => $userInfo['nickname'],
            'pay_price' => floatval($orderInfo['pay_price']),
            'number' => floatval($brokeragePrice)
        ], $balance, $orderInfo['id']);
        if ($res1) {
            /** @var UserBrokerageFrozenServices $frozenService */
            $frozenService = app()->make(UserBrokerageFrozenServices::class);
            $res1 = $frozenService->saveBrokage($userInfoTwo['spread_uid'], $brokeragePrice, $res1->id, $orderInfo['order_id']);
        }
        // 添加用户余额
        $res2 = $userServices->bcInc($userInfoTwo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        return $res1 && $res2;
    }

    /**
     * 赠送积分发送模板消息
     * @param $order
     * @param $give_integral
     * @param $integral
     */
    public function sendUserIntegral($order, $give_integral, $integral, $storeTitle)
    {
        Queue::instance()->do('sendUserIntegral')->job(RoutineTemplateJob::class)->data($order['uid'], $order, $storeTitle, $give_integral, $integral)->push();
    }

    /**
     * 确认收货发送模板消息
     * @param $order
     * @param $userInfo
     * @param $storeTitle
     * @return mixed
     */
    public function orderTakeSendAfter($order, $userInfo, $storeTitle)
    {
        /** @var WechatUserServices $wechatServices */
        $wechatServices = app()->make(WechatUserServices::class);
        if ($order['is_channel'] == 1) {
            //小程序
            $openid = $wechatServices->uidToOpenid($userInfo['uid'], 'routine');
            return Queue::instance()->do('sendOrderTakeOver')->job(RoutineTemplateJob::class)->data($openid, $order, $storeTitle)->push();
        } else {
            $openid = $wechatServices->uidToOpenid($userInfo['uid'], 'wecaht');
            return Queue::instance()->do('sendOrderTakeSuccess')->job(TemplateJob::class)->data($openid, $order, $storeTitle)->push();
        }
    }

    /**
     * 发送短信
     * @param $order
     * @param $storeTitle
     */
    public function smsSend($order, $storeTitle)
    {
        /** @var SmsSendServices $smsServices */
        $smsServices = app()->make(SmsSendServices::class);
        $switch = sys_config('confirm_take_over_switch') ? true : false;
        //模板变量
        $store_name = $storeTitle;
        $order_id = $order['order_id'];
        $smsServices->send($switch, $order['user_phone'], compact('store_name', 'order_id'), 'TAKE_DELIVERY_CODE');
    }

    /**
     * 发送确认收货管理员短信
     * @param $order
     */
    public function smsSendTake($order)
    {
        $switch = sys_config('admin_refund_switch') ? true : false;
        /** @var StoreServiceServices $services */
        $services = app()->make(StoreServiceServices::class);
        $adminList = $services->getStoreServiceOrderNotice();
        Queue::instance()->do('sendAdminConfirmTakeOver')->job(SmsAdminJob::class)->data($switch, $adminList, $order)->push();
    }

    /**
     * 赠送经验
     * @param $order
     * @param $userInfo
     * @return bool
     */
    public function gainUserExp($order, $userInfo)
    {
        if (!$userInfo) {
            return true;
        }
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $order_exp = 0;
        $res3 = true;
        $order_give_exp = sys_config('order_give_exp');
        if ($order['pay_price'] && $order_give_exp) {
            $order_exp = bcmul($order_give_exp, (string)$order['pay_price'], 2);
            $res3 = false != $userBillServices->income('order_give_exp', $order['uid'], $order_exp, $userInfo['exp'], $order['id']);
        }
        if ($order_exp > 0) {
            $exp = $userInfo['exp'] + $order_exp;
            $userInfo->exp = $exp;
            $res1 = false != $userInfo->save();
            $res = $res1 && $res3;
            return $res;
        }
        return true;
    }
}
