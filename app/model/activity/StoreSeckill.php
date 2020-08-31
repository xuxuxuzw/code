<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\activity;

use app\model\product\product\StoreDescription;
use app\model\product\product\StoreProduct;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * TODO 秒杀商品Model
 * Class StoreSeckill
 * @package app\model\activity
 */
class StoreSeckill extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_seckill';

    use ModelTrait;

    /**
     * 一对一关联
     * 商品关联商品商品详情
     * @return \think\model\relation\HasOne
     */
    public function description()
    {
        return $this->hasOne(StoreDescription::class, 'product_id', 'id')->where('type', 1);
    }
    /**
     * 一对一关联
     * 商品关联商品商品详情
     * @return \think\model\relation\HasOne
     */
    public function product()
    {
        return $this->hasOne(StoreProduct::class, 'id', 'product_id')->where('is_show', 1)->where('is_del', 0)->field(['SUM(sales+ficti) as total','id'])->bind([
            'total'=>'total'
        ]);
    }

    /**
     * 添加时间获取器
     * @param $value
     * @return false|string
     */
    protected function getAddTimeAttr($value)
    {
        if ($value) return date('Y-m-d H:i:s', (int)$value);
        return '';
    }

    /**
     * 图片获取器
     * @param $value
     * @return array|mixed
     */
    protected function getImagesAttr($value)
    {
        return json_decode($value, true) ?: [];
    }

    /**
     * 秒杀商品名称搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStoreNameAttr($query, $value, $data)
    {
        if ($value) $query->where('title|id', 'like', '%' . $value . '%');
    }

    /**
     * 是否推荐搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsHotAttr($query, $value, $data)
    {
        $query->where('is_hot', $value ?? 1);
    }

    /**
     * 状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsShowAttr($query, $value, $data)
    {
        $query->where('is_show', $value ?? 1);
    }

    /**
     * 是否删除搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsDelAttr($query, $value, $data)
    {
        $query->where('is_del', $value ?? 0);
    }

    /**
     * 状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStatusAttr($query, $value, $data)
    {
        if ($value != '') $query->where('status', $value);
    }

    /**
     * 商品ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchProductIdAttr($query, $value, $data)
    {
        if ($value) $query->where('product_id', $value);
    }
}
