<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-02
 */

namespace app\dao\product\product;

use app\dao\BaseDao;
use app\model\product\product\StoreCategory;

/**
 * Class StoreCategoryDao
 * @package app\dao\product\product
 */
class StoreCategoryDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCategory::class;
    }

    /**
     * 获取分类列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        return $this->search($where)->with('children')->order('sort desc,id desc')->select()->toArray();
    }

    /**
     *
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTierList(array $where = [])
    {
        return $this->search($where)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 添加修改选择上级分类列表
     * @param array $where
     * @return array
     */
    public function getMenus(array $where)
    {
        return $this->search($where)->column('cate_name,id');
    }

    /**
     * 根据id获取分类
     * @param string $cateIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCateArray(string $cateIds)
    {
        return $this->search(['id' => $cateIds])->field('cate_name,id')->select()->toArray();
    }

    /**
     * 前端分类页面分离列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCategory()
    {
        return $this->getModel()->with('children')->where('is_show', 1)->where('pid', 0)->order('sort desc,id desc')->hidden(['add_time', 'is_show', 'sort', 'children.sort', 'children.add_time', 'children.pid', 'children.is_show'])->select()->toArray();
    }

    /**
     * 根据分类id获取上级id
     * @param array $cateId
     * @return array
     */
    public function cateIdByPid(array $cateId)
    {
        return $this->getModel()->whereIn('id', $cateId)->column('pid');
    }

    /**
     * 获取首页展示的二级分类  排序默认降序
     * @param int $limit
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function byIndexList($limit = 4, $field = 'id,cate_name,pid,pic')
    {
        return $this->getModel()->where('pid', '>', 0)->where('is_show', 1)->field($field)->order('sort DESC')->limit($limit)->select()->toArray();
    }

    /**
     * 获取一级分类和二级分类组成的集合
     * @param $cateId
     * @return mixed
     */
    public function getCateParentAndChildName(string $cateId)
    {
        return $this->getModel()->alias('c')->join('StoreCategory b', 'b.id = c.pid')
            ->where('c.id', 'IN', $cateId)->field('c.cate_name as two,b.cate_name as one,c.id')
            ->select()->toArray();
    }
}
