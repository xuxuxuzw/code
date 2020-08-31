<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/15
 */
declare (strict_types=1);

namespace app\dao\diy;

use app\dao\BaseDao;
use app\model\diy\Diy;

/**
 *
 * Class DiyDao
 * @package app\dao\diy
 */
class DiyDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Diy::class;
    }

    /**
     * 获取DIY列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiyList(array $where, int $page, int $limit)
    {
        return $this->search($where)->where('is_del', 0)->page($page, $limit)->order('id desc')->select()->toArray();
    }

}
