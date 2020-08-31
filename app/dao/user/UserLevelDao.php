<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserLevel;

/**
 *
 * Class UserLevelDao
 * @package app\dao\user
 */
class UserLevelDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserLevel::class;
    }

    /**
     * 根据uid 获取用户会员等级详细信息
     * @param int $uid
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLevel(int $uid, string $field = '*')
    {
        return $this->getModel()->where('uid', $uid)->where('is_del', 0)->where('status', 1)->where('is_forever = 1 or ( is_forever = 0 and valid_time > ' . time() . ')')->field($field)->with(['levelInfo'])->order('grade desc')->find();
    }

    /**
     * 获取用户等级折扣
     * @param int $uid
     * @return mixed
     */
    public function getDiscount(int $uid)
    {
        return $this->search(['uid' => $uid, 'is_del' => 0, 'status' => 1])->order('id desc')->value('discount');
    }
}
