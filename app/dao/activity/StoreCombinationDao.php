<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\dao\activity;

use app\dao\BaseDao;
use app\model\activity\StoreCombination;

/**
 *
 * Class StoreCombinationDao
 * @package app\dao\activity
 */
class StoreCombinationDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCombination::class;
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['pinkIngTime']), function ($query) use ($where) {
            [$startTime, $stopTime] = is_array($where['pinkIngTime']) ? $where['pinkIngTime'] : [time(), time() - 86400];
            $query->where('start_time', '<=', $startTime)->where('stop_time', '>=', $stopTime);
        })->when(isset($where['storeProductId']), function ($query) {
            $query->where('product_id', 'IN', function ($query) {
                $query->name('store_product')->where('is_show', 1)->where('is_del', 0)->field('id');
            });
        });
    }

    /**
     * 获取指定条件下的条数
     * @param array $where
     * @return int
     */
    public function count(array $where = []): int
    {
        return $this->search($where)->count();
    }

    /**
     * 拼团商品列表
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
        return $this->search($where)->where('is_del', 0)->with('getPrice')->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 获取正在进行拼团的商品以数组形式返回
     * @param array $ids
     * @param array $field
     * @return array
     */
    public function getPinkIdsArray(array $ids, array $field = [])
    {
        return $this->search(['is_del' => 0, 'is_show' => 1, 'pinkIngTime' => 1])->whereIn('product_id', $ids)->column(implode(',', $field), 'product_id');
    }

    /**
     * 获取拼团列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function combinationList(array $where, int $page, int $limit)
    {
        return $this->search($where)->with('getPrice')->page($page, $limit)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 根据id获取拼团数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function idCombinationList(array $ids, string $field)
    {
        return $this->getModel()->whereIn('id', $ids)->field($field)->select()->toArray();
    }

    /**
     * 获取一条拼团数据
     * @param int $id
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function validProduct(int $id, string $field)
    {
        $where = ['is_show' => 1, 'is_del' => 0, 'pinkIngTime' => true];
        return $this->search($where)->where('id', $id)->with(['total'])->field($field)->find();
    }

    /**
     * 获取推荐拼团
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCombinationHost()
    {
        $where = ['is_del' => 0, 'is_host' => 1, 'is_show' => 1, 'pinkIngTime' => true];
        return $this->search($where)->select()->toArray();
    }
}
