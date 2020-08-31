<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/4
 */
declare (strict_types=1);

namespace app\services\wechat;

use app\services\BaseServices;
use app\dao\wechat\WechatReplyKeyDao;

/**
 *
 * Class UserWechatuserServices
 * @package app\services\user
 */
class WechatReplyKeyServices extends BaseServices
{

    /**
     * 构造方法
     * WechatReplyKeyServices constructor.
     * @param WechatReplyKeyDao $dao
     */
    public function __construct(WechatReplyKeyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @return mixed
     */
    public function getReplyKeyAll(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getReplyKeyList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }
}
