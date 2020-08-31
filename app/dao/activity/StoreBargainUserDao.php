<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/7
 */
declare (strict_types=1);

namespace app\dao\activity;

use app\dao\BaseDao;
use app\model\activity\StoreBargainUser;

/**
 *
 * Class StoreBargainUserDao
 * @package app\dao\activity
 */
class StoreBargainUserDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBargainUser::class;
    }

    /**
     * 获取帮砍数量
     * @param array $where
     * @return array
     */
    public function getAllCount(array $where = [])
    {
        return $this->getColumn($where, 'count(*)', 'bargain_id');
    }

    /**
     * 获取砍价表ID
     * @param int $bargainId $bargainId 砍价商品
     * @param int $bargainUserUid $bargainUserUid  开启砍价用户编号
     * @param int $status $status  砍价状态 1参与中 2 活动结束参与失败 3活动结束参与成功
     * @return mixed
     */
    public function getBargainUserTableId(int $bargainId = 0, int $bargainUserUid = 0)
    {
        return $this->value(['bargain_id' => $bargainId, 'uid' => $bargainUserUid, 'is_del' => 0], 'id') ?? 0;
    }

    /**
     * 获取用户砍价列表
     * @param int $bargainUserUid
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userAll(int $bargainUserUid, int $page, int $limit)
    {
        return $this->search(['uid' => $bargainUserUid, 'is_del' => 0])->with('getBargain')->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取砍价状态
     * @param $bargainId
     * @param $uid
     * @return mixed
     */
    public function getBargainUserStatus($bargainId, $uid)
    {
        return $this->search(['bargain_id' => $bargainId, 'uid' => $uid])->order('add_time DESC')->value('status');
    }


    /**
     * 修改砍价状态
     * @param int $id
     * @param int $status
     * @return \crmeb\basic\BaseModel
     */
    public function updateBargainStatus(int $id, int $status = 3)
    {
        return $this->getModel()->where('id', $id)->where('status', 1)->update(['status' => $status]);
    }


}
