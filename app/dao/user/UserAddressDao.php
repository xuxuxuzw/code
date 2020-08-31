<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserAddress;

/**
 * 用户收获地址
 * Class UserAddressDao
 * @package app\dao\user
 */
class UserAddressDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserAddress::class;
    }

    /**
     * 获取列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page, int $limit): array
    {
        return $this->search($where)->field($field)->page($page, $limit)->select()->toArray();
    }
}