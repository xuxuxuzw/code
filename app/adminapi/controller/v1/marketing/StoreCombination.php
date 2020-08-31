<?php
/**
 * Created by PhpStorm.
 * User: lofate
 * Date: 2019/12/19
 * Time: 09:30
 */

namespace app\adminapi\controller\v1\marketing;

use app\adminapi\controller\AuthController;
use app\services\activity\StoreCombinationServices;
use app\services\activity\StorePinkServices;
use think\facade\App;


/**
 * 拼团管理
 * Class StoreCombination
 * @package app\admin\controller\store
 */
class StoreCombination extends AuthController
{
    public function __construct(App $app, StoreCombinationServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 拼团列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_show', ''],
            ['store_name', '']
        ]);
        $where['is_del'] = 0;
        $list = $this->services->systemPage($where);
        return $this->success($list);
    }

    /**
     * 拼团统计
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function statistics()
    {
        /** @var StorePinkServices $storePinkServices */
        $storePinkServices = app()->make(StorePinkServices::class);
        $info = $storePinkServices->getStatistics();
        return $this->success($info);
    }

    /**
     * 详情
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        $info = $info = $this->services->getInfo((int)$id);
        return $this->success(compact('info'));
    }

    /**
     * 保存新建的资源
     * @param int $id
     */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            [['product_id', 'd'], 0],
            [['title', 's'], ''],
            [['info', 's'], ''],
            [['unit_name', 's'], ''],
            ['image', ''],
            ['images', []],
            ['section_time', []],
            [['is_host', 'd'], 0],
            [['is_show', 'd'], 0],
            [['num', 'd'], 0],
            [['temp_id', 'd'], 0],
            [['effective_time', 'd'], 0],
            [['people', 'd'], 0],
            [['description', 's'], ''],
            ['attrs', []],
            ['items', []],
            ['num', 1],
            ['sort', 0]
        ]);
        $this->validate($data, \app\adminapi\validate\marketing\StoreCombinationValidate::class, 'save');
        $this->services->saveData($id, $data);
        return $this->success('保存成功');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $this->services->update($id, ['is_del' => 1]);
        return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        $this->services->update($id, ['is_show' => $status]);
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**拼团列表
     * @return mixed
     */
    public function combine_list()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['data', '', '', 'time'],
        ]);
        /** @var StorePinkServices $storePinkServices */
        $storePinkServices = app()->make(StorePinkServices::class);
        $list = $storePinkServices->systemPage($where);
        return $this->success($list);
    }

    /**拼团人列表
     * @return mixed
     */
    public function order_pink($id)
    {
        /** @var StorePinkServices $storePinkServices */
        $storePinkServices = app()->make(StorePinkServices::class);
        $list = $storePinkServices->getPinkMember($id);
        return $this->success(compact('list'));
    }

}
