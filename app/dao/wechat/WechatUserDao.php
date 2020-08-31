<?php
/**
 * @author: zhypy<214681832@qq.com>
 * @day: 2020/7/4
 */
declare (strict_types=1);

namespace app\dao\wechat;

use app\dao\BaseDao;
use app\model\wechat\WechatUser;

/**
 *
 * Class UserWechatUserDao
 * @package app\dao\user
 */
class WechatUserDao extends BaseDao
{
    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WechatUser::class;
    }

}
