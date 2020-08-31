<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\dao\activity;

use app\dao\BaseDao;
use app\model\activity\StoreBargain;

/**
 *
 * Class StoreBargainDao
 * @package app\dao\activity
 */
class StoreBargainDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBargain::class;
    }

    /**
     * 获取砍价列表
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
        return $this->search($where)->where('is_del', 0)->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 获取活动开启中的砍价id以数组形式返回
     * @param array $ids
     * @param array $field
     * @return array
     */
    public function getBargainIdsArray(array $ids, array $field = [])
    {
        return $this->search(['is_del' => 0, 'status' => 1])->whereIn('product_id', $ids)->column(implode(',', $field), 'product_id');
    }

    /**
     * 根据id获取砍价数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function idByBargainList(array $ids, string $field)
    {
        return $this->getModel()->whereIn('id', $ids)->field($field)->select()->toArray();
    }

    /**
     * 正在开启的砍价活动
     * @param int $status
     * @return StoreBargain
     */
    public function validWhere(int $status = 1)
    {
        return $this->getModel()->where('is_del', 0)->where('status', $status)->where('start_time', '<', time())->where('stop_time', '>', time() - 86400);

    }

    /**
     * 砍价列表
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bargainList(int $page, int $limit)
    {
        return $this->search(['is_del' => 0, 'status' => 1])
            ->where('start_time', '<=', time())
            ->where('stop_time', '>=', time() - 86400)
            ->where('product_id', 'IN', function ($query) {
                $query->name('store_product')->where('is_show', 1)->where('is_del', 0)->field('id');
            })->page($page, $limit)->select()->toArray();
    }

    /**
     * 修改砍价状态
     * @param int $id
     * @param string $field
     * @return mixed
     */
    public function addBargain(int $id, string $field)
    {
        return $this->getModel()->where('id', $id)->inc($field, 1)->update();
    }
}
