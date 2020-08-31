<?php

namespace app\api\controller\v1\admin;

use app\Request;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use app\services\order\StoreOrderWapServices;
use app\services\order\StoreOrderWriteOffServices;
use app\services\pay\OrderOfflineServices;
use app\services\user\UserServices;

/**
 * 订单类
 * Class StoreOrderController
 * @package app\api\controller\admin\order
 */
class StoreOrderController
{
    /**
     * @var StoreOrderWapServices
     */
    protected $service;

    /**
     * StoreOrderController constructor.
     * @param StoreOrderWapServices $services
     */
    public function __construct(StoreOrderWapServices $services)
    {
        $this->service = $services;
    }

    /**
     *  订单数据统计
     * @param Request $request
     * @return mixed
     */
    public function statistics(StoreOrderServices $services)
    {
        $dataCount = $services->getOrderData();
        $dataPrice = $this->service->getOrderTimeData();
        $data = array_merge($dataCount, $dataPrice);
        return app('json')->successful($data);
    }

    /**
     * 订单每月统计数据
     * @param Request $request
     * @return mixed
     */
    public function data()
    {
        return app('json')->successful($this->service->getOrderDataPriceCount());
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['status', ''],
            ['is_del', 0],
            ['data', '', '', 'time'],
            ['type', '']
        ]);
        return app('json')->successful($this->service->getWapAdminOrderList($where));
    }

    /**
     * 订单详情
     * @param Request $request
     * @param $orderId
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail(Request $request, StoreOrderServices $services, UserServices $userServices, $orderId)
    {
        $order = $this->service->getOne(['order_id' => $orderId]);
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        $nickname = $userServices->value(['uid' => $order['uid']], 'nickname');
        $orderInfo = $services->tidyOrder($order, true);
        unset($orderInfo['uid'], $orderInfo['seckill_id'], $orderInfo['pink_id'], $orderInfo['combination_id'], $orderInfo['bargain_id'], $orderInfo['status'], $orderInfo['total_postage']);
        $orderInfo['nickname'] = $nickname;
        return app('json')->successful('ok', $orderInfo);
    }

    /**
     * 订单发货获取订单信息
     * @param Request $request
     * @param $orderId
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delivery_gain(UserServices $userServices, $orderId)
    {
        $order = $this->service->getOne(['order_id' => $orderId], 'real_name,user_phone,user_address,order_id,uid,status,paid');
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['paid']) {
            $order['nickname'] = $userServices->value(['uid' => $order['uid']], 'nickname');
            $order = $order->hidden(['uid', 'status', 'paid'])->toArray();
            return app('json')->successful('ok', $order);
        }
        return app('json')->fail('状态错误');
    }

    /**
     * 订单发货
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delivery_keep(Request $request, StoreOrderDeliveryServices $services)
    {
        [$order_id, $delivery_type, $delivery_name, $delivery_id] = $request->postMore([
            ['order_id', ''],
            ['delivery_type', 0],
            ['delivery_name', ''],
            ['delivery_id', ''],
        ], true);
        $order = $this->service->getOne(['order_id' => $order_id], 'id');
        if (!$order) return app('json')->fail('订单不存在');
        if ($delivery_type == 'express') {
            $data['type'] = 1;
            $data['delivery_type'] = $delivery_type;
            $data['delivery_name'] = $delivery_name;
            $data['delivery_id'] = $delivery_id;
        } else if ($delivery_type == 'send') {
            $data['type'] = 2;
            $data['sh_delivery_name'] = $delivery_name;
            $data['sh_delivery_id'] = $delivery_id;
        } else if ($delivery_type == 'fictitious') {
            $data['type'] = 3;
        }
        $services->delivery($order['id'], $data);
        return app('json')->successful('发货成功!');
    }

    /**
     * 订单改价
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function price(Request $request, StoreOrderStatusServices $services)
    {
        [$order_id, $price] = $request->postMore([
            ['order_id', ''],
            ['price', '']
        ], true);
        $order = $this->service->getOne(['order_id' => $order_id], 'id,paid,pay_price,order_id,total_price,total_postage,pay_postage,gain_integral');
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['paid']) {
            return app('json')->fail('订单已支付');
        }
        if ($price === '') return app('json')->fail('请填写实际支付金额');
        if ($price < 0) return app('json')->fail('实际支付金额不能小于0元');
        if ($order['pay_price'] == $price) return app('json')->successful('改价成功');
        $order->pay_price = $price;
        if (!$order->save())
            return app('json')->fail('改价失败');
        $services->save([
            'oid' => $order['id'],
            'change_type' => 'order_edit',
            'change_message' => '修改实际支付金额' . $price,
            'change_time' => time()
        ]);
        return app('json')->successful('改价成功');
    }

    /**
     * 订单备注
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function remark(Request $request)
    {
        [$order_id, $remark] = $request->postMore([
            ['order_id', ''],
            ['remark', '']
        ], true);
        $order = $this->service->getOne(['order_id' => $order_id], 'id,remark');
        if (!$order) return app('json')->fail('订单不存在');
        if (!strlen(trim($remark))) return app('json')->fail('请填写备注内容');
        $order->remark = $remark;
        if (!$order->save())
            return app('json')->fail('备注失败');
        return app('json')->successful('备注成功');
    }

    /**
     * 订单交易额/订单数量时间统计
     * @param Request $request
     * @return bool
     */
    public function time(Request $request)
    {
        list($start, $stop, $type) = $request->getMore([
            ['start', strtotime(date('Y-m'))],
            ['stop', time()],
            ['type', 1]
        ], true);
        if ($start == $stop) return false;
        if ($start > $stop) {
            $middle = $stop;
            $stop = $start;
            $start = $middle;
        }
        $space = bcsub($stop, $start, 0);//间隔时间段
        $front = bcsub($start, $space, 0);//第一个时间段
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        if ($type == 1) {//销售额
            $frontPrice = $orderService->sum([
                ['is_del', '=', 0],
                ['paid', '=', 1],
                ['refund_status', '=', 0],
                ['add_time', '>=', $front],
                ['add_time', '<', $start],
            ], 'pay_price');
            $afterPrice = $orderService->sum([
                ['is_del', '=', 0],
                ['paid', '=', 1],
                ['refund_status', '=', 0],
                ['add_time', '>=', $start],
                ['add_time', '<', $stop],
            ], 'pay_price');
            $chartInfo = $orderService->chartTimePrice($start, $stop);
            $data['chart'] = $chartInfo;//营业额图表数据
            $data['time'] = $afterPrice;//时间区间营业额
            $increase = (float)bcsub((string)$afterPrice, (string)$frontPrice, 2); //同比上个时间区间增长营业额
            $growthRate = abs($increase);
            if ($growthRate == 0) $data['growth_rate'] = 0;
            else if ($frontPrice == 0) $data['growth_rate'] = $growthRate;
            else $data['growth_rate'] = (int)bcmul((string)bcdiv((string)$growthRate, (string)$frontPrice, 2), '100', 0);//时间区间增长率
            $data['increase_time'] = abs($increase); //同比上个时间区间增长营业额
            $data['increase_time_status'] = $increase >= 0 ? 1 : 2; //同比上个时间区间增长营业额增长 1 减少 2
        } else {//订单数
            $frontNumber = $orderService->getCount([
                ['is_del', '=', 0],
                ['paid', '=', 1],
                ['refund_status', '=', 0],
                ['add_time', '>=', $front],
                ['add_time', '<', $start],
            ]);
            $afterNumber = $orderService->getCount([
                ['is_del', '=', 0],
                ['paid', '=', 1],
                ['refund_status', '=', 0],
                ['add_time', '>=', $start],
                ['add_time', '<', $stop],
            ]);
            $chartInfo = $orderService->chartTimeNumber($start, $stop);
            $data['chart'] = $chartInfo;//订单数图表数据
            $data['time'] = $afterNumber;//时间区间订单数
            $increase = $afterNumber - $frontNumber; //同比上个时间区间增长订单数
            $growthRate = abs($increase);
            if ($growthRate == 0) $data['growth_rate'] = 0;
            else if ($frontNumber == 0) $data['growth_rate'] = $growthRate;
            else $data['growth_rate'] = (int)bcmul((string)bcdiv((string)$growthRate, (string)$frontNumber, 2), '100', 0);//时间区间增长率
            $data['increase_time'] = abs($increase); //同比上个时间区间增长营业额
            $data['increase_time_status'] = $increase >= 0 ? 1 : 2; //同比上个时间区间增长营业额增长 1 减少 2
        }
        return app('json')->successful($data);
    }

    /**
     * 订单支付
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function offline(Request $request, OrderOfflineServices $services)
    {
        [$orderId] = $request->postMore([['order_id', '']], true);
        $orderInfo = $this->service->getOne(['order_id' => $orderId], 'id');
        if (!$orderInfo) return app('json')->fail('参数错误');
        $id = $orderInfo->id;
        $services->orderOffline((int)$id);
        return app('json')->successful('修改成功!');

    }

    /**
     * 订单退款
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refund(Request $request, StoreOrderRefundServices $services)
    {
        list($orderId, $price, $type) = $request->postMore([
            ['order_id', ''],
            ['price', '0'],
            ['type', 1],
        ], true);
        if (!strlen(trim($orderId))) return app('json')->fail('参数错误');
        $orderInfo = $this->service->getOne(['order_id' => $orderId]);
        if (!$orderInfo) return app('json')->fail('数据不存在!');
        if ($type == 1)
            $data['refund_status'] = 2;
        else if ($type == 2)
            $data['refund_status'] = 0;
        else
            return app('json')->fail('退款修改状态错误');
        if ($orderInfo['pay_price'] == 0 || $type == 2) {
            $orderInfo->refund_status = $data['refund_status'];
            $orderInfo->save();
            return app('json')->successful('修改退款状态成功!');
        }
        $orderInfo = $orderInfo->toArray();
        if ($orderInfo['pay_price'] == $orderInfo['refund_price']) return app('json')->fail('已退完支付金额!不能再退款了');
        if (!$price) {
            return app('json')->fail('请输入退款金额');
        }
        $data['refund_price'] = bcadd($price, $orderInfo['refund_price'], 2);
        $bj = bccomp((float)$orderInfo['pay_price'], (float)$data['refund_price'], 2);
        if ($bj < 0) {
            return app('json')->fail('退款金额大于支付金额，请修改退款金额');
        }
        $refundData['pay_price'] = $orderInfo['pay_price'];
        $refundData['refund_price'] = $price;

        //退款处理
        $services->payOrderRefund(1, $orderInfo, $refundData);
        //修改订单退款状态
        if ($this->service->update((int)$orderInfo['id'], $data)) {
            $services->storeProductOrderRefundY($data, $orderInfo, $price);
            return app('json')->success('退款成功');
        } else {
            $services->storeProductOrderRefundYFasle((int)$orderInfo['id'], $price);
            return app('json')->fail('退款失败');
        }
    }

    /**
     * 门店核销
     * @param Request $request
     */
    public function order_verific(Request $request, StoreOrderWriteOffServices $services)
    {
        list($verifyCode, $isConfirm) = $request->postMore([
            ['verify_code', ''],
            ['is_confirm', 0]
        ], true);
        if (!$verifyCode) return app('json')->fail('Lack of write-off code');
        $uid = $request->uid();
        $orderInfo = $services->writeOffOrder($verifyCode, (int)$isConfirm, $uid);
        if ($isConfirm == 0) {
            return app('json')->success($orderInfo);
        }
        return app('json')->success('Write off successfully');
    }
}
