<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */
namespace app\model\other;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\Model;

/**
 *  缓存Model
 * Class Cache
 * @package app\model\other
 */
class Cache extends BaseModel
{
    use ModelTrait;

    const EXPIRE = 0;
    /**
     * 模型名称
     * @var string
     */
    protected $name = 'cache';

    /**
     * 缓存KEY搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchKeyAttr($query, $value, $data)
    {
        $query->where('key', $value);
    }
}
