<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\dao\sms;

use app\dao\BaseDao;
use app\model\sms\SmsRecord;

/**
 * 短信发送记录
 * Class SmsRecordDao
 * @package app\dao\sms
 */
class SmsRecordDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    public function setModel(): string
    {
        return SmsRecord::class;
    }

    /**
     * 短信发送记录
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecordList(array $where, int $page, int $limit)
    {
        return $this->search($where)->page($page, $limit)->order('add_time DESC')->select()->toArray();
    }

    /**
     * 获取10分钟前20条无状态的短信记录
     * @return array
     */
    public function getCodeNull()
    {
        return $this->getModel()->where([
            ['resultcode', '=', null],
            ['add_time', '<=', time() - 600],
        ])->limit(20)->column('record_id');
    }

}
