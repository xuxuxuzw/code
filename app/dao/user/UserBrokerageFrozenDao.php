<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/31
 */

namespace app\dao\user;


use app\dao\BaseDao;
use app\model\user\UserBrokerageFrozen;

/**
 * 佣金冻结
 * Class UserBrokerageFrozenDao
 * @package app\dao\user
 */
class UserBrokerageFrozenDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserBrokerageFrozen::class;
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['isFrozen']), function ($query) use ($where) {
            if ($where['isFrozen']) {
                $query->where('frozen_time', '>', time());
            } else {
                $query->where('frozen_time', '<=', time());
            }
        });
    }

    /**
     * 获取某个账户下的冻结佣金
     * @param int $uid
     * @param bool $isFrozen 获取冻结之前或者冻结之后的总金额
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserFrozenPrice(int $uid, bool $isFrozen = true)
    {
        return $this->search(['uid' => $uid, 'status' => 1, 'isFrozen' => $isFrozen])->column('price', 'id');
    }

    /**
     * 修改佣金冻结状态
     * @param string $orderId
     * @return \crmeb\basic\BaseModel
     */
    public function updateFrozen(string $orderId)
    {
        return $this->search(['order_id' => $orderId, 'isFrozen' => true])->update(['status' => 0]);
    }

}
