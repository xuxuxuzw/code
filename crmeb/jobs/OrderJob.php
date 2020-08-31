<?php


namespace crmeb\jobs;

use app\services\activity\StoreBargainServices;
use app\services\activity\StoreCombinationServices;
use app\services\activity\StoreSeckillServices;
use app\services\message\service\StoreServiceServices;
use app\services\message\sms\SmsSendServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;
use app\services\user\UserLabelRelationServices;
use app\services\user\UserLevelServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use crmeb\basic\BaseJob;
use crmeb\services\WechatService;
use crmeb\services\workerman\ChannelService;
use think\facade\Log;

/**
 * 订单消息队列
 * Class OrderJob
 * @package crmeb\jobs
 */
class OrderJob extends BaseJob
{
    /**
     * 执行订单支付成功发送消息
     * @param $order
     * @return bool
     */
    public function doJob($order)
    {
        //更新用户支付订单数量
        try {
            $this->setUserPayCountAndPromoter($order);
        } catch (\Throwable $e) {
            Log::error('更新用户订单数失败,失败原因:' . $e->getMessage());
        }
        //增加用户标签
        try {
            $this->setUserLabel($order);
        } catch (\Throwable $e) {
            Log::error('用户标签添加失败,失败原因:' . $e->getMessage());
        }
        //发送模版消息、客服消息、短信、小票打印给客户和管理员
        try {
            $this->sendServicesAndTemplate($order);
        } catch (\Throwable $e) {
            Log::error('发送客服消息,短信消息失败,失败原因:' . $e->getMessage());
        }
        //打印小票
        $switch = sys_config('pay_success_printing_switch') ? true : false;
        if($switch) {
            try {
                /** @var StoreOrderServices $orderServices */
                $orderServices = app()->make(StoreOrderServices::class);
                $orderServices->orderPrint($order, $order['cart_id']);
            } catch (\Throwable $e) {
                Log::error('打印小票发生错误,错误原因:' . $e->getMessage());
            }
        }
        //支付成功发送短信
        $this->mssageSendPaySuccess($order);
        //检测会员等级
        try {
            /** @var UserLevelServices $levelServices */
            $levelServices = app()->make(UserLevelServices::class);
            $levelServices->detection((int)$order['uid']);
        } catch (\Throwable $e) {
            Log::error('会员等级升级失败,失败原因:' . $e->getMessage());
        }
        //向后台发送新订单消息
        try {
            ChannelService::instance()->send('NEW_ORDER', ['order_id' => $order['order_id']]);
        } catch (\Throwable $exception) {
        };
        return true;
    }

    /**
     * 设置用户购买次数和检测时候成为推广人
     * @param $order
     */
    public function setUserPayCountAndPromoter($order)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->get($order['uid']);
        if ($userInfo) {
            $userInfo->pay_count = $userInfo->pay_count + 1;
            if (!$userInfo->is_promoter) {
                /** @var StoreOrderServices $orderServices */
                $orderServices = app()->make(StoreOrderServices::class);
                $price = $orderServices->sum(['paid' => 1, 'refund_status' => 0, 'uid' => $userInfo['uid']], 'pay_price');
                $status = is_brokerage_statu($price);
                if ($status) {
                    $userInfo->is_promoter = 1;
                }
            }
            $userInfo->save();
        }
    }

    /**
     * 设置用户购买的标签
     * @param $order
     */
    public function setUserLabel($order)
    {
        /** @var StoreOrderCartInfoServices $cartInfoServices */
        $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $productIds = $cartInfoServices->getCartColunm(['oid' => $order['id']], 'product_id', '');
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $label = $productServices->getColumn([['id', 'in', $productIds]], 'label_id');
        $labelIds = array_unique(explode(',', implode(',', $label)));
        /** @var UserLabelRelationServices $labelServices */
        $labelServices = app()->make(UserLabelRelationServices::class);
        $where = [
            ['label_id', 'in', $labelIds],
            ['uid', '=', $order['uid']]
        ];
        $data = [];
        $userLabel = $labelServices->getColumn($where, 'label_id');
        foreach ($labelIds as $item) {
            if (!in_array($item, $userLabel)) {
                $data[] = ['uid' => $order['uid'], 'label_id' => $item];
            }
        }
        $re = true;
        if($data){
            $re = $labelServices->saveAll($data);
        }
        return $re;
    }

    /**
     * 发送模板消息和客服消息
     * @param $order
     * @return bool
     */
    public function sendServicesAndTemplate($order)
    {
        try {
            /** @var WechatUserServices $wechatUserServices */
            $wechatUserServices = app()->make(WechatUserServices::class);
            if (in_array($order['is_channel'], [0, 2])) {//公众号发送模板消息
                $openid = $wechatUserServices->uidToOpenid($order['uid'], 'wechat');
                if (!$openid) {
                    return true;
                }
                $wechatTemplate = new WechatTemplateJob();
                $wechatTemplate->sendOrderPaySuccess($openid, $order);
                //订单支付成功后给客服发送模版消息
                $wechatTemplate->sendServiceNotice($openid, $order);
                //订单支付成功后给客服发送客服消息
                $this->sendOrderPaySuccessCustomerService($order, 1);
            } else if (in_array($order['is_channel'], [1, 2])) {//小程序发送模板消息
                $openid = $wechatUserServices->uidToOpenid($order['uid'], 'routine');
                if (!$openid) {
                    return true;
                }
                $tempJob = new RoutineTemplateJob();
                $tempJob->sendOrderSuccess($openid, $order['pay_price'], $order['order_id']);
                //订单支付成功后给客服发送客服消息
                $this->sendOrderPaySuccessCustomerService($order, 0);
            }
        } catch (\Exception $e) {
        }

    }

    /**
     * 订单支付成功后给客服发送客服消息
     * @param $order
     * @param int $type 1 公众号 0 小程序
     * @return string
     */
    public function sendOrderPaySuccessCustomerService($order, $type = 0)
    {
        /** @var StoreServiceServices $services */
        $services = app()->make(StoreServiceServices::class);
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $serviceOrderNotice = $services->getStoreServiceOrderNotice();
        if (count($serviceOrderNotice)) {
            /** @var StoreProductServices $services */
            $services = app()->make(StoreProductServices::class);
            /** @var StoreSeckillServices $seckillServices */
            $seckillServices = app()->make(StoreSeckillServices::class);
            /** @var StoreCombinationServices $pinkServices */
            $pinkServices = app()->make(StoreCombinationServices::class);
            /** @var StoreBargainServices $bargainServices */
            $bargainServices = app()->make(StoreBargainServices::class);
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            /** @var SmsSendServices $smsServices */
            $smsServices = app()->make(SmsSendServices::class);
            $switch = sys_config('admin_pay_success_switch') ? true : false;
            foreach ($serviceOrderNotice as $key => $item) {
                $admin_name = $item['nickname'];
                $order_id = $order['order_id'];
                $smsServices->send($switch, $item['phone'], compact('admin_name', 'order_id'), 'ADMIN_PAY_SUCCESS_CODE');
                $userInfo = $wechatUserServices->getOne(['uid'=>$item['uid'],'user_type'=>'wechat']);
                if ($userInfo) {
                    $userInfo = $userInfo->toArray();
                    if ($userInfo['subscribe'] && $userInfo['openid']) {
                        if ($item['customer']) {
                            // 统计管理开启  推送图文消息
                            $head = '订单提醒 订单号：' . $order['order_id'];
                            $url = sys_config('site_url') . '/pages/admin/orderDetail/index?id=' . $order['order_id'];
                            $description = '';
                            $image = sys_config('site_logo');
                            if (isset($order['seckill_id']) && $order['seckill_id'] > 0) {
                                $description .= '秒杀商品：' . $seckillServices->value(['id' => $order['seckill_id']], 'title');
                                $image = $seckillServices->value(['id' => $order['seckill_id']], 'image');
                            } else if (isset($order['combination_id']) && $order['combination_id'] > 0) {
                                $description .= '拼团商品：' . $pinkServices->value(['id' => $order['combination_id']], 'title');
                                $image = $pinkServices->value(['id' => $order['combination_id']], 'image');
                            } else if (isset($order['bargain_id']) && $order['bargain_id'] > 0) {
                                $title = $bargainServices->value(['id' => $order['bargain_id']], 'title');
                                $description .= '砍价商品：' . $title;
                                $image = $bargainServices->value(['id' => $order['bargain_id']], 'image');
                            } else {
                                $productIds = $cartInfoServices->getCartIdsProduct($order['cart_id']);
                                $storeProduct = $services->getProductArray([['id', 'in', $productIds]], 'image', 'id');
                                if (count($storeProduct)) {
                                    foreach ($storeProduct as $value) {
                                        $description .= $value['store_name'] . '  ';
                                        $image = $value['image'];
                                    }
                                }
                            }
                            $message = WechatService::newsMessage($head, $description, $url, $image);
                            try {
                                WechatService::staffService()->message($message)->to($userInfo['openid'])->send();
                            } catch (\Exception $e) {
                                Log::error($userInfo['nickname'] . '发送失败' . $e->getMessage());
                            }
                        } else {
                            // 推送文字消息
                            $head = "客服提醒：亲,您有一个新订单 \r\n订单单号:{$order['order_id']}\r\n支付金额：￥{$order['pay_price']}\r\n备注信息：{$order['mark']}\r\n订单来源：小程序";
                            if ($type) $head = "客服提醒：亲,您有一个新订单 \r\n订单单号:{$order['order_id']}\r\n支付金额：￥{$order['pay_price']}\r\n备注信息：{$order['mark']}\r\n订单来源：公众号";
                            try {
                                WechatService::staffService()->message($head)->to($userInfo['openid'])->send();
                            } catch (\Exception $e) {
                                Log::error($userInfo['nickname'] . '发送失败' . $e->getMessage());
                            }
                        }
                    }
                }

            }
        }
    }

    /**
     *  支付成功短信提醒
     * @param string $order_id
     */
    public function mssageSendPaySuccess($order)
    {
        $switch = sys_config('lower_order_switch') ? true : false;
        //模板变量
        $pay_price = $order['pay_price'];
        $order_id = $order['order_id'];
        /** @var SmsSendServices $smsServices */
        $smsServices = app()->make(SmsSendServices::class);
        $smsServices->send($switch, $order['user_phone'], compact('order_id', 'pay_price'), 'PAY_SUCCESS_CODE');
    }
}
