<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreProductCoupon;

/**
 *
 * Class StoreProductCouponDao
 * @package app\dao\coupon
 */
class StoreProductCouponDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductCoupon::class;
    }

    /**
     * 获取商品关联优惠卷
     * @param array $product_ids
     * @param string $field
     * @return int|void
     */
    public function getProductCoupon(array $product_ids, string $field = '*')
    {
        return $this->search(['product_id' => $product_ids])->field($field)->select()->toArray();
    }

}
