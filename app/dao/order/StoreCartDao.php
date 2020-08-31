<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/8
 */
declare (strict_types=1);

namespace app\dao\order;

use app\dao\BaseDao;
use app\model\order\StoreCart;

/**
 *
 * Class StoreCartDao
 * @package app\dao\order
 */
class StoreCartDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCart::class;
    }

    /**
     * @param array $where
     * @param array $unique
     * @return array
     */
    public function getUserCartNums(array $where, array $unique)
    {
        return $this->search($where)->whereIn('product_attr_unique', $unique)->column('cart_num', 'product_attr_unique');
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['id']) && $where['id'], function ($query) use ($where) {
            $query->whereIn('id', $where['id']);
        })->when(isset($where['status']), function ($query) use ($where) {
            $query->where('status', $where['status']);
        });
    }

    /**
     * 获取购物车列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartList(array $where, int $page = 0, int $limit = 0, array $with = [])
    {
        return $this->search($where)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->order('add_time DESC')->select()->toArray();
    }

    /**
     * 修改购物车数据未已删除
     * @param array $id
     * @param array $data
     * @return \crmeb\basic\BaseModel
     */
    public function updateDel(array $id)
    {
        return $this->getModel()->whereIn('id', $id)->update(['is_del' => 1]);
    }

    /**
     * 删除购物车
     * @param int $uid
     * @param array $ids
     * @return bool
     * @throws \Exception
     */
    public function removeUserCart(int $uid, array $ids)
    {
        return $this->getModel()->where('uid', $uid)->whereIn('id', $ids)->delete();
    }

    /**
     * 获取购物车数量
     * @param $uid
     * @param $type
     * @param $numType
     */
    public function getUserCartNum($uid, $type, $numType)
    {
        $model = $this->getModel()->where(['uid' => $uid, 'type' => $type, 'is_pay' => 0, 'is_new' => 0, 'is_del' => 0]);
        if ($numType) {
            return $model->count();
        } else {
            return $model->sum('cart_num');
        }
    }

    /**
     * 修改购物车数量
     * @param $cartId
     * @param $cartNum
     * @param $uid
     */
    public function changeUserCartNum(array $where, int $carNum)
    {
        return $this->getModel()->where($where)->update(['cart_num' => $carNum]);
    }

    /**
     * 修改购物车状态
     * @param $cartIds
     * @return \crmeb\basic\BaseModel
     */
    public function deleteCartStatus($cartIds)
    {
        return $this->getModel()->where('id', 'IN', $cartIds)->delete();
    }

    /**
     * 获取购物车最大的id
     * @return mixed
     */
    public function getCartIdMax()
    {
        return $this->getModel()->max('id');
    }
}
