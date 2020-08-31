<?php

namespace app\adminapi\validate\setting;

use think\Validate;

class SystemCityValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name' => 'require',
        'level' => 'number',
        'parent_id' => 'number',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'name.require' => '请填写城市名称',
        'level.number' => 'level数据格式错误，应为整数',
        'parent_id.number' => 'parent_id数据格式错误，应为整数',
    ];

    protected $scene = [
        'save' => ['name', 'level', 'parent_id'],
    ];
}