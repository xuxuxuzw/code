<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/4
 */
declare (strict_types=1);

namespace app\dao\system;

use app\dao\BaseDao;
use app\model\system\SystemUserLevel;

/**
 *
 * Class SystemUserLevelDao
 * @package app\dao\system
 */
class SystemUserLevelDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SystemUserLevel::class;
    }

    /**
     * 获取列表
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, $limit = 0)
    {
        return $this->getModel()->where($where)->field($field)->page($page, $limit)->order('grade asc')->select()->toArray();
    }

    /**
     * 复杂条件获取总数
     * @param array $where
     * @return int
     */
    public function getCount(array $where)
    {
        return $this->getModel()->where($where)->count();
    }
}
