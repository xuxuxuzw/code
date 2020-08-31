<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-13
 */

namespace app\services\wechat;


use app\dao\wechat\WechatMessageDao;
use app\services\BaseServices;
use think\exception\ValidateException;
use think\facade\Cache;

class WechatMessageServices extends BaseServices
{
    /**
     * 构造方法
     * WechatMessageServices constructor.
     * @param WechatMessageDao $dao
     */
    public function __construct(WechatMessageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param $result
     * @param $openid
     * @param $type
     * @return \think\Model
     */
    public function setMessage($result, $openid, $type)
    {
        if (is_object($result) || is_array($result)) $result = json_encode($result);
        $add_time = time();
        $data = compact('result', 'openid', 'type', 'add_time');
        return $this->dao->save($data);
    }

    public function setOnceMessage($result, $openid, $type, $unique, $cacheTime = 172800)
    {
        $cacheName = 'wechat_message_' . $type . '_' . $unique;
        if (Cache::has($cacheName)) return true;
        $res = $this->setMessage($result, $openid, $type);
        if ($res) Cache::set($cacheName, 1, $cacheTime);
        return $res;
    }
    /**
     * 微信消息前置操作
     * @param $event
     */
    public function wechatMessageBefore($message)
    {
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);
        $wechatUser->saveUser($message->FromUserName);

        $event = isset($message->Event) ?
            $message->MsgType . (
            $message->Event == 'subscribe' && isset($message->EventKey) ? '_scan' : ''
            ) . '_' . $message->Event : $message->MsgType;
        $result = json_encode($message);
        $openid = $message->FromUserName;
        $type = strtolower($event);
        $add_time = time();
        if(!$this->dao->save(compact('result','openid','type','add_time'))){
            throw new ValidateException('更新信息失败');
        }
        return true;
    }
}
