<?php

namespace app\adminapi\controller\v1\setting;

use app\adminapi\controller\AuthController;
use app\services\shipping\ShippingTemplatesServices;
use app\services\shipping\SystemCityServices;
use think\facade\App;

/**
 * 运费模板
 * Class ShippingTemplates
 * @package app\adminapi\controller\v1\setting
 */
class ShippingTemplates extends AuthController
{
    /**
     * 构造方法
     * ShippingTemplates constructor.
     * @param App $app
     * @param ShippingTemplatesServices $services
     */
    public function __construct(App $app, ShippingTemplatesServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 运费模板列表
     * @return mixed
     */
    public function temp_list()
    {
        $where = $this->request->getMore([
            [['name', 's'], '']
        ]);
        return $this->success($this->services->getShippingList($where));
    }

    /**
     * 修改
     * @return string
     * @throws \Exception
     */
    public function edit($id)
    {
        return $this->success($this->services->getShipping((int)$id));
    }

    /**
     * 保存或者修改
     * @param int $id
     */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            [['region_info', 'a'], []],
            [['appoint_info', 'a'], []],
            [['sort', 'd'], 0],
            [['type', 'd'], 0],
            [['name', 's'], ''],
            [['appoint', 'd'], 0],
        ]);
        $this->validate($data, \app\adminapi\validate\setting\ShippingTemplatesValidate::class, 'save');
        $temp['name'] = $data['name'];
        $temp['type'] = $data['type'];
        $temp['appoint'] = $data['appoint'];
        $temp['sort'] = $data['sort'];
        $temp['add_time'] = time();
        $this->services->save((int)$id, $temp, $data);
        return $this->success('添加成功!');
    }

    /**
     * 删除运费模板
     */
    public function delete()
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        if ($id == 1) {
            return $this->fail('默认模板不能删除');
        } else {
            $this->services->detete($id);
            return $this->success('删除成功');
        }
    }

    /**
     * 城市数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function city_list(SystemCityServices $services)
    {
        return $this->success($services->getShippingCity());
    }
}