<?php

namespace app\adminapi\controller\v1\application\wechat;


use app\adminapi\business\wechat\WechatMessageBusiness;
use app\adminapi\model\wechat\WechatMessage as MessageModel;
use app\adminapi\controller\AuthController;

/**
 * 用户扫码点击事件
 * Class SystemMessage
 * @package app\admin\controller\system
 */
class WechatMessage extends AuthController
{
    /**
     * 显示操作记录
     */
    public function index()
    {
        $where = $this->getMore([
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['type', ''],
            ['data', ''],
        ]);
        return $this->success(app()->make(WechatMessageBusiness::class)->systemPage($where));
    }

    /**
     * 操作名称列表
     * @return mixed
     */
    public function operate()
    {
        $operate = app()->make(MessageModel::class)->mold;
        return $this->success(compact('operate'));
    }

}

