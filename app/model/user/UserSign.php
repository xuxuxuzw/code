<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\user;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\model;

/**
 * Class UserSign
 * @package app\model\user
 */
class UserSign extends BaseModel
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
    protected $name = 'user_sign';

    /**
     * 用户uid
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if(is_array($value))
            $query->whereIn('uid', $value);
        else
            $query->where('uid', $value);

    }

    /**
     * 时间
     * @param Model $query
     * @param $value
     */
    public function searchAddTimeAttr($query, $value)
    {
        if (is_string($value)) $query->whereTime('add_time', $value);
        if (is_array($value) && count($value) == 2) $query->whereTime('add_time', 'BETWEEN', $value);
    }
}