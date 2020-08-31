<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/11
 */
declare (strict_types=1);

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserStoreOrderDao;

/**
 *
 * Class UserStoreOrderServices
 * @package app\services\user
 */
class UserStoreOrderServices extends BaseServices
{

    /**
     * UserStoreOrderServices constructor.
     * @param UserStoreOrderDao $dao
     */
    public function __construct(UserStoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    public function getUserSpreadCountList($uid, $orderBy = '', $keyword = '')
    {
        if ($orderBy === '') {
            $orderBy = 'u.add_time desc';
        }
        $where = [];
        $where[] = ['u.uid', 'IN', $uid];
        if (strlen(trim($keyword))) {
            $where[] = ['u.nickname|u.phone', 'LIKE', "%$keyword%"];
        }
        [$page, $limit] = $this->getPageValue();
        $field = "u.uid,u.nickname,u.avatar,from_unixtime(u.add_time,'%Y/%m/%d') as time,u.spread_count as childCount,u.pay_count as orderCount,p.numberCount";
        return $this->dao->getUserSpreadCountList($where, $field, $orderBy, $page, $limit);
    }
}
