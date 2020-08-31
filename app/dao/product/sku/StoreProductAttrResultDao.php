<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\dao\product\sku;

use app\dao\BaseDao;
use app\model\product\sku\StoreProductAttrResult;

/**
 * Class StoreProductAttrResultDao
 * @package app\dao\product\sku
 */
class StoreProductAttrResultDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductAttrResult::class;
    }

    /**
     * 删除指定条件下的sku
     * @param int $id
     * @param int $type
     * @return bool
     * @throws \Exception
     */
    public function del(int $id, int $type)
    {
        return $this->search(['product_id' => $id, 'type' => $type])->delete();
    }
}
