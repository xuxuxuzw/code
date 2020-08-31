<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-14
 */

namespace crmeb\jobs;

use app\services\message\sms\SmsRecordServices;
use app\services\system\attachment\SystemAttachmentServices;
use crmeb\basic\BaseJob;
use crmeb\services\sms\Sms;
use crmeb\services\UploadService;

class TaskJob extends BaseJob
{
    /**
     * 修改短信发送记录短信状态
     */
    public function modifyResultCode()
    {
        /** @var SmsRecordServices $smsRecord */
        $smsRecord = app()->make(SmsRecordServices::class);
        $recordIds = $smsRecord->getCodeNull();
        if (count($recordIds)) {
            $smsHandle = new Sms('yunxin', [
                'sms_account' => sys_config('sms_account'),
                'sms_token' => sys_config('sms_token'),
                'site_url' => sys_config('site_url')
            ]);
            $codeLists = $smsHandle->getStatus($recordIds);
            foreach ($codeLists as $item) {
                if (isset($item['id'])) {
                    if ($item['resultcode'] == '' || $item['resultcode'] == null) $item['resultcode'] = 134;
                    $smsRecord->update($item['id'], ['resultcode' => $item['resultcode']], 'record_id');
                }
            }
            return true;
        }
        return true;
    }

    /**
     * 清除昨日海报
     * @return bool
     * @throws \Exception
     */
    public static function emptyYesterdayAttachment()
    {
        /** @var SystemAttachmentServices $attach */
        $attach = app()->make(SystemAttachmentServices::class);
        try {
            $list = $attach->getYesterday();
            foreach ($list as $key => $item) {
                $upload = UploadService::init((int)$item['image_type']);
                if ($item['image_type'] == 1) {
                    $att_dir = $item['att_dir'];
                    if ($att_dir && strstr($att_dir, 'uploads') !== false) {
                        if (strstr($att_dir, 'http') === false)
                            $upload->delete($att_dir);
                        else {
                            $filedir = substr($att_dir, strpos($att_dir, 'uploads'));
                            if ($filedir) $upload->delete($filedir);
                        }
                    }
                } else {
                    if ($item['name']) $upload->delete($item['name']);
                }
            }
            $attach->delYesterday();
            return true;
        } catch (\Exception $e) {
            $attach->delYesterday();
            return true;
        }
    }
}
