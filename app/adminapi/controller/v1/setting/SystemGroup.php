<?php

namespace app\adminapi\controller\v1\setting;

use app\services\system\config\SystemGroupDataServices;
use think\facade\App;
use app\adminapi\controller\AuthController;
use app\services\system\config\SystemGroupServices;

/**
 * 组合数据
 * Class SystemGroup
 * @package app\adminapi\controller\v1\setting
 */
class SystemGroup extends AuthController
{
    /**
     * 构造方法
     * SystemGroup constructor.
     * @param App $app
     * @param SystemGroupServices $services
     */
    public function __construct(App $app, SystemGroupServices $services)
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
            ['title', '']
        ]);
        return $this->success($this->services->getGroupList($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save()
    {
        $params = $this->request->postMore([
            ['name', ''],
            ['config_name', ''],
            ['info', ''],
            ['typelist', []],
        ]);

        //数据组名称判断
        if (!$params['name']) {
            return $this->fail('请输入数据组名称！');
        }
        if (!$params['config_name']) {
            return $this->fail('请输入配置名称！');
        }
        $data["name"] = $params['name'];
        $data["config_name"] = $params['config_name'];
        $data["info"] = $params['info'];
        //字段信息判断
        if (!count($params['typelist']))
            return $this->fail('字段至少存在一个！');
        else {
            $validate = ["name", "type", "title", "description"];
            foreach ($params["typelist"] as $key => $value) {
                foreach ($value as $name => $field) {
                    if (empty($field["value"]) && in_array($name, $validate))
                        return $this->fail("字段" . ($key + 1) . "：" . $field["placeholder"] . "不能为空！");
                    else
                        $data["fields"][$key][$name] = $field["value"];
                }
            }
        }
        $data["fields"] = json_encode($data["fields"]);
        $this->services->save($data);
        \crmeb\services\CacheService::clear();
        return $this->success('添加数据组成功!');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        $info = $this->services->get($id);
        $fields = json_decode($info['fields'], true);
        $type_list = [];
        foreach ($fields as $key => $v) {
            $type_list[$key]['name']['value'] = $v['name'];
            $type_list[$key]['title']['value'] = $v['title'];
            $type_list[$key]['type']['value'] = $v['type'];
            $type_list[$key]['param']['value'] = $v['param'];
        }
        $info['typelist'] = $type_list;
        unset($info['fields']);
        return $this->success(compact('info'));
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function update($id)
    {
        $params = $this->request->postMore([
            ['name', ''],
            ['config_name', ''],
            ['info', ''],
            ['typelist', []],
        ]);

        //数据组名称判断
        if (!$params['name']) return $this->fail('请输入数据组名称！');
        if (!$params['config_name']) return $this->fail('请输入配置名称！');
        //判断ID是否存在，存在就是编辑，不存在就是添加
        if (!$id) {
            if ($this->services->count(['config_name' => $params['config_name']])) {
                return $this->fail('数据关键字已存在！');
            }
        }
        $data["name"] = $params['name'];
        $data["config_name"] = $params['config_name'];
        $data["info"] = $params['info'];
        //字段信息判断
        if (!count($params['typelist']))
            return $this->fail('字段至少存在一个！');
        else {
            $validate = ["name", "type", "title", "description"];
            foreach ($params["typelist"] as $key => $value) {
                foreach ($value as $name => $field) {
                    if (empty($field["value"]) && in_array($name, $validate))
                        return $this->fail("字段" . ($key + 1) . "：" . $field["placeholder"] . "不能为空！");
                    else
                        $data["fields"][$key][$name] = $field["value"];
                }
            }
        }
        $data["fields"] = json_encode($data["fields"]);
        $this->services->update($id, $data);
        \crmeb\services\CacheService::clear();
        return $this->success('编辑数据组成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id, SystemGroupDataServices $services)
    {
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else {
            $services->delete($id, 'gid');
            return $this->success('删除成功!');
        }
    }
}
