<?php

namespace app\adminapi\controller\v1\user;

use app\services\user\UserServices;
use app\adminapi\controller\AuthController;
use think\facade\App;
use think\Request;


class User extends AuthController
{
    /**
     * user constructor.
     * @param App $app
     * @param UserServices $services
     */
    public function __construct(App $app, UserServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表头部
     *
     * @return \think\Response
     */
    public function type_header()
    {
        $list = $this->services->typeHead();
        return $this->success(compact('list'));
    }


    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['status', ''],
            ['pay_count', ''],
            ['is_promoter', ''],
            ['order', ''],
            ['data', ''],
            ['user_type', ''],
            ['country', ''],
            ['province', ''],
            ['city', ''],
            ['user_time_type', ''],
            ['user_time', ''],
            ['sex', ''],
            [['level', 0], 0],
            [['group_id', 'd'], 0],
            [['label_id', 'd'], 0]
        ]);
        return $this->success($this->services->index($where));
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
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        if (is_string($id)) {
            $id = (int)$id;
        }
        return $this->success($this->services->read($id));
    }

    /**
     * 赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function give_level($id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->giveLevel((int)$id));
    }

    /*
     * 执行赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function save_give_level($id)
    {
        if (!$id) return $this->fail('缺少参数');
        list($level_id) = $this->request->postMore([
            ['level_id', 0],
        ], true);
        return $this->success($this->services->saveGiveLevel((int)$id, (int)$level_id) ? '赠送成功' : '赠送失败');
    }

    /**
     * 清除会员等级
     * @param int $uid
     * @return json
     */
    public function del_level($id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->cleanUpLevel((int)$id) ? '清除成功' : '清除失败');
    }

    /**
     * 设置会员分组
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set_group()
    {
        list($uids) = $this->request->postMore([
            ['uids', []],
        ], true);
        if (!$uids) return $this->fail('缺少参数');
        return $this->success($this->services->setGroup($uids));
    }

    /**
     * 保存会员分组
     * @param $id
     * @return mixed
     */
    public function save_set_group()
    {
        list($group_id, $uids) = $this->request->postMore([
            ['group_id', 0],
            ['uids', ''],
        ], true);
        if (!$uids) return $this->fail('缺少参数');
        if (!$group_id) return $this->fail('请选择分组');
        $uids = explode(',', $uids);
        return $this->success($this->services->saveSetGroup($uids, (int)$group_id) ? '设置分组成功' : '设置分组失败或无改动');
    }

    /**
     * 设置用户标签
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set_label()
    {
        list($uids) = $this->request->postMore([
            ['uids', []],
        ], true);
        $uid = implode(',', $uids);
        if (!$uid) return $this->fail('缺少参数');
        return $this->success($this->services->setLabel($uids));
    }

    /**
     * 保存用户标签
     * @return mixed
     */
    public function save_set_label()
    {
        list($lables, $uids) = $this->request->postMore([
            ['label_id', []],
            ['uids', ''],
        ], true);
        if (!$uids) return $this->fail('缺少参数');
        if (!$lables) return $this->fail('请选择标签');
        $uids = explode(',', $uids);
        return $this->success($this->services->saveSetLabel($uids, $lables) ? '设置标签成功' : '设置标签失败');
    }

    /**
     * 编辑其他
     * @param $id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function edit_other($id)
    {
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->editOther((int)$id));
    }

    /**
     * 执行编辑其他
     * @param int $id
     * @return mixed
     */
    public function update_other($id)
    {
        $data = $this->request->postMore([
            ['money_status', 0],
            ['money', 0],
            ['integration_status', 0],
            ['integration', 0],
        ]);
        if (!$id) return $this->fail('数据不存在');
        $data['adminId'] = $this->adminId;
        $data['money'] = (string)$data['money'];
        $data['integration'] = (string)$data['integration'];
        $data['is_other'] = true;
        return $this->success($this->services->updateInfo($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 修改user表状态
     *
     * @return array
     */
    public function set_status($status, $id)
    {
//        if ($status == '' || $id == 0) return $this->fail('参数错误');
//        UserModel::where(['uid' => $id])->update(['status' => $status]);

        return $this->success($status == 0 ? '禁用成功' : '解禁成功');
    }

    /**
     * 编辑会员信息
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function edit($id)
    {
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->edit($id));
    }

    public function update($id)
    {
        $data = $this->request->postMore([
            ['money_status', 0],
            ['is_promoter', 1],
            ['real_name', ''],
            ['card_id', ''],
            ['birthday', ''],
            ['mark', ''],
            ['money', 0],
            ['integration_status', 0],
            ['integration', 0],
            ['status', 0],
            ['level', 0],
            ['phone', 0],
            ['addres', ''],
            ['label_id', ''],
            ['group_id', 0]
        ]);
        if ($data['phone']) {
            if (!preg_match("/^1[3456789]\d{9}$/", $data['phone'])) return $this->fail('手机号码格式不正确');
        }
        if ($data['card_id']) {
            if (!preg_match('/^[1-9]{1}\d{5}[1-9]{2}\d{9}[Xx0-9]{1}$/', $data['card_id'])) return $this->fail('请输入正确的身份证');
        }
        if (!$id) return $this->fail('数据不存在');
        $data['adminId'] = $this->adminId;
        $data['money'] = (string)$data['money'];
        $data['integration'] = (string)$data['integration'];
        return $this->success($this->services->updateInfo($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 获取单个用户信息
     * @param $id 用户id
     * @return mixed
     */
    public function oneUserInfo($id)
    {
        $data = $this->request->getMore([
            ['type', ''],
        ]);
        $id = (int)$id;
        if ($data['type'] == '') return $this->fail('缺少参数');
        return $this->success($this->services->oneUserInfo($id, $data['type']));
    }

}
