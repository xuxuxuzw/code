<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/6
 */

namespace app\services\message\sms;


use app\services\BaseServices;
use crmeb\services\FormBuilder;

/**
 * 短信模板
 * Class SmsTemplateApplyServices
 * @package app\services\message\sms
 */
class SmsTemplateApplyServices extends BaseServices
{
    /**
     * @var FormBuilder
     */
    protected $builder;

    /**
     * SmsTemplateApplyServices constructor.
     * @param FormBuilder $builder
     */
    public function __construct(FormBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * 创建短信模板表单
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function createSmsTemplateForm()
    {
        $field = [
            $this->builder->input('title', '模板名称'),
            $this->builder->input('content', '模板内容')->type('textarea'),
            $this->builder->radio('type', '模板类型', 1)->options([['label' => '验证码', 'value' => 1], ['label' => '通知', 'value' => 2], ['label' => '推广', 'value' => 3]])
        ];
        return $field;
    }

    /**
     * 获取短信申请模板
     * @return array
     * @throws \FormBuilder\exception\FormBuilderException
     */
    public function getSmsTemplateForm()
    {
        return create_form('申请短信模板', $this->createSmsTemplateForm(), $this->url('/notify/sms/temp'), 'POST');
    }

}