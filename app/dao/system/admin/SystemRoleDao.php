<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\dao\system\admin;

use app\dao\BaseDao;
use app\model\system\admin\SystemRole;

/**
 * Class SystemRoleDao
 * @package app\dao\system\admin
 */
class SystemRoleDao extends BaseDao
{
    /**
     * 设置模型名
     * @return string
     */
    protected function setModel(): string
    {
        return SystemRole::class;
    }

    /**
     * 获取权限
     * @param string $field
     * @param string $key
     * @return mixed
     */
    public function getRoule(array $where = [], ?string $field = null, ?string $key = null)
    {
        return $this->search($where)->column($field ?: 'role_name', $key ?: 'id');
    }

    /**
     * 获取身份列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRouleList(array $where, int $page, int $limit)
    {
        return $this->search($where)->page($page, $limit)->select()->toArray();
    }
}