<?php

namespace app\adminapi\controller\v1\setting;

use app\adminapi\controller\AuthController;
use app\services\system\config\SystemConfigServices;
use app\services\system\config\SystemConfigTabServices;
use think\facade\App;


/**
 * 配置分类
 * Class SystemConfigTab
 * @package app\adminapi\controller\v1\setting
 */
class SystemConfigTab extends AuthController
{
    /**
     * g构造方法
     * SystemConfigTab constructor.
     * @param App $app
     * @param SystemConfigTabServices $services
     */
    public function __construct(App $app, SystemConfigTabServices $services)
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
            ['title', '']
        ]);
        return $this->success($this->services->getConfgTabList($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save()
    {
        $data = $this->request->postMore([
            'eng_title',
            'status',
            'title',
            'icon',
            ['type', 0],
            ['sort', 0],
            ['pid', 0],
        ]);
        if (!$data['title']) return $this->fail('请输入按钮名称');
        $this->services->save($data);
        return $this->success('添加配置分类成功!');
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
        return $this->success($this->services->updateForm((int)$id));
    }

    /**
     * 保存更新的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            'title',
            'status',
            'eng_title',
            'icon',
            ['type', 0],
            ['sort', 0],
            ['pid', 0],
        ]);
        if (!$data['title']) return $this->fail('请输入分类昵称');
        if (!$data['eng_title']) return $this->fail('请输入分类字段');
        $this->services->update($id, $data);
        return $this->success('修改成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete(SystemConfigServices $services, $id)
    {
        if ($services->count(['tab_id' => $id])) {
            return $this->fail('存在下级配置，无法删除！');
        }
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else
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
        if ($status == '' || $id == 0) {
            return $this->fail('参数错误');
        }
        $this->services->update($id, ['status' => $status]);
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }
}
