<?php
/**
 * Created by PhpStorm.
 * User: lofate
 * Date: 2019/12/18
 * Time: 12:24
 */

namespace app\adminapi\controller\v1\marketing;

use app\adminapi\controller\AuthController;
use app\services\activity\StoreBargainServices;
use app\services\activity\StoreBargainUserServices;
use think\facade\App;

/**
 * 砍价管理
 * Class StoreBargain
 * @package app\adminapi\controller\v1\marketing
 */
class StoreBargain extends AuthController
{
    public function __construct(App $app, StoreBargainServices $services)
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
            ['store_name', ''],
        ]);
        $where['is_del'] = 0;
        $list = $this->services->getStoreBargainList($where);
        return $this->success($list);
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['title', ''],
            ['info', ''],
            ['unit_name', ''],
            ['section_time', []],
            ['image', ''],
            ['images', []],
            ['bargain_max_price', 0],
            ['bargain_min_price', 0],
            ['sort', 0],
            ['give_integral', 0],
            ['is_hot', 0],
            ['status', 0],
            ['product_id', 0],
            ['description', ''],
            ['attrs', []],
            ['items', []],
            ['temp_id', 0],
            ['rule', ''],
            ['num', 1]
        ]);
        $data['num'] = 1;
        $this->validate($data, \app\adminapi\validate\marketing\StoreBargainValidate::class, 'save');
        $this->services->saveData($id, $data);
        return $this->success('保存成功');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $info = $this->services->getInfo($id);
        return $this->success(compact('info'));
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
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserService->UserBargainStatusFail($id);
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
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserService->UserBargainStatusFail($id);
        $this->services->update($id, ['status' => $status]);
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }
}
