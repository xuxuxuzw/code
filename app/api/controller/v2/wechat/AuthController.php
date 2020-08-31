<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/24
 */

namespace app\api\controller\v2\wechat;

use app\Request;
use app\services\wechat\RoutineServices;
use crmeb\jobs\TaskJob;
use crmeb\utils\Queue;


/**
 * Class AuthController
 * @package app\api\controller\v2\wechat
 */
class AuthController
{

    protected $services = NUll;

    /**
     * AuthController constructor.
     * @param RoutineServices $services
     */
    public function __construct(RoutineServices $services)
    {
        $this->services = $services;
    }

    /**
     * 静默授权
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function silenceAuth($code, $spread, $spid)
    {
        $token = $this->services->silenceAuth($code, $spread, $spid);
        Queue::instance()->do('emptyYesterdayAttachment')->job(TaskJob::class)->push();
        if ($token && isset($token['key'])) {
            return app('json')->success('授权成功，请绑定手机号', $token);
        } else if ($token) {
            return app('json')->success('登录成功', ['token' => $token['token'], 'expires_time' => $token['params']['exp']]);
        } else
            return app('json')->fail('登录失败');
    }

    /**
     * 授权获取小程序用户手机号 直接绑定
     * @param $code
     * @param $iv
     * @param $encryptedData
     * @return mixed
     */
    public function authBindindPhone($code, $iv, $encryptedData, $spread, $spid)
    {
        if (!$code || !$iv || !$encryptedData)
            return app('json')->fail('参数有误');
        $token = $this->services->authBindindPhone($code, $iv, $encryptedData, $spread, $spid);
        if ($token) {
            return app('json')->success('登录成功', $token);
        } else
            return app('json')->fail('登录失败');
    }

    /**
     *  更新用户信息
     * @param $userInfo
     * @return mixed
     */
    public function updateInfo(Request $request, $userInfo)
    {
        if (!$userInfo) {
            return app('json')->fail('参数有误');
        }
        $uid = (int)$request->uid();
        $re = $this->services->updateUserInfo($uid, $userInfo);
        if ($re) {
            return app('json')->success('更新成功');
        } else
            return app('json')->fail('更新失败');
    }
}
