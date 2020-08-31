<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\dao\product\product;


use app\dao\BaseDao;
use app\model\product\product\StoreProduct;
use think\facade\Config;

/**
 * Class StoreProductDao
 * @package app\dao\product\product
 */
class StoreProductDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProduct::class;
    }

    /**
     * 条件获取数量
     * @param array $where
     * @return int
     */
    public function getCount(array $where)
    {
        return $this->search($where)->count();
    }

    /**
     * 获取商品列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, int $page = 0, int $limit = 0)
    {
        $prefix = Config::get('database.connections.' . Config::get('database.default') . '.prefix');
        return $this->search($where)->order('sort desc,id desc')
            ->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->field([
                '*',
                '(SELECT count(*) FROM `' . $prefix . 'store_product_relation` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'collect\') as collect',
                '(SELECT count(*) FROM `' . $prefix . 'store_product_relation` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'like\') as likes',
                '(SELECT SUM(stock) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as stork',
                '(SELECT SUM(sales) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as sales',
                '(SELECT count(*) FROM `' . $prefix . 'store_visit` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `product_type` = \'product\') as visitor',
            ])->select()->toArray();
    }

    /**
     * 获取商品详情
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        return $this->search()->with('coupons')->find($id);
    }

    /**
     * 条件获取商品列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param array|string[] $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSearchList(array $where, int $page = 0, int $limit = 0, array $field = ['*'])
    {
        return $this->search($where)->where(['is_show' => 1, 'is_del' => 0])->with(['couponId', 'description'])->when($page != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(isset($where['sid']) && $where['sid'], function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('store_product_cate')->where('cate_id', $where['sid'])->field('product_id')->select();
            });
        })->when(isset($where['cid']) && $where['cid'], function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($query) use ($where) {
                    $query->name('store_category')->where('pid', $where['cid'])->field('id')->select();
                })->field('product_id')->select();
            });
        })->when(isset($where['priceOrder']) && $where['priceOrder'] != '', function ($query) use ($where) {
            if ($where['priceOrder'] === 'desc') {
                $query->order("price desc");
            } else {
                $query->order("price asc");
            }
        })->when(isset($where['salesOrder']) && $where['salesOrder'] != '', function ($query) use ($where) {
            if ($where['salesOrder'] === 'desc') {
                $query->order("sales desc");
            } else {
                $query->order("sales asc");
            }
        })->field($field)->order('sort desc,id desc')->select()->toArray();
    }

    /**商品列表
     * @param array $where
     * @param $limit
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductLimit(array $where, $limit, $field)
    {
        return $this->search($where)->field($field)->order('val', 'desc')->limit($limit)->select()->toArray();

    }

    /**
     * 根据id获取商品数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function idByProductList(array $ids, string $field)
    {
        return $this->getModel()->whereIn('id', $ids)->field($field)->select()->toArray();
    }

    /**
     * 获取推荐商品
     * @param string $field
     * @param int $num
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecommendProduct(string $field, int $num = 0, int $page = 0, int $limit = 0)
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        $where[$field] = 1;
        return $this->search($where)->with('couponId')
            ->field(['id', 'image', 'store_name', 'cate_id', 'price', 'ot_price', 'IFNULL(sales,0) + IFNULL(ficti,0) as sales', 'unit_name', 'sort', 'activity', 'stock'])
            ->when($num, function ($query) use ($num) {
                $query->limit($num);
            })
            ->when($page, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->select()->toArray();
    }
}
