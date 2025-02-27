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

namespace app\services\order;

use app\dao\order\StoreOrderCartInfoDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;
use crmeb\traits\OptionTrait;
use crmeb\utils\Arr;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * Class StoreOrderCartInfoServices
 * @package app\services\order
 * @mixin StoreOrderCartInfoDao
 */
class StoreOrderCartInfoServices extends BaseServices
{
    use OptionTrait;

    /**
     * @var StoreOrderCartInfoDao
     */
    #[Inject]
    protected StoreOrderCartInfoDao $dao;

    /**
     * 检测订单商品是否核销完
     * @param int $oid
     * @return bool
     */
    public function checkWriteOff(int $oid): bool
    {
        return !$this->dao->count(['oid' => $oid, 'is_writeoff' => 0]);
    }

    /**
     * 清空订单商品缓存
     * @param int $oid
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function clearOrderCartInfo(int $oid)
    {
        return CacheService::delete(md5('store_order_cart_info_' . $oid));
    }

    /**
     * 获取指定订单下的商品详情
     * @param int $oid
     * @return array|mixed
     */
    public function getOrderCartInfo(int $oid)
    {
        $key = md5('store_order_cart_info_' . $oid);
        return $this->dao->cacheTag()->remember($key, function () use ($oid) {
            $cart_info = $this->dao->getCartColunm(['oid' => $oid], 'cart_info', 'cart_id');
            $info = [];
            foreach ($cart_info as $k => $v) {
                $_info = is_string($v) ? json_decode($v, true) : $v;
                if (!isset($_info['productInfo'])) $_info['productInfo'] = [];
                //缩略图处理
                if (isset($_info['productInfo']['attrInfo'])) {
                    $_info['productInfo']['attrInfo'] = get_thumb_water($_info['productInfo']['attrInfo']);
                }
                $_info['product_type'] = $_info['productInfo']['product_type'] ?? 0;
                $_info['supplier_id'] = $_info['productInfo']['supplier_id'] ?? 0;
                $_info['is_support_refund'] = $_info['productInfo']['is_support_refund'] ?? 1;
                $_info['store_name'] = $_info['store_name'] ?? $_info['title'] ?? '';
                $_info['productInfo'] = get_thumb_water($_info['productInfo']);
                $_info['refund_num'] = $this->dao->sum(['cart_id' => $_info['id']], 'refund_num');
                $info[$k]['cart_info'] = $_info;
                unset($_info);
            }
            return $info;
        }, 7 * 24 * 3600);
    }

    /**
     * 获取指定订单下的商品详情 供应商
     * @param int $oid
     * @return array
     */
    public function getOrderCartInfoSettlePrice(int $oid)
    {
        $cart_info = $this->dao->getCartColunm(['oid' => $oid], 'cart_num,refund_num,cart_info', 'id');
        $settlePrice = 0;
        $refundSettlePrice = 0;
        foreach ($cart_info as $k => &$v) {
            $_info = is_string($v['cart_info']) ? json_decode($v['cart_info'], true) : $v['cart_info'];
            $v['cart_info'] = $_info;
            if (!isset($_info['productInfo'])) $_info['productInfo'] = [];
            $settle_price = $_info['productInfo']['attrInfo']['settle_price'] ?? 0;
            $_info['settlePrice'] = bcmul((string)$settle_price, (string)$v['cart_num'], 2); //购买结算价格
            $settlePrice = bcadd($_info['settlePrice'], $settlePrice, 2);
            $_info['refundSettlePrice'] = bcmul((string)$settle_price, (string)$v['cart_num'], 2);//退款结算价格
            $refundSettlePrice = bcadd($_info['refundSettlePrice'], $refundSettlePrice, 2);
        }
        return ['settlePrice' => $settlePrice, 'refundSettlePrice' => $refundSettlePrice, 'info' => $cart_info];
    }

    /**
     * 查找购物车里的所有商品标题
     * @param int $oid
     * @param false $goodsNum
     * @return bool|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCarIdByProductTitle(int $oid, bool $goodsNum = false)
    {
        $key = md5('store_order_cart_product_title_' . $oid);
        $title = CacheService::get($key);
        if (!$title) {
            $orderCart = $this->dao->getCartInfoList(['oid' => $oid], ['cart_info']);
            foreach ($orderCart as $item) {
                if (isset($item['cart_info']['productInfo']['store_name'])) {
                    if ($goodsNum && isset($item['cart_info']['cart_num'])) {
                        $title .= $item['cart_info']['productInfo']['store_name'] . ' * ' . $item['cart_info']['cart_num'] . ' | ';
                    } else {
                        $title .= $item['cart_info']['productInfo']['store_name'] . '|';
                    }
                }
            }
            if ($title) {
                $title = substr($title, 0, strlen($title) - 1);
            }
            CacheService::set($key, $title, 7 * 24 * 3600);
        }
        return $title ? $title : '';
    }

    /**
     * 获取打印订单的商品信息
     * @param int $oid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartInfoPrintProduct(int $oid)
    {
        $cartInfo = $this->dao->getCartInfoList(['oid' => $oid], ['is_gift', 'cart_info']);
        $product = [];
        foreach ($cartInfo as $item) {
            $value = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
            $value['productInfo']['store_name'] = $value['productInfo']['store_name'] ?? "";
            $value['productInfo']['store_name'] = substrUTf8($value['productInfo']['store_name'], 10, 'UTF-8', '');
            $value['is_gift'] = $item['is_gift'];
            $product[] = $value;
        }
        return $product;
    }

    /**
     * 保存购物车info
     * @param $oid
     * @param array $cartInfo
     * @param $uid
     * @param array $promotions
     * @return int
     */
    public function setCartInfo($oid, array $cartInfo, $uid, array $promotions = [])
    {
        $group = [];
        foreach ($cartInfo as $cart) {
            $group[] = [
                'oid' => $oid,
                'uid' => $uid,
                'cart_id' => $cart['id'],
                'type' => $cart['type'] == 5 ? ($cart['productInfo']['plat_type'] ?? 0) : ($cart['productInfo']['type'] ?? 0),
                'relation_id' => $cart['productInfo']['relation_id'] ?? 0,
                'product_id' => $cart['product_id'] ?? $cart['productInfo']['id'],//原商品ID
                'product_type' => $cart['productInfo']['product_type'] ?? 0,
                'sku_unique' => $cart['product_attr_unique'] ?? '',
                'promotions_id' => implode(',', $cart['promotions_id'] ?? []),
                'is_gift' => isset($cart['is_gift']) && $cart['is_gift'] ? 1 : 0,
                'is_support_refund' => isset($cart['is_gift']) && $cart['is_gift'] ? 0 : ($cart['productInfo']['is_support_refund'] ?? 1),
                'cart_info' => json_encode($cart),
                'cart_num' => $cart['cart_num'],
                'surplus_num' => $cart['cart_num'],
                'split_surplus_num' => $cart['cart_num'],
                'write_times' => bcmul((string)$cart['cart_num'], (string)(max($cart['productInfo']['attrInfo']['write_times'] ?? 1, 1)), 0),
                'write_surplus_times' => bcmul((string)$cart['cart_num'], (string)(max($cart['productInfo']['attrInfo']['write_times'] ?? 1, 1)), 0),
                'unique' => md5($cart['id'] . '' . $oid),
                'total_price' => $cart['total_price'] ?? 0,
                'pay_price' => $cart['pay_price'] ?? 0,
                'pay_postage' => $cart['postage_price'] ?? 0,
                'coupon_price' => $cart['coupon_price'] ?? 0,
                'promotions_price' => $cart['sum_promotions_price'] ?? 0,
                'first_order_price' => $cart['first_order_price'] ?? 0,
            ];
        }
        if ($promotions) {
            /** @var StoreOrderPromotionsServices $services */
            $services = app()->make(StoreOrderPromotionsServices::class);
            $services->setPromotionsDetail((int)$uid, (int)$oid, $cartInfo, $promotions);
        }
        return $this->dao->saveAll($group);
    }

    /**
     * 订单创建成功之后计算订单（实际优惠、积分、佣金、上级、上上级）
     * @param $oid
     * @param array $cartInfo
     * @return bool
     */
    public function updateCartInfo($oid, array $cartInfo)
    {
        foreach ($cartInfo as $cart) {
            $group = [
                'cart_info' => json_encode($cart),
                'pay_price' => $cart['pay_price'] ?? 0,
                'deduction_price' => $cart['integral_price'] ?? 0,
                'change_price' => $cart['change_price'] ?? 0
            ];
            $this->dao->update(['oid' => $oid, 'cart_id' => $cart['id']], $group);
        }
        $this->dao->cacheTag()->clear();
        return true;
    }

    /**
     * 商品编号
     * @param int $oid
     * @return array
     */
    public function getCartIdsProduct(int $oid)
    {
        $where = [
            'oid' => $oid,
        ];
        return $this->dao->getColumn($where, 'product_id', 'oid', true);
    }

    /**
     * 检测这些商品是否还可以拆分
     * @param int $oid
     * @param array $cart_data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkCartIdsIsSplit(int $oid, array $cart_data)
    {
        if (!$cart_data) return false;
        $ids = array_unique(array_column($cart_data, 'cart_id'));
        if ($this->dao->getCartInfoList(['oid' => $oid, 'cart_id' => $ids, 'split_status' => 2], ['cart_id'])) {
            throw new ValidateException('您选择的商品已经拆分完成，请刷新或稍后重新选择');
        }
        $cartInfo = $this->getSplitCartList($oid, 'surplus_num,split_surplus_num,cart_info,cart_num', 'cart_id');
        if (!$cartInfo) {
            throw new ValidateException('该订单已发货完成');
        }
        foreach ($cart_data as $cart) {
            $surplus_num = $cartInfo[$cart['cart_id']]['surplus_num'] ?? 0;
            if (!$surplus_num) {//兼容之前老数据
                $_info = $cartInfo[$cart['cart_id']]['cart_info'] ?? [];
                $surplus_num = $_info['cart_num'] ?? 0;
            }
            if ($cart['cart_num'] > $surplus_num) {
                throw new ValidateException('您选择商品拆分数量大于购买数量');
            }
        }
        return true;
    }

    /**
     * 获取可退款商品
     * @param int $oid
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getRefundCartList(int $oid, string $field = '*', string $key = '')
    {
        $cartInfo = $this->dao->getColumn([['oid', '=', $oid]], $field, $key);
        foreach ($cartInfo as $key => &$item) {
            if ($field == 'cart_info') {
                $item = is_string($item) ? json_decode($item, true) : $item;
            } else {
                if (isset($item['cart_info'])) $item['cart_info'] = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
                if (isset($item['cart_num']) && !$item['cart_num']) {//兼容之前老数据
                    $item['cart_num'] = $item['cart_info']['cart_num'] ?? 0;
                }
            }
            $surplus = (int)bcsub((string)$item['cart_num'], (string)$item['refund_num'], 0);
            if ($surplus > 0) {
                $item['surplus_num'] = $surplus;
            } else {
                unset($cartInfo[$key]);
            }
        }
        return array_merge($cartInfo);
    }

    /**
     * 获取某个订单还可以拆分商品 split_status 0：未拆分1：部分拆分2：拆分完成
     * @param int $oid
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getSplitCartList(int $oid, string $field = '*', string $key = '')
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->get($oid);
        if (!$order) {
            throw new ValidateException('订单不存在');
        }
        $store_id = $this->getItem('store_id', 0);
        $supplier_id = $this->getItem('supplier_id', 0);

        //拆分完整主订单查询未发货子订单
        if ($order['pid'] == -1) {
            $oid = $orderServices->value(['pid' => $oid, 'status' => 0, 'supplier_id' => $supplier_id, 'store_id' => $store_id, 'refund_type' => [0, 3]], 'id');
        }
        $cartInfo = $this->dao->getColumn([['oid', '=', $oid], ['split_status', 'IN', [0, 1]]], $field, $key);
        foreach ($cartInfo as &$item) {
            if ($field == 'cart_info') {
                $item = is_string($item) ? json_decode($item, true) : $item;
            } else {
                if (isset($item['cart_info'])) $item['cart_info'] = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
                if (isset($item['cart_num']) && !$item['cart_num']) {//兼容之前老数据
                    $item['cart_num'] = $item['cart_info']['cart_num'] ?? 0;
                }
                $item['surplus_num'] = $item['split_surplus_num'];
            }
        }
        return $cartInfo;
    }

    /**
     * 次卡商品未核销短信提醒
     * @return bool
     */
    public function reminderUnverifiedRemind()
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        // 临期
        //系统预设取消订单时间段
        $keyValue = ['reminder_deadline_second_card_time'];
        //获取配置
        $systemValue = SystemConfigService::more($keyValue);
        //格式化数据
        $systemValue = Arr::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
        $reminder_deadline_second_card_time = $systemValue['reminder_deadline_second_card_time'];
        $reminder_deadline_second_card_time = (int)bcmul((string)$reminder_deadline_second_card_time, '3600', 0);
        $writeTime = time() + $reminder_deadline_second_card_time;
        $adventList = $this->dao->getAdventCartInfoList(time(), $writeTime);
        if ($adventList) {
            foreach ($adventList as $key => $item) {
                $cart_info = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
                $store_name = substrUTf8($cart_info['productInfo']['store_name'], 10, 'UTF-8', '');
                $data['store_name'] = $store_name;
                $order = $orderServices->get($item['oid'], ['id', 'uid', 'pay_time', 'user_phone']);
                if (!$order) {
                    continue;
                }
                $data['uid'] = $order['uid'];
                $data['end_time'] = date('Y-m-d H:i', $item['write_end']);
                $data['pay_time'] = date('Y-m-d H:i', $order['pay_time']);
                $data['phone'] = $order['user_phone'];
                event('notice.notice', [$data, 'reminder_brink_death']);
                $this->dao->update($item['id'], ['is_advent_sms' => 1]);
            }
        }
        // 过期
        $expireList = $this->dao->getExpireCartInfoList(time());
        if ($expireList) {
            foreach ($expireList as $key => $item) {
                $cart_info = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
                $store_name = substrUTf8($cart_info['productInfo']['store_name'], 10, 'UTF-8', '');
                $data['store_name'] = $store_name;
                $data['end_time'] = date('Y-m-d H:i', $item['write_end']);
                $order = $orderServices->get($item['oid'], ['id', 'uid', 'pay_time', 'user_phone']);
                if (!$order) {
                    continue;
                }
                $data['uid'] = $order['uid'];
                $data['phone'] = $order['user_phone'];
                event('notice.notice', [$data, 'expiration_reminder']);
                $this->dao->update($item['id'], ['is_expire_sms' => 1]);
            }
        }
        return true;
    }
}
