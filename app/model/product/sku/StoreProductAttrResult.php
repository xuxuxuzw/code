<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */
namespace app\model\product\sku;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * Class StoreProductAttrResult
 * @package app\common\model\product
 */
class StoreProductAttrResult extends BaseModel
{

    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product_attr_result';

    protected $insert = ['change_time'];

    /**
     * 自动增加改变时间
     * @param $value
     * @return int
     */
    protected static function setChangeTimeAttr($value)
    {
        return time();
    }

    /**
     * 数据json化
     * @param $value
     * @return false|string
     */
    protected static function setResultAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 商品搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchProductIdAttr($query, $value)
    {
        $query->where('product_id', $value);
    }

    /**
     * 商品类型搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }
}
