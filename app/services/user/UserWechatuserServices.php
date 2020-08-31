<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/4
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserWechatUserDao;

/**
 *
 * Class UserWechatuserServices
 * @package app\services\user
 */
class UserWechatuserServices extends BaseServices
{

    /**
     * UserWechatuserServices constructor.
     * @param UserWechatUserDao $dao
     */
    public function __construct(UserWechatUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 自定义简单查询总数
     * @param array $where
     * @return int
     */
    public function getCount(array $where): int
    {
        return $this->dao->getCount($where);
    }

    /**
     * 复杂条件搜索列表
     * @param array $where
     * @param string $field
     * @return array
     */
    public function getWhereUserList(array $where, string $field): array
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getListByModel($where, $field, $page, $limit);
        $count = $this->dao->getCountByWhere($where);
        return [$list, $count];
    }
}
