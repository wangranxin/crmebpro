<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\product\sku;


use app\dao\product\sku\StoreProductAttrValueDao;
use app\jobs\product\ProductStockTips;
use app\jobs\product\ProductStockValueTips;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\integral\StoreIntegralServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\product\product\StoreProductStockRecordServices;
use app\webscoket\SocketPush;
use crmeb\exceptions\AdminException;
use app\services\product\product\StoreProductServices;
use crmeb\services\CacheService;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * Class StoreProductAttrValueService
 * @package app\services\product\sku
 * @mixin StoreProductAttrValueDao
 */
class StoreProductAttrValueServices extends BaseServices
{

    /**
     * @var StoreProductAttrValueDao
     */
    #[Inject]
    protected StoreProductAttrValueDao $dao;

    /**
     * 获取单规格规格
     * @param array $where
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne(array $where, string $field = '*')
    {
        return $this->dao->getOne($where, $field);
    }

    /**
     * 获取商品规格信息
     * @param int $id
     * @param int $pid
     * @param int $type
     * @return array
     */
    public function attrList(int $id, int $pid, int $type = 0)
    {
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        $result = $storeProductAttrResultServices->value(['product_id' => $id, 'type' => $type], 'result');
        $items = json_decode($result, true)['attr'];
        $productAttr = $this->getAttr($items, $pid, 0);
        $activityAttr = $this->getAttr($items, $id, $type);
        foreach ($productAttr as $pk => &$pv) {
            if ($type == 3){
                $sv['ot_price'] = isset($pv['_checked']) && $pv['_checked'] ? $pv['ot_price'] : $pv['price'];
            }
            foreach ($activityAttr as &$sv) {
                if ($pv['detail'] == $sv['detail']) {
                    if ($type == 3){
                        $sv['ot_price'] = isset($sv['_checked']) && $sv['_checked'] ? $sv['ot_price'] : $sv['price'];
                    }
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
        $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
        if ($type == 3) {
            $header[] = ['title' => '日常售价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 4) {
            $header[] = ['title' => '金额', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } else {
            $header[] = ['title' => '划线价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        }
        if ($type == 1) {
            $header[] = ['title' => '秒杀价', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 2) {
            $header[] = ['title' => '砍价起始金额', 'slot' => 'price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '砍价最低价', 'slot' => 'min_price', 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 3) {
            $header[] = ['title' => '拼团价', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 4) {
            $header[] = ['title' => '兑换积分', 'key' => 'integral', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '库存', 'key' => 'stock', 'align' => 'center', 'minWidth' => 80];
        if (in_array($type, [1, 3, 4])) {
            $header[] = ['title' => $type == 4 ? '兑换次数' : '限量', 'key' => 'quota', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } else {
            $header[] = ['title' => '限量', 'slot' => 'quota', 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '重量(KG)', 'key' => 'weight', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '体积(m³)', 'key' => 'volume', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品条形码', 'key' => 'bar_code', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品编号', 'key' => 'code', 'align' => 'center', 'minWidth' => 80];
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
    public function getattr($attr, $id, $type)
    {
        foreach ($attr as  $k=>$v){
            foreach ($v['detail'] as $key=>$item){
                if(isset($item['value'])){
                    $attr[$k]['detail'][$key] = $item['value'];
                }
            }
        }
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        if ($type == 2) {
            /** @var StoreBargainServices $storeBargainServices */
            $storeBargainServices = app()->make(StoreBargainServices::class);
            $min_price = $storeBargainServices->value(['id' => $id], 'min_price');
        } else {
            $min_price = 0;
        }
        $sukValueArr = $this->getSkuArray(['product_id' => $id, 'type' => $type], 'bar_code,code,cost,price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,quota,quota_show,settle_price,integral', 'suk');
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $item['detail']);
            $sukValue = $sukValueArr[$suk] ?? [];
            if ($sukValue) {
                foreach (array_values($detail) as $k => $v) {
                    $valueNew[$count]['value' . ($k + 1)] = $v;
                }
                $valueNew[$count]['detail'] = json_encode($detail);
                $valueNew[$count]['pic'] = $sukValue['pic'] ?? '';
                $valueNew[$count]['price'] = $sukValue['price'] ? floatval($sukValue['price']) : 0;
                $valueNew[$count]['integral'] = $sukValue['integral'] ?: 0;
                $valueNew[$count]['settle_price'] = $sukValue['cost'] ? floatval($sukValue['settle_price']) : 0;
                $valueNew[$count]['cost'] = $sukValue['cost'] ? floatval($sukValue['cost']) : 0;
                $valueNew[$count]['ot_price'] = isset($sukValue['ot_price']) ? floatval($sukValue['ot_price']) : 0;
                $valueNew[$count]['stock'] = $sukValue['stock'] ? intval($sukValue['stock']) : 0;
                $valueNew[$count]['quota'] = isset($sukValue['quota_show']) && $sukValue['quota_show'] ? intval($sukValue['quota_show']) : 0;
                $valueNew[$count]['code'] = $sukValue['code'] ?? '';
                $valueNew[$count]['bar_code'] = $sukValue['bar_code'] ?? '';
                $valueNew[$count]['weight'] = $sukValue['weight'] ? floatval($sukValue['weight']) : 0;
                $valueNew[$count]['volume'] = $sukValue['volume'] ? floatval($sukValue['volume']) : 0;
                $valueNew[$count]['brokerage'] = $sukValue['brokerage'] ? floatval($sukValue['brokerage']) : 0;
                $valueNew[$count]['brokerage_two'] = $sukValue['brokerage_two'] ? floatval($sukValue['brokerage_two']) : 0;
                switch ($type) {
                    case 1://秒杀
                        $valueNew[$count]['_checked'] = true;
                        break;
                    case 2://砍价
                        $valueNew[$count]['min_price'] = $min_price ? floatval($min_price) : 0;
                        $valueNew[$count]['opt'] = true;
                        break;
                    case 3://拼团
                        $valueNew[$count]['_checked'] = true;
                        break;
                    case 4://积分
                        $valueNew[$count]['integral'] = isset($sukValue['integral']) ? floatval($sukValue['integral']) : 0;
                        $valueNew[$count]['_checked'] = true;
                        break;
                    default:
                        $valueNew[$count]['_checked'] = false;
                        $valueNew[$count]['opt'] = false;
                        break;
                }
                $count++;
            }
        }
        return $valueNew;
    }

    /**
     * 根据活动商品unique查看原商品unique
     * @param string $unique
     * @param int $activity_id
     * @param int $type
     * @param array|string[] $field
     * @return array|mixed|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUniqueByActivityUnique(string $unique, int $activity_id, int $type = 1, array $field = ['unique'])
    {
        if ($type == 0) return $unique;
        $attrValue = $this->dao->get(['unique' => $unique, 'product_id' => $activity_id, 'type' => $type], ['id', 'suk', 'product_id']);
        if (!$attrValue) {
            return '';
        }
        switch ($type) {
            case 1://秒杀
                /** @var StoreSeckillServices $activityServices */
                $activityServices = app()->make(StoreSeckillServices::class);
                break;
            case 2://砍价
                /** @var StoreBargainServices $activityServices */
                $activityServices = app()->make(StoreBargainServices::class);
                break;
            case 3://拼团
                /** @var StoreCombinationServices $activityServices */
                $activityServices = app()->make(StoreCombinationServices::class);
                break;
            case 4://积分
                /** @var StoreIntegralServices $activityServices */
                $activityServices = app()->make(StoreIntegralServices::class);
                break;
            case 5://套餐
                /** @var StoreDiscountsServices $activityServices */
                $activityServices = app()->make(StoreDiscountsServices::class);
                break;
            default:
                /** @var StoreProductServices $activityServices */
                $activityServices = app()->make(StoreProductServices::class);
                break;

        }
        $product_id = $activityServices->value(['id' => $activity_id], 'product_id');
        if (!$product_id) {
            return '';
        }
        if (count($field) == 1) {
            return $this->dao->value(['suk' => $attrValue['suk'], 'product_id' => $product_id, 'type' => 0], $field[0] ?? 'unique');
        } else {
            return $this->dao->get(['suk' => $attrValue['suk'], 'product_id' => $product_id, 'type' => 0], $field);
        }

    }

    /**
     * 删除一条数据
     * @param int $id
     * @param int $type
     * @param array $suk
     * @return bool
     */
    public function del(int $id, int $type, array $suk = [])
    {
        return $this->dao->del($id, $type, $suk);
    }

    /**
     * 批量保存
     * @param array $data
     */
    public function saveAll(array $data)
    {
        $res = $this->dao->saveAll($data);
        if (!$res) throw new AdminException('规格保存失败');
        return $res;
    }

    /**
     * 获取sku
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getSkuArray(array $where, string $field = 'id,unique,bar_code,cost,price,integral,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,quota,product_id,code,settle_price,level_price,vip_price', string $key = 'suk')
    {
        return $this->dao->getColumn($where, $field, $key);
    }

    /**
     * 交易排行榜
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function purchaseRanking()
    {
        $dlist = $this->dao->attrValue();
        /** @var StoreProductServices $proServices */
        $proServices = app()->make(StoreProductServices::class);
        $slist = $proServices->getProductLimit(['is_del' => 0], 20, 'id as product_id,store_name,sales * price as val');
        $data = array_merge($dlist, $slist);
        $last_names = array_column($data, 'val');
        array_multisort($last_names, SORT_DESC, $data);
        $list = array_splice($data, 0, 20);
        return $list;
    }

    /**
     * 获取商品的属性数量
     * @param $product_id
     * @param $unique
     * @param $type
     * @return int
     */
    public function getAttrvalueCount($product_id, $unique, $type)
    {
        return $this->dao->count(['product_id' => $product_id, 'unique' => $unique, 'type' => $type]);
    }

    /**
     * 获取唯一值下的库存
     * @param string $unique
     * @return int
     */
    public function uniqueByStock(string $unique)
    {
        if (!$unique) return 0;
        return $this->dao->uniqueByStock($unique);
    }

    /**
     * 减销量,加库存
     * @param $productId
     * @param $unique
     * @param $num
     * @param int $type
     * @return mixed
     */
    public function decProductAttrStock($productId, $unique, $num, $type = 0)
    {
        $res = $this->dao->decStockIncSales([
            'product_id' => $productId,
            'unique' => $unique,
            'type' => $type
        ], $num);
        if ($res) {
            $this->workSendStock($productId, $unique, $type);
        }
        return $res;
    }

    /**
     * 减少销量增加库存
     * @param $productId
     * @param $unique
     * @param $num
     * @return bool
     */
    public function incProductAttrStock(int $productId, string $unique, int $num, int $type = 0)
    {
        return $this->dao->incStockDecSales(['unique' => $unique, 'product_id' => $productId, 'type' => $type], $num);
    }

    /**
     * 库存预警消息提醒
     * @param int $productId
     * @param string $unique
     * @param int $type
     */
    public function workSendStock(int $productId, string $unique, int $type)
    {
        ProductStockValueTips::dispatch([$productId, $unique, $type]);
    }


    /**
     * 更新sum_stock
     * @param array $uniques
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateSumStock(array $uniques)
    {
        $stockSumData = [];
        $this->dao->getList(['unique' => $uniques])->map(function ($item) use ($stockSumData) {
            if (isset($stockSumData[$item->unique])) {
                $data['sum_stock'] = $item->stock + $stockSumData[$item->unique];
            } else {
                $data['sum_stock'] = $item->stock;
            }
            $this->dao->update(['product_id' => $item['product_id'], 'unique' => $item['unique'], 'type' => $item['type']], $data);
        });
    }

    /**
     * 批量快速修改商品规格库存
     * @param int $id
     * @param array $data
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveProductAttrsStock(int $id, array $data)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $product = $productServices->get($id);
        if (!$product) {
            throw new ValidateException('商品不存在');
        }
        $attrs = $this->dao->getProductAttrValue(['product_id' => $id, 'type' => 0]);
        if ($attrs) $attrs = array_combine(array_column($attrs, 'unique'), $attrs);
        $dataAll = $update = [];
        $stock = 0;
        $time = time();
        foreach ($data as $attr) {
            if (!isset($attrs[$attr['unique']])) continue;
            if ($attr['pm']) {
                $stock = bcadd((string)$stock, (string)$attr['stock'], 0);
                $update['stock'] = bcadd((string)$attrs[$attr['unique']]['stock'], (string)$attr['stock'], 0);
                $update['sum_stock'] = bcadd((string)$attrs[$attr['unique']]['sum_stock'], (string)$attr['stock'], 0);
            } else {
                $stock = bcsub((string)$stock, (string)$attr['stock'], 0);
                $update['stock'] = bcsub((string)$attrs[$attr['unique']]['stock'], (string)$attr['stock'], 0);
                $update['sum_stock'] = bcsub((string)$attrs[$attr['unique']]['sum_stock'], (string)$attr['stock'], 0);
            }
            $update['stock'] = $update['stock'] > 0 ? $update['stock'] : 0;
            $this->dao->update(['id' => $attrs[$attr['unique']]['id']], $update);

            $dataAll[] = [
                'product_id' => $id,
                'unique' => $attr['unique'],
                'cost_price' => $attrs[$attr['unique']]['cost'] ?? 0,
                'number' => $attr['stock'],
                'pm' => $attr['pm'] ? 1 : 0,
                'add_time' => $time,
            ];
        }
        $product_stock = $stock ? bcadd((string)$product['stock'], (string)$stock, 0) : bcsub((string)$product['stock'], (string)$stock, 0);
        $product_stock = $product_stock > 0 ? $product_stock : 0;
        //修改商品库存
        $productServices->update($id, ['stock' => $product_stock]);
        //添加库存记录$product_stock
        if ($dataAll) {
            /** @var StoreProductStockRecordServices $storeProductStockRecordServces */
            $storeProductStockRecordServces = app()->make(StoreProductStockRecordServices::class);
            $storeProductStockRecordServces->saveAll($dataAll);
        }

        //清除缓存
        $productServices->cacheTag()->clear();
        /** @var StoreProductAttrServices $attrService */
        $attrService = app()->make(StoreProductAttrServices::class);
        $attrService->cacheTag()->clear();
        //警戒库存
        $productServices->workSendStock($id);
        return $product_stock;
    }

    /**
     * 查询库存预警产品ids
     * @param array $where
     * @return array
     */
    public function getGroupId(array $where)
    {
        $res1 = [];
        $res2 = $this->dao->getGroupData('product_id', 'product_id', $where);
        foreach ($res2 as $id) {
            $res1[] = $id['product_id'];
        }
        return $res1;
    }

    /**
     * 批量修改库存价格
     * @param $id
     * @param $data
     * @return true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/1/25 10:01
     */
    public function updateAttrs($id, $data)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $product = $productServices->get($id);
        if (!$product) {
            throw new ValidateException('商品不存在');
        }

        $attrs = $this->dao->getProductAttrValue(['product_id' => $id, 'type' => 0]);
        $data = array_combine(array_column($data, 'unique'), $data);
        $dataAll = $update = [];
        $product_stock = $product_price = $product_ot_price = $product_cost = 0;
        $time = time();
        foreach ($attrs as $item) {
            $attr = $data[$item['unique']] ?? [];
            if ($attr) {
                $this->update($item['id'], [
                    'price' => $attr['price'],
                    'stock' => $attr['stock'],
                    'sum_stock' => $attr['stock'],
                    'cost' => $attr['cost'],
                    'ot_price' => $attr['ot_price']
                ]);
                $number = bcsub((string)$attr['stock'], (string)$item['stock'], 0);
                $dataAll[] = [
                    'product_id' => $id,
                    'unique' => $attr['unique'],
                    'cost_price' => $attr['cost'] ?? 0,
                    'number' => abs($number),
                    'pm' => $number > 0 ? 1 : 0,
                    'add_time' => $time,
                ];
            }

            $product_array = $attr ?: $item;
            // 计算商品库存
            $product_stock = bcadd((string)$product_stock, (string)$product_array['stock'], 0);
            // 更新商品价格
            $product_price = max($product_price, $product_array['price']);
            // 更新商品原价
            $product_ot_price = max($product_ot_price, $product_array['ot_price']);
            // 更新商品成本
            $product_cost = max($product_cost, $product_array['cost']);
        }

        // 修改商品库存等信息
        $productServices->update($id, [
            'stock' => $product_stock,
            'price' => $product_price,
            'ot_price' => $product_ot_price,
            'cost' => $product_cost,
        ]);

        if ($dataAll) {
            /** @var StoreProductStockRecordServices $storeProductStockRecordServces */
            $storeProductStockRecordServces = app()->make(StoreProductStockRecordServices::class);
            $storeProductStockRecordServces->saveAll($dataAll);
        }

        // 清除缓存
        $productServices->cacheTag()->clear();
        /** @var StoreProductAttrServices $attrService */
        $attrService = app()->make(StoreProductAttrServices::class);
        $attrService->cacheTag()->clear();
        return true;
    }
}
