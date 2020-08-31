<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\dao\service;

use app\dao\BaseDao;
use app\model\service\StoreService;

/**
 * 客服dao
 * Class StoreServiceDao
 * @package app\dao\service
 */
class StoreServiceDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreService::class;
    }

    /**
     * 获取客服列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getServiceList(array $where, int $page, int $limit)
    {
        return $this->search($where)->order('id DESC')->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取接受通知的客服
     * @return array
     */
    public function getStoreServiceOrderNotice(int $customer = 0)
    {
        return $this->getModel()::where(['status' => 1, 'notify' => 1])->when($customer, function ($query) use ($customer) {
            $query->where('customer', $customer);
        })->field(['nickname', 'phone'])->select()->toArray();
    }

}