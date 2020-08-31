<?php

namespace app\adminapi\controller\v1\notification\sms;

use app\adminapi\controller\AuthController;
use app\services\message\sms\SmsTemplateApplyServices;
use crmeb\exceptions\AdminException;
use crmeb\services\{
    sms\Sms
};
use think\facade\App;


/**
 * 短信模板申请
 * Class SmsTemplateApply
 * @package app\admin\controller\sms
 */
class SmsTemplateApply extends AuthController
{
    /**
     * @var Sms
     */
    protected $smsHandle;

    public function __construct(App $app, SmsTemplateApplyServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 构造函数 验证是否配置了短信
     * @return mixed|void
     */
    public function initialize()
    {
        parent::initialize();
        $this->smsHandle = new Sms('yunxin', [
            'sms_account' => sys_config('sms_account'),
            'sms_token' => sys_config('sms_token'),
            'site_url' => sys_config('site_url')
        ]);
        if (!$this->smsHandle->isLogin()) {
            throw new AdminException('请先填写短息配置');
        }
    }

    /**
     * 异步获取模板列表
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['title', ''],
            ['page', 1],
            ['limit', 20],
        ]);
        $templateList = $this->smsHandle->template($where);
        if ($templateList['status'] == 400) {
            return $this->fail($templateList['msg']);
        }
        $arr = $templateList['data']['data'];
        foreach ($arr as $key => $value) {
            switch ($value['type']) {
                case 1:
                    $arr[$key]['type'] = '验证码';
                    break;
                case 2:
                    $arr[$key]['type'] = '通知';
                    break;
                case 3:
                    $arr[$key]['type'] = '推广';
                    break;
                default:
                    $arr[$key]['type'] = '';
                    break;
            }
        }
        $templateList['data']['data'] = $arr;
        return $this->success($templateList['data']);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return string
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->getSmsTemplateForm());
    }

    /**
     * 保存新建的资源
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['title', ''],
            ['content', ''],
            ['type', 0]
        ]);
        if (!strlen(trim($data['title']))) {
            return $this->fail('请输入模板名称');
        }
        if (!strlen(trim($data['content']))) {
            return $this->fail('请输入模板内容');
        }
        $applyStatus = $this->smsHandle->apply($data['title'], $data['content'], $data['type']);
        if ($applyStatus['status'] == 400) {
            return $this->fail($applyStatus['msg']);
        }
        return $this->success('申请成功');
    }
}