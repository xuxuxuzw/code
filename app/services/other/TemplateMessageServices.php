<?php
/**
 * @author: liaofei<136327134@qq.com>
 * @day: 2020/7/7
 */

namespace app\services\other;


use app\dao\other\TemplateMessageDao;
use app\services\BaseServices;

/**
 * 模板消息
 * Class TemplateMessageServices
 * @package app\services\other
 * @method getOne(array $where, ?string $field = '*')  获取一条信息
 * @method save(array $data) 添加
 * @method get(int $id, ?array $field = []) 获取一条信息
 * @method update($id, array $data, ?string $key = null) 更新数据
 * @method delete($id, ?string $key = null) 删除
 */
class TemplateMessageServices extends BaseServices
{
    /**
     * 模板消息
     * TemplateMessageServices constructor.
     * @param TemplateMessageDao $dao
     */
    public function __construct(TemplateMessageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取模板消息列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTemplateList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getTemplateList($where, $page, $limit);
        foreach ($list as &$item) {
            if ($item['content']) $item['content'] = explode("\n", $item['content']);
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取模板消息id
     * @param string $templateId
     * @param int $type
     * @return mixed
     */
    public function getTempId(string $templateId, int $type = 0)
    {
        return $this->dao->value(['type' => $type, 'tempkey' => $templateId, 'status' => 1], 'tempid');
    }
}