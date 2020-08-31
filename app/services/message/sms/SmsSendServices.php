<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/8
 */

namespace app\services\message\sms;

use app\services\BaseServices;
use crmeb\jobs\TaskJob;
use crmeb\services\sms\Sms;
use crmeb\utils\Queue;
use think\exception\ValidateException;

/**
 * 短信发送
 * Class SmsSendServices
 * @package app\services\message\sms
 */
class SmsSendServices extends BaseServices
{
    /**
     * 发送短信
     * @param bool $switch
     * @param $phone
     * @param array $data
     * @param string $template
     * @return bool
     */
    public function send(bool $switch, $phone, array $data, string $template)
    {
        if ($switch && $phone) {
            /** @var Sms $seervices */
            $seervices = app()->make(Sms::class, [[
                'sms_account' => sys_config('sms_account'),
                'sms_token' => sys_config('sms_token'),
                'site_url' => sys_config('site_url')
            ]]);
            $res = $seervices->send($phone, $template, $data);
            if ($res === false) {
                throw new ValidateException($seervices->getError());
            } else {
                /** @var SmsRecordServices $recordServices */
                $recordServices = app()->make(SmsRecordServices::class);
                $recordServices->save([
                    'uid' => sys_config('sms_account'),
                    'phone' => $phone,
                    'content' => $res['data']['content'],
                    'add_time' => time(),
                    'template' => $res['data']['template'],
                    'record_id' => $res['data']['id']
                ]);
            }
            Queue::instance()->do('modifyResultCode')->job(TaskJob::class)->push();
            return true;
        } else {
            return false;
        }
    }

}
