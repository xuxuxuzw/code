<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\wechat;

use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * Class WechatUser
 * @package app\model\wechat
 */
class WechatUser extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'uid';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'wechat_user';

    protected $insert = ['add_time'];

    public static function setAddTimeAttr($value)
    {
        return time();
    }

    protected function getAddTimeAttr($value)
    {
        if ($value) return date('Y-m-d H:i', (int)$value);
        return $value;
    }

    /**
     * 关联user
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    /**
     * 绑定公众号
     * @param Model $query
     * @param $value
     */
    public function searchUnionidAttr($query, $value)
    {
        return $query->where('unionid', $value);
    }

    /**
     * 公众号唯一id
     * @param Model $query
     * @param $value
     */
    public function searchOpenidAttr($query, $value)
    {
        return $query->where('openid', $value);
    }

    /**
     * 分组
     * @param Model $query
     * @param $value
     */
    public function searchGroupIdAttr($query, $value)
    {
        return $query->where('group_id', $value);
    }

    /**
     * 性别
     * @param Model $query
     * @param $value
     */
    public function searchSexAttr($query, $value)
    {
        return $query->where('sex', $value);
    }

    /**
     * 是否关注
     * @param Model $query
     * @param $value
     */
    public function searchSubscribeAttr($query, $value)
    {
        return $query->where('subscribe', $value);
    }

    /**
     * 用户类型
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        return $query->where('user_type', $value);
    }

    /**
     * 用户类型
     * @param Model $query
     * @param $value
     */
    public function searchUserTypeAttr($query, $value)
    {
        return $query->where('user_type', $value);
    }

}
