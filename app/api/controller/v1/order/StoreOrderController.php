<?php

namespace app\api\controller\v1\order;

use app\Request;
use app\services\system\admin\SystemAdminServices;
use app\services\activity\{
    StoreBargainServices, StorePinkServices, StoreSeckillServices
};
use app\services\coupon\StoreCouponIssueServices;
use app\services\order\{StoreCartServices,
    StoreOrderCartInfoServices,
    StoreOrderComputedServices,
    StoreOrderCreateServices,
    StoreOrderRefundServices,
    StoreOrderServices,
    StoreOrderSuccessServices,
    StoreOrderTakeServices
};
use app\services\pay\OrderPayServices;
use app\services\pay\YuePayServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\shipping\ShippingTemplatesServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\store\SystemStoreServices;
use crmeb\services\CacheService;
use crmeb\services\ExpressService;
use crmeb\services\UtilService;

/**
 * 订单控制器
 * Class StoreOrderController
 * @package app\api\controller\order
 */
class StoreOrderController
{

    /**
     * @var StoreOrderServices
     */
    protected $services;

    /**
     * StoreOrderController constructor.
     * @param StoreOrderServices $services
     */
    public function __construct(StoreOrderServices $services)
    {
        $this->services = $services;
    }

    /**
     * 订单确认
     * @param Request $request
     * @return mixed
     */
    public function confirm(Request $request, ShippingTemplatesServices $services)
    {
        if (!$services->get(1)) {
            return app('json')->fail('默认模板未配置，无法下单');
        }
        list($cartId, $new) = $request->postMore([
            'cartId',
            'new'
        ], true);
        if (!is_string($cartId) || !$cartId) {
            return app('json')->fail('请提交购买的商品');
        }
        $user = $request->user()->toArray();
        return app('json')->successful($this->services->getOrderConfirmData($user, $cartId, !!$new));
    }

    /**
     * 计算订单金额
     * @param Request $request
     * @param $key
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function computedOrder(Request $request, StoreOrderComputedServices $computedServices, $key)
    {
        if (!$key) return app('json')->fail('参数错误!');
        $uid = $request->uid();
        if ($this->services->be(['order_id|unique' => $key, 'uid' => $uid, 'is_del' => 0]))
            return app('json')->status('extend_order', '订单已生成', ['orderId' => $key, 'key' => $key]);
        list($addressId, $couponId, $payType, $useIntegral, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $shipping_type) = $request->postMore([
            'addressId', 'couponId', ['payType', 'yue'], ['useIntegral', 0], 'mark', ['combinationId', 0], ['pinkId', 0], ['seckill_id', 0], ['bargainId', ''],
            ['shipping_type', 1],
        ], true);
        $payType = strtolower($payType);
        $cartGroup = $this->services->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) return app('json')->fail('订单已过期,请刷新当前页面!', true);

        $priceGroup = $computedServices->computedOrder($request->uid(), $key, $cartGroup, $addressId, $payType, !!$useIntegral, (int)$couponId, false, (int)$shipping_type);
        if ($priceGroup)
            return app('json')->status('NONE', 'ok', $priceGroup);
        else
            return app('json')->fail('计算失败');
    }

    /**
     * 订单创建
     * @param Request $request
     * @param $key
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function create(Request $request, StoreBargainServices $bargainServices, StorePinkServices $pinkServices, StoreOrderCreateServices $createServices, StoreSeckillServices $seckillServices, $key)
    {
        if (!$key) return app('json')->fail('参数错误!');
        $uid = (int)$request->uid();
        if ($this->services->be(['order_id|unique' => $key, 'uid' => $uid, 'is_del' => 0]))
            return app('json')->status('extend_order', '订单已生成', ['orderId' => $key, 'key' => $key]);
        [$addressId, $couponId, $payType, $useIntegral, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $from, $shipping_type, $real_name, $phone, $storeId, $news] = $request->postMore([
            [['addressId', 'd'], 0],
            [['couponId', 'd'], 0],
            ['payType', ''],
            ['useIntegral', 0],
            ['mark', ''],
            [['combinationId', 'd'], 0],
            [['pinkId', 'd'], 0],
            [['seckill_id', 'd'], 0],
            [['bargainId', 'd'], ''],
            ['from', 'weixin'],
            ['shipping_type', 1],
            ['real_name', ''],
            ['phone', ''],
            [['store_id', 'd'], 0],
            ['new', 0]
        ], true);
        $payType = strtolower($payType);

        //下单前砍价验证
        if ($bargainId) {
            $bargainServices->checkBargainUser((int)$bargainId, $uid);
        }

        if ($pinkId) {
            $pinkId = (int)$pinkId;
            if ($pinkServices->isPink($pinkId, $uid))
                return app('json')->status('ORDER_EXIST', '订单生成失败，你已经在该团内不能再参加了', ['orderId' => $this->services->getStoreIdPink($pinkId, $uid)]);
            if ($this->services->getIsOrderPink($pinkId, $uid))
                return app('json')->status('ORDER_EXIST', '订单生成失败，你已经参加该团了，请先支付订单', ['orderId' => $this->services->getStoreIdPink($pinkId, $uid)]);
        }

        if (!$this->services->checkPaytype($payType)) {
            return app('json')->fail('暂不支持该支付方式，请刷新页面或者联系管理员');
        }

        $isChannel = 1;
        if ($from == 'weixin')
            $isChannel = 0;
        elseif ($from == 'weixinh5')
            $isChannel = 2;
        $cartGroup = $this->services->getCacheOrderInfo($uid, $key);

        if (!$cartGroup) {
            return app('json')->fail('订单已过期,请刷新当前页面!');
        }

        if ($seckill_id) {
            $cartInfo = $cartGroup['cartInfo'];
            foreach ($cartInfo as $item) {
                if ($item['seckill_id'] && $item['product_attr_unique']) {
                    if (!$seckillServices->popSeckillStock($item['product_attr_unique'], 1, (int)$item['cart_num'])) {
                        return app('json')->fail('您购买的秒杀商品已不足' . $item['cart_num'] . $item['productInfo']['unit_name']);
                    }
                    if (!$seckillServices->checkSeckillStock((int)$item['seckill_id'], $item['product_attr_unique'], (int)$item['cart_num'], $key)) {
                        return app('json')->fail('库存不足,无法购买!');
                    }
                }
            }
        }

        $order = $createServices->createOrder($uid, $key, $cartGroup, $request->user()->toArray(), $addressId, $payType, !!$useIntegral, $couponId, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $isChannel, $shipping_type, $real_name, $phone, $storeId, !!$news);
        if ($order === false) {
            //回退占用
            $seckillServices->cancelOccupySeckillStock($cartGroup['cartInfo'], $key);
            return app('json')->fail('订单生成失败');
        }
        $orderId = $order['order_id'];
        $orderInfo = $this->services->getOne(['order_id' => $orderId]);
        if (!$orderInfo || !isset($orderInfo['paid'])) {
            return app('json')->fail('支付订单不存在!');
        }
        $orderInfo = $orderInfo->toArray();
        $info = compact('orderId', 'key');
        if ($orderId) {
            switch ($payType) {
                case "weixin":
                    if ($orderInfo['paid']) return app('json')->fail('支付已支付!');
                    //支付金额为0
                    if (bcsub((string)$orderInfo['pay_price'], '0', 2) <= 0) {
                        //创建订单jspay支付
                        /** @var StoreOrderSuccessServices $success */
                        $success = app()->make(StoreOrderSuccessServices::class);
                        $payPriceStatus = $success->zeroYuanPayment($orderInfo, $uid);
                        if ($payPriceStatus)//0元支付成功
                            return app('json')->status('success', '微信支付成功', $info);
                        else
                            return app('json')->status('pay_error');
                    } else {
                        /** @var OrderPayServices $payServices */
                        $payServices = app()->make(OrderPayServices::class);
                        $info['jsConfig'] = $payServices->orderPay($orderInfo, $from);
                        if ($from == 'weixinh5') {
                            return app('json')->status('wechat_h5_pay', '订单创建成功', $info);
                        } else {
                            return app('json')->status('wechat_pay', '订单创建成功', $info);
                        }
                    }
                    break;
                case 'yue':
                    /** @var YuePayServices $yueServices */
                    $yueServices = app()->make(YuePayServices::class);
                    $pay = $yueServices->yueOrderPay($orderInfo, $uid);
                    if ($pay['status'] === true)
                        return app('json')->status('success', '余额支付成功', $info);
                    else {
                        if (is_array($pay))
                            return app('json')->status($pay['status'], $pay['msg'], $info);
                        else
                            return app('json')->status('pay_error', $pay);
                    }
                    break;
                case 'offline':
                    return app('json')->status('success', '订单创建成功', $info);
                    break;
            }
        } else return app('json')->fail('订单生成失败!');
    }

    /**
     * 订单 再次下单
     * @param Request $request
     * @return mixed
     */
    public function again(Request $request, StoreCartServices $services)
    {
        list($uni) = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order) return app('json')->fail('订单不存在!');
        $order = $this->services->tidyOrder($order, true);
        $cateId = [];
        foreach ($order['cartInfo'] as $v) {
            if ($v['combination_id']) return app('json')->fail('拼团商品不能再来一单，请在拼团商品内自行下单!');
            else if ($v['bargain_id']) return app('json')->fail('砍价商品不能再来一单，请在砍价商品内自行下单!');
            else if ($v['seckill_id']) return app('json')->ail('秒杀商品不能再来一单，请在秒杀商品内自行下单!');
            else $cateId[] = $services->setCart($request->uid(), $v['product_id'], $v['cart_num'], isset($v['productInfo']['attrInfo']['unique']) ? $v['productInfo']['attrInfo']['unique'] : '', 'product', true);
        }
        if (!$cateId) return app('json')->fail('再来一单失败，请重新下单!');
        return app('json')->successful('ok', ['cateId' => implode(',', $cateId)]);
    }


    /**
     * 订单支付
     * @param Request $request
     * @return mixed
     */
    public function pay(Request $request, StorePinkServices $services, OrderPayServices $payServices, YuePayServices $yuePayServices)
    {
        [$uni, $paytype, $from] = $request->postMore([
            ['uni', ''],
            ['paytype', 'weixin'],
            ['from', 'weixin']
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order)
            return app('json')->fail('订单不存在!');
        if ($order['paid'])
            return app('json')->fail('该订单已支付!');
        if ($order['pink_id'] && $services->isPinkStatus($order['pink_id'])) {
            return app('json')->fail('该订单已失效!');
        }
        if ($from == 'weixin') {//0
            if (in_array($order->is_channel, [1, 2]))
                $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
        }
        if ($from == 'weixinh5') {//2
            if (in_array($order->is_channel, [0, 1]))
                $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
        }
        if ($from == 'routine') {//1
            if (in_array($order->is_channel, [0, 2]))
                $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
        }
        $order['pay_type'] = $paytype; //重新支付选择支付方式
        switch ($order['pay_type']) {
            case 'weixin':
                $jsConfig = $payServices->orderPay($order->toArray(), $from);
                if ($from == 'weixinh5') {
                    return app('json')->status('wechat_h5_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                } else {
                    return app('json')->status('wechat_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                }
                break;
            case 'yue':
                $pay = $yuePayServices->yueOrderPay($order->toArray(), $request->uid());
                if ($pay['status'] === true)
                    return app('json')->status('success', '余额支付成功');
                else {
                    if (is_array($pay))
                        return app('json')->status($pay['status'], $pay['msg']);
                    else
                        return app('json')->status('pay_error', $pay);
                }
                break;
            case 'offline':
                if ($this->services->setOrderTypePayOffline($order['order_id']))
                    return app('json')->status('success', '订单创建成功');
                else
                    return app('json')->status('success', '支付失败');
                break;
        }
        return app('json')->fail('支付方式错误');
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['type', '', '', 'status'],
            ['search', '', '', 'real_name'],
        ]);
        $where['uid'] = $request->uid();
        $where['is_del'] = 0;
        $where['is_system_del'] = 0;
        return app('json')->successful($this->services->getOrderApiList($where));
    }

    /**
     * 订单详情
     * @param Request $request
     * @param $uni
     * @return mixed
     */
    public function detail(Request $request, $uni)
    {
        if (!strlen(trim($uni))) return app('json')->fail('参数错误');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        //是否开启门店自提
        $store_self_mention = sys_config('store_self_mention');
        //关闭门店自提后 订单隐藏门店信息
        if ($store_self_mention == 0) $order['shipping_type'] = 1;
        if ($order['verify_code']) {
            $verify_code = $order['verify_code'];
            $verify[] = substr($verify_code, 0, 4);
            $verify[] = substr($verify_code, 4, 4);
            $verify[] = substr($verify_code, 8);
            $order['_verify_code'] = implode(' ', $verify);
        }
        $order['add_time_y'] = date('Y-m-d', $order['add_time']);
        $order['add_time_h'] = date('H:i:s', $order['add_time']);
        /** @var SystemStoreServices $storeServices */
        $storeServices = app()->make(SystemStoreServices::class);
        $order['system_store'] = $storeServices->getStoreDispose($order['store_id']);
        if ($order['shipping_type'] === 2 && $order['verify_code']) {
            $name = $order['verify_code'] . '.jpg';
            /** @var SystemAttachmentServices $attachmentServices */
            $attachmentServices = app()->make(SystemAttachmentServices::class);
            $imageInfo = $attachmentServices->getInfo(['name' => $name]);
            $siteUrl = sys_config('site_url');
            if (!$imageInfo) {
                $imageInfo = UtilService::getQRCodePath($order['verify_code'], $name);
                if (is_array($imageInfo)) {
                    $attachmentServices->attachmentAdd($imageInfo['name'], $imageInfo['size'], $imageInfo['type'], $imageInfo['dir'], $imageInfo['thumb_path'], 1, $imageInfo['image_type'], $imageInfo['time'], 2);
                    $url = $imageInfo['dir'];
                } else
                    $url = '';
            } else $url = $imageInfo['att_dir'];
            if (isset($imageInfo['image_type']) && $imageInfo['image_type'] == 1) $url = $siteUrl . $url;
            $order['code'] = $url;
        }
        $order['mapKey'] = sys_config('tengxun_map_key');
        return app('json')->successful('ok', $this->services->tidyOrder($order, true, true));
    }

    /**
     * 订单删除
     * @param Request $request
     * @return mixed
     */
    public function del(Request $request)
    {
        [$uni] = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $res = $this->services->removeOrder($uni, (int)$request->uid());
        if ($res) {
            return app('json')->successful();
        } else {
            return app('json')->fail('删除失败');
        }
    }

    /**
     * 订单收货
     * @param Request $request
     * @return mixed
     */
    public function take(Request $request, StoreOrderTakeServices $services, StoreCouponIssueServices $issueServices)
    {
        list($uni) = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $services->takeOrder($uni, (int)$request->uid());
        if ($order) {
            $gain_integral = intval($order['gain_integral']);
            $gain_coupon = $issueServices->getUserIssuePrice($order['total_price']);
            return app('json')->successful(['gain_integral' => $gain_integral, 'gain_coupon' => $gain_coupon]);
        } else
            return app('json')->fail('收货失败');
    }


    /**
     * 订单 查看物流
     * @param Request $request
     * @param $uni
     * @return mixed
     */
    public function express(Request $request, StoreOrderCartInfoServices $services, $uni)
    {
        if (!$uni || !($order = $this->services->getUserOrderDetail($uni, $request->uid()))) return app('json')->fail('查询订单不存在!');
        if ($order['delivery_type'] != 'express' || !$order['delivery_id']) return app('json')->fail('该订单不存在快递单号!');
        $cacheName = $uni . $order['delivery_id'];
        $result = CacheService::get($cacheName, null);
        if ($result === NULL) {
            $result = ExpressService::query($order['delivery_id']);
            if (is_array($result) &&
                isset($result['result']) &&
                isset($result['result']['deliverystatus']) &&
                $result['result']['deliverystatus'] >= 3)
                $cacheTime = 0;
            else
                $cacheTime = 1800;
            CacheService::set($cacheName, $result, $cacheTime);
        }
        $orderInfo = [];
        $cartInfo = $services->getCartColunm(['oid' => $order['id']], 'cart_info', 'unique');
        $info = [];
        $cartNew = [];
        foreach ($cartInfo as $k => $cart) {
            $cart = json_decode($cart, true);
            $cartNew['cart_num'] = $cart['cart_num'];
            $cartNew['truePrice'] = $cart['truePrice'];
            $cartNew['productInfo']['image'] = $cart['productInfo']['image'];
            $cartNew['productInfo']['store_name'] = $cart['productInfo']['store_name'];
            $cartNew['productInfo']['unit_name'] = $cart['productInfo']['unit_name'] ?? '';
            array_push($info, $cartNew);
            unset($cart);
        }
        $orderInfo['delivery_id'] = $order['delivery_id'];
        $orderInfo['delivery_name'] = $order['delivery_name'];
        $orderInfo['delivery_type'] = $order['delivery_type'];
        $orderInfo['cartInfo'] = $info;
        return app('json')->successful(['order' => $orderInfo, 'express' => $result ? $result : []]);
    }

    /**
     * 订单评价
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function comment(Request $request, StoreOrderCartInfoServices $cartInfoServices, StoreProductReplyServices $replyServices)
    {

        $group = $request->postMore([
            ['unique', ''], ['comment', ''], ['pics', ''], ['product_score', 5], ['service_score', 5]
        ]);
        $unique = $group['unique'];
        unset($group['unique']);
        if (!$unique) return app('json')->fail('参数错误!');
        $cartInfo = $cartInfoServices->getOne(['unique' => $unique]);
        $uid = $request->uid();
        $user_info = $request->user();
        $group['nickname'] = $user_info['nickname'];
        $group['avatar'] = $user_info['avatar'];
        if (!$cartInfo) return app('json')->fail('评价商品不存在!');
        $orderUid = $this->services->value(['id' => $cartInfo['oid']], 'uid');
        if ($uid != $orderUid) return app('json')->fail('评价商品不存在!');
        if ($replyServices->be(['oid' => $cartInfo['oid'], 'unique' => $unique]))
            return app('json')->fail('该商品已评价!');
        $group['comment'] = htmlspecialchars(trim($group['comment']));
        if ($group['product_score'] < 1) return app('json')->fail('请为商品评分');
        else if ($group['service_score'] < 1) return app('json')->fail('请为商家服务评分');
        if ($cartInfo['cart_info']['combination_id']) $productId = $cartInfo['cart_info']['product_id'];
        else if ($cartInfo['cart_info']['seckill_id']) $productId = $cartInfo['cart_info']['product_id'];
        else if ($cartInfo['cart_info']['bargain_id']) $productId = $cartInfo['cart_info']['product_id'];
        else $productId = $cartInfo['product_id'];
        if ($group['pics']) $group['pics'] = json_encode(is_array($group['pics']) ? $group['pics'] : explode(',', $group['pics']));
        $group = array_merge($group, [
            'uid' => $uid,
            'oid' => $cartInfo['oid'],
            'unique' => $unique,
            'product_id' => $productId,
            'add_time' => time(),
            'reply_type' => 'product'
        ]);

        $res = $replyServices->save($group);
        if (!$res) {
            return app('json')->fail('评价失败!');
        }
        try {
            $this->services->checkOrderOver($replyServices, $cartInfoServices->getCartColunm(['oid' => $cartInfo['oid']], 'unique', ''), $cartInfo['oid']);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }

        /** @var SystemAdminServices $systemAdmin */
        $systemAdmin = app()->make(SystemAdminServices::class);
        $systemAdmin->adminNewPush();

        return app('json')->successful();
    }

    /**
     * 订单统计数据
     * @param Request $request
     * @return mixed
     */
    public function data(Request $request)
    {
        return app('json')->successful($this->services->getOrderData((int)$request->uid()));
    }

    /**
     * 订单退款理由
     * @return mixed
     */
    public function refund_reason()
    {
        $reason = sys_config('stor_reason') ?: [];//退款理由
        $reason = str_replace("\r\n", "\n", $reason);//防止不兼容
        $reason = explode("\n", $reason);
        return app('json')->successful($reason);
    }

    /**
     * 订单退款审核
     * @param Request $request
     * @return mixed
     */
    public function refund_verify(Request $request, StoreOrderRefundServices $services)
    {
        $data = $request->postMore([
            ['text', ''],
            ['refund_reason_wap_img', ''],
            ['refund_reason_wap_explain', ''],
            ['uni', '']
        ]);
        $uni = $data['uni'];
        unset($data['uni']);
        if ($data['refund_reason_wap_img'] != '') {
            $data['refund_reason_wap_img'] = explode(',', $data['refund_reason_wap_img']);
        } else {
            $data['refund_reason_wap_img'] = [];
        }
        if (!$uni || $data['text'] == '') return app('json')->fail('参数错误!');
        $res = $services->orderApplyRefund($this->services->getUserOrderDetail($uni, (int)$request->uid()), $data['text'], $data['refund_reason_wap_explain'], $data['refund_reason_wap_img']);
        if ($res)
            return app('json')->successful('提交申请成功');
        else
            return app('json')->fail('提交失败');
    }


    /**
     * 订单取消   未支付的订单回退积分,回退优惠券,回退库存
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel(Request $request)
    {
        list($id) = $request->postMore([['id', 0]], true);
        if (!$id) return app('json')->fail('参数错误');
        if ($this->services->cancelOrder($id, (int)$request->uid()))
            return app('json')->successful('取消订单成功');
        return app('json')->fail('取消订单失败');
    }


    /**
     * 订单商品信息
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function product(Request $request, StoreOrderCartInfoServices $services)
    {
        list($unique) = $request->postMore([['unique', '']], true);
        if (!$unique || !($cartInfo = $services->getOne(['unique' => $unique]))) return app('json')->fail('评价商品不存在!');
        $cartInfo = $cartInfo->toArray();
        $cartProduct = [];
        $cartProduct['cart_num'] = $cartInfo['cart_info']['cart_num'];
        $cartProduct['productInfo']['image'] = $cartInfo['cart_info']['productInfo']['image'] ?? '';
        $cartProduct['productInfo']['price'] = $cartInfo['cart_info']['productInfo']['price'] ?? 0;
        $cartProduct['productInfo']['store_name'] = $cartInfo['cart_info']['productInfo']['store_name'] ?? '';
        if (isset($cartInfo['cart_info']['productInfo']['attrInfo'])) {
            $cartProduct['productInfo']['attrInfo']['product_id'] = $cartInfo['cart_info']['productInfo']['attrInfo']['product_id'] ?? '';
            $cartProduct['productInfo']['attrInfo']['suk'] = $cartInfo['cart_info']['productInfo']['attrInfo']['suk'] ?? '';
            $cartProduct['productInfo']['attrInfo']['price'] = $cartInfo['cart_info']['productInfo']['attrInfo']['price'] ?? '';
            $cartProduct['productInfo']['attrInfo']['image'] = $cartInfo['cart_info']['productInfo']['attrInfo']['image'] ?? '';
        }
        $cartProduct['product_id'] = $cartInfo['cart_info']['product_id'] ?? 0;
        $cartProduct['combination_id'] = $cartInfo['cart_info']['combination_id'] ?? 0;
        $cartProduct['seckill_id'] = $cartInfo['cart_info']['seckill_id'] ?? 0;
        $cartProduct['bargain_id'] = $cartInfo['cart_info']['bargain_id'] ?? 0;
        $cartProduct['order_id'] = $this->services->value(['id' => $cartInfo['oid']], 'order_id');
        return app('json')->successful($cartProduct);
    }

}
