<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/
 */
declare (strict_types=1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\User;
use app\model\user\UserBill;


/**
 *
 * Class UserUserBillDao
 * @package app\dao\user
 */
class UserUserBillDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return User::class;
    }

    public function joinModel(): string
    {
        return UserBill::class;
    }

    /**
     * 关联模型
     * @param string $alias
     * @param string $join_alias
     * @return \crmeb\basic\BaseModel
     */
    public function getModel(string $alias = 'u', string $join_alias = 'b', $join = 'left')
    {
        $this->alias = $alias;
        $this->join_alis = $join_alias;
        /** @var $userBiil $userBiil */
        $userBiil = app()->make($this->joinModel());
        $table = $userBiil->getName();
        return parent::getModel()::join($table . ' ' . $join_alias, $alias . '.uid = ' . $join_alias . '.uid', $join)->alias($alias);
    }

    /**
     * 组合条件模型查询列表
     * @param Model $model
     * @return array
     */
    public function getList(array $where, string $field = '', int $page, int $limit)
    {
        return $this->getModel()->where($where)->field($field)->group('u.uid')->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取条数
     * @param array $where
     * @return mixed
     */
    public function getCount(array $where)
    {
        return $this->getModel()->where($where)->group('u.uid')->count();
    }
}
