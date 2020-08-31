<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020-07-13
 */

namespace app\services\export;


use app\services\BaseServices;
use crmeb\services\SpreadsheetExcelService;

class ExportServices extends BaseServices
{
    /**
     * 真实请求导出
     * @param $header excel表头
     * @param $title 标题
     * @param array $export 填充数据
     * @param string $filename 保存文件名称
     * @param string $suffix 保存文件后缀
     * @param bool $is_save true|false 是否保存到本地
     * @return mixed
     */
    public function export($header, $title_arr, $export = [], $filename = '', $suffix = 'xlsx', $is_save = false)
    {
        $title = isset($title_arr[0]) && !empty($title_arr[0]) ? $title_arr[0] : '导出数据';
        $name = isset($title_arr[1]) && !empty($title_arr[1]) ? $title_arr[1] : '导出数据';
        $info = isset($title_arr[2]) && !empty($title_arr[2]) ? $title_arr[2] : date('Y-m-d H:i:s', time());

        $path = SpreadsheetExcelService::instance()->setExcelHeader($header)
            ->setExcelTile($title, $name, $info)
            ->setExcelContent($export)
            ->excelSave($filename, $suffix, $is_save);
        $path = $this->siteUrl() . $path;
        return [$path];
    }

    public function siteUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'];
        return $protocol . $domainName;
    }

    /**
     * 用户资金导出
     * @param $data 导出数据
     */
    public function userFinance($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $export[] = [
                    $value['uid'],
                    $value['nickname'],
                    $value['pm'] == 0 ? '-' . $value['number'] : $value['number'],
                    $value['title'],
                    $value['mark'],
                    $value['add_time'],
                ];
            }
        }
        $header = ['会员ID', '昵称', '金额/积分', '类型', '备注', '创建时间'];
        $title = ['资金监控', '资金监控', date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户佣金导出
     * @param $data 导出数据
     */
    public function userCommission($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as &$value) {
                $export[] = [
                    $value['nickname'],
                    $value['sum_number'],
                    $value['now_money'],
                    $value['brokerage_price'],
                    $value['extract_price'],
                ];
            }
        }
        $header = ['昵称/姓名', '总佣金金额', '账户余额', '账户佣金', '提现到账佣金'];
        $title = ['拥金记录', '拥金记录' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户积分导出
     * @param $data 导出数据
     */
    public function userPoint($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $key => $item) {
                $export[] = [
                    $item['id'],
                    $item['title'],
                    $item['balance'],
                    $item['number'],
                    $item['mark'],
                    $item['nickname'],
                    date('Y-m-d H:i:s', (int)$item['add_time']),
                ];
            }
        }
        $header = ['编号', '标题', '积分余量', '明细数字', '备注', '用户微信昵称', '添加时间'];
        $title = ['积分日志', '积分日志' . time(), '生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户充值导出
     * @param $data 导出数据
     */
    public function userRecharge($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                switch ($item['recharge_type']) {
                    case 'routine':
                        $item['_recharge_type'] = '小程序充值';
                        break;
                    case 'weixin':
                        $item['_recharge_type'] = '公众号充值';
                        break;
                    default:
                        $item['_recharge_type'] = '其他充值';
                        break;
                }
                $item['_pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '暂无';
                $item['_add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '暂无';
                $item['paid_type'] = $item['paid'] ? '已支付' : '未支付';

                $export[] = [
                    $item['nickname'],
                    $item['price'],
                    $item['paid_type'],
                    $item['_recharge_type'],
                    $item['_pay_time'],
                    $item['paid'] == 1 && $item['refund_price'] == $item['price'] ? '已退款' : '未退款',
                    $item['_add_time']
                ];
            }
        }
        $header = ['昵称/姓名', '充值金额', '是否支付', '充值类型', '支付事件', '是否退款', '添加时间'];
        $title = ['充值记录', '充值记录' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 用户推广导出
     * @param $data 导出数据
     */
    public function userAgent($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['uid'],
                    $item['nickname'],
                    $item['phone'],
                    $item['spread_count'],
                    $item['order_count'],
                    $item['order_price'],
                    $item['brokerage_money'],
                    $item['extract_count_price'],
                    $item['extract_count_num'],
                    $item['brokerage_price'],
                    $item['spread_name'],
                ];
            }
        }
        $header = ['用户编号', '昵称', '电话号码', '推广用户数量', '订单数量', '推广订单金额', '佣金金额', '已提现金额', '提现次数', '未提现金额', '上级推广人'];
        $title = ['推广用户', '推广用户导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 微信用户导出
     * @param $data 导出数据
     */
    public function wechatUser($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['nickname'],
                    $item['sex'],
                    $item['country'] . $item['province'] . $item['city'],
                    $item['subscribe'] == 1 ? '关注' : '未关注',
                ];
            }
        }
        $header = ['名称', '性别', '地区', '是否关注公众号'];
        $title = ['微信用户导出', '微信用户导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 订单资金导出
     * @param $data 导出数据
     */
    public function orderFinance($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $info) {
                $time = $info['pay_time'];
                $price = $info['total_price'] + $info['pay_postage'];
                $zhichu = $info['coupon_price'] + $info['deduction_price'] + $info['cost'];
                $profit = ($info['total_price'] + $info['pay_postage']) - ($info['coupon_price'] + $info['deduction_price'] + $info['cost']);
                $deduction = $info['deduction_price'];//积分抵扣
                $coupon = $info['coupon_price'];//优惠
                $cost = $info['cost'];//成本
                $export[] = [$time, $price, $zhichu, $cost, $coupon, $deduction, $profit];
            }
        }
        $header = ['时间', '营业额(元)', '支出(元)', '成本', '优惠', '积分抵扣', '盈利(元)'];
        $title = ['财务统计', '财务统计', date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺砍价活动导出
     * @param $data 导出数据
     */
    public function storeBargain($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['title'],
                    $item['info'],
                    '￥' . $item['price'],
                    '￥' . $item['bargain_max_price'],
                    '￥' . $item['bargain_min_price'],
                    $item['bargain_num'],
                    $item['status'] ? '开启' : '关闭',
                    empty($item['start_time']) ? '' : date('Y-m-d H:i:s', (int)$item['start_time']),
                    empty($item['stop_time']) ? '' : date('Y-m-d H:i:s', (int)$item['stop_time']),
                    $item['sales'],
                    $item['stock'],
                    $item['give_integral'],
                    empty($item['add_time']) ? '' : $item['add_time'],
                ];
            }
        }
        $header = ['砍价活动名称', '砍价活动简介', '砍价金额', '用户每次砍价的最大金额', '用户每次砍价的最小金额',
            '用户每次砍价的次数', '砍价状态', '砍价开启时间', '砍价结束时间', '销量', '库存', '返多少积分', '添加时间'];
        $title = ['砍价商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺拼团导出
     * @param $data 导出数据
     */
    public function storeCombination($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                $export[] = [
                    $item['id'],
                    $item['title'],
                    $item['ot_price'],
                    $item['price'],
                    $item['stock'],
                    $item['people'],
                    $item['count_people_all'],
                    $item['count_people_pink'],
                    $item['sales'] ?? 0,
                    $item['is_show'] ? '开启' : '关闭',
                    empty($item['stop_time']) ? '' : date('Y/m/d H:i:s', (int)$item['stop_time'])
                ];
            }
        }
        $header = ['编号', '拼团名称', '原价', '拼团价', '库存', '拼团人数', '参与人数', '成团数量', '销量', '商品状态', '结束时间'];
        $title = ['拼团商品导出', ' ', ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺秒杀活动导出
     * @param $data 导出数据
     */
    public function storeSeckill($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                if ($item['status']) {
                    if ($item['start_time'] > time())
                        $item['start_name'] = '活动未开始';
                    else if ($item['stop_time'] < time())
                        $item['start_name'] = '活动已结束';
                    else if ($item['stop_time'] > time() && $item['start_time'] < time())
                        $item['start_name'] = '正在进行中';
                } else {
                    $item['start_name'] = '活动已结束';
                }
                $export[] = [
                    $item['id'],
                    $item['title'],
                    $item['info'],
                    $item['ot_price'],
                    $item['price'],
                    $item['stock'],
                    $item['sales'],
                    $item['start_name'],
                    $item['stop_time'] ? date('Y-m-d H:i:s', $item['stop_time']) : '/',
                    $item['status'] ? '开启' : '关闭',
                ];
            }
        }
        $header = ['编号', '活动标题', '活动简介', '原价', '秒杀价', '库存', '销量', '秒杀状态', '结束时间', '状态'];
        $title = ['秒杀商品导出', ' ', ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺商品导出
     * @param $data 导出数据
     */
    public function storeProduct($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['store_name'],
                    $item['store_info'],
                    $item['cate_name'],
                    '￥' . $item['price'],
                    $item['stock'],
                    $item['sales'],
                    $item['browse'],
                ];
            }
        }
        $header = ['商品名称', '商品简介', '商品分类', '价格', '库存', '销量', '浏览量'];
        $title = ['商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺订单导出
     * @param $data 导出数据
     */
    public function storeOrder($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $item) {
                if ($item['paid'] == 1) {
                    switch ($item['pay_type']) {
                        case 'weixin':
                            $item['pay_type_name'] = '微信支付';
                            break;
                        case 'yue':
                            $item['pay_type_name'] = '余额支付';
                            break;
                        case 'offline':
                            $item['pay_type_name'] = '线下支付';
                            break;
                        default:
                            $item['pay_type_name'] = '其他支付';
                            break;
                    }
                } else {
                    switch ($item['pay_type']) {
                        default:
                            $item['pay_type_name'] = '未支付';
                            break;
                        case 'offline':
                            $item['pay_type_name'] = '线下支付';
                            $item['pay_type_info'] = 1;
                            break;
                    }
                }
                if ($item['paid'] == 0 && $item['status'] == 0) {
                    $item['status_name'] = '未支付';
                } else if ($item['paid'] == 1 && $item['status'] == 0 && $item['shipping_type'] == 1 && $item['refund_status'] == 0) {
                    $item['status_name'] = '未发货';
                } else if ($item['paid'] == 1 && $item['status'] == 0 && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                    $item['status_name'] = '未核销';
                } else if ($item['paid'] == 1 && $item['status'] == 1 && $item['shipping_type'] == 1 && $item['refund_status'] == 0) {
                    $item['status_name'] = '待收货';
                } else if ($item['paid'] == 1 && $item['status'] == 1 && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                    $item['status_name'] = '未核销';
                } else if ($item['paid'] == 1 && $item['status'] == 2 && $item['refund_status'] == 0) {
                    $item['status_name'] = '待评价';
                } else if ($item['paid'] == 1 && $item['status'] == 3 && $item['refund_status'] == 0) {
                    $item['status_name'] = '已完成';
                } else if ($item['paid'] == 1 && $item['refund_status'] == 1) {
                    $refundReasonTime = date('Y-m-d H:i', $item['refund_reason_time']);
                    $refundReasonWapImg = json_decode($item['refund_reason_wap_img'], true);
                    $refundReasonWapImg = $refundReasonWapImg ? $refundReasonWapImg : [];
                    $img = '';
                    if (count($refundReasonWapImg)) {
                        foreach ($refundReasonWapImg as $itemImg) {
                            if (strlen(trim($itemImg)))
                                $img .= '<img style="height:50px;" src="' . $itemImg . '" />';
                        }
                    }
                    if (!strlen(trim($img))) $img = '无';
                    $item['status_name'] = <<<HTML
<b style="color:#f124c7">申请退款</b><br/>
<span>退款原因：{$item['refund_reason_wap']}</span><br/>
<span>备注说明：{$item['refund_reason_wap_explain']}</span><br/>
<span>退款时间：{$refundReasonTime}</span><br/>
<span>退款凭证：{$img}</span>
HTML;
                } else if ($item['paid'] == 1 && $item['refund_status'] == 2) {
                    $item['status_name'] = '已退款';
                }

                $goodsName = [];
                foreach ($item['_info'] as $k => $v) {

                    $suk = '';
                    if (isset($v['productInfo']['attrInfo'])) {
                        if (isset($v['productInfo']['attrInfo']['suk'])) {
                            $suk = '(' . $v['productInfo']['attrInfo']['suk'] . ')';
                        }
                    }
                    $goodsName[] = implode(
                        [$v['productInfo']['store_name'],
                            $suk,
                            "[{$v['cart_num']} * {$v['truePrice']}]"
                        ], ' ');
                }
                if ($item['sex'] == 1) $sex_name = '男';
                else if ($item['sex'] == 2) $sex_name = '女';
                else $sex_name = '未知';
                $export[] = [
                    $item['order_id'],
                    $sex_name,
                    $item['user_phone'],
                    $item['real_name'],
                    $item['user_phone'],
                    $item['user_address'],
                    $goodsName ? implode("\n", $goodsName) : '',
                    $item['total_price'],
                    $item['pay_price'],
                    $item['pay_postage'],
                    $item['coupon_price'],
                    $item['pay_type_name'],
                    $item['pay_time'] > 0 ? date('Y/m-d H:i', (int)$item['pay_time']) : '暂无',
                    $item['status_name'],
                    empty($item['add_time']) ? 0 : date('Y-m-d H:i:s', (int)$item['add_time']),
                    $item['mark']
                ];
            }
        }
        $header = ['订单号', '性别', '电话', '收货人姓名', '收货人电话', '收货地址', '商品信息',
            '总价格', '实际支付', '邮费', '优惠金额', '支付状态', '支付时间', '订单状态', '下单时间', '用户备注'];
        $title = ['订单导出', '订单信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }

    /**
     * 商铺自提点导出
     * @param $data 导出数据
     */
    public function storeMerchant($data = [])
    {
        $export = [];
        if (!empty($data)) {
            foreach ($data as $index => $item) {
                $export[] = [
                    $item['name'],
                    $item['phone'],
                    $item['address'] . '' . $item['detailed_address'],
                    $item['day_time'],
                    $item['is_show'] ? '开启' : '关闭'
                ];
            }
        }
        $header = ['提货点名称', '提货点', '地址', '营业时间', '状态'];
        $title = ['提货点导出', '提货点信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '';
        $suffix = 'xlsx';
        $is_save = true;
        return $this->export($header, $title, $export, $filename, $suffix, $is_save);
    }
}
