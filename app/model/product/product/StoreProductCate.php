<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */
namespace app\model\product\product;

use crmeb\traits\ModelTrait;
use think\Model;

/**
 *  商品分类关联Model
 * Class StoreProductCate
 * @package app\model\product\product
 */
class StoreProductCate extends Model
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product_cate';

}
