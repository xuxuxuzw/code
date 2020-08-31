<?php

namespace app\adminapi\controller\v1\user;

use app\adminapi\controller\AuthController;
use app\services\user\UserLabelServices;
use think\facade\App;

/**
 * 用户标签控制器
 * Class UserLabel
 * @package app\adminapi\controller\v1\user
 */
class UserLabel extends AuthController
{

    /**
     * UserLabel constructor.
     * @param App $app
     * @param UserLabelServices $service
     */
    public function __construct(App $app, UserLabelServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 标签列表
     * @return mixed
     */
    public function index()
    {
        return $this->success($this->service->getList());
    }

    /**
     * 添加修改标签表单
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function add()
    {
        list($id) = $this->request->getMore([
            ['id', 0],
        ], true);
        return $this->success($this->service->add((int)$id));
    }

    /**
     * 保存标签表单数据
     * @param int $id
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['label_name', ''],
        ]);
        if (!$data['label_name'] = trim($data['label_name'])) return $this->fail('会员标签不能为空！');
        $this->service->save((int)$data['id'], $data);
        return $this->success('保存成功');
    }

    /**
     * 删除
     * @param $id
     * @throws \Exception
     */
    public function delete()
    {
        list($id) = $this->request->getMore([
            ['id', 0],
        ], true);
        if (!$id) return $this->fail('数据不存在');
        $this->service->delLabel((int)$id);
        return $this->success('刪除成功！');
    }
}