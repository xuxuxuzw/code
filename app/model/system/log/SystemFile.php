<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/6/30
 */

namespace app\model\system\log;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 文件校验模型
 * Class SystemFile
 * @package app\model\system\log
 */
class SystemFile extends BaseModel
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
    protected $name = 'system_file';

}