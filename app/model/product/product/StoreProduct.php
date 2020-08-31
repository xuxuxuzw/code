<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\product\product;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use app\model\coupon\StoreCouponProduct;
use think\Model;

/**
 *  商品Model
 * Class StoreProduct
 * @package app\model\product\product
 */
class StoreProduct extends BaseModel
{
    use  ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product';

    /**
     * 一对一关联
     * 商品关联商品商品详情
     * @return \think\model\relation\HasOne
     */
    public function description()
    {
        return $this->hasOne(StoreDescription::class, 'product_id', 'id')->where('type', 0)->bind(['description']);
    }

    /**
     * 一对多关联
     * 商品关联优惠卷模板id
     * @return \think\model\relation\HasMany
     */
    public function couponId()
    {
        return $this->hasMany(StoreCouponProduct::class, 'product_id', 'id');
    }

    /**
     * 优惠券名称一对多
     * @return \think\model\relation\HasMany
     */
    public function coupons()
    {
        return $this->hasMany(StoreProductCoupon::class, 'product_id', 'id');
    }

    /**
     * 轮播图获取器
     * @param $value
     * @return array|mixed
     */
    public function getSliderImageAttr($value)
    {
        return is_string($value) ? json_decode($value, true) : [];
    }

    /**
     * 是否显示搜索器
     * @param $query
     * @param $value
     */
    public function searchIsShowAttr($query, $value)
    {
        $query->where('is_show', $value ?? 1);
    }

    /**
     * @param Model $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * 是否删除搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value ?: 0);
    }

    /**
     * 商户ID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchMerIdAttr($query, $value)
    {
        $query->where('mer_id', $value ?? 0);
    }

    /**
     * keyword搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStoreNameAttr($query, $value, $data)
    {
        if ($value != '') $query->where('keyword|store_name|id', 'LIKE', htmlspecialchars("%$value%"));
    }

    /**
     * 新品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsNewAttr($query, $value)
    {
        if ($value) $query->where('is_new', $value);
    }

    /**
     * 优惠商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsBenefitAttr($query, $value)
    {
        $query->where('is_benefit', $value ?? 1);
    }

    /**
     * 热卖商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsHotAttr($query, $value)
    {
        $query->where('is_hot', $value ?? 1);
    }

    /**
     * 精品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsBestAttr($query, $value)
    {
        $query->where('is_best', $value ?? 1);
    }

    /**
     * 精品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsGoodAttr($query, $value)
    {
        $query->where('is_good', $value ?? 1);
    }

    /**
     * 标签商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchLabelIdAttr($query, $value)
    {
        $query->whereFindInSet('label_id', $value);
    }

    /**
     * SPU搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchSpuAttr($query, $value)
    {
        $query->where('spu', $value);
    }

    /**
     * 库存搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchStockAttr($query, $value)
    {
        $query->where('stock', $value);
    }

    /**
     * 分类搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchCateIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('id', function ($query) use ($value) {
                    $query->name('store_product_cate')->where('cate_id', 'IN', $value)->field('product_id')->select();
                });
            } else {
                $query->whereFindInSet('cate_id', $value);
            }
        }
    }

    /**
     * 商品数量条件搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value, $data)
    {
        switch ((int)$value) {
            case 1:
                $query->where(['is_show' => 1, 'is_del' => 0]);
                break;
            case 2:
                $query->where(['is_show' => 0, 'is_del' => 0]);
                break;
            case 3:
                $query->where(['is_del' => 0]);
                break;
            case 4:
                $query->where(['is_del' => 0])->where(function ($query) {
                    $query->whereIn('id', function ($query) {
                        $query->name('store_product_attr_value')->where('stock', 0)->where('type', 0)->field('product_id')->select();
                    })->whereOr('stock', 0);
                });
                break;
            case 5:
                if (isset($data['store_stock']) && $data['store_stock']) {
                    $store_stock = $data['store_stock'];
                    $query->where(['is_show' => 1, 'is_del' => 0])->where('stock', '<=', $store_stock)->where('stock', '>', 0);
                } else {
                    $query->where(['is_show' => 1, 'is_del' => 0])->where('stock', '>', 0);
                }
                break;
            case 6:
                $query->where(['is_del' => 1]);
                break;
        };
    }
}
