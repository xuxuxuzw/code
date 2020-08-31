<?php
/**
 * @author: 吴昊天<442384644@qq.com>
 * @day: 2020/7/6
 */
declare (strict_types=1);

namespace app\services\activity;

use app\api\model\store\StoreSeckill;
use app\Request;
use app\services\BaseServices;
use app\dao\activity\StoreSeckillDao;
use app\services\other\QrcodeServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductRelationServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\system\config\SystemGroupDataServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\utils\Arr;
use think\exception\ValidateException;
use function GuzzleHttp\Psr7\str;

/**
 *
 * Class StoreSeckillServices
 * @package app\services\activity
 * @method getSeckillIdsArray(array $ids, array $field)
 * @method get(int $id,array $field) 获取一条数据
 */
class StoreSeckillServices extends BaseServices
{

    /**
     * StoreSeckillServices constructor.
     * @param StoreSeckillDao $dao
     */
    public function __construct(StoreSeckillDao $dao)
    {
        $this->dao = $dao;
    }

    public function getCount(array $where)
    {
        $this->dao->count($where);
    }

    /**
     * 秒杀是否存在
     * @param int $id
     * @return int
     */
    public function getSeckillCount(int $id = 0)
    {
        $where = [];
        $where[] = ['is_del', '=', 0];
        $where[] = ['status', '=', 1];
        if ($id) {
            $time = time();
            $where[] = ['id', '=', $id];
            $where[] = ['start_time', '<=', $time];
            $where[] = ['stop_time', '>=', $time];
            $seckill_one = $this->dao->getOne($where, 'time_id');
            if (!$seckill_one) {
                return 0;
            }
            /** @var SystemGroupDataServices $systemGroupDataService */
            $systemGroupDataService = app()->make(SystemGroupDataServices::class);
            $seckillTime = array_column($systemGroupDataService->getConfigNameValue('routine_seckill_time'), null, 'id');
            $config = $seckillTime[$seckill_one['time_id']] ?? false;
            if (!$config) {
                return 0;
            }
            $now_hour = date('H', time());
            $start_hour = $config['time'];
            $end_hour = (int)$start_hour + (int)$config['continued'];
            if ($start_hour <= $now_hour && $end_hour > $now_hour) {
                return 1;
            }
            return 0;
        } else {
            $seckillTime = sys_data('routine_seckill_time') ?: [];//秒杀时间段
            $timeInfo = ['time' => 0, 'continued' => 0];
            foreach ($seckillTime as $key => $value) {
                $currentHour = date('H');
                $activityEndHour = (int)$value['time'] + (int)$value['continued'];
                if ($currentHour >= (int)$value['time'] && $currentHour < $activityEndHour && $activityEndHour < 24) {
                    $timeInfo = $value;
                    break;
                }
            }
            if ($timeInfo['time'] == 0) return 0;
            $activityEndHour = $timeInfo['time'] + (int)$timeInfo['continued'];
            $startTime = strtotime(date('Y-m-d')) + (int)$timeInfo['time'] * 3600;
            $stopTime = strtotime(date('Y-m-d')) + (int)$activityEndHour * 3600;

            $where[] = ['start_time', '<', $startTime];
            $where[] = ['stop_time', '>', $stopTime];
            return $this->dao->getCount($where);
        }
    }


    /**
     * 保存数据
     * @param int $id
     * @param array $data
     */
    public function saveData(int $id, array $data)
    {
        $description = $data['description'];
        $detail = $data['attrs'];
        $items = $data['items'];
        $data['start_time'] = strtotime($data['section_time'][0]);
        $data['stop_time'] = strtotime($data['section_time'][1]);
        $data['images'] = json_encode($data['images']);
        $data['price'] = min(array_column($detail, 'price'));
        $data['ot_price'] = min(array_column($detail, 'ot_price'));
        $data['quota'] = $data['quota_show'] = array_sum(array_column($detail, 'quota'));
        $data['stock'] = array_sum(array_column($detail, 'stock'));
        unset($data['section_time'], $data['description'], $data['attrs'], $data['items']);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $this->transaction(function () use ($id, $data, $description, $detail, $items, $storeDescriptionServices, $storeProductAttrServices, $storeProductServices) {
            if ($id) {
                $res = $this->dao->update($id, $data);
                $storeDescriptionServices->saveDescription((int)$id, $description, 1);
                $skuList = $storeProductServices->validateProductAttr($items, $detail, (int)$id, 1);
                $valueGroup = $storeProductAttrServices->saveProductAttr($skuList, (int)$id, 1);
                if (!$res) throw new AdminException('修改失败');
            } else {
                $data['add_time'] = time();
                $res = $this->dao->save($data);
                $storeDescriptionServices->saveDescription((int)$res->id, $description, 1);
                $skuList = $storeProductServices->validateProductAttr($items, $detail, (int)$res->id, 1);
                $valueGroup = $storeProductAttrServices->saveProductAttr($skuList, (int)$res->id, 1);
                if (!$res) throw new AdminException('添加失败');
            }
            $res = true;
            foreach ($valueGroup->toArray() as $item) {
                $res = $res && $this->pushSeckillStock($item['unique'], 1, (int)$item['quota_show']);
            }
            if (!$res) {
                throw new AdminException('占用库存失败');
            }
        });
    }

    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function systemPage(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        $count = $this->dao->count($where + ['is_del' => 0]);
        $time_ids = array_unique(array_column($list, 'time_id'));
        /** @var SystemGroupDataServices $systemGroupDataService */
        $systemGroupDataService = app()->make(SystemGroupDataServices::class);
        $seckillTime = array_column($systemGroupDataService->getConfigNameValue('routine_seckill_time'), null, 'id');
        foreach ($list as &$item) {
            $item['store_name'] = $item['title'];
            $config = $seckillTime[$item['time_id']] ?? false;
            if ($item['status']) {
                if ($item['start_time'] > time())
                    $item['start_name'] = '活动未开始';
                else if (bcadd($item['stop_time'], '86400') < time())
                    $item['start_name'] = '活动已结束';
                else if (bcadd($item['stop_time'], '86400') > time() && $item['start_time'] < time()) {
                    if ($config) {
                        $now_hour = date('H', time());
                        $start_hour = $config['time'];
                        $continued = $config['continued'];
                        $end_hour = $start_hour + $continued;
                        if ($start_hour > $now_hour) {
                            $item['start_name'] = '活动未开始';
                        } elseif ($end_hour <= $now_hour) {
                            $item['start_name'] = '活动已结束';
                        } else {
                            $item['start_name'] = '正在进行中';
                        }
                    } else {
                        $item['start_name'] = '正在进行中';
                    }
                }
            } else $item['start_name'] = '关闭';
            $end_time = $item['stop_time'] ? date('Y/m/d', (int)$item['stop_time']) : '';
            if ($end_time) {
                if ($config) {
                    $start_hour = $config['time'];
                    $continued = $config['continued'];
                    $end_hour = (int)$start_hour + (int)$continued;
                    $end_time = $end_time . ' ' . $end_hour . ':00:00';
                }
            }
            $item['_stop_time'] = $end_time;
        }
        return compact('list', 'count');
    }

    /**
     * 获取秒杀详情
     * @param int $id
     * @return array|\think\Model|null
     */
    public function getInfo(int $id)
    {
        $info = $this->dao->get($id);
        if ($info) {
            if ($info['start_time'])
                $start_time = date('Y-m-d', (int)$info['start_time']);

            if ($info['stop_time'])
                $stop_time = date('Y-m-d', (int)$info['stop_time']);
            if (isset($start_time) && isset($stop_time))
                $info['section_time'] = [$start_time, $stop_time];
            else
                $info['section_time'] = [];
            unset($info['start_time'], $info['stop_time']);
            $info['give_integral'] = intval($info['give_integral']);
            $info['price'] = floatval($info['price']);
            $info['ot_price'] = floatval($info['ot_price']);
            $info['postage'] = floatval($info['postage']);
            $info['cost'] = floatval($info['cost']);
            $info['weight'] = floatval($info['weight']);
            $info['volume'] = floatval($info['volume']);
            /** @var StoreDescriptionServices $storeDescriptionServices */
            $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
            $info['description'] = $storeDescriptionServices->getDescription(['product_id' => $id, 'type' => 1]);
            $info['attrs'] = $this->attrList($id, $info['product_id']);
        }
        return $info;
    }

    /**
     * 获取规格
     * @param int $id
     * @param int $pid
     * @return mixed
     */
    public function attrList(int $id, int $pid)
    {
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        $seckillResult = $storeProductAttrResultServices->value(['product_id' => $id, 'type' => 1], 'result');
        $items = json_decode($seckillResult, true)['attr'];
        $productAttr = $this->getAttr($items, $pid, 0);
        $seckillAttr = $this->getAttr($items, $id, 1);
        foreach ($productAttr as $pk => $pv) {
            foreach ($seckillAttr as &$sv) {
                if ($pv['detail'] == $sv['detail']) {
                    $productAttr[$pk] = $sv;
                }
            }
            $productAttr[$pk]['detail'] = json_decode($productAttr[$pk]['detail']);
        }
        $attrs['items'] = $items;
        $attrs['value'] = $productAttr;
        foreach ($items as $key => $item) {
            $header[] = ['title' => $item['value'], 'key' => 'value' . ($key + 1), 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 120];
        $header[] = ['title' => '秒杀价', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '原价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '库存', 'key' => 'stock', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '限量', 'key' => 'quota', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '重量(KG)', 'key' => 'weight', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '体积(m³)', 'key' => 'volume', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品编号', 'key' => 'bar_code', 'align' => 'center', 'minWidth' => 80];
        $attrs['header'] = $header;
        return $attrs;
    }

    /**
     * 获取规格
     * @param $attr
     * @param $id
     * @param $type
     * @return array
     */
    public function getAttr($attr, $id, $type)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $item['detail']);
            $sukValue = $storeProductAttrValueServices->getColumn(['product_id' => $id, 'type' => $type, 'suk' => $suk], 'bar_code,cost,price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,quota', 'suk');
            if (count($sukValue)) {
                foreach (array_values($detail) as $k => $v) {
                    $valueNew[$count]['value' . ($k + 1)] = $v;
                }
                $valueNew[$count]['detail'] = json_encode($detail);
                $valueNew[$count]['pic'] = $sukValue[$suk]['pic'] ?? '';
                $valueNew[$count]['price'] = $sukValue[$suk]['price'] ? floatval($sukValue[$suk]['price']) : 0;
                $valueNew[$count]['cost'] = $sukValue[$suk]['cost'] ? floatval($sukValue[$suk]['cost']) : 0;
                $valueNew[$count]['ot_price'] = isset($sukValue[$suk]['ot_price']) ? floatval($sukValue[$suk]['ot_price']) : 0;
                $valueNew[$count]['stock'] = $sukValue[$suk]['stock'] ? intval($sukValue[$suk]['stock']) : 0;
                $valueNew[$count]['quota'] = $sukValue[$suk]['quota'] ? intval($sukValue[$suk]['quota']) : 0;
                $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
                $valueNew[$count]['weight'] = $sukValue[$suk]['weight'] ? floatval($sukValue[$suk]['weight']) : 0;
                $valueNew[$count]['volume'] = $sukValue[$suk]['volume'] ? floatval($sukValue[$suk]['volume']) : 0;
                $valueNew[$count]['brokerage'] = $sukValue[$suk]['brokerage'] ? floatval($sukValue[$suk]['brokerage']) : 0;
                $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ? floatval($sukValue[$suk]['brokerage_two']) : 0;
                $valueNew[$count]['_checked'] = $type != 0 ? true : false;
                $count++;
            }
        }
        return $valueNew;
    }

    /**
     * 获取某个时间段的秒杀列表
     * @param int $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getListByTime(int $time)
    {
        [$page, $limit] = $this->getPageValue();
        $seckillInfo = $this->dao->getListByTime($time, $page, $limit);
        if (count($seckillInfo)) {
            foreach ($seckillInfo as $key => &$item) {
                if ($item['quota'] > 0) {
                    $percent = (int)(($item['quota_show'] - $item['quota']) / $item['quota_show'] * 100);
                    $item['percent'] = $percent;
                    $item['stock'] = $item['quota'];
                } else {
                    $item['percent'] = 100;
                    $item['stock'] = 0;
                }
                $item['price'] = floatval($item['price']);
                $item['ot_price'] = floatval($item['ot_price']);
            }
        }
        return $seckillInfo;
    }

    /**
     * 获取秒杀详情
     * @param Request $request
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function seckillDetail(Request $request, int $id)
    {
        $storeInfo = $this->dao->getOne(['id' => $id], '*', ['description']);
        if (!$storeInfo) {
            throw new ValidateException('商品不存在');
        } else {
            $storeInfo = $storeInfo->toArray();
        }
        $siteUrl = sys_config('site_url');
        $storeInfo['image'] = set_file_url($storeInfo['image'], $siteUrl);
        $storeInfo['image_base'] = set_file_url($storeInfo['image'], $siteUrl);

        /** @var StoreProductServices $storeProductService */
        $storeProductService = app()->make(StoreProductServices::class);
        $productInfo = $storeProductService->get($storeInfo['product_id']);
        $storeInfo['total'] = $productInfo['sales'] + $productInfo['ficti'];

        /** @var QrcodeServices $qrcodeService */
        $qrcodeService = app()->make(QrcodeServices::class);
        $storeInfo['code_base'] = $qrcodeService->getWechatQrcodePath($id . '_product_detail_wap.jpg', '/pages/goods_details/index?id=' . $id);

        $uid = $request->uid();

        /** @var StoreDescriptionServices $storeDescriptionService */
        $storeDescriptionService = app()->make(StoreDescriptionServices::class);
        $storeInfo['description'] = $storeDescriptionService->getDescription(['product_id' => $id, 'type' => 1]);


        /** @var StoreProductRelationServices $storeProductRelationServices */
        $storeProductRelationServices = app()->make(StoreProductRelationServices::class);
        $storeInfo['userCollect'] = $storeProductRelationServices->isProductRelation(['uid' => $uid, 'product_id' => $id, 'type' => 'collect', 'category' => 'product']);
        $storeInfo['userLike'] = $storeProductRelationServices->isProductRelation(['uid' => $uid, 'product_id' => $id, 'type' => 'collect', 'category' => 'like']);

//        $storeInfo['like_num'] = $storeProductRelationServices->productRelationNum((int)$id, 'like', 'product_seckill');

        $storeInfo['uid'] = $uid;
        //商品详情
        $data['storeInfo'] = $storeInfo;

        /** @var StoreProductReplyServices $storeProductReplyService */
        $storeProductReplyService = app()->make(StoreProductReplyServices::class);
        $data['reply'] = $storeProductReplyService->getRecProductReply($storeInfo['product_id']);
        $data['replyChance'] = $storeProductReplyService->getProductReplyChance($storeInfo['product_id']);
        $data['replyCount'] = $storeProductReplyService->count(['product_id' => $storeInfo['product_id']]);

        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        list($productAttr, $productValue) = $storeProductAttrServices->getProductAttrDetail($id, $uid, 0, 1, $storeInfo['product_id']);
        $data['productAttr'] = $productAttr;
        $data['productValue'] = $productValue;
        event('SetProductView', [$uid, $storeInfo['product_id'], 0, 'seckill']);
        return $data;
    }

    /**
     * 获取秒杀数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSeckillColumn(array $ids, string $field = '')
    {
        $seckillProduct = $systemGroupData = [];
        $seckillInfoField = 'id,image,price,ot_price,postage,give_integral,sales,stock,title as store_name,unit_name,is_show,is_del,is_postage,cost,temp_id,weight,volume,start_time,stop_time,time_id';
        if (!empty($seckill_ids)) {
            $seckillProduct = $this->dao->idSeckillList($ids, $field ?: $seckillInfoField);
            if (!empty($seckillProduct)) {
                $timeIds = Arr::getUniqueKey($seckillProduct, 'time_id');
                $seckillProduct = array_combine(array_column($seckillProduct, 'id'), $seckillProduct);
                /** @var SystemGroupDataServices $groupServices */
                $groupServices = app()->make(SystemGroupDataServices::class);
                $systemGroupData = $groupServices->getGroupDataColumn($timeIds);
            }
        }
        return [$seckillProduct, $systemGroupData];
    }

    /**
     * 秒杀库存添加入redis的队列中
     * @param string $unique sku唯一值
     * @param int $type 类型
     * @param int $number 库存个数
     * @param bool $isPush 是否放入之前删除当前队列
     * @return bool|int
     */
    public function pushSeckillStock(string $unique, int $type, int $number, bool $isPush = false)
    {
        $name = 'seckill_' . $unique . '_' . $type;
        /** @var CacheService $cache */
        $cache = app()->make(CacheService::class);
        $res = true;
        if (!$isPush) {
            $cache->del($name);
        }
        for ($i = 1; $i <= $number; $i++) {
            $res = $res && $cache->lPush($name, $i);
        }
        return $res;
    }

    /**
     * @param int $productId
     * @param string $unique
     * @param int $cartNum
     * @param string $value
     * @return bool
     */
    public function checkSeckillStock(int $productId, string $unique, int $cartNum = 1, string $value = '')
    {
        $set_key = md5('seckill_set_attr_stock_' . $productId . '_' . $unique);
        $sum = CacheService::zCard($set_key);
        $fail = CacheService::zCount($set_key, 0, time());
        $sall = ($sum - $fail) < 0 ? 0 : ($sum - $fail);
        /** @var StoreProductAttrValueServices $skuValueServices */
        $skuValueServices = app()->make(StoreProductAttrValueServices::class);
        $seckillStock = $skuValueServices->getSeckillAttrStock($productId, $unique);
        if (($seckillStock['quota'] - $sall) < $cartNum) {
            return false;
        }
        $StoreSeckillinfo = $this->getValidProduct($productId);
        $product_stock = $skuValueServices->getProductAttrStock($StoreSeckillinfo['product_id'], $seckillStock['suk']);
        if (($product_stock - $sall) < $cartNum) {
            return false;
        }
        //秒杀成功成员
        $sall_member = CacheService::zRangeByScore($set_key, time());
        if ($value && $sall_member) {
            $i = 0;
            for ($i; $i < $cartNum; $i++) {
                if (in_array($value . $i, $sall_member)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 弹出redis队列中的库存条数
     * @param string $unique
     * @param int $type
     * @return mixed
     */
    public function popSeckillStock(string $unique, int $type, int $number = 1)
    {
        $name = 'seckill_' . $unique . '_' . $type;
        /** @var CacheService $cache */
        $cache = app()->make(CacheService::class);
        if ($number > $cache->lLen($name)) {
            return false;
        }
        $res = true;
        for ($i = 1; $i <= $number; $i++) {
            $res = $res && $cache->lPop($name);
        }
        return $res;
    }

    /**
     * 是否有库存
     * @param string $unique
     * @param int $type
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function isSeckillStock(string $unique, int $type, int $number)
    {
        /** @var CacheService $cache */
        $cache = app()->make(CacheService::class);
        return $cache->redisHandler()->lLen('seckill_' . $unique . '_' . $type) >= $number;
    }

    /**
     * 回滚库存
     * @param array $cartInfo
     * @param int $number
     * @return bool
     */
    public function rollBackStock(array $cartInfo)
    {
        $res = true;
        foreach ($cartInfo as $item) {
            $value = $item['cart_info'];
            if ($value['seckill_id']) {
                $res = $res && $this->pushSeckillStock($value['product_attr_unique'], 1, (int)$value['cart_num'], true);
            }
        }
        return $res;
    }

    /**
     * 占用库存
     * @param $cartInfo
     */
    public function occupySeckillStock($cartInfo, $key, $time = 0)
    {
        //占用库存
        if ($cartInfo) {
            if (!$time) {
                $time = time() + 600;
            }
            foreach ($cartInfo as $val) {
                if ($val['seckill_id']) {
                    $this->setSeckillStock($val['product_id'], $val['product_attr_unique'], $time, $key, (int)$val['cart_num']);
                }
            }
        }
        return true;
    }

    /**
     * 取消秒杀占用的库存
     * @param array $cartInfo
     * @param string $key
     * @return bool
     */
    public function cancelOccupySeckillStock(array $cartInfo, string $key)
    {
        if ($cartInfo) {
            foreach ($cartInfo as $val) {
                if (isset($val['seckill_id']) && $val['seckill_id']) {
                    $this->backSeckillStock((int)$val['product_id'], $val['product_attr_unique'], $key, (int)$val['cart_num']);
                }
            }
        }
        return true;
    }

    /**
     * 存入当前秒杀商品属性有序集合
     * @param $product_id
     * @param $unique
     * @param $score
     * @param $value
     * @param int $cart_num
     * @return bool
     */
    public function setSeckillStock($product_id, $unique, $score, $value, $cart_num = 1)
    {
        $set_key = md5('seckill_set_attr_stock_' . $product_id . '_' . $unique);
        $i = 0;
        for ($i; $i < $cart_num; $i++) {
            CacheService::zAdd($set_key, $score, $value . $i);
        }
        return true;
    }

    /**
     * 取消集合中的秒杀商品
     * @param int $product_id
     * @param string $unique
     * @param $value
     * @param int $cart_num
     * @return bool
     */
    public function backSeckillStock(int $product_id, string $unique, $value, int $cart_num = 1)
    {
        $set_key = md5('seckill_set_attr_stock_' . $product_id . '_' . $unique);
        $i = 0;
        for ($i; $i < $cart_num; $i++) {
            CacheService::zRem($set_key, $value . $i);
        }
        return true;
    }

    /**
     * 修改秒杀库存
     * @param int $num
     * @param int $seckillId
     * @return bool
     */
    public function decSeckillStock(int $num, int $seckillId, string $unique = '')
    {
        $product_id = $this->dao->value(['id' => $seckillId], 'product_id');
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //减去秒杀商品的sku库存增加销量
            $res = false !== $skuValueServices->decProductAttrStock($seckillId, $unique, $num, 1);
            //减去秒杀库存
            $res = $res && $this->dao->decStockIncSales(['id' => $seckillId, 'type' => 1], $num);
            //减去当前普通商品sku的库存增加销量
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $seckillId], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id], 'unique');
            if ($productUnique) {
                $res = $res && $skuValueServices->decProductAttrStock($product_id, $productUnique, $num);
            }
        } else {
            $res = false !== $this->dao->decStockIncSales(['id' => $seckillId, 'type' => 1], $num);
        }
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        //减去普通商品库存
        $res = $res && $services->decProductStock($num, $product_id);
        if ($res) {

        }
        return $res;
    }

    /**
     * 加库存减销量
     * @param int $num
     * @param int $seckillId
     * @param string $unique
     * @return bool
     */
    public function incSeckillStock(int $num, int $seckillId, string $unique = '')
    {
        $product_id = $this->dao->value(['id' => $seckillId], 'product_id');
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //减去秒杀商品的sku库存增加销量
            $res = false !== $skuValueServices->incProductAttrStock($seckillId, $unique, $num, 1);
            //减去秒杀库存
            $res = $res && $this->dao->incStockDecSales(['id' => $seckillId, 'type' => 1], $num);
            //减去当前普通商品sku的库存增加销量
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $seckillId], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id], 'unique');
            if ($productUnique) {
                $res = $res && $skuValueServices->incProductAttrStock($product_id, $productUnique, $num);
            }
        } else {
            $res = false !== $this->dao->incStockDecSales(['id' => $seckillId, 'type' => 1], $num);
        }
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        //减去普通商品库存
        $res = $res && $services->incProductStock($num, $product_id);
        return $res;
    }

    /**
     * 获取一条秒杀商品
     * @param $id
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getValidProduct($id, $field = '*')
    {
        return $this->dao->validProduct($id, $field);
    }


}
