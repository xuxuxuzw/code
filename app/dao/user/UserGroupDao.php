<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserGroup;

/**
 *
 * Class UserGroupDao
 * @package app\dao\user
 */
class UserGroupDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserGroup::class;
    }

    /**
     * 获取列表
     * @param array $where
     * @param string $feild
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where = [], string $feild = '*', int $page = 0, int $limit)
    {
        return $this->search($where)->field($feild)->page($page, $limit)->select()->toArray();
    }
}