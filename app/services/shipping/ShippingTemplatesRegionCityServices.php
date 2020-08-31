<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\services\shipping;


use app\dao\shipping\ShippingTemplatesRegionCityDao;
use app\services\BaseServices;

/**
 * 根据地区设置邮费
 * Class ShippingTemplatesRegionCityServices
 * @package app\services\shipping
 * @method getUniqidList(array $where,bool $group) 获取指定条件下的包邮列表
 */
class ShippingTemplatesRegionCityServices extends BaseServices
{

    /**
     * 构造方法
     * ShippingTemplatesRegionCityServices constructor.
     * @param ShippingTemplatesRegionCityDao $dao
     */
    public function __construct(ShippingTemplatesRegionCityDao $dao)
    {
        $this->dao = $dao;
    }
}