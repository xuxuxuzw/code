<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserExtract;

/**
 *
 * Class UserExtractDao
 * @package app\dao\user
 */
class UserExtractDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserExtract::class;
    }

    /**
     * 获取某个条件的提现总和
     * @param array $where
     * @return float
     */
    public function getWhereSum(array $where)
    {
        return $this->search($where)->sum('extract_price');
    }

    /**
     * 获取某些条件总数组合列表
     * @param array $where
     * @param string $field
     * @param string $key
     * @return mixed
     */
    public function getWhereSumList(array $where, string $field = 'extract_price', string $key = 'uid')
    {
        return $this->search($where)->group($key)->column('sum(' . $field . ')', $key);
    }

    /**
     * 获取提现列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExtractList(array $where, string $field = '*', int $page, int $limit)
    {
        return $this->search($where)->field($field)->with([
            'user' => function ($query) {
                $query->field('uid,nickname');
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
