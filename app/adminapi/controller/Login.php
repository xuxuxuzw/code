<?php

namespace app\adminapi\controller;

use think\facade\App;
use crmeb\utils\Captcha;
use crmeb\basic\BaseController;
use app\services\system\admin\SystemAdminServices;

/**
 * 后台登陆
 * Class Login
 * @package app\adminapi\controller
 */
class Login extends BaseController
{

    /**
     * Login constructor.
     * @param App $app
     * @param SystemAdminServices $services
     */
    public function __construct(App $app, SystemAdminServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 验证码
     * @return $this|\think\Response
     */
    public function captcha()
    {
        return app()->make(Captcha::class)->create();
    }

    /**
     * 登陆
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login()
    {
        [$account, $password, $imgcode] = $this->request->postMore([
            'account', 'pwd', ['imgcode', '']
        ], true);

        if (!app()->make(Captcha::class)->check($imgcode)) {
            return app('json')->fail('验证码错误，请重新输入');
        }

        $this->validate(['account' => $account, 'pwd' => $password], \app\adminapi\validate\setting\SystemAdminValidata::class, 'get');

        return $this->success($this->services->login($account, $password, 'admin'));
    }

    /**
     * 获取后台登录页轮播图以及LOGO
     * @return mixed
     */
    public function info()
    {
        return $this->success($this->services->getLoginInfo());
    }
}
