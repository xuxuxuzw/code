<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\product\product;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 *  商品详情Model
 * Class StoreDescription
 * @package app\model\product\product
 */
class StoreDescription extends BaseModel
{
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product_description';

    use ModelTrait;

    public function getDescriptionAttr($value)
    {
        return htmlspecialchars_decode($value);
    }

    /**
     * 商品ID搜索器
     * @param $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        if ($value) $query->where('product_id', $value);
    }

    /**
     * 类型搜索器
     * @param $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }
}
