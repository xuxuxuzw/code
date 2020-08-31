<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\services\shipping;


use app\dao\shipping\ShippingTemplatesFreeCityDao;
use app\services\BaseServices;

/**
 * 包邮和城市数据连表业务处理层
 * Class ShippingTemplatesFreeCityServices
 * @package app\services\shipping
 * @method getUniqidList(array $where, bool $group) 获取指定条件下的包邮列表
 */
class ShippingTemplatesFreeCityServices extends BaseServices
{
    /**
     * 构造方法
     * ShippingTemplatesFreeCityServices constructor.
     * @param ShippingTemplatesFreeCityDao $dao
     */
    public function __construct(ShippingTemplatesFreeCityDao $dao)
    {
        $this->dao = $dao;
    }
}