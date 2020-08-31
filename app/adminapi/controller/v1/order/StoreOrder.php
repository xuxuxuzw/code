<?php

namespace app\adminapi\controller\v1\order;


use app\adminapi\controller\AuthController;
use app\adminapi\validate\order\StoreOrderValidate;
use app\services\order\{StoreOrderDeliveryServices,
    StoreOrderRefundServices,
    StoreOrderStatusServices,
    StoreOrderTakeServices,
    StoreOrderWriteOffServices,
    StoreOrderServices
};
use app\services\pay\OrderOfflineServices;
use app\services\shipping\ExpressServices;
use app\services\system\store\SystemStoreServices;
use app\services\user\UserServices;
use think\facade\App;
use crmeb\services\{
    CacheService,
    ExpressService
};

/**
 * 订单管理
 * Class StoreOrder
 * @package app\adminapi\controller\v1\order
 */
class StoreOrder extends AuthController
{
    /**
     * StoreOrder constructor.
     * @param App $app
     * @param StoreOrderServices $service
     */
    public function __construct(App $app, StoreOrderServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 获取订单类型数量
     * @return mixed
     */
    public function chart()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],
            [['type', 'd'], 0],
        ]);
        $data = $this->services->orderCount($where);
        return $this->success($data);
    }

    /**
     * 获取订单列表
     * @return mixed
     */
    public function lst()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['real_name', ''],
            ['is_del', 0],
            ['data', '', '', 'time'],
            ['type', ''],
            ['pay_type', ''],
            ['order', ''],
        ]);
        $where['shipping_type'] = 1;
        $where['is_system_del'] = 0;
        return $this->success($this->services->getOrderList($where, ['*'], ['pink']));
    }

    /**
     * 核销码核销
     * @param $code 核销码
     * @param int $confirm 确认核销 0=确认，1=核销
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function write_order(StoreOrderWriteOffServices $services)
    {
        [$code, $confirm] = $this->request->getMore([
            ['code', ''],
            ['confirm', 0]
        ], true);
        if (!$code) return $this->fail('Lack of write-off code');
        $orderInfo = $services->writeOffOrder($code, (int)$confirm);
        if ($confirm == 0) {
            return $this->success('验证成功', $orderInfo);
        }
        return $this->success('Write off successfully');
    }

    /**
     * 订单号核销
     * @param StoreOrderWriteOffServices $services
     * @param $order_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function write_update(StoreOrderWriteOffServices $services, $order_id)
    {
        $orderInfo = $this->services->getOne(['order_id' => $order_id, 'is_del' => 0, 'shipping_type' => 2]);
        if (!$orderInfo) {
            return $this->fail('核销订单未查到!');
        } else {
            if (!$orderInfo->verify_code) {
                return $this->fail('Lack of write-off code');
            }
            $orderInfo = $services->writeOffOrder($orderInfo->verify_code, 1);
            if ($orderInfo) {
                return $this->success('Write off successfully');
            } else {
                return $this->fail('核销失败!');
            }
        }
    }

    /**
     * 修改支付金额等
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function edit($id)
    {
        if (!$id) return $this->fail('Data does not exist!');
        return $this->success($this->services->updateForm($id));
    }

    /**
     * 修改订单
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        if (!$id) return $this->fail('Missing order ID');
        $data = $this->request->postMore([
            ['order_id', ''],
            ['total_price', 0],
            ['total_postage', 0],
            ['pay_price', 0],
            ['pay_postage', 0],
            ['gain_integral', 0],
        ]);

        $this->validate($data, StoreOrderValidate::class);

        if ($data['total_price'] < 0) return $this->fail('Please enter the total price');
        if ($data['pay_price'] < 0) return $this->fail('Please enter the actual payment amount');

        $this->services->updateOrder((int)$id, $data);
        return $this->success('Modified success');
    }

    /**
     * 获取快递公司
     * @return mixed
     */
    public function express(ExpressServices $services)
    {
        return $this->success($services->express(['is_show' => 1]));
    }

    /**
     * 批量删除用户已经删除的订单
     * @return mixed
     */
    public function del_orders()
    {
        [$ids] = $this->request->postMore([
            ['ids', []],
        ], true);
        if (!count($ids)) return $this->fail('请选择需要删除的订单');
        if ($this->services->getOrderIdsCount($ids))
            return $this->fail('您选择的的订单存在用户未删除的订单');
        if ($this->services->batchUpdate($ids, ['is_system_del' => 1]))
            return $this->success('SUCCESS');
        else
            return $this->fail('ERROR');
    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        if (!$id || !($orderInfo = $this->services->get($id)))
            return $this->fail('订单不存在');
        if (!$orderInfo->is_del)
            return $this->fail('订单用户未删除无法删除');
        $orderInfo->is_system_del = 1;
        if ($orderInfo->save())
            return $this->success('SUCCESS');
        else
            return $this->fail('ERROR');
    }

    /**
     * 订单发送货
     * @param $id 订单id
     * @return mixed
     */
    public function update_delivery($id, StoreOrderDeliveryServices $services)
    {
        $data = $this->request->postMore([
            ['type', 1],
            ['delivery_name', ''],
            ['delivery_id', ''],
            ['sh_delivery_name', ''],
            ['sh_delivery_id', ''],
        ]);
        $services->delivery((int)$id, $data);
        return $this->success('SUCCESS');
    }


    /**
     * 确认收货
     * @param $id 订单id
     * @return mixed
     * @throws \Exception
     */
    public function take_delivery(StoreOrderTakeServices $services, $id)
    {
        if (!$id) return $this->fail('缺少参数');
        $order = $this->services->get($id);
        if (!$order)
            return $this->fail('Data does not exist!');
        if ($order['status'] == 2)
            return $this->fail('不能重复收货!');
        if ($order['paid'] == 1 && $order['status'] == 1)
            $data['status'] = 2;
        else if ($order['pay_type'] == 'offline')
            $data['status'] = 2;
        else
            return $this->fail('请先发货或者送货!');

        if (!$this->services->update($id, $data)) {
            return $this->fail('收货失败,请稍候再试!');
        } else {
            $services->storeProductOrderUserTakeDelivery($order);
            return $this->success('收货成功');
        }
    }

    /**
     * 退款表单生成
     * @param $id 订单id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function refund(StoreOrderRefundServices $services, $id)
    {
        if (!$id) {
            return $this->fail('Data does not exist!');
        }
        return $this->success($services->refundOrderForm((int)$id));
    }

    /**
     * 订单退款
     * @param $id 订单id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update_refund(StoreOrderRefundServices $services, $id)
    {
        $data = $this->request->postMore([['refund_price', 0], ['type', 1]]);
        if (!$id) {
            return $this->fail('Data does not exist!');
        }
        $order = $this->services->get($id);
        if (!$order) {
            return $this->fail('Data does not exist!');
        }
        if ($order['pay_price'] == $order['refund_price']) {
            return $this->fail('已退完支付金额!不能再退款了');
        }
        if (!$data['refund_price']) {
            return $this->fail('请输入退款金额');
        }
        $refund_price = $data['refund_price'];
        $data['refund_price'] = bcadd($data['refund_price'], $order['refund_price'], 2);
        $bj = bccomp((string)$order['pay_price'], (string)$data['refund_price'], 2);
        if ($bj < 0) {
            return $this->fail('退款金额大于支付金额，请修改退款金额');
        }
        if ($data['type'] == 1) {
            $data['refund_status'] = 2;
        } else if ($data['type'] == 2) {
            $data['refund_status'] = 0;
        }
        $type = $data['type'];
        unset($data['type']);
        $refund_data['pay_price'] = $order['pay_price'];
        $refund_data['refund_price'] = $refund_price;
        if ($order['refund_price'] > 0) {
            $refund_data['refund_id'] = $order['order_id'] . rand(100, 999);
        }

        //退款处理
        $services->payOrderRefund($type, $order, $refund_data);

        //回退库存
        $services->regressionStock($order);

        //修改订单退款状态
        if ($this->services->update($id, $data)) {
            $services->storeProductOrderRefundY($data, $order, $refund_price);
            return $this->success('退款成功');
        } else {
            $services->storeProductOrderRefundYFasle((int)$id, $refund_price);
            return $this->fail('退款失败');
        }
    }

    /**
     * 订单详情
     * @param $id 订单id
     * @return mixed
     */
    public function order_info($id)
    {
        if (!$id || !($orderInfo = $this->services->get($id)))
            return $this->fail('订单不存在');
        /** @var UserServices $services */
        $services = app()->make(UserServices::class);
        $userInfo = $services->get($orderInfo['uid']);
        if (!$userInfo) return $this->fail('用户信息不存在');
        $userInfo = $userInfo->hidden(['pwd', 'add_ip', 'last_ip', 'login_type']);
        $userInfo['spread_name'] = '';
        if ($userInfo['spread_uid'])
            $userInfo['spread_name'] = $services->value(['uid' => $userInfo['spread_uid']], 'nickname');
        $orderInfo = $this->services->tidyOrder($orderInfo->toArray());
        if ($orderInfo['store_id'] && $orderInfo['shipping_type'] == 2) {
            /** @var  $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $orderInfo['_store_name'] = $storeServices->value(['id' => $orderInfo['store_id']], 'name');
        } else
            $orderInfo['_store_name'] = '';
        $userInfo = $userInfo->toArray();
        return $this->success(compact('orderInfo', 'userInfo'));
    }

    /**
     * 查询物流信息
     * @param $id 订单id
     * @return mixed
     */
    public function get_express($id)
    {
        if (!$id || !($orderInfo = $this->services->get($id)))
            return $this->fail('订单不存在');
        if ($orderInfo['delivery_type'] != 'express' || !$orderInfo['delivery_id'])
            return $this->fail('该订单不存在快递单号');

        $cacheName = $orderInfo['order_id'] . $orderInfo['delivery_id'];
        if (!$result = CacheService::get($cacheName, null)) {
            $result = ExpressService::query($orderInfo['delivery_id']);
            if (is_array($result) &&
                isset($result['result']) &&
                isset($result['result']['deliverystatus']) &&
                $result['result']['deliverystatus'] >= 3)
                $cacheTime = 0;
            else
                $cacheTime = 1800;
            CacheService::set($cacheName, $result, $cacheTime);
        }
        $data['delivery_name'] = $orderInfo['delivery_name'];
        $data['delivery_id'] = $orderInfo['delivery_id'];
        $data['result'] = $result['result']['list'] ?? [];
        return $this->success($data);
    }


    /**
     * 获取修改配送信息表单结构
     * @param $id 订单id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function distribution(StoreOrderDeliveryServices $services, $id)
    {
        if (!$id) {
            return $this->fail('订单不存在');
        }
        return $this->success($services->distributionForm((int)$id));
    }

    /**
     * 修改配送信息
     * @param $id  订单id
     * @return mixed
     */
    public function update_distribution(StoreOrderDeliveryServices $services, $id)
    {
        $data = $this->request->postMore([['delivery_name', ''], ['delivery_id', '']]);
        if (!$id) return $this->fail('Data does not exist!');
        $services->updateDistribution($id, $data);
        return $this->success('Modified success');
    }

    /**
     * 不退款表单结构
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function no_refund(StoreOrderRefundServices $services, $id)
    {
        if (!$id) return $this->fail('Data does not exist!');
        return $this->success($services->noRefundForm((int)$id));
    }

    /**
     * 订单不退款
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return mixed
     */
    public function update_un_refund(StoreOrderRefundServices $services, $id)
    {
        if (!$id || !($orderInfo = $this->services->get($id)))
            return $this->fail('订单不存在');
        [$refund_reason] = $this->request->postMore([['refund_reason', '']], true);
        if (!$refund_reason) {
            return $this->fail('请输入不退款原因');
        }
        $orderInfo->refund_reason = $refund_reason;
        $orderInfo->refund_status = 0;
        $orderInfo->save();
        $services->storeProductOrderRefundNo((int)$id, $refund_reason);
        $services->OrderRefundNoSendTemplate($orderInfo);
        return $this->success('Modified success');
    }

    /**
     * 线下支付
     * @param $id 订单id
     * @return mixed
     */
    public function pay_offline(OrderOfflineServices $services, $id)
    {
        if (!$id) return $this->fail('缺少参数');
        $res = $services->orderOffline((int)$id);
        if ($res) {
            return $this->success('Modified success');
        } else {
            return $this->fail('Modification failed');
        }
    }

    /**
     * 退积分表单获取
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function refund_integral(StoreOrderRefundServices $services, $id)
    {
        if (!$id)
            return $this->fail('订单不存在');
        return $this->success($services->refundIntegralForm((int)$id));
    }

    /**
     * 退积分保存
     * @param $id
     * @return mixed
     */
    public function update_refund_integral(StoreOrderRefundServices $services, $id)
    {
        [$back_integral] = $this->request->postMore([['back_integral', 0]], true);
        if (!$id || !($orderInfo = $this->services->get($id)))
            return $this->fail('订单不存在');
        if ($orderInfo->is_del) {
            return $this->fail('订单已删除无法退积分');
        }
        if ($back_integral <= 0)
            return $this->fail('请输入积分');
        if ($orderInfo['use_integral'] == $orderInfo['back_integral'])
            return $this->fail('已退完积分!不能再积分了');

        $data['back_integral'] = bcadd((string)$back_integral, (string)$orderInfo['back_integral'], 2);
        $bj = bccomp((string)$orderInfo['use_integral'], (string)$data['back_integral'], 2);
        if ($bj < 0) return $this->fail('退积分大于支付积分，请修改退积分');
        //积分退款处理
        $orderInfo->back_integral = $data['back_integral'];
        if ($services->refundIntegral($orderInfo, $back_integral)) {
            return $this->success('退积分成功');
        } else {
            return $this->fail('退积分失败');
        }
    }

    /**
     * 修改备注
     * @param $id
     * @return mixed
     */
    public function remark($id)
    {
        $data = $this->request->postMore([['remark', '']]);
        if (!$data['remark'])
            return $this->fail('请输入要备注的内容');
        if (!$id)
            return $this->fail('缺少参数');

        if (!$order = $this->services->get($id)) {
            return $this->fail('修改的订单不存在!');
        }
        $order->remark = $data['remark'];
        if ($order->save()) {
            return $this->success('备注成功');
        } else
            return $this->fail('备注失败');
    }

    /**
     * 获取订单状态列表并分页
     * @param $id
     * @return mixed
     */
    public function status(StoreOrderStatusServices $services, $id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($services->getStatusList(['oid' => $id])['list']);
    }

    /**
     * 易联云打印机打印
     * @param $id
     * @return mixed
     */
    public function order_print($id)
    {
        if (!$id) return $this->fail('缺少参数');
        $order = $this->services->get($id);
        if (!$order) {
            return $this->fail('订单没有查到,无法打印!');
        }
        $res = $this->services->orderPrint($order, $order->cart_id);
        if ($res) {
            return $this->success('打印成功');
        } else {
            return $this->fail('打印失败');
        }
    }

}
