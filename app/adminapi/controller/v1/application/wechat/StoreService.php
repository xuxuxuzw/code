<?php

namespace app\adminapi\controller\v1\application\wechat;

use app\adminapi\controller\AuthController;
use app\services\message\service\StoreServiceLogServices;
use app\services\message\service\StoreServiceServices;
use app\services\user\UserServices;
use app\services\user\UserWechatuserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use think\facade\App;

/**
 * 客服管理
 * Class StoreService
 * @package app\admin\controller\store
 */
class StoreService extends AuthController
{
    /**
     * StoreService constructor.
     * @param App $app
     * @param StoreServiceServices $services
     */
    public function __construct(App $app, StoreServiceServices $services)
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
        return $this->success($this->services->getServiceList([]));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create(UserWechatuserServices $services)
    {
        $where = $this->request->getMore([
            ['nickname', ''],
            ['data', '', '', 'time'],
            ['type', '', '', 'user_type'],
        ]);
        [$list, $count] = $services->getWhereUserList($where, 'u.nickname,u.uid,u.avatar as headimgurl,w.subscribe,w.province,w.country,w.city,w.sex');
        return $this->success(compact('list', 'count'));
    }

    /**
     * 添加客服表单
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function add()
    {
        return $this->success($this->services->create());
    }

    /*
     * 保存新建的资源
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['image', ''],
            ['uid', 0],
            ['avatar', ''],
            ['customer', ''],
            ['notify', ''],
            ['phone', ''],
            ['nickname', ''],
            ['status', 1],
        ]);
        if ($data['image'] == '') return $this->fail('请选择用户');
        $data['uid'] = $data['image']['uid'];
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userInfo = $userService->get($data['uid']);
        if ($data['phone'] == '') {
            if (!$userInfo['phone']) {
                throw new AdminException('该用户没有绑定手机号，请手动填写');
            } else {
                $data['phone'] = $userInfo['phone'];
            }
        }
        if ($data['nickname'] == '') $data['nickname'] = $userInfo['nickname'];
        $data['avatar'] = $data['image']['image'];
        if ($this->services->count(['uid' => $data['uid']])) {
            return $this->fail('客服已存在!');
        }
        unset($data['image']);
        $data['add_time'] = time();
        $res = $this->services->save($data);
        if ($res) {
            return $this->success('客服添加成功');
        } else {
            return $this->fail('客服添加失败，请稍后再试');
        }
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        return $this->success($this->services->edit((int)$id));
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function update($id)
    {
        $params = $this->request->postMore([
            ['avatar', ''],
            ['nickname', ''],
            ['phone', ''],
            ['status', 1],
            ['notify', 1],
            ['customer', 1]
        ]);
        if ($params["nickname"] == '') {
            return $this->fail("客服名称不能为空！");
        }
        $data = ["avatar" => $params["avatar"]
            , "nickname" => $params["nickname"]
            , "phone" => $params["phone"]
            , 'status' => $params['status']
            , 'notify' => $params['notify']
            , 'customer' => $params['customer']
        ];
        $this->services->update($id, $data);
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
        if ($status == '' || $id == 0) return $this->fail('参数错误');
        $this->services->update($id, ['status' => $status]);
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }

    /**
     * 聊天记录
     *
     * @return \think\Response
     */
    public function chat_user($id)
    {
        $uid = $this->services->value(['id' => $id], 'uid');
        if (!$uid) {
            return $this->fail('数据不存在!');
        }
        return $this->success($this->services->getChatUser((int)$uid));
    }


    /**
     * 聊天记录
     * @param StoreServiceLogServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function chat_list(StoreServiceLogServices $services)
    {
        $data = $this->request->getMore([
            ['uid', 0],
            ['to_uid', 0],
            ['id', 0]
        ]);
        if ($data['uid']) {
            CacheService::set('admin_chat_list' . $this->adminId, $data);
        }
        $data = CacheService::get('admin_chat_list' . $this->adminId);
        if ($data['uid']) {
            $where = [
                'chat' => [$data['uid'], $data['to_uid']],
            ];
        } else {
            $where = [];
        }
        $list = $services->getChatLogList($where);
        return $this->success($list);
    }
}
