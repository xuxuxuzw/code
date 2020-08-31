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
 * Class UserAddress
 * @package app\model\user
 */
class UserAddress extends BaseModel
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
    protected $name = 'user_address';

    protected $insert = ['add_time'];

    protected $hidden = ['add_time', 'is_del', 'uid'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 用户uid
     * @param $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    /**
     * 是否删除
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value);
    }

    /**
     * 是否默认地址
     * @param Model $query
     * @param $value
     */
    public function searchIsDefaultAttr($query, $value)
    {
        $query->where('is_default', $value);
    }

}