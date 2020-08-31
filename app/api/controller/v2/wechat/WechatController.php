<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/24
 */

namespace app\api\controller\v2\wechat;

use app\services\wechat\WechatServices;
use crmeb\jobs\TaskJob;
use crmeb\utils\Queue;

/**
 * Class WechatController
 * @package app\api\controller\v2\wechat
 */
class WechatController
{
    protected $services = NUll;

    /**
     * WechatController constructor.
     * @param WechatServices $services
     */
    public function __construct(WechatServices $services)
    {
        $this->services = $services;
    }

    /**
     * 微信公众号静默授权
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function silenceAuth($spread)
    {
        $token = $this->services->silenceAuth($spread);
        Queue::instance()->do('emptyYesterdayAttachment')->job(TaskJob::class)->push();
        if ($token && isset($token['key'])) {
            return app('json')->success('授权成功，请绑定手机号', $token);
        } else if ($token) {
            return app('json')->success('登录成功', ['token' => $token['token'], 'expires_time' => $token['params']['exp']]);
        } else
            return app('json')->fail('登录失败');
    }
}
