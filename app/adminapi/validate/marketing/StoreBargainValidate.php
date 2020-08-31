<?php

namespace app\adminapi\validate\marketing;

use think\Validate;

class StoreBargainValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'product_id' => 'require',
        'title' => 'require',
        'info' => 'require',
        'unit_name' => 'require',
        'image' => 'require',
        'images' => 'require',
        'section_time' => 'require',
        'num' => 'require',
        'temp_id' => 'require',
        'description' => 'require',
        'attrs' => 'require',
        'items' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'product_id.require' => '请选择商品',
        'title.require' => '请填写商品标题',
        'info.require' => '请填写砍价活动简介',
        'unit_name.require' => '请填写单位',
        'image.require' => '请选择商品主图',
        'images.require' => '请选择商品轮播图',
        'section_time.require' => '请选择时间段',
        'num.require' => '请填写单次购买次数',
        'temp_id.require' => '请选择运费模板',
        'description.require' => '请填写砍价商品详情',
        'attrs.require' => '请选择规格',
    ];

    protected $scene = [
        'save' => ['product_id', 'title', 'info', 'unit_name', 'image', 'images', 'give_integral', 'section_time', 'is_hot', 'status', 'num', 'temp_id', 'sort', 'description', 'attrs', 'items'],
    ];
}