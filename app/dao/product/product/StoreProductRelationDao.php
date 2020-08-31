<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreProductRelation;

/**
 * Class StoreProductRelationDao
 * @package app\dao\product\product
 */
class StoreProductRelationDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductRelation::class;
    }

    /**
     * 获取收藏列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field, int $page, int $limit)
    {
        return $this->search($where)->field($field)->with(['product'])->page($page, $limit)->select()->toArray();
    }
}
