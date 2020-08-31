<?php
/**
 * Created by PhpStorm.
 * User: lofate
 * Date: 2019/11/28
 * TIME: 12:27
 */

namespace app\adminapi\controller\v1\finance;

use app\adminapi\controller\AuthController;
use app\services\user\UserExtractServices;
use think\facade\App;
use think\Request;

class UserExtract extends AuthController
{
    /**
     * UserExtract constructor.
     * @param App $app
     * @param UserExtractServices $services
     */
    public function __construct(App $app, UserExtractServices $services)
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
            ['status', ''],
            ['extract_type', ''],
            ['nireid', '', '', 'like'],
            ['data', '', '', 'time'],
        ]);
        return $this->success($this->services->index($where));
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->edit((int)$id));
    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        if (!$id) return $this->fail('缺少参数!');
        $id = (int)$id;
        $UserExtract = $this->services->getExtract($id);
        if (!$UserExtract) $this->fail('数据不存在');
        if ($UserExtract['extract_type'] == 'alipay') {
            $data = $this->request->postMore([
                'real_name',
                'mark',
                'extract_price',
                'alipay_code',
            ]);
            if (!$data['real_name']) return $this->fail('请输入姓名');
            if ($data['extract_price'] <= -1) return $this->fail('请输入提现金额');
            if (!$data['alipay_code']) return $this->fail('请输入支付宝账号');
        } else if ($UserExtract['extract_type'] == 'weixin') {
            $data = $this->request->postMore([
                'real_name',
                'mark',
                'extract_price',
                'wechat',
            ]);
            if ($data['extract_price'] <= -1) return $this->fail('请输入提现金额');
            if (!$data['wechat']) return $this->fail('请输入微信账号');
        } else {
            $data = $this->request->postMore([
                'real_name',
                'extract_price',
                'mark',
                'bank_code',
                'bank_address',
            ]);
            if (!$data['real_name']) return $this->fail('请输入姓名');
            if ($data['extract_price'] <= -1) return $this->fail('请输入提现金额');
            if (!$data['bank_code']) return $this->fail('请输入银行卡号');
            if (!$data['bank_address']) return $this->fail('请输入开户行');
        }
        return $this->success($this->services->update($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 拒绝
     * @param $id
     * @return mixed
     */
    public function refuse($id)
    {
        if (!$id) $this->fail('缺少参数');
        $data = $this->request->postMore([
            ['message', '']
        ]);
        return $this->success($this->services->refuse((int)$id, $data['message']) ? '操作成功' : '操作失败');
    }

    /**
     * 通过
     * @param $id
     * @return mixed
     */
    public function adopt($id)
    {
        if (!$id) $this->fail('缺少参数');
        return $this->success($this->services->adopt((int)$id) ? '操作成功' : '操作失败');
    }
}
