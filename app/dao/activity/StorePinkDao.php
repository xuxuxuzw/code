<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/7
 */
declare (strict_types=1);

namespace app\dao\activity;

use app\dao\BaseDao;
use app\model\activity\StorePink;

/**
 *
 * Class StorePinkDao
 * @package app\dao\activity
 */
class StorePinkDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StorePink::class;
    }

    /**
     * 获取拼团数量集合
     * @param array $where
     * @return array
     */
    public function getPinkCount(array $where = [])
    {
        return $this->getModel()->where($where)->group('cid')->column('count(*)','cid');
    }

    /**
     * 获取列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, int $page = 0, int $limit = 0)
    {
        return $this->search($where)->with(['getUser', 'getProduct'])->when($page != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('stop_time desc')->select()->toArray();
    }

    /**
     * 获取正在拼团中的人,取最早写入的一条
     * @param array $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPinking(array $where)
    {
        return $this->search($where)->order('add_time asc')->find();
    }

    /**
     * 获取拼团列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pinkList(array $where)
    {
        return $this->search($where)
            ->where('stop_time', '>', time())
            ->with('getUser')
            ->order('add_time desc')
            ->field('id,uid,people,price,stop_time')
            ->select()->toArray();
    }

    /**
     * 获取正在拼团的人数
     * @param int $kid
     * @return int
     */
    public function getPinkPeople(int $kid)
    {
        return $this->count(['k_id' => $kid, 'is_refund' => 0]) + 1;
    }

    /**
     * 获取拼团成功的列表
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function successList(int $uid)
    {
        return $this->search(['status' => 2, 'is_refund' => 0])
            ->where('uid', '<>', $uid)
            ->with('getUser')->select()->toArray();
    }


    /**
     * 获取拼团完成的个数
     * @return float
     */
    public function getPinkOkSumTotalNum()
    {
        return $this->sum(['status' => 2, 'is_refund' => 0], 'total_num');
    }

    /**
     * 是否能继续拼团
     * @param int $id
     * @param int $uid
     * @return int
     */
    public function isPink(int $id, int $uid)
    {
        return $this->getModel()->where('k_id|id', $id)->where('uid', $uid)->where('is_refund', 0)->count();
    }

    /**
     * 获取一条拼团信息
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPinkUserOne(int $id)
    {
        return $this->search()->with(['getUser', 'getProduct'])->find($id);
    }

    /**
     * 获取拼团信息
     * @param array $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPinkUserList(array $where)
    {
        return $this->getModel()->where($where)->with(['getUser', 'getProduct'])->select()->toArray();
    }

    /**
     * 获取拼团结束的列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pinkListEnd()
    {
        return $this->getModel()->where('stop_time', '<=', time())
            ->where('status', 1)
            ->where('k_id', 0)
            ->where('is_refund', 0)
            ->field('id,people')->select()->toArray();
    }
}
