<?php

namespace crmeb\subscribes;


/**
 * 用户事件
 * Class UserSubscribe
 * @package crmeb\subscribes
 */
class UserSubscribe
{

    public function handle()
    {

    }

    /**
     * 管理员后台给用户添加金额
     * @param $event
     */
    public function onAdminAddMoney($event)
    {
        list($user, $money) = $event;
        //$user 用户信息
        //$money 添加的金额
    }

}