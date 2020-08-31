<?php
declare (strict_types=1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserRecharge;

/**
 *
 * Class UserRechargeDao
 * @package app\dao\user
 */
class UserRechargeDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserRecharge::class;
    }

    /**
     * 获取充值记录
     * @param array $where
     * @param string $filed
     * @param int $page
     * @param int $limit
     */
    public function getList(array $where, string $filed = '*', int $page, int $limit)
    {
        return $this->search($where)->field($filed)->with([
            'user' => function ($query) {
                $query->field('uid,phone,nickname,avatar');
            }])->page($page, $limit)->order('id desc')->select()->toArray();
    }

    /**
     * 获取某个字段总和
     * @param array $where
     * @param string $field
     * @return float
     */
    public function getWhereSumField(array $where, string $field)
    {
        return $this->search($where)->sum($field);
    }
}