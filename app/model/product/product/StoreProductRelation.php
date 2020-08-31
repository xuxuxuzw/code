<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */
namespace app\model\product\product;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\Model;

/**
 *  点赞收藏model
 * Class StoreProductRelation
 * @package app\model\product\product
 */
class StoreProductRelation extends BaseModel
{
    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product_relation';

    /**
     * 关联商品
     */
    public function product()
    {
        return $this->hasOne(StoreProduct::class,'id','product_id');
    }
    /**
     * 用户搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    /**
     * 商品搜索器
     * @param Model $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        $query->where('product_id', $value);
    }

    /**
     * 类型搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }

    /**
     * 商品类型搜索器
     * @param Model $query
     * @param $value
     */
    public function searchCategoryAttr($query, $value)
    {
        $query->where('category', $value);
    }
}
