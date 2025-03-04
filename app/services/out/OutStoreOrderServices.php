<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2023 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\out;

use app\dao\order\StoreOrderDao;
use app\services\activity\combination\StorePinkServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\BaseServices;
use app\services\pay\PayServices;
use crmeb\exceptions\ApiException;
use crmeb\services\HttpService;
use think\annotation\Inject;

/**
 * Class OutStoreOrderServices
 * @package app\services\order
 * @method getOrderIdsCount(array $ids) 获取订单id下没有删除的订单数量
 * @method StoreOrderDao getUserOrderDetail(string $key, int $uid, array $with) 获取订单详情
 * @method chartTimePrice($start, $stop) 获取当前时间到指定时间的支付金额 管理员
 * @method chartTimeNumber($start, $stop) 获取当前时间到指定时间的支付订单数 管理员
 * @method together(array $where, string $field, string $together = 'sum') 聚合查询
 * @method getBuyCount($uid, $type, $typeId) 获取用户已购买此活动商品的个数
 * @method getDistinctCount(array $where, $field, ?bool $search = true)
 * @method getTrendData($time, $type, $timeType, $str) 用户趋势
 * @method getRegion($time, $channelType) 地域统计
 * @method getProductTrend($time, $timeType, $field, $str) 商品趋势
 */
class OutStoreOrderServices extends BaseServices
{

    /**
     * 发货类型
     * @var string[]
     */
    public array $deliveryType = ['send' => '商家配送', 'express' => '快递配送', 'fictitious' => '虚拟发货', 'delivery_part_split' => '拆分部分发货', 'delivery_split' => '拆分发货完成'];

    /**
     * @var StoreOrderDao
     */
    #[Inject]
    protected StoreOrderDao $dao;

    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderList(array $where)
    {
        if (!is_numeric($where['paid'])) {
            $where['paid'] = -1;
        }
        [$page, $limit] = $this->getPageValue();
        $field = ['id', 'pid', 'order_id', 'trade_no', 'uid', 'freight_price', 'real_name', 'user_phone', 'user_address', 'total_num',
            'total_price', 'total_postage', 'pay_price', 'coupon_price', 'deduction_price', 'paid', 'pay_time', 'pay_type', 'add_time',
            'shipping_type', 'status', 'refund_status', 'delivery_name', 'delivery_code', 'delivery_id'];
        $data = $this->dao->getOutOrderList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        $list = $this->tidyOrderList($data);
        return compact('list', 'count');
    }

    /**
     * 数据转换
     * @param array $data
     * @return array
     */
    public function tidyOrderList(array $data)
    {
        /** @var StoreOrderCartInfoServices $services */
        $services = app()->make(StoreOrderCartInfoServices::class);
        foreach ($data as &$item) {
            $list = [];
            $carts = $services->getOrderCartInfo((int)$item['id']);
            foreach ($carts as $key => $cart) {
                $list = $this->tidyCartList($cart['cart_info'], $list, $key);
            }
            $item['pay_type_name'] = PayServices::PAY_TYPE[$item['pay_type']] ?? '其他方式';
            $item['items'] = $list;
            unset($item['refund_status'], $item['shipping_type']);
        }
        return $data;
    }

	/**
     * 订单推送
     * @param int $id
     * @param string $pushUrl
     * @return bool
     */
    public function orderCreatePush(int $id, string $pushUrl): bool
    {
        $orderInfo = $this->getInfo('', $id);
        return $this->outPush($pushUrl, $orderInfo, '订单');
    }

	/**
     * 支付推送
     * @param int $id
     * @param string $pushUrl
     * @return bool
     */
    public function paySuccessPush(int $id, string $pushUrl): bool
    {
        $orderInfo = $this->getInfo('', $id);
        return $this->outPush($pushUrl, $orderInfo, '订单支付');
    }

    /**
     * 默认数据推送
     * @param string $pushUrl
     * @param array $data
     * @param string $tip
     * @return bool
     */
    function outPush(string $pushUrl, array $data, string $tip = ''): bool
    {
        $param = json_encode($data, JSON_UNESCAPED_UNICODE);
        $res = HttpService::postRequest($pushUrl, $param, ['Content-Type:application/json', 'Content-Length:' . strlen($param)]);
        $res = $res ? json_decode($res, true) : [];
        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            \think\facade\Log::error(['msg' => $tip . '推送失败', 'data' => $res]);
            return false;
        }
        return true;
    }

    /**
     * 订单详情
     * @param string $orderId 订单号
     * @param int $id 订单ID
     * @return mixed
     */
    public function getInfo(string $orderId = '', int $id = 0)
    {
        $field = ['id', 'pid', 'order_id', 'trade_no', 'uid', 'freight_price', 'real_name', 'user_phone', 'user_address', 'total_num',
            'total_price', 'total_postage', 'pay_price', 'coupon_price', 'deduction_price', 'paid', 'pay_time', 'pay_type', 'add_time',
            'shipping_type', 'status', 'refund_status', 'delivery_name', 'delivery_code', 'delivery_id', 'refund_type', 'delivery_type', 'pink_id', 'use_integral', 'back_integral'];

        if ($id > 0) {
            $where = $id;
        } else {
            $where = ['order_id' => $orderId];
        }

        if (!$orderInfo = $this->dao->get($where, $field, ['invoice'])) {
            throw new ApiException(400118);
        }

        if (!$orderInfo['invoice']) {
            $orderInfo['invoice'] = new \StdClass();
        } else {
            $orderInfo['invoice']->hidden(['uid', 'category', 'id', 'order_id', 'add_time']);
        }

        $orderInfo = $this->tidyOrder($orderInfo->toArray(), true);
        //核算优惠金额
        $vipTruePrice = array_column($orderInfo['items'], 'vip_sum_truePrice');
        $vipTruePrice = round(array_sum($vipTruePrice), 2);
        $orderInfo['vip_true_price'] = sprintf("%.2f", $vipTruePrice ?: '0.00');
        $orderInfo['total_price'] = bcadd($orderInfo['total_price'], $orderInfo['vip_true_price'], 2);
        return $orderInfo;
    }

    /**
     * 订单详情数据格式化
     * @param $order
     * @param bool $detail 是否需要订单商品详情
     * @return mixed
     */
    public function tidyOrder($order, bool $detail = false)
    {
        if ($detail == true && isset($order['id'])) {
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $carts = $cartServices->getOrderCartInfo((int)$order['id']);

            $list = [];
            foreach ($carts as $key => $cart) {
                $list = $this->tidyCartList($cart['cart_info'], $list, $key);
            }
            $order['items'] = $list;
        }

        $order['pay_type_name'] = PayServices::PAY_TYPE[$order['pay_type']] ?? '其他方式';

        if (!$order['paid'] && $order['pay_type'] == 'offline' && !$order['status'] >= 2) {
            $order['status_name'] = '线下付款,未支付';
        } else if (!$order['paid']) {
            $order['status_name'] = '待付款';
        } else if ($order['status'] == 4) {
            if ($order['delivery_type'] == 'send') {
                $order['status_name'] = '待收货';
            } elseif ($order['delivery_type'] == 'express') {
                $order['status_name'] = '待收货';
            } elseif ($order['delivery_type'] == 'split') {//拆分发货
                $order['status_name'] = '待收货';
            } else {
                $order['status_name'] = '待收货';
            }
        } else if ($order['refund_status'] == 1) {
            if (in_array($order['refund_type'], [0, 1, 2])) {
                $order['status_name'] = '申请退款中';
            } elseif ($order['refund_type'] == 4) {
                $order['status_name'] = '申请退款中';
            } elseif ($order['refund_type'] == 5) {
                $order['status_name'] = '申请退款中';
            }
        } else if ($order['refund_status'] == 2 || $order['refund_type'] == 6) {
            $order['status_name'] = '已退款';
        } else if ($order['refund_status'] == 3) {
            $order['status_name'] = '部分退款（子订单）';
        } else if ($order['refund_status'] == 4) {
            $order['status_name'] = '子订单已全部申请退款中';
        } else if (!$order['status']) {
            if ($order['pink_id']) {
                /** @var StorePinkServices $pinkServices */
                $pinkServices = app()->make(StorePinkServices::class);
                if ($pinkServices->getCount(['id' => $order['pink_id'], 'status' => 1])) {
                    $order['status_name'] = '拼团中';
                } else {
                    $order['status_name'] = '未发货';
                }
            } else {
                if ($order['shipping_type'] === 1) {
                    $order['status_name'] = '未发货';
                } else {
                    $order['status_name'] = '待核销';
                }
            }
        } else if ($order['status'] == 1) {
            if ($order['delivery_type'] == 'send') {//TODO 送货
                $order['status_name'] = '待收货';
            } elseif ($order['delivery_type'] == 'express') {//TODO  发货
                $order['status_name'] = '待收货';
            } elseif ($order['delivery_type'] == 'split') {//拆分发货
                $order['status_name'] = '待收货';
            } else {
                $order['status_name'] = '待收货';
            }
        } else if ($order['status'] == 2) {
            $order['status_name'] = '待评价';
        } else if ($order['status'] == 3) {
            $order['status_name'] = '交易完成';
        }
        unset($order['pink_id'], $order['refund_type']);
        return $order;
    }

    /**
     * 格式化订单商品
     * @param array $cartInfo
     * @param array $list
     * @return array
     */
    public function tidyCartList(array $cartInfo, array $list, $cartId = 0): array
    {
        $list[] = [
            'cart_id' => $cartId,
            'store_name' => $cartInfo['productInfo']['store_name'] ?? '',
            'suk' => $cartInfo['productInfo']['attrInfo']['suk'] ?? '',
            'image' => $cartInfo['productInfo']['attrInfo']['image'] ?: $cartInfo['productInfo']['image'],
            'price' => sprintf("%.2f", $cartInfo['truePrice'] ?? '0.00'),
            'cart_num' => $cartInfo['cart_num'] ?? 0,
            'surplus_num' => $cartInfo['surplus_num'] ?? 0,
            'refund_num' => $cartInfo['refund_num'] ?? 0
        ];
        return $list;
    }

}
