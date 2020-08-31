<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-15
 */

namespace app\model\diy;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

class Diy extends BaseModel
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
    protected $name = 'diy';

    /**
     * 添加时间获取器
     * @param $value
     * @return false|string
     */
    public function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 修改时间获取器
     * @param $value
     * @return false|string
     */
    public function getUpdateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 类型搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value != '') $query->where('type', $value);
    }

    /**
     * 版本号搜索器
     * @param Model $query
     * @param $value
     */
    public function searchVersionAttr($query, $value)
    {
        if ($value != '') $query->where('version', $value);
    }

    /**
     * 是否使用搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value != '') $query->where('status', $value);
    }

    /**
     * 名称搜索器
     * @param Model $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if ($value != '') $query->where('name', $value);
    }

}
