<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/11/11
 */

namespace crmeb\traits;

use think\Model;

/**
 * Trait ModelTrait
 * @package crmeb\traits
 */
trait ModelTrait
{
    /**
     * 时间段搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTimeAttr($query, $value, $data)
    {
        $timeKey = $data['timeKey'] ?? 'add_time';
        switch ($value) {
            case 'today':
            case 'week':
            case 'month':
            case 'year':
            case 'yesterday':
                $query->whereTime($timeKey, $value);
                break;
            case 'quarter':
                list($startTime, $endTime) = $this->getMonth();
                $query->whereBetween($timeKey, [$startTime, $endTime]);
                break;
            case 'lately7':
                $query->whereBetween($timeKey, [strtotime("-7 day"), time()]);
                break;
            case 'lately30':
                $query->whereBetween($timeKey, [strtotime("-30 day"), time()]);
                break;
            default:
                if (strstr($value, '-') !== false) {
                    [$startTime, $endTime] = explode('-', $value);
                    $query->whereBetween($timeKey, [strtotime($startTime), strtotime($endTime) + 86400]);
                }
                break;
        }
    }

    /**
     * 获取本季度 time
     * @param int $ceil
     * @return array
     */
    public function getMonth(int $ceil = 0)
    {
        if ($ceil != 0) {
            $season = ceil(date('n') / 3) - $ceil;
        } else {
            $season = ceil(date('n') / 3);
        }
        $firstday = date('Y-m-01', mktime(0, 0, 0, ($season - 1) * 3 + 1, 1, date('Y')));
        $lastday = date('Y-m-t', mktime(0, 0, 0, $season * 3, 1, date('Y')));
        return [$firstday, $lastday];
    }

    /**
     * 获取某个字段内的值
     * @param $value
     * @param string $filed
     * @param string $valueKey
     * @param array|string[] $where
     * @return mixed
     */
    public function getFieldValue($value, string $filed, ?string $valueKey = '', ?array $where = [])
    {
        $model = $this->where($filed, $value);
        if ($where) {
            $model->where(...$where);
        }
        return $model->value($valueKey ?: $filed);
    }


}