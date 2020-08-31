<?php

namespace app\adminapi\controller\v1\setting;

use app\adminapi\controller\AuthController;
use app\services\system\config\SystemConfigServices;
use app\services\system\config\SystemConfigTabServices;
use think\facade\App;

/**
 * 系统配置
 * Class SystemConfig
 * @package app\adminapi\controller\v1\setting
 */
class SystemConfig extends AuthController
{

    /**
     * SystemConfig constructor.
     * @param App $app
     * @param SystemConfigServices $services
     */
    public function __construct(App $app, SystemConfigServices $services)
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
            ['tab_id', 0],
            ['status', 1]
        ]);
        if (!$where['tab_id']) {
            return $this->fail('参数错误');
        }
        return $this->success($this->services->getConfigList($where));
    }

    /**
     * 显示创建资源表单页.
     * @param $type
     * @return \think\Response
     */
    public function create()
    {
        [$type, $tabId] = $this->request->getMore([
            [['type', 'd'], ''],
            [['tab_id', 'd'], 1]
        ], true);
        return $this->success($this->services->createFormRule($type, $tabId));
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save()
    {
        $data = $this->request->postMore([
            'menu_name',
            'type',
            'input_type',
            'config_tab_id',
            'parameter',
            'upload_type',
            'required',
            'width',
            'high',
            'value',
            'info',
            'desc',
            'sort',
            'status',
        ]);
        if (!$data['info']) return $this->fail('请输入配置名称');
        if (!$data['menu_name']) return $this->fail('请输入字段名称');
        if (!$data['desc']) return $this->fail('请输入配置简介');
        if ($data['sort'] < 0) {
            $data['sort'] = 0;
        }
        if ($data['type'] == 'text') {
            if (!$data['width']) return $this->fail('请输入文本框的宽度');
            if ($data['width'] <= 0) return $this->fail('请输入正确的文本框的宽度');
        }
        if ($data['type'] == 'textarea') {
            if (!$data['width']) return $this->fail('请输入多行文本框的宽度');
            if (!$data['high']) return $this->fail('请输入多行文本框的高度');
            if ($data['width'] < 0) return $this->fail('请输入正确的多行文本框的宽度');
            if ($data['high'] < 0) return $this->fail('请输入正确的多行文本框的宽度');
        }
        if ($data['type'] == 'radio' || $data['type'] == 'checkbox') {
            if (!$data['parameter']) return $this->fail('请输入配置参数');
            $this->services->valiDateRadioAndCheckbox($data);
            $data['value'] = json_encode($data['value']);
        }
        $config = $this->services->getOne(['menu_name' => $data['menu_name']]);
        if ($config) {
            $this->services->update($config['id'], $data, 'id');
        } else {
            $this->services->save($data);
        }
        \crmeb\services\CacheService::clear();
        return $this->success('添加配置成功!');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('参数错误，请重新打开');
        }
        $info = $this->services->getReadList((int)$id);
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
        return $this->success($this->services->editConfigForm((int)$id));
    }

    /**
     * 保存更新的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function update($id)
    {
        $type = request()->post('type');
        if ($type == 'text' || $type == 'textarea' || $type == 'radio' || ($type == 'upload' && (request()->post('upload_type') == 1 || request()->post('upload_type') == 3))) {
            $value = request()->post('value');
        } else {
            $value = request()->post('value/a');
        }
        $data = $this->request->postMore(['status', 'info', 'desc', 'sort', 'config_tab_id', 'required', 'parameter', ['value', $value], 'upload_type', 'input_type']);
        $data['value'] = json_encode($data['value']);
        if (!$this->services->get($id)) {
            return $this->fail('编辑的记录不存在!');
        }
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
        if ($status == '' || $id == 0) {
            return $this->fail('参数错误');
        }
        $this->services->update($id, ['status' => $status]);
        \crmeb\services\CacheService::clear();
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }

    /**
     * 基础配置
     * */
    public function edit_basics()
    {
        $tabId = $this->request->param('tab_id', 1);
        return $this->success($this->services->getConfigForm($tabId));
    }

    /**
     * 保存数据    true
     * */
    public function save_basics()
    {
        $post = $this->request->post();
        foreach ($post as $k => $v) {
            if (is_array($v)) {
                $res = $this->services->getUploadTypeList($k);
                foreach ($res as $kk => $vv) {
                    if ($kk == 'upload') {
                        if ($vv == 1 || $vv == 3) {
                            $post[$k] = $v[0];
                        }
                    }
                }
            }
        }
        foreach ($post as $k => $v) {
            if ($k === 'user_extract_min_price' && !preg_match('/[0-9]$/', $v)) {
                return $this->fail('提现最低金额只能为数字!');
            }
            $this->services->update($k, ['value' => json_encode($v)], 'menu_name');
        }
        \crmeb\services\CacheService::clear();
        return $this->success('修改成功');

    }

    /**
     * 获取系统设置头部分类
     * @param SystemConfigTabServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function header_basics(SystemConfigTabServices $services)
    {
        [$type] = $this->request->getMore([
            [['type', 'd'], 0],
        ], true);
        if ($type == 3) {//其它分类
            $config_tab = [];
        } else {
            $config_tab = $services->getConfigTab($type);
        }
        return $this->success(compact('config_tab'));
    }

}
