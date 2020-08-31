<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/1
 */

namespace app\model\sms;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 *  短信记录Model
 * Class SmsRecord
 * @package app\model\sms
 */
class SmsRecord extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'sms_record';

    /**
     * 短信状态
     * @var array
     */
    protected $resultcode = ['100' => '成功', '130' => '失败', '131' => '空号', '132' => '停机', '133' => '关机', '134' => '无状态'];

    /**
     * 时间获取器
     * @param $value
     * @return false|string
     */
    protected function getAddTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * 状态码获取器
     * @param $value
     * @return mixed|string
     */
    protected function getResultcodeAttr($value)
    {
        return $this->resultcode[$value] ?? '无状态';
    }

    /**
     * 电话号码搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchPhoneAttr($query, $value)
    {
        $query->where('phone', $value);
    }

    /**
     * 短信状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchResultcodeAttr($query, $value)
    {
        $query->where('resultcode', $value);
    }

    /**
     * uid搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            $query->where('uid', $value);
        }
    }

    /**
     * ip
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchAddIpAttr($query, $value)
    {
        $query->where('add_ip', $value);
    }

    /**
     * resultcode
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('resultcode', $value);
    }
}
