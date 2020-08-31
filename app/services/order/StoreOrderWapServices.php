<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/13
 */

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\services\BaseServices;

/**
 * Class StoreOrderWapServices
 * @package app\services\order
 * @method getOne(array $where, ?string $field = '*') 获取一条数据
 */
class StoreOrderWapServices extends BaseServices
{
    /**
     * StoreOrderWapServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取 今日 昨日 本月 订单金额
     * @return mixed
     */
    public function getOrderTimeData()
    {
        $where = ['is_del' => 0, 'timeKey' => 'pay_time', 'paid' => 1, 'refund_status' => 0];
        //今日成交额
        $data['todayPrice'] = $this->dao->together($where + ['time' => 'today'], 'pay_price');
        //今日订单数
        $data['todayCount'] = $this->dao->count($where + ['time' => 'today']);
        //昨日成交额
        $data['proPrice'] = $this->dao->together($where + ['time' => 'yesterday'], 'pay_price');
        //昨日订单数
        $data['proCount'] = $this->dao->count($where + ['time' => 'yesterday']);
        //本月成交额
        $data['monthPrice'] = $this->dao->together($where + ['time' => 'year'], 'pay_price');
        //本月订单数
        $data['monthCount'] = $this->dao->count($where + ['time' => 'year']);
        return $data;
    }

    /**
     * 订单每月统计数据
     * @return array
     */
    public function getOrderDataPriceCount()
    {
        [$page, $limit] = $this->getPageValue();
        return $this->dao->getOrderDataPriceCount(['sum(pay_price) as price', 'count(id) as count', 'FROM_UNIXTIME(add_time, \'%m-%d\') as time'], $page, $limit);
    }

    /**
     * 获取手机端订单管理
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWapAdminOrderList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getOrderList($where, ['*'], $page, $limit, []);
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        return $orderServices->tidyOrderList($list);
    }

}