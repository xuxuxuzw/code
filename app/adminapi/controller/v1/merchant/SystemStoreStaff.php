<?php

namespace app\adminapi\controller\v1\merchant;

use app\services\system\store\SystemStoreServices;
use app\services\system\store\SystemStoreStaffServices;
use think\facade\App;
use app\adminapi\controller\AuthController;

/**
 * 店员
 * Class SystemStoreStaff
 * @package app\adminapi\controller\v1\merchant
 */
class SystemStoreStaff extends AuthController
{
    /**
     * 构造方法
     * SystemStoreStaff constructor.
     * @param App $app
     * @param SystemStoreStaffServices $services
     */
    public function __construct(App $app, SystemStoreStaffServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取店员列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            [['store_id', 'd'], 0],
        ]);
        return $this->success($this->services->getStoreStaffList($where));
    }

    /**
     * 门店列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function store_list(SystemStoreServices $services)
    {
        return $this->success($services->getStore());
    }

    /**
     * 店员新增表单
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 店员修改表单
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit()
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        return $this->success($this->services->updateForm($id));
    }

    /**
     * 保存店员信息
     */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            ['image', ''],
            ['uid', 0],
            ['avatar', ''],
            ['store_id', ''],
            ['staff_name', ''],
            ['phone', ''],
            ['verify_status', 1],
            ['status', 1],
        ]);

        if (!$id) {
            if ($data['image'] == '') {
                return $this->fail('请选择用户');
            }
            if ($this->services->count(['uid' => $data['uid']])) {
                return $this->fail('添加的核销员用户已存在!');
            }
            $data['uid'] = $data['image']['uid'];
            $data['avatar'] = $data['image']['image'];
        }
        if ($data['uid'] == 0) {
            return $this->fail('请选择用户');
        }
        if ($data['store_id'] == '') {
            return $this->fail('请选择所属提货点');
        }
        unset($data['image']);
        if ($id) {
            $res = $this->services->update($id, $data);
            if ($res) {
                return $this->success('编辑成功');
            } else {
                return $this->fail('编辑失败');
            }
        } else {
            $data['add_time'] = time();
            $res = $this->services->save($data);
            if ($res) {
                return $this->success('核销员添加成功');
            } else {
                return $this->fail('核销员添加失败，请稍后再试');
            }
        }
    }

    /**
     * 设置单个店员是否开启
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function set_show($is_show = '', $id = '')
    {
        if ($is_show == '' || $id == '') {
            $this->fail('缺少参数');
        }
        $res = $this->services->update($id, ['status' => (int)$is_show]);
        if ($res) {
            return $this->success($is_show == 1 ? '开启成功' : '关闭成功');
        } else {
            return $this->fail($is_show == 1 ? '开启失败' : '关闭失败');
        }
    }

    /**
     * 删除店员
     * @param $id
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('数据不存在');
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else
            return $this->success('删除成功!');
    }
}
