<?php

namespace app\adminapi\controller\v1\application\wechat;

use EasyWeChat\Core\Exceptions\HttpException;
use app\adminapi\controller\AuthController;
use app\services\wechat\WechatReplyServices;
use app\services\wechat\WechatKeyServices;
use think\facade\App;

/**
 * 关键字管理  控制器
 * Class Reply
 * @package app\admin\controller\wechat
 */
class Reply extends AuthController
{
    /**
     * 构造方法
     * Menus constructor.
     * @param App $app
     * @param WechatReplyServices $services
     */
    public function __construct(App $app, WechatReplyServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**关注回复
     * @return mixed|void
     */
    public function reply()
    {
        $where = $this->request->getMore([
            ['key', ''],
        ]);
        if ($where['key'] == '') return $this->fail('请输入参数key');
        $info = $this->services->getDataByKey($where['key']);
        return $this->success(compact('info'));
    }

    /**
     * 关键字回复列表
     * */
    public function index()
    {
        $where = $this->request->getMore([
            ['key', ''],
            ['type', ''],
        ]);
        $list = $this->services->getKeyAll($where);
        return $this->success($list);
    }

    /**
     * 关键字详情
     * */
    public function read($id)
    {
        $info = $this->services->getKeyInfo($id);
        return $this->success(compact('info'));
    }

    /**
     * 保存关键字
     * */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            'key',
            'type',
            ['status', 0],
            ['data', []],
        ]);
        try {
            if (!isset($data['key']) && empty($data['key']))
                return $this->fail('请输入关键字');
            if (!isset($data['type']) && empty($data['type']))
                return $this->fail('请选择回复类型');
            if (!in_array($data['type'], $this->services->replyType()))
                return $this->fail('回复类型有误!');

            if (!isset($data['data']) || !is_array($data['data']))
                return $this->fail('回复消息参数有误!');
            $res = $this->services->redact($data['data'], $id, $data['key'], $data['type'], $data['status']);
            if (!$res)
                return $this->fail('保存失败！');
            else
                return $this->success('保存成功!', $data);
        } catch (HttpException $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 删除关键字
     * */
    public function delete($id)
    {
        if (!$this->services->delete($id)) {
            return $this->fail('删除失败,请稍候再试!');
        } else {
            /** @var WechatKeyServices $keyServices */
            $keyServices = app()->make(WechatKeyServices::class);
            $res = $keyServices->delete($id, 'reply_id');
            if (!$res) {
                return $this->fail('删除失败,请稍候再试!');
            }
        }
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
        $this->services->update($id, ['status' => $status], 'id');
        return $this->success($status == 0 ? '禁用成功' : '启用成功');
    }

}
