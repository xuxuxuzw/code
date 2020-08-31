<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/2
 */

namespace app\dao\system\admin;


use app\dao\BaseDao;
use app\model\system\admin\SystemAdmin;

/**
 * admin授权dao
 * Class AdminAuthDao
 * @package app\dao\system\admin
 */
class AdminAuthDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SystemAdmin::class;
    }

}