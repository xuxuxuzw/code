<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\system\store;

use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 店员模型
 * Class SystemStoreStaff
 * @package app\model\system\store
 */
class SystemStoreStaff extends BaseModel
{
    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'system_store_staff';

    /**
     * user用户表一对一关联
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field(['uid', 'nickname'])->bind([
            'nickname' => 'nickname'
        ]);
    }

    /**
     * 门店表一对一关联
     * @return \think\model\relation\HasOne
     */
    public function store()
    {
        return $this->hasOne(SystemStore::class, 'id', 'store_id')->field(['id', 'name'])->bind([
            'name' => 'name'
        ]);
    }

    /**
     * 时间戳获取器转日期
     * @param $value
     * @return false|string
     */
    public static function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 是否有核销权限搜索器
     * @param Model $query
     * @param $value 用户uid
     */
    public function searchIsStatusAttr($query, $value)
    {
        $query->where(['uid' => $value, 'status' => 1, 'verify_status' => 1]);
    }

    /**
     * uid搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    /**
     * 门店id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value && $value > 0) {
            $query->where('store_id', $value);
        }
    }
}