<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/1
 */

namespace app\dao\system\config;


use app\dao\BaseDao;
use app\model\system\config\SystemConfigTab;

/**
 * 配置分类
 * Class SystemConfigTabDao
 * @package app\dao\system\config
 */
class SystemConfigTabDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SystemConfigTab::class;
    }

    /**
     * 获取配置分类
     * @param array $where
     * @param array $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigTabAll(array $where, array $field = ['*'])
    {
        return $this->search($where)->field($field)->order('sort desc,id asc')->select()->toArray();
    }

    /**
     * 配置分类列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfgTabList(array $where, int $page, int $limit)
    {
        return $this->search($where)->order('sort desc,id asc')->page($page, $limit)->select();
    }

}