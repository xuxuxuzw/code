<?php

namespace app\adminapi\controller\v1\setting;

use think\facade\App;
use app\adminapi\controller\AuthController;
use app\services\system\config\SystemGroupDataServices;
use app\services\system\config\SystemGroupServices;

/**
 * 数据管理
 * Class SystemGroupData
 * @package app\adminapi\controller\v1\setting
 */
class SystemGroupData extends AuthController
{
    /**
     * 构造方法
     * SystemGroupData constructor.
     * @param App $app
     * @param SystemGroupDataServices $services
     */
    public function __construct(App $app, SystemGroupDataServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取数据列表头
     * @return mixed
     */
    public function header(SystemGroupServices $services)
    {
        $gid = $this->request->param('gid/d');
        if (!$gid) return $this->fail('参数错误');
        return $this->success($services->getGroupDataTabHeader($gid));
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['gid', 0],
            ['status', ''],
        ]);
        return $this->success($this->services->getGroupDataList($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $gid = $this->request->param('gid/d');
        if ($this->services->isGroupGidSave($gid, 4, 'index_categy_images')) {
            return $this->fail('不能大于四个！');
        }
        return $this->success($this->services->createForm($gid));
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save(SystemGroupServices $services)
    {
        $params = request()->post();
        $fields = $services->getValueFields((int)$params['gid']);
        $value = [];
        foreach ($params as $key => $param) {
            foreach ($fields as $index => $field) {
                if ($key == $field["title"]) {
                    if ($param == "")
                        return $this->fail($field["name"] . "不能为空！");
                    else {
                        $value[$key]["type"] = $field["type"];
                        $value[$key]["value"] = $param;
                    }
                }
            }
        }
        $data = [
            "gid" => $params['gid'],
            "add_time" => time(),
            "value" => json_encode($value),
            "sort" => $params["sort"],
            "status" => $params["status"]
        ];
        $this->services->save($data);
        \crmeb\services\CacheService::clear();
        return $this->success('添加数据成功!');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        $gid = $this->request->param('gid/d');
        return $this->success($this->services->updateForm($gid, (int)$id));
    }

    /**
     * 保存更新的资源
     *
     * @param \think\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(SystemGroupServices $services, $id)
    {
        $groupData = $this->services->get($id);
        $fields = $services->getValueFields((int)$groupData["gid"]);
        $params = request()->post();
        foreach ($params as $key => $param) {
            foreach ($fields as $index => $field) {
                if ($key == $field["title"]) {
                    if ($param == '')
                        return $this->fail($field["name"] . "不能为空！");
                    else {
                        $value[$key]["type"] = $field["type"];
                        $value[$key]["value"] = $param;
                    }
                }
            }
        }
        $data = [
            "value" => json_encode($value),
            "sort" => $params["sort"],
            "status" => $params["status"]
        ];
        $this->services->update($id, $data);
        \crmeb\services\CacheService::clear();
        return $this->success('修改成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else {
            \crmeb\services\CacheService::clear();
            return $this->success('删除成功!');
        }
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        if ($status == '' || $id == 0) return $this->fail('参数错误');
        $this->services->update($id, ['status' => $status]);
        \crmeb\services\CacheService::clear();
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }
}
