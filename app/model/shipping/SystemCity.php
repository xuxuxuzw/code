<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\model\shipping;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 城市数据
 * Class SystemCity
 * @package app\model\shipping
 */
class SystemCity extends BaseModel
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
    protected $name = 'system_city';

    /**
     * 获取子集分类查询条件
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'city_id')->order('id ASC');
    }


    /**
     * city搜索器
     * @param Model $query
     * @param $value
     */
    public function searchCityIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('city_id', $value);
        } else {
            $query->where('city_id', $value);
        }
    }

    /**
     * ParentId搜索器
     * @param Model $query
     * @param $value
     */
    public function searchParentIdAttr($query, $value)
    {
        $query->where('parent_id', $value);
    }


}