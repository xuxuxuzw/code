<?php

namespace app\adminapi\controller\v1\product;

use app\adminapi\controller\AuthController;
use app\services\product\sku\StoreProductRuleServices;
use think\facade\App;

/**
 * 规则管理
 * Class StoreProductRule
 * @package app\adminapi\controller\v1\product
 */
class StoreProductRule extends AuthController
{

    public function __construct(App $app, StoreProductRuleServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 规格列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['rule_name', '']
        ]);
        $list = $this->services->getList($where);
        return $this->success($list);
    }

    /**
     * 保存规格
     * @param $id
     * @return mixed
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['rule_name', ''],
            ['spec', []]
        ]);
        $this->services->save($id, $data);
        return $this->success('保存成功!');
    }

    /**
     * 获取规格信息
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        $info = $this->services->getInfo($id);
        return $this->success($info);
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete()
    {
        [$ids] = $this->request->postMore([
            ['ids', '']
        ], true);
        $this->services->del((string)$ids);
        return $this->success('删除成功!');
    }
}
