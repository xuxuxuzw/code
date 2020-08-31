<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreProductCate;

/**
 * Class StoreProductCateDao
 * @package app\dao\product\product
 */
class StoreProductCateDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductCate::class;
    }

    /**
     * 保存数据
     * @param array $data
     * @return mixed|void
     */
    public function saveAll(array $data)
    {
        $this->getModel()->insertAll($data);
    }

    /**
     * 根据商品id获取分类id
     * @param array $productId
     * @return array
     */
    public function productIdByCateId(array $productId)
    {
        return $this->getModel()->whereIn('product_id', $productId)->column('cate_id');
    }
}
