<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/7
 */
declare (strict_types=1);

namespace app\dao\activity;

use app\dao\BaseDao;
use app\model\activity\StoreBargainUserHelp;

/**
 *
 * Class StoreBargainUserHelpDao
 * @package app\dao\activity
 */
class StoreBargainUserHelpDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBargainUserHelp::class;
    }

    /**
     * 获取帮砍人数
     * @return array
     */
    public function getHelpAllCount(array $where = [])
    {
        return $this->getColumn($where, 'count(*)', 'bargain_id');
    }

    /**
     * 获取帮砍列表
     * @param int $bid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getHelpList(int $bid, int $page, int $limit)
    {
        return $this->getModel()->where('bargain_user_id', $bid)
            ->order('add_time desc')
            ->page($page, $limit)
            ->column("uid,price,from_unixtime(add_time,'%Y-%m-%d %H:%i:%s') as add_time", 'id');
    }
}
