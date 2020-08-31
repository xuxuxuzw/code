<?php

namespace app\adminapi\controller\v1\freight;

use app\adminapi\controller\AuthController;
use app\services\shipping\ExpressServices;
use think\facade\App;

/**
 * 物流
 * Class Express
 * @package app\adminapi\controller\v1\freight
 */
class Express extends AuthController
{
    /**
     * 构造方法
     * Express constructor.
     * @param App $app
     * @param ExpressServices $services
     */
    public function __construct(App $app, ExpressServices $services)
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
            ['keyword', '']
        ]);
        return $this->success($this->services->getExpressList($where));
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
            'name',
            'code',
            ['sort', 0],
            ['is_show', 0]]);
        if (!$data['name']) return $this->fail('请输入公司名称');
        $this->services->save($data);
        return $this->success('添加公司成功!');
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
            'name',
            'code',
            ['sort', 0],
            ['is_show', 0]]);
        if (!$data['name']) return $this->fail('请输入公司名称');
        if (!$expressInfo = $this->services->get($id)) return $this->fail('编辑的记录不存在!');
        $expressInfo->name = $data['name'];
        $expressInfo->code = $data['code'];
        $expressInfo->sort = $data['sort'];
        $expressInfo->is_show = $data['is_show'];
        $expressInfo->save();
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
        if (!$id) return $this->fail('参数错误，请重新打开');
        $res = $this->services->delete($id);
        if (!$res)
            return $this->fail('删除失败,请稍候再试!');
        else
            return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param int $id
     * @param string $status
     * @return mixed
     */
    public function set_status($id = 0, $status = '')
    {
        if ($status == '' || $id == 0) return $this->fail('参数错误');
        $this->services->update($id, ['is_show' => $status]);
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }
}
