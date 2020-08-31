<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\services\wechat;

use app\services\BaseServices;
use app\dao\wechat\WechatUserDao;
use crmeb\services\CacheService as Cache;
use crmeb\services\WechatService as WechatAuthService;
use crmeb\utils\Canvas;
use think\exception\ValidateException;

/**
 *
 * Class WechatServices
 * @package app\services\wechat
 */
class WechatServices extends BaseServices
{

    /**
     * WechatServices constructor.
     * @param WechatUserDao $dao
     */
    public function __construct(WechatUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 微信公众号服务
     * @return \think\Response
     */
    public function serve()
    {
        ob_clean();
        return WechatAuthService::serve();
    }

    /**
     * 支付异步回调
     */
    public function notify()
    {
        ob_clean();
        return WechatAuthService::handleNotify();
    }

    /**
     * 公众号权限配置信息获取
     * @param $url
     * @return mixed
     */
    public function config($url)
    {
        return json_decode(WechatAuthService::jsSdk($url), true);
    }

    /**
     * 公众号授权登陆
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function auth($spreadId, $login_type)
    {
        try {
            $wechatInfo = WechatAuthService::oauthService()->user()->getOriginal();
        } catch (\Exception $e) {
            throw new ValidateException('授权失败' . $e->getMessage() . 'line' . $e->getLine());
        }
        if (!isset($wechatInfo['nickname'])) {
            $wechatInfo = WechatAuthService::getUserInfo($wechatInfo['openid']);
            if (!$wechatInfo['subscribe'] && !isset($wechatInfo['nickname']))
                throw new ValidateException('授权失败');
            if (isset($wechatInfo['tagid_list']))
                $wechatInfo['tagid_list'] = implode(',', $wechatInfo['tagid_list']);
        } else {
            if (isset($wechatInfo['privilege'])) unset($wechatInfo['privilege']);
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            if (!$wechatUser->getOne(['openid' => $wechatInfo['openid']])) {
                $wechatInfo['subscribe'] = 0;
            }
        }
        $wechatInfo['user_type'] = 'wechat';
        $openid = $wechatInfo['openid'];
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, 'wechat');
        $createData = [$openid, $wechatInfo, $spreadId, $login_type, 'wechat'];
        if (!$user) {
            $user = $wechatUserServices->wechatOauthAfter($createData);
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], $wechatInfo]);
        }
        $token = $this->createToken((int)$user['uid'], 'wechat');
        if ($token) {
            return ['userInfo' => $user];
        } else
            throw new ValidateException('登录失败');
    }

    public function follow()
    {
        $canvas = Canvas::instance();
        $path = 'uploads/follow/';
        $imageType = 'jpg';
        $name = 'follow';
        $siteUrl = sys_config('site_url');
        $imageUrl = $path . $name . '.' . $imageType;
        $canvas->setImageUrl('statics/qrcode/follow.png')->setImageHeight(720)->setImageWidth(500)->pushImageValue();
        $wechatQrcode = sys_config('wechat_qrcode');
        if (($strlen = stripos($wechatQrcode, 'uploads')) !== false) {
            $wechatQrcode = substr($wechatQrcode, $strlen);
        }
        if (!$wechatQrcode)
            throw new ValidateException('请上传二维码');
        $canvas->setImageUrl($wechatQrcode)->setImageHeight(344)->setImageWidth(344)->setImageLeft(76)->setImageTop(76)->pushImageValue();
        $image = $canvas->setFileName($name)->setImageType($imageType)->setPath($path)->setBackgroundWidth(500)->setBackgroundHeight(720)->starDrawChart();
        return ['path' => $image ? $siteUrl . '/' . $image : ''];
    }

    /**
     * 微信公众号静默授权
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function silenceAuth($spread)
    {
        $wechatInfoConfig = WechatAuthService::oauthService()->user()->getOriginal();
        $wechatInfo = WechatAuthService::getUserInfo($wechatInfoConfig['openid'])->toArray();
        $openid = $wechatInfoConfig['openid'];
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, 'wechat');
        $wechatInfo['headimgurl'] = sys_config('h5_avatar');
        $createData = [$openid, $wechatInfo, $spread, '', 'wechat'];
        //获取是否强制绑定手机号
        $storeUserMobile = sys_config('store_user_mobile');
        if ($storeUserMobile && !$user) {
            $userInfoKey = md5($openid . '_' . time() . '_wechat');
            Cache::setTokenBucket($userInfoKey, $createData, 7200);
            return ['key' => $userInfoKey];
        } else if (!$user) {
            //写入用户信息
            $user = $wechatUserServices->wechatOauthAfter($createData);
            $token = $this->createToken((int)$user['uid'], 'wechat');
            if ($token) {
                return $token;
            } else
                throw new ValidateException('登录失败');
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], ['code' => $spread]]);
            $token = $this->createToken((int)$user['uid'], 'wechat');
            if ($token) {
                return $token;
            } else
                throw new ValidateException('登录失败');
        }
    }
}
