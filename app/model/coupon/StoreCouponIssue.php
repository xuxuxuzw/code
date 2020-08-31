<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\coupon;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * TODO 发布优惠券Model
 * Class StoreCouponIssue
 * @package app\model\coupon
 */
class StoreCouponIssue extends BaseModel
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
    protected $name = 'store_coupon_issue';

    /**
     * 文章详情一对一关联
     * @return \think\model\relation\HasOne
     */
    public function coupon()
    {
        return $this->hasOne(StoreCoupon::class, 'id', 'cid');
    }

    /**
     * 用户是否拥有
     * @return \think\model\relation\HasOne
     */
    public function used()
    {
        return $this->hasOne(StoreCouponIssueUser::class, 'issue_coupon_id', 'id')->field('issue_coupon_id');
    }

    /**
     * id
     * @param Model $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value))
            $query->whereIn('id', $value);
        else
            $query->where('id', $value);
    }

    /**
     * 优惠券模板搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchCidAttr($query, $value, $data)
    {
        $query->where('cid', $value);
    }

    /**
     * 优惠券是否不限量
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsPermanentAttr($query, $value, $data)
    {
        $query->where('is_permanent', $value);
    }

    /**
     * 优惠券是否新人券
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsGiveSubscribeAttr($query, $value, $data)
    {
        $query->where('is_give_subscribe', $value);
    }

    /**
     * 优惠券是否满赠
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsFullGiveAttr($query, $value, $data)
    {
        $query->where('is_full_give', $value);
    }

    /**
     * 优惠券状态
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStatusAttr($query, $value, $data)
    {
        if ($value != '') $query->where('status', $value);
    }

    /**
     * 优惠券是否删除
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsDelAttr($query, $value, $data)
    {
        $query->where('is_del', $value ?? 0);
    }

    /**
     * 优惠券名称
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchCouponTitleAttr($query, $value, $data)
    {
        if ($value) $query->whereLike('coupon_title', '%' . $value . '%');
    }
}
