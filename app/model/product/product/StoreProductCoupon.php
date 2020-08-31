<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-03
 */

namespace app\model\product\product;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * Class StoreProductCoupon
 * @package app\model\product\product
 */
class StoreProductCoupon extends BaseModel
{
    use  ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product_coupon';


    public function searchProductIdAttr($query, $value)
    {
        if(is_array($value))
            $query->whereIn('product_id',$value);
        else
            $query->where('product_id',$value);
    }

}
