<?php

namespace app\adminapi\controller\v1\marketing;

use app\adminapi\controller\AuthController;
use app\services\coupon\StoreCouponIssueServices;
use think\facade\App;

/**
 * 已发布优惠券管理
 * Class StoreCouponIssue
 * @package app\adminapi\controller\v1\marketing
 */
class StoreCouponIssue extends AuthController
{
    public function __construct(App $app, StoreCouponIssueServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', 1],
            ['coupon_title', ''],
        ]);
        $list = $this->services->getCouponIssueList($where);
        return $this->success($list);
    }

    /**
     * 删除
     * @param string $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->services->update($id, ['is_del' => 1]);
        return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit($id)
    {
        return $this->success($this->services->createForm($id));
    }

    /**
     * 修改状态
     * @param $id
     * @return mixed
     */
    public function status($id)
    {
        $data = $this->request->postMore([
            'status'
        ]);
        $this->services->update($id, $data);
        return $this->success('修改成功');
    }

    /**
     * 领取记录
     * @param string $id
     * @return mixed|string
     */
    public function issue_log($id)
    {
        $list = $this->services->issueLog($id);
        return $this->success($list);
    }
}
