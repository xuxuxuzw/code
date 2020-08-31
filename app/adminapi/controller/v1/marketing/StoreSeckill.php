<?php
/**
 * Created by PhpStorm.
 * User: lofate
 * Date: 2019/12/19
 * Time: 16:30
 */

namespace app\adminapi\controller\v1\marketing;

use app\adminapi\controller\AuthController;
use app\services\activity\StoreSeckillServices;
use think\facade\App;

/**
 * 限时秒杀  控制器
 * Class StoreSeckill
 * @package app\admin\controller\store
 */
class StoreSeckill extends AuthController
{
    public function __construct(App $app, StoreSeckillServices $services)
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
            [['status', 's'], ''],
            [['store_name', 's'], '']
        ]);
        return $this->success($this->services->systemPage($where));
    }

    /**
     * 详情
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        $info = $this->services->getInfo($id);
        return $this->success(compact('info'));
    }

    /**
     * 保存秒杀商品
     * @param int $id
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            [['product_id', 'd'], 0],
            [['title', 's'], ''],
            [['info', 's'], ''],
            [['unit_name', 's'], ''],
            ['image', ''],
            ['images', []],
            [['give_integral', 'd'], 0],
            ['section_time', []],
            [['is_hot', 'd'], 0],
            [['status', 'd'], 0],
            [['num', 'd'], 0],
            [['time_id', 'd'], 0],
            [['temp_id', 'd'], 0],
            [['sort', 'd'], 0],
            [['description', 's'], ''],
            ['attrs', []],
            ['items', []],
        ]);
        $this->validate($data, \app\adminapi\validate\marketing\StoreSeckillValidate::class, 'save');
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
        $this->services->update($id, ['status' => $status]);
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**
     * 秒杀时间段列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function time_list()
    {
        $list['data'] = sys_data('routine_seckill_time');
        return $this->success(compact('list'));
    }
}
