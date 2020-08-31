<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\dao\product\sku;

use app\dao\BaseDao;
use app\model\product\sku\StoreProductRule;

/**
 * Class StoreProductRuleDao
 * @package app\dao\product\sku
 */
class StoreProductRuleDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductRule::class;
    }

    /**
     * 获取列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where = [], int $page, int $limit)
    {
        return $this->search($where)->page($page, $limit)->order('id desc')->select()->toArray();
    }

    /**
     * 删除数据
     * @param string $ids
     * @throws \Exception
     */
    public function del(string $ids)
    {
        $this->getModel()->whereIn('id', $ids)->delete();
    }
}
