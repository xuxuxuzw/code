<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreDescription;

/**
 * Class StoreDescriptionDao
 * @package app\dao\product\product
 */
class StoreDescriptionDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreDescription::class;
    }

    /**
     * 根据条件获取商品详情
     * @param array $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDescription(array $where)
    {
        return $this->getOne($where);
    }
}
