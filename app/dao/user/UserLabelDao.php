<?php
/**
 * author: zhypy<214681832@qq.com>
 * Date: 2020/7/2
 */
declare (strict_types=1);

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserLabel;

/**
 *
 * Class UserLabelDao
 * @package app\dao\user
 */
class UserLabelDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserLabel::class;
    }

    /**
     * 获取列表
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(int $page = 0, int $limit = 0): array
    {
        return $this->getModel()->page($page, $limit)->select()->toArray();
    }
}
