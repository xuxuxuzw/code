<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/8
 */

namespace app\dao\order;


use app\dao\BaseDao;
use app\model\order\StoreOrderStatus;

/**
 * 订单状态
 * Class StoreOrderStatusDao
 * @package app\dao\order
 */
class StoreOrderStatusDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreOrderStatus::class;
    }

    /**
     * 获取订单状态列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStatusList(array $where, int $page, int $limit)
    {
        return $this->search($where)->page($page, $limit)->select()->toArray();
    }

}