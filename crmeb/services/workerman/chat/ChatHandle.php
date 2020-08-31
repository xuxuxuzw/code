<?php


namespace crmeb\services\workerman\chat;

use app\services\message\service\StoreServiceLogServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use app\services\user\UserAuthServices;
use crmeb\exceptions\AuthException;
use crmeb\services\WechatService;
use crmeb\services\workerman\Response;
use think\facade\Log;
use Workerman\Connection\TcpConnection;

/**
 * Class ChatHandle
 * @package crmeb\services\workerman\chat
 */
class ChatHandle
{
    protected $service;

    public function __construct(ChatService &$service)
    {
        $this->service = &$service;
    }

    public function login(TcpConnection &$connection, array $res, Response $response)
    {
        if (!isset($res['data']) || !$token = $res['data']) {
            return $response->close([
                'msg' => '授权失败!'
            ]);
        }

        try {
            /** @var UserAuthServices $services */
            $services = app()->make(UserAuthServices::class);
            $authInfo = $services->parseToken($token);
        } catch (AuthException $e) {
            return $response->close([
                'msg' => $e->getMessage()
            ]);
        }

        $connection->user = $authInfo['user'];
        $connection->tokenData = $authInfo['tokenData'];
        $this->service->setUser($connection);

        return $response->success();
    }

    public function to_chat(TcpConnection &$connection, array $res)
    {
        $connection->chatToUid = $res['data']['id'] ?? 0;
    }

    public function chat(TcpConnection &$connection, array $res, Response $response)
    {
        $to_uid = $res['data']['to_uid'] ?? 0;
        $msn_type = $res['data']['type'] ?? 0;
        $msn = $res['data']['msn'] ?? '';
        $uid = $connection->user->uid;
        if (!$to_uid) return $response->send('err_tip', ['msg' => '用户不存在']);
        if ($to_uid == $uid) return $response->send('err_tip', ['msg' => '不能和自己聊天']);
        /** @var StoreServiceLogServices $logServices */
        $logServices = app()->make(StoreServiceLogServices::class);
        if (!in_array($msn_type, $logServices::MSN_TYPE)) return $response->send('err_tip', ['msg' => '格式错误']);
        $msn = trim(strip_tags(str_replace(["\n", "\t", "\r", " ", "&nbsp;"], '', htmlspecialchars_decode($msn))));
        $data = compact('to_uid', 'msn_type', 'msn', 'uid');
        $data['add_time'] = time();
        $connections = $this->service->user();
        $online = isset($connections[$to_uid]) && isset($connections[$to_uid]->chatToUid) && $connections[$to_uid]->chatToUid == $uid;
        $data['type'] = $online ? 1 : 0;
        $logServices->save($data);

        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $_userInfo = $userService->getUserInfo($data['uid'], 'nickname,avatar');
        $data['nickname'] = $_userInfo['nickname'];
        $data['avatar'] = $_userInfo['avatar'];

        $data['productInfo'] = [];
        if ($msn_type == $logServices::MSN_TYPE_GOODS && $msn) {
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            $productInfo = $productServices->getProductInfo((int)$msn);
            $data['productInfo'] = $productInfo ? $productInfo->toArray() : [];
        }

        $data['orderInfo'] = [];
        if ($msn_type == $logServices::MSN_TYPE_ORDER && $msn) {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $order = $orderServices->getUserOrderDetail($msn, $uid);
            if ($order) {
                $order = $orderServices->tidyOrder($order->toArray(), true, true);
                $order['add_time_y'] = date('Y-m-d', $order['add_time']);
                $order['add_time_h'] = date('H:i:s', $order['add_time']);
                $data['orderInfo'] = $order;
            }
        }

        $response->send('chat', $data);

        if ($online) {
            $response->connection($this->service->user()[$to_uid])->send('reply', $data);
        } else {
            /** @var WechatUserServices $wechatUserServices */
            $wechatUserServices = app()->make(WechatUserServices::class);
            $userInfo = $wechatUserServices->getOne(['uid' => $to_uid], 'nickname,subscribe,openid,headimgurl');
            if ($userInfo && $userInfo['subscribe'] && $userInfo['openid']) {
                $head = '客服提醒';
                $description = '您有新的消息，请注意查收！';
                $url = sys_config('site_url') . '/pages/customer_list/chat?uid=' . $uid;
                $message = WechatService::newsMessage($head, $description, $url, $_userInfo['avatar']);
                $userInfo = $userInfo->toArray();
                try {
                    WechatService::staffService()->message($message)->to($userInfo['openid'])->send();
                } catch (\Exception $e) {
                    Log::error($userInfo['nickname'] . '发送失败' . $e->getMessage());
                }
            }
        }
    }
}