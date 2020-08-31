<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\product\product;

use app\model\order\StoreOrderCartInfo;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 *  商品评价Model
 * Class StoreProductReply
 * @package app\model\product\product
 */
class StoreProductReply extends BaseModel
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
    protected $name = 'store_product_reply';

    protected $insert = ['add_time'];

    /**
     * 一对一关联
     * 商品评论关联商品
     * @return \think\model\relation\HasOne
     */
    public function productInfo()
    {
        return $this->hasOne(StoreProduct::class, 'id', 'product_id');
    }

    /**
     * 一对一关联
     * 商品评论关联订单
     * @return \think\model\relation\HasOne
     */
    public function cartInfo()
    {
        return $this->hasOne(StoreOrderCartInfo::class, 'product_id', 'id')->bind(['cart_info']);
    }

    /**
     * 添加时间修改器
     * @return int
     */
    protected function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 评价图片修改器
     * @param $value
     * @return false|string
     */
    protected function setPicsAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    /**
     * 评价图片获取器
     * @param $value
     * @return mixed
     */
    protected function getPicsAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * 用户搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    /**
     * 商品搜索器
     * @param Model $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        $query->where('product_id', $value);
    }

    /**
     * 是否删除搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value ?? 0);
    }

    /**
     * 是否回复搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsReplyAttr($query, $value)
    {
        $query->where('is_reply', $value);
    }

    /**
     * @param Model $query
     * @param $value
     */
    public function searchUniqueAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('unique', $value);
        } else {
            $query->where('unique', $value);
        }
    }

    /**
     * oid订单id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchOidAttr($query, $value)
    {
        $query->where('oid', $value);
    }

    /**
     * 商品分数搜索器
     * @param Model $query
     * @param $value
     */
    public function searchProductScoreAttr($query, $value)
    {
        $query->where('product_score', $value);
    }
}
