<?php

namespace app\adminapi\controller\v1\notification\sms;

use app\adminapi\controller\AuthController;
use app\services\message\sms\SmsAdminServices;
use think\facade\App;

/**
 * 短信账号
 * Class SmsAdmin
 * @package app\adminapi\controller\v1\sms
 */
class SmsAdmin extends AuthController
{
    /**
     * 构造方法
     * SmsAdmin constructor.
     * @param App $app
     * @param SmsAdminServices $services
     */
    public function __construct(App $app, SmsAdminServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 发送验证码
     * @return mixed
     */
    public function captcha()
    {
        if (!request()->isPost()) {
            return $this->fail('发送失败');
        }
        $phone = request()->param('phone');
        if (!trim($phone)) {
            return $this->fail('请填写手机号');
        }
        return $this->success($this->services->captcha($phone));
    }

    /**
     * 修改/注册短信平台账号
     */
    public function save()
    {
        [$account, $password, $phone, $code, $url, $sign] = $this->request->postMore([
            ['account', ''],
            ['password', ''],
            ['phone', ''],
            ['code', ''],
            ['url', ''],
            ['sign', ''],
        ], true);
        $signLen = mb_strlen(trim($sign));
        if (!strlen(trim($account))) return $this->fail('请填写账号');
        if (!strlen(trim($password))) return $this->fail('请填写密码');
        if (!$signLen) return $this->fail('请填写短信签名');
        if ($signLen > 8) return $this->fail('短信签名最长为8位');
        if (!strlen(trim($code))) return $this->fail('请填写验证码');
        if (!strlen(trim($url))) return $this->fail('请填写域名');
        $status = $this->services->register($account, $password, $url, $phone, $code, $sign);
        return $this->success('短信平台：' . $status['msg']);
    }
}