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
 * Class UserRecharge
 * @package app\model\user
 */
class UserRecharge extends BaseModel
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
    protected $name = 'user_recharge';

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 关联user
     * @return model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->bind([
            'nickname' => 'nickname',
            'avatar' => 'avatar'
        ]);
    }

    /**
     * 用户uid
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if (is_array($value))
            $query->whereIn('uid', $value);
        else
            $query->where('uid', $value);
    }

    /**
     * 订单号
     * @param Model $query
     * @param $value
     */
    public function searchOrderIdAttr($query, $value)
    {
        $query->where('order_id', $value);
    }

    /**
     * 充值类型
     * @param Model $query
     * @param $value
     */
    public function searchRechargeTypeAttr($query, $value)
    {
        $query->where('recharge_type', $value);
    }

    /**
     * 是否支付
     * @param Model $query
     * @param $value
     */
    public function searchPaidAttr($query, $value)
    {
        $query->where('paid', $value);
    }

    /**
     * 模糊搜索
     * @param Model $query
     * @param $value
     */
    public function searchLikeAttr($query, $value)
    {
        $query->where(function ($query) use ($value) {
            $query->whereLike('uid|order_id', "%" . $value . "%")->whereOr('uid', 'in', function ($query) use ($value) {
                $query->name('user')->whereLike('nickname', "%" . $value . "%")->field('uid')->select();
            });
        });
    }
}
