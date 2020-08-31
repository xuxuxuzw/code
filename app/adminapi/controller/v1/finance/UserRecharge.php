<?php

namespace app\adminapi\controller\v1\finance;


use app\adminapi\controller\AuthController;
use app\services\user\UserRechargeServices;
use think\facade\App;

class UserRecharge extends AuthController
{
    /**
     * UserRecharge constructor.
     * @param App $app
     * @param UserRechargeServices $services
     */
    public function __construct(App $app, UserRechargeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['data', ''],
            ['paid', ''],
            ['nickname', ''],
        ]);
        return $this->success($this->services->getRechargeList($where));
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('缺少参数');
        $this->success($this->services->delRecharge((int)$id) ? '删除成功' : '删除失败');
    }

    /**
     * 获取用户充值数据
     * @return array
     */
    public function user_recharge()
    {
        $where = $this->request->getMore([
            ['data', ''],
            ['paid', ''],
            ['nickname', ''],
        ]);
        return $this->success($this->services->user_recharge($where));
    }

    /**退款表单
     * @param $id
     * @return mixed|void
     */
    public function refund_edit($id)
    {
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->refund_edit((int)$id));
    }

    /**
     * 退款操作
     * @param $id
     */
    public function refund_update($id)
    {
        $data = $this->request->postMore([
            'refund_price',
        ]);
        if (!$id) return $this->fail('数据不存在');
        if (!$data['refund_price']) return $this->fail('请输入退款金额');
        return $this->success($this->services->refund_update((int)$id, (string)$data['refund_price']) ? '退款成功' : '退款失败');
    }
}
