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
 * 商品规则
 * Class StoreProductRule
 * @package app\common\model\product
 */
class StoreProductRule extends BaseModel
{
    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product_rule';

    /**
     * 属性模板名称搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchRuleNameAttr($query, $value)
    {
        $query->where('rule_name', 'like', '%' . $value . '%');
    }
}
