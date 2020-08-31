<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/8
 */

namespace app\services\order;


use app\dao\order\StoreOrderStatusDao;
use app\services\BaseServices;

/**
 * 订单状态
 * Class StoreOrderStatusServices
 * @package app\services\order
 * @method save(array $data) 保存数据
 * @method value(array $where, ?string $field = '') 获取指定键值
 */
class StoreOrderStatusServices extends BaseServices
{
    /**
     * 构造方法
     * StoreOrderStatusServices constructor.
     * @param StoreOrderStatusDao $dao
     */
    public function __construct(StoreOrderStatusDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 订单状态分页
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStatusList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getStatusList($where, $page, $limit);
        foreach ($list as &$item) {
            if (is_int($item['change_time'])) $item['change_time'] = date('Y-m-d H:i:s', $item['change_time']);
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

}
