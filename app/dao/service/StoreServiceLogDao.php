<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\dao\service;

use app\dao\BaseDao;
use app\model\service\StoreServiceLog;

/**
 * 客服聊天记录dao
 * Class StoreServiceLogDao
 * @package app\dao\service
 */
class StoreServiceLogDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreServiceLog::class;
    }

    /**
     * 获取聊天记录下的uid和to_uid
     * @param int $uid
     * @return mixed
     */
    public function getServiceUserUids(int $uid)
    {
        return $this->search(['uid' => $uid])->group('uid,to_uid')->field(['uid', 'to_uid'])->select()->toArray();
    }

    /**
     * 获取聊天记录并分页
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getServiceList(array $where, int $page, int $limit, array $field = ['*'])
    {
        return $this->search($where)->with('service')->field($field)->order('add_time DESC')->page($page, $limit)->select()->toArray();
    }
}