<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */
namespace app\model\product\product;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;

/**
 *  商品浏览分析Model
 * Class StoreVisit
 * @package app\model\product\product
 */
class StoreVisit extends BaseModel
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
    protected $name = 'store_visit';

}
