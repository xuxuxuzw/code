<?php

namespace app\adminapi\controller\v1\notification\sms;

use app\services\message\sms\SmsAdminServices;
use app\services\message\sms\SmsRecordServices;
use crmeb\services\CacheService;
use app\adminapi\controller\AuthController;
use think\facade\App;

/**
 * 短信配置
 * Class SmsConfig
 * @package app\admin\controller\sms
 */
class SmsConfig extends AuthController
{
    /**
     * 构造方法
     * SmsConfig constructor.
     * @param App $app
     * @param SmsAdminServices $services
     */
    public function __construct(App $app, SmsAdminServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }


    /**
     * 保存短信配置
     * @return mixed
     */
    public function save_basics()
    {
        [$account, $token] = $this->request->postMore([
            ['sms_account', ''],
            ['sms_token', '']
        ], true);

        $this->validate(['sms_account' => $account, 'sms_token' => $token], \app\adminapi\validate\notification\SmsConfigValidate::class);

        if ($this->services->login($account, $token)) {
            return $this->success('登录成功');
        } else {
            return $this->fail('账号或密码错误');
        }
    }

    /**
     * 检测登录
     * @return mixed
     */
    public function is_login()
    {
        $sms_info = CacheService::redisHandler()->get('sms_account');
        if ($sms_info) {
            return $this->success(['status' => true, 'info' => $sms_info]);
        } else {
            return $this->success(['status' => false]);
        }
    }

    /**
     * 退出
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function logout()
    {
        $res = CacheService::redisHandler()->delete('sms_account');
        if ($res) {
            $this->services->updateSmsConfig('', '');
            CacheService::clear();
            return $this->success('退出成功');
        } else {
            return $this->fail('退出失败');
        }
    }

    /**
     * 短信发送记录
     * @return mixed
     */
    public function record(SmsRecordServices $services)
    {
        $where = $this->request->getMore([
            ['type', '']
        ]);
        return $this->success($services->getRecordList($where));
    }

    /**
     * @return mixed
     */
    public function data()
    {
        return $this->success($this->services->getSmsData());
    }
}