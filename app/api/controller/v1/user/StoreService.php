<?php

namespace app\api\controller\v1\user;

use app\Request;
use app\services\message\service\StoreServiceLogServices;
use app\services\message\service\StoreServiceServices;

/**
 * 客服类
 * Class StoreService
 * @package app\api\controller\user
 */
class StoreService
{
    /**
     * @var StoreServiceLogServices
     */
    protected $services;

    /**
     * StoreService constructor.
     * @param StoreServiceLogServices $services
     */
    public function __construct(StoreServiceLogServices $services)
    {
        $this->services = $services;
    }

    /**
     * 客服列表
     * @return mixed
     */
    public function lst(StoreServiceServices $services)
    {
        $serviceInfoList = $services->getServiceList(['status' => 1]);
        if (!count($serviceInfoList)) return app('json')->successful([]);
        return app('json')->successful($serviceInfoList['list']);
    }

    /**
     * 客服聊天记录
     * @param Request $request
     * @param $toUid
     * @return array
     */
    public function record(Request $request, StoreServiceServices $services)
    {
        $serviceInfoList = $services->getServiceList(['status' => 1]);
        if (!count($serviceInfoList)) return app('json')->fail('暂无客服人员在线，请稍后联系');
        $uid = $request->uid();
        $uids = array_column($serviceInfoList['list'], 'uid');
        //自己是客服
        if (in_array($uid, $uids)) {
            $uids = array_merge(array_diff($uids, [$uid]));
            if (!$uids) return app('json')->fail('不能和自己聊天');
        }
        if (!$uids) {
            return app('json')->fail('暂无客服人员在线，请稍后联系');
        }
        $toUid = $uids[array_rand($uids)] ?? 0;
        if (!$toUid) return app('json')->fail('暂无客服人员在线，请稍后联系');
        $result = ['serviceList' => [], 'uid' => $toUid];
        $serviceLogList = $this->services->getChatList(['uid' => $uid], $uid);
        if (!$serviceLogList) return app('json')->successful($result);
        $idArr = array_column($serviceLogList, 'id');
        array_multisort($idArr, SORT_ASC, $serviceLogList);
        $result['serviceList'] = $serviceLogList;
        return app('json')->successful($result);
    }
}