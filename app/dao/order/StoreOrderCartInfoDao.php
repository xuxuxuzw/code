<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/4
 */

namespace app\dao\order;


use app\dao\BaseDao;
use app\model\order\StoreOrderCartInfo;

/**
 * 订单详情
 * Class StoreOrderCartInfoDao
 * @package app\dao\order
 */
class StoreOrderCartInfoDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreOrderCartInfo::class;
    }

    /**
     * 获取购物车详情列表
     * @param array $where
     * @param array $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartInfoList(array $where, array $field)
    {
        return $this->search($where)->field($field)->select()->toArray();
    }

    /**
     * 获取购物车信息以数组返回
     * @param array $where
     * @param string $field
     * @param string $key
     */
    public function getCartColunm(array $where, string $field, string $key)
    {
        return $this->search($where)->column($field, $key);
    }
}
