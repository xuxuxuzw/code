<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/9
 */
declare (strict_types=1);

namespace app\services\wechat;

use app\services\BaseServices;
use app\dao\wechat\WechatUserDao;
use app\services\other\QrcodeServices;
use app\services\user\LoginServices;
use app\services\user\UserServices;
use crmeb\services\CacheService as Cache;
use crmeb\services\MiniProgramService;
use crmeb\services\template\Template;
use think\exception\ValidateException;
use think\facade\Config;

/**
 *
 * Class RoutineServices
 * @package app\services\wechat
 */
class RoutineServices extends BaseServices
{

    /**
     * RoutineServices constructor.
     * @param WechatUserDao $dao
     */
    public function __construct(WechatUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 小程序授权登录
     * @param $code
     * @param $post_cache_key
     * @param $login_type
     * @param $spread_spid
     * @param $spread_code
     * @param $iv
     * @param $encryptedData
     * @return mixed
     */
    public function mp_auth($code, $post_cache_key, $login_type, $spread_spid, $spread_code, $iv, $encryptedData)
    {
        $session_key = Cache::get('eb_api_code_' . $post_cache_key);
        if (!$code && !$session_key)
            throw new ValidateException('授权失败,参数有误');
        if ($code && !$session_key) {
            try {
                $userInfoCong = MiniProgramService::getUserInfo($code);
                $session_key = $userInfoCong['session_key'];
                $cache_key = md5(time() . $code);
                Cache::set('eb_api_code_' . $cache_key, $session_key, 86400);
            } catch (\Exception $e) {
                throw new ValidateException('获取session_key失败，请检查您的配置！:' . $e->getMessage() . 'line' . $e->getLine());
            }
        }
        try {
            //解密获取用户信息
            $userInfo = MiniProgramService::encryptor($session_key, $iv, $encryptedData);
        } catch (\Exception $e) {
            if ($e->getCode() == '-41003') {
                throw new ValidateException('获取会话密匙失败');
            }
        }
        if (!isset($userInfo['openId'])) {
            throw new ValidateException('openid获取失败');
        }
        if (!isset($userInfo['unionId'])) $userInfo['unionId'] = '';
        $openid = $userInfo['openId'];
        $userInfo['spid'] = $spread_spid;
        $userInfo['code'] = $spread_code;
        $userInfo['session_key'] = $session_key;
        $userInfo['login_type'] = $login_type;
        $createData = $this->routineOauth($userInfo);

        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, 'routine');
        if (!$user) {
            $user = $wechatUserServices->wechatOauthAfter($createData);
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], $createData[1]]);
        }
        $token = $this->createToken((int)$user['uid'], 'routine');
        if ($token) {
            return [
                'userInfo' => $user
            ];
        } else
            throw new ValidateException('获取用户访问token失败!');
    }

    /**
     * 小程序创建用户后返回uid
     * @param $routineInfo
     * @return mixed
     */
    public function routineOauth($routine)
    {
        $routineInfo['nickname'] = filter_emoji($routine['nickName']);//姓名
        $routineInfo['sex'] = $routine['gender'];//性别
        $routineInfo['language'] = $routine['language'];//语言
        $routineInfo['city'] = $routine['city'];//城市
        $routineInfo['province'] = $routine['province'];//省份
        $routineInfo['country'] = $routine['country'];//国家
        $routineInfo['headimgurl'] = $routine['avatarUrl'];//头像
        $routineInfo['openid'] = $routine['openId'];
        $routineInfo['session_key'] = $routine['session_key'];//会话密匙
        $routineInfo['unionid'] = $routine['unionId'];//用户在开放平台的唯一标识符
        $routineInfo['user_type'] = 'routine';//用户类型
        $routineInfo['phone'] = $routine['phone'] ?? '';
        $spid = $routine['spid'] ?? 0;//绑定关系uid
        //获取是否有扫码进小程序
        /** @var QrcodeServices $qrcode */
        $qrcode = app()->make(QrcodeServices::class);
        if (isset($routine['code']) && $routine['code'] && ($info = $qrcode->get($routine['code']))) {
            $spid = $info['third_id'];
        }
        return [$routine['openId'], $routineInfo, $spid, $routine['login_type'] ?? 'routine', 'routine'];
    }

    /**
     * 小程序支付回调
     */
    public function notify()
    {
        return MiniProgramService::handleNotify();
    }

    /**
     * 获取小程序订阅消息id
     * @return mixed
     */
    public function temlIds()
    {
        $temlIdsName = Config::get('template.stores.subscribe.template_id', []);
        $temlIdsList = Cache::get('TEML_IDS_LIST', function () use ($temlIdsName) {
            $temlId = [];
            $templdata = new Template('subscribe');
            foreach ($temlIdsName as $key => $item) {
                $temlId[strtolower($key)] = $templdata->getTempId($item);
            }
            return $temlId;
        });
        return $temlIdsList;
    }

    /**
     * 获取小程序直播列表
     * @param $pgae
     * @param $limit
     * @return mixed
     */
    public function live($page, $limit)
    {
        $list = Cache::get('WECHAT_LIVE_LIST_' . $page . '_' . $limit, function () use ($page, $limit) {
            $list = MiniProgramService::getLiveInfo($page, $limit);
            foreach ($list as &$item) {
                $item['_start_time'] = date('m-d H:i', $item['start_time']);
            }
            return $list;
        }, 600) ?: [];
        return $list;
    }

    /**
     * 静默授权
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function silenceAuth($code, $spread, $spid)
    {
        $userInfoConfig = MiniProgramService::getUserInfo($code);
        if (!isset($userInfoConfig['openid'])) {
            throw new ValidateException('静默授权失败');
        }
        $routineInfo = [
            'unionid' => $userInfoConfig['unionid'] ?? null
        ];
        /** @var QrcodeServices $qrcode */
        $qrcode = app()->make(QrcodeServices::class);
        if ($spid && ($info = $qrcode->getOne(['id' => $spid, 'status' => 1]))) {
            $spread = $info['third_id'];
        }

        $openid = $userInfoConfig['openid'];
        $routineInfo['openid'] = $openid;
        $routineInfo['headimgurl'] = sys_config('h5_avatar');
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, 'routine');
        $createData = [$openid, $routineInfo, $spread, '', 'routine'];
        //获取是否强制绑定手机号
        $storeUserMobile = sys_config('store_user_mobile');
        if ($storeUserMobile && !$user) {
            $userInfoKey = md5($openid . '_' . time() . '_routine');
            Cache::setTokenBucket($userInfoKey, $createData, 7200);
            return ['key' => $userInfoKey];
        } else if (!$user) {
            //写入用户信息
            $user = $wechatUserServices->wechatOauthAfter($createData);
            $token = $this->createToken((int)$user['uid'], 'routine');
            if ($token) {
                return $token;
            } else
                throw new ValidateException('登录失败');
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], ['code' => $spread]]);
            $token = $this->createToken((int)$user['uid'], 'routine');
            if ($token) {
                return $token;
            } else
                throw new ValidateException('登录失败');
        }

    }

    /**
     * 静默授权
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function authBindindPhone($code, $iv, $encryptedData, $spread, $spid)
    {
        try {
            $userInfoCong = MiniProgramService::getUserInfo($code);
            $session_key = $userInfoCong['session_key'];
        } catch (\Exception $e) {
            throw new ValidateException('获取session_key失败，请检查您的配置！:' . $e->getMessage() . 'line' . $e->getLine());
        }
        try {
            //解密获取用户信息
            $userInfo = MiniProgramService::encryptor($session_key, $iv, $encryptedData);
        } catch (\Exception $e) {
            if ($e->getCode() == '-41003') {
                throw new ValidateException('获取会话密匙失败');
            }
        }
        if (!$userInfo || !isset($userInfo['purePhoneNumber'])) {
            throw new ValidateException('获取用户信息失败');
        }
        $routineInfo = [
            'unionid' => $userInfoCong['unionid'] ?? null
        ];
        $openid = $userInfoCong['openid'];
        $routineInfo['openid'] = $userInfoCong['openid'];
        $routineInfo['phone'] = $userInfo['purePhoneNumber'];
        $routineInfo['session_key'] = $session_key;
        /** @var QrcodeServices $qrcode */
        $qrcode = app()->make(QrcodeServices::class);
        if ($spid && ($info = $qrcode->getOne(['id' => $spid, 'status' => 1]))) {
            $spread = $info['third_id'];
        }
        $createData = [$openid, $routineInfo, $spread, '', 'routine'];
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        //写入用户信息
        $user = $wechatUserServices->wechatOauthAfter($createData);
        $token = $this->createToken((int)$user['uid'], 'routine');
        if ($token) {
            return [
                'token' => $token['token'],
                'userInfo' => $user,
                'expires_time' => $token['params']['exp'],
            ];
        } else
            throw new ValidateException('登录失败');
    }

    public function updateUserInfo($uid, array $data)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        $userInfo = [];
        $userInfo['nickname'] = filter_emoji($data['nickName'] ?? '');//姓名
        $userInfo['sex'] = $data['gender'] ?? '';//性别
        $userInfo['language'] = $data['language'] ?? '';//语言
        $userInfo['city'] = $data['city'] ?? '';//城市
        $userInfo['province'] = $data['province'] ?? '';//省份
        $userInfo['country'] = $data['country'] ?? '';//国家
        $userInfo['headimgurl'] = $data['avatarUrl'] ?? '';//头像
        $userInfo['is_complete'] = 1;
        /** @var LoginServices $loginService */
        $loginService = app()->make(LoginServices::class);
        $loginService->updateUserInfo($userInfo, $user);
        //更新用户信息
        if (!$this->dao->whereUpdate(['uid' => $user['uid'], 'user_type' => 'routine'], $userInfo)) {
            throw new ValidateException('更新失败');
        }
        return true;
    }
}
