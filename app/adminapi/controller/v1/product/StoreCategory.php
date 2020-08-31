<?php

namespace app\adminapi\controller\v1\product;

use app\adminapi\controller\AuthController;
use app\services\product\product\StoreCategoryServices;
use think\facade\App;

/**
 * 商品分类控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class StoreCategory extends AuthController
{
    /**
     * @var StoreCategoryServices
     */
    protected $service;

    /**
     * StoreCategory constructor.
     * @param App $app
     * @param StoreCategoryServices $service
     */
    public function __construct(App $app, StoreCategoryServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 分类列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_show', ''],
            ['pid', ''],
            ['cate_name', ''],
        ]);
        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 商品分类搜索
     * @return mixed
     */
    public function tree_list($type)
    {
        $list = $this->service->getTierList(1, $type);
        return $this->success($list);
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show = '', $id = '')
    {
        if ($is_show == '' || $id == '') return $this->fail('缺少参数');
        $this->service->setShow($id, $is_show);
        return $this->success($is_show == 1 ? '显示成功' : '隐藏成功');
    }

    /**
     * 生成新增表单
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->service->createForm());
    }

    /**
     * 保存新增分类
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['cate_name', ''],
            ['pic', ''],
            ['sort', 0],
            ['is_show', 0]
        ]);
        if (!$data['cate_name']) {
            return $this->fail('请输入分类名称');
        }
        $this->service->createData($data);
        return $this->success('添加分类成功!');
    }

    /**
     * 生成更新表单
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit($id)
    {
        return $this->success($this->service->editForm((int)$id));
    }

    /**
     * 更新分类
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['cate_name', ''],
            ['pic', ''],
            ['sort', 0],
            ['is_show', 0]
        ]);
        if (!$data['cate_name']) {
            return $this->fail('请输入分类名称');
        }
        $this->service->editData($id, $data);
        return $this->success('修改成功!');
    }

    /**
     * 删除分类
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->service->del((int)$id);
        return $this->success('删除成功!');
    }
}
