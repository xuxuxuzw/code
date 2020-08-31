<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/4
 */

namespace app\dao\order;


use app\dao\BaseDao;
use app\model\order\StoreOrder;

/**
 * 订单
 * Class StoreOrderDao
 * @package app\dao\order
 */
class StoreOrderDao extends BaseDao
{
    /**
     * @return string
     */
    protected function setModel(): string
    {
        return StoreOrder::class;
    }

    /**
     * 订单搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [])
    {
        $isDel = isset($where['is_del']) && $where['is_del'] != '' && $where['is_del'] != -1;
        return parent::search($where)->when($isDel, function ($query) use ($where) {
            $query->where('is_del', $where['is_del']);
        })->when(isset($where['is_system_del']), function ($query) {
            $query->where('is_system_del', 0);
        })->when(isset($where['status']) && $where['status'] !== '', function ($query) use ($where) {
            switch ((int)$where['status']) {
                case 0://未支付
                    $query->where('paid', 0)->where('status', 0)->where('refund_status', 0)->where('is_del', 0);
                    break;
                case 1://已支付 未发货
                    $query->where('paid', 1)->where('status', 0)->where('refund_status', 0)->when(isset($where['shipping_type']), function ($query) {
                        $query->where('shipping_type', 1);
                    })->where('is_del', 0);
                    break;
                case 2://已支付  待收货
                    $query->where('paid', 1)->where('status', 1)->where('refund_status', 0)->where('is_del', 0);
                    break;
                case 3:// 已支付  已收货  待评价
                    $query->where('paid', 1)->where('status', 2)->where('refund_status', 0)->where('is_del', 0);
                    break;
                case 4:// 交易完成
                    $query->where('paid', 1)->where('status', 3)->where('refund_status', 0)->where('is_del', 0);
                    break;
                case 5://已支付  待核销
                    $query->where('paid', 1)->where('status', 0)->where('refund_status', 0)->where('shipping_type', 2)->where('is_del', 0);
                    break;
                case 6://已支付 已核销 没有退款
                    $query->where('paid', 1)->where('status', 2)->where('refund_status', 0)->where('shipping_type', 2)->where('is_del', 0);
                    break;
                case -1://退款中
                    $query->where('paid', 1)->where('refund_status', 1)->where('is_del', 0);
                    break;
                case -2://已退款
                    $query->where('paid', 1)->where('refund_status', 2)->where('is_del', 0);
                    break;
                case -3://退款
                    $query->where('paid', 1)->where('refund_status', 'in', '1,2')->where('is_del', 0);
                    break;
                case -4://已删除
                    $query->where('is_del', 1);
                    break;
            }
        })->when(isset($where['type']), function ($query) use ($where) {
            switch ($where['type']) {
                case 1:
                    $query->where('combination_id', 0)->where('seckill_id', 0)->where('bargain_id', 0);
                    break;
                case 2:
                    $query->where('pink_id|combination_id', ">", 0);
                    break;
                case 3:
                    $query->where('seckill_id', ">", 0);
                    break;
                case 4:
                    $query->where('bargain_id', ">", 0);
                    break;
            }
        })->when(isset($where['pay_type']), function ($query) use ($where) {
            switch ($where['pay_type']) {
                case 1:
                    $query->where('pay_type', 'weixin');
                    break;
                case 2:
                    $query->where('pay_type', 'yue');
                    break;
                case 3:
                    $query->where('pay_type', 'offline');
                    break;
            }
        })->when(isset($where['real_name']) && $where['real_name'], function ($query) use ($where) {
            $query->where(function ($que) use ($where) {
                $que->whereLike('order_id|real_name', '%' . $where['real_name'] . '%')->whereOr('uid', 'in', function ($q) use ($where) {
                    $q->name('user')->whereLike('nickname|uid|phone', '%' . $where['real_name'] . '%')->field(['uid'])->select();
                })->whereOr('id', 'in', function ($que) use ($where) {
                    $que->name('store_order_cart_info')->whereIn('product_id', function ($q) use ($where) {
                        $q->name('store_product')->whereLike('store_name|keyword', '%' . $where['real_name'] . '%')->field(['id'])->select();
                    })->field(['oid'])->select();
                });
            });
        })->when(isset($where['store_id']) && $where['store_id'], function ($query) use ($where) {
            $query->where('store_id', $where['store_id']);
        })->when(isset($where['unique']), function ($query) use ($where) {
            $query->where('unique', $where['unique']);
        })->when(isset($where['is_remind']), function ($query) use ($where) {
            $query->where('is_remind', $where['is_remind']);
        });
    }

    /**
     * 订单搜索列表
     * @param array $where
     * @param array $field
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderList(array $where, array $field, int $page, int $limit, array $with = [])
    {
        return $this->search($where)->field($field)->with(array_merge(['user', 'spread'], $with))->page($page, $limit)->order('id DESC')->select()->toArray();
    }

    /**
     * 获取订单总数
     * @param array $where
     * @return int
     */
    public function count(array $where = []): int
    {
        return $this->search($where)->count();
    }

    /**
     * 聚合查询
     * @param array $where
     * @param string $field
     * @param string $together
     * @return int
     */
    public function together(array $where, string $field, string $together = 'sum')
    {
        if (!in_array($together, ['sum', 'max', 'min', 'avg'])) {
            return 0;
        }
        return $this->search($where)->{$together}($field);
    }

    /**
     * 查找指定条件下的订单数据以数组形式返回
     * @param array $where
     * @param string $field
     * @param string $key
     * @param string $group
     * @return array
     */
    public function column(array $where, string $field, string $key = '', string $group = '')
    {
        return $this->search($where)->when($group, function ($query) use ($group) {
            $query->group($group);
        })->column($field, $key);
    }

    /**
     * 获取订单id下没有删除的订单数量
     * @param array $ids
     * @return int
     */
    public function getOrderIdsCount(array $ids)
    {
        return $this->getModel()->whereIn('id', $ids)->where('is_del', 0)->count();
    }

    /**
     * 获取一段时间内订单列表
     * @param $datebefor
     * @param $dateafter
     * @return mixed
     */
    public function orderAddTimeList($datebefor, $dateafter)
    {
        return $this->getModel()->where('add_time', 'between time', [$datebefor, $dateafter])
            ->field("FROM_UNIXTIME(add_time,'%m-%d') as day,count(*) as count,sum(pay_price) as price")
            ->group("FROM_UNIXTIME(add_time, '%Y%m%d')")
            ->order('add_time asc')
            ->select()->toArray();
    }

    /**
     * 统计总数上期
     * @param $pre_datebefor
     * @param $pre_dateafter
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function preTotalFind($pre_datebefor, $pre_dateafter)
    {
        return $this->getModel()->where('add_time', 'between time', [$pre_datebefor, $pre_dateafter])
            ->field("count(*) as count,sum(pay_price) as price")
            ->find();
    }

    /**
     * 获取一段时间内订单列表
     * @param $now_datebefor
     * @param $now_dateafter
     * @return mixed
     */
    public function nowOrderList($now_datebefor, $now_dateafter)
    {
        return $this->getModel()->where('add_time', 'between time', [$now_datebefor, $now_dateafter])
            ->field("FROM_UNIXTIME(add_time,'%w') as day,count(*) as count,sum(pay_price) as price")
            ->group("FROM_UNIXTIME(add_time, '%Y%m%e')")
            ->order('add_time asc')
            ->select()->toArray();
    }

    /**
     * 获取订单数量
     * @return int
     */
    public function storeOrderCount()
    {
        return $this->search(['paid' => 1, 'is_del' => 0, 'refund_status' => 0, 'status' => 0, 'shipping_type' => 1])->count();
    }

    /**
     * 获取特定时间内订单总价
     * @param $time
     * @return float
     */
    public function todaySales($time)
    {
        return $this->search(['paid' => 1, 'is_del' => 0, 'refund_status' => 0, 'time' => $time ?: 'today', 'timekey' => 'pay_time'])->sum('pay_price');
    }

    /**
     * 获取特定时间内订单总价
     * @param $time
     * @return float
     */
    public function thisWeekSales($time)
    {
        return $this->search(['paid' => 1, 'is_del' => 0, 'refund_status' => 0, 'time' => $time ?: 'week', 'timeKey' => 'pay_time'])->sum('pay_price');
    }

    /**
     * 总销售额
     * @return float
     */
    public function totalSales()
    {
        return $this->search(['paid' => 1, 'is_del' => 0, 'refund_status' => 0])->sum('pay_price');
    }

    public function newOrderUpdates($newOrderId)
    {
        return $this->getModel()->where('order_id', 'in', $newOrderId)->update(['is_remind' => 1]);
    }

    /**
     * 获取特定时间内订单量
     * @param $time
     * @return float
     */
    public function todayOrderVisit($time, $week)
    {
        switch ($week) {
            case 1:
                return $this->search(['time' => $time ?: 'today', 'timeKey' => 'add_time'])->count();
            case 2:
                return $this->search(['time' => $time ?: 'week', 'timeKey' => 'add_time'])->count();
        }
    }

    /**
     * 获取订单详情
     * @param $uid
     * @param $key
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserOrderDetail(string $key, int $uid)
    {
        return $this->getOne(['order_id|unique' => $key, 'uid' => $uid, 'is_del' => 0]);
    }

    /**
     * 获取用户推广订单
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStairOrderList(array $where, string $field, int $page, int $limit, array $with = [])
    {
        return $this->search($where)->with($with)->field($field)->page($page, $limit)->order('id DESC')->select()->toArray();
    }

    /**
     * 订单每月统计数据
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getOrderDataPriceCount(array $field, int $page, int $limit)
    {
        return $this->search(['is_del' => 0, 'paid' => 1, 'refund_status' => 0])
            ->field($field)->group("FROM_UNIXTIME(add_time, '%Y-%m-%d')")
            ->order('add_time DESC')->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取当前时间到指定时间的支付金额 管理员
     * @param $start 开始时间
     * @param $stop  结束时间
     * @return mixed
     */
    public function chartTimePrice($start, $stop)
    {
        return $this->search(['is_del' => 0, 'paid' => 1, 'refund_status' => 0])
            ->where('add_time', '>=', $start)
            ->where('add_time', '<', $stop)
            ->field('sum(pay_price) as num,FROM_UNIXTIME(add_time, \'%Y-%m-%d\') as time')
            ->group("FROM_UNIXTIME(add_time, '%Y-%m-%d')")
            ->order('add_time ASC')->select()->toArray();
    }

    /**
     * 获取当前时间到指定时间的支付订单数 管理员
     * @param $start 开始时间
     * @param $stop  结束时间
     * @return mixed
     */
    public function chartTimeNumber($start, $stop)
    {
        return $this->search(['is_del' => 0, 'paid' => 1, 'refund_status' => 0])
            ->where('add_time', '>=', $start)
            ->where('add_time', '<', $stop)
            ->field('count(id) as num,FROM_UNIXTIME(add_time, \'%Y-%m-%d\') as time')
            ->group("FROM_UNIXTIME(add_time, '%Y-%m-%d')")
            ->order('add_time ASC')->select()->toArray();
    }
}
