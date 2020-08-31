<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/31
 */

namespace app\model\user;


use crmeb\basic\BaseModel;
use think\Model;

/**
 * 佣金冻结
 * Class UserBrokerageFrozen
 * @package app\model\user
 */
class UserBrokerageFrozen extends BaseModel
{

    /**
     * 设置主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 设置表名
     * @var string
     */
    protected $name = 'user_brokerage_frozen';


    /**
     * 用户id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    /**
     * 状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * @param Model $query
     * @param $value
     */
    public function searchOrderIdAttr($query, $value)
    {
        $query->where('order_id', $value);
    }
}
