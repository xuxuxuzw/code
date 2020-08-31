<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\dao\shipping;


use app\dao\BaseDao;
use app\model\shipping\ShippingTemplatesFree;

/**
 * 包邮
 * Class ShippingTemplatesFreeDao
 * @package app\dao\shipping
 */
class ShippingTemplatesFreeDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return ShippingTemplatesFree::class;
    }

    /**
     * 获取运费模板列表并按照指定字段进行分组
     * @param array $where
     * @param string $group
     * @param string $field
     * @param string $key
     * @return mixed
     */
    public function getShippingGroupArray(array $where, string $group, string $field, string $key)
    {
        return $this->search($where)->group($group)->column($field, $key);
    }

    /**
     * 获取运费模板列表
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getShippingArray(array $where, string $field, string $key)
    {
        return $this->search($where)->column($field, $key);
    }

    /**
     * 是否可以满足包邮
     * @param $tempId
     * @param $cityid
     * @param $number
     * @param $price
     * @return int
     */
    public function isFree($tempId, $cityid, $number, $price)
    {
        return $this->getModel()->where('temp_id', $tempId)
            ->where('city_id', $cityid)
            ->where('number', '<=', $number)
            ->where('price', '<=', $price)->count();
    }

}