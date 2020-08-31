<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\services\message\service;


use app\dao\service\StoreServiceLogDao;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;

/**
 * 客服聊天记录
 * Class StoreServiceLogServices
 * @package app\services\message\service
 */
class StoreServiceLogServices extends BaseServices
{
    /**
     * 消息类型
     * @var array  1=文字 2=表情 3=图片 4=语音 5 = 商品链接 6 = 订单类型
     */
    const MSN_TYPE = [1, 2, 3, 4, 5, 6];

    /**
     * 商品链接消息类型
     */
    const MSN_TYPE_GOODS = 5;

    /**
     * 订单信息消息类型
     */
    const MSN_TYPE_ORDER = 6;

    /**
     * 构造方法
     * StoreServiceLogServices constructor.
     * @param StoreServiceLogDao $dao
     */
    public function __construct(StoreServiceLogDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取聊天记录中的uid和to_uid
     * @param int $uid
     * @return array
     */
    public function getChatUserIds(int $uid)
    {
        $list = $this->dao->getServiceUserUids($uid);
        $arr_user = $arr_to_user = [];
        foreach ($list as $key => $value) {
            array_push($arr_user, $value["uid"]);
            array_push($arr_to_user, $value["to_uid"]);
        }
        $uids = array_merge($arr_user, $arr_to_user);
        $uids = array_flip(array_flip($uids));
        $uids = array_flip($uids);
        unset($uids[$uid]);
        return array_flip($uids);
    }

    /**
     * 获取某个用户的客服聊天记录
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChatLogList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getServiceList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取聊天记录列表
     * @param array $where
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChatList(array $where, int $uid)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getServiceList($where, $page, $limit);
        return $this->tidyChat($list, $uid);
    }

    /**
     * 聊天列表格式化
     * @param array $list
     * @param int $uid
     * @return array
     */
    public function tidyChat(array $list, int $uid)
    {
        $productIds = $orderIds = $productList = $orderInfo = [];
        foreach ($list as $item) {
            $item['productInfo'] = $item['orderInfo'] = [];
            if ($item['msn_type'] == self::MSN_TYPE_GOODS && $item['msn']) {
                $productIds[] = $item['msn'];
            } elseif ($item['msn_type'] == self::MSN_TYPE_ORDER && $item['msn']) {
                $orderIds[] = $item['msn'];
            }
        }
        if ($productIds) {
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            $where = [
                ['id', 'in', $productIds],
                ['is_del', '=', 0],
                ['is_show', '=', 1],
            ];
            $productList = $productServices->getProductArray($where, '*', 'id');
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        if ($orderIds) {
            $orderWhere = [
                ['order_id|unique', 'in', $orderIds],
                ['uid', '=', $uid],
                ['is_del', '=', 0],
            ];
            $orderInfo = $orderServices->getColumn($orderWhere, '*', 'order_id');
        }

        foreach ($list as &$item) {
            if ($item['msn_type'] == self::MSN_TYPE_GOODS && $item['msn']) {
                $item['productInfo'] = $productList[$item['msn']] ?? [];
            } elseif ($item['msn_type'] == self::MSN_TYPE_ORDER && $item['msn']) {
                $order = $orderInfo[$item['msn']] ?? null;
                if ($order) {
                    $order = $orderServices->tidyOrder($order, true, true);
                    $order['add_time_y'] = date('Y-m-d', $order['add_time']);
                    $order['add_time_h'] = date('H:i:s', $order['add_time']);
                    $item['orderInfo'] = $order;
                } else {
                    $item['orderInfo'] = [];
                }
            }
            $item['msn_type'] = (int)$item['msn_type'];
        }
        return $list;
    }

}