<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\services\message\sms;


use app\dao\sms\SmsRecordDao;
use app\services\BaseServices;

/**
 * 短信发送记录
 * Class SmsRecordServices
 * @package app\services\message\sms
 * @method save(array $data) 保存数据
 * @method getColumn(array $where, ?string $field, ?string $key = '')
 * @method update(int $id, array $data, ?string $field = '')
 * @method getCodeNull
 */
class SmsRecordServices extends BaseServices
{
    /**
     * 构造方法
     * SmsRecordServices constructor.
     * @param SmsRecordDao $dao
     */
    public function __construct(SmsRecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取短信发送列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getRecordList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('data', 'count');
    }
}
