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
namespace app\controller\api\admin\order;

use app\Request;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\combination\StorePinkServices;
use app\services\message\service\StoreServiceServices;
use app\services\order\StoreOrderPromotionsServices;
use app\services\order\store\WriteOffOrderServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderSplitServices;
use app\services\order\StoreOrderWriteOffServices;
use app\services\product\product\StoreProductLogServices;
use app\services\store\DeliveryServiceServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderWapServices;
use app\services\pay\OrderOfflineServices;
use app\services\serve\ServeServices;
use app\services\user\UserServices;
use app\services\other\ExpressServices;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

/**
 * 订单类
 * Class StoreOrder
 * @package app\api\controller\admin\order
 */
class StoreOrder
{
    /**
     * @var StoreOrderWapServices
     */
    #[Inject]
    protected StoreOrderWapServices $services;

    /**
     * 获取erp开关
     * @return Response
     */
    public function getErpConfig(): Response
    {
        return app('json')->success(['open_erp' => !!sys_config('erp_open')]);
    }


    /**
     *  订单数据统计
     * @param StoreOrderServices $services
     * @return Response
     */
    public function statistics(StoreOrderServices $services): Response
    {
        $dataCount = $services->getOrderData(0, [], 0);
        $dataPrice = $this->services->getOrderTimeData(0);
        $data = array_merge($dataCount, $dataPrice);
        return app('json')->successful($data);
    }

    /**
     *  工作台数据
     * @param StoreOrderServices $services
     * @return Response
     * @throws DbException
     */
    public function stagingData(StoreOrderServices $services): Response
    {
        $data = $services->getStagingData();
        return app('json')->successful($data);
    }

    /**
     * 订单每月统计数据
     * @param Request $request
     * @return Response
     */
    public function data(Request $request): Response
    {
        [$start, $stop] = $request->getMore([
            ['start', strtotime(date('Y-m'))],
            ['stop', time()],
        ], true);
        return app('json')->successful($this->services->getOrderDataPriceCount(['time' => [$start, $stop]]));
    }

    /**
     * 订单列表
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function lst(Request $request): Response
    {
        $where = $request->getMore([
            ['status', ''],
            ['is_del', 0],
            ['data', '', '', 'time'],
            ['type', ''],
            ['pay_type', ''],
            ['field_key', ''],
            ['field_value', ''],
            ['keyword', '', '', 'real_name']
        ]);
        $where['is_system_del'] = 0;
        if (!in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = 0;
        }
        return app('json')->successful($this->services->getWapAdminOrderList($where, ['split' => function ($query) {
            $query->field('id,pid');
        }, 'pink', 'invoice']));
    }

    /**
     * 订单详情
     * @param Request $request
     * @param StoreOrderServices $services
     * @param UserServices $userServices
     * @param StoreOrderPromotionsServices $storeOrderPromotiosServices
     * @param $orderId
     * @return Response
     */
    public function detail(Request $request, StoreOrderServices $services, UserServices $userServices, StoreOrderPromotionsServices $storeOrderPromotiosServices, $orderId): Response
    {
        if (!strlen(trim($orderId))) return app('json')->fail('参数错误');
        $order = $this->services->getOne(['order_id' => $orderId], '*', ['invoice', 'refund' => function ($query) {
            $query->field('id,store_order_id,refund_num');
        }]);
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        $order['split'] = [];
        $orderInfo = $services->tidyOrder($order, true);
        //核算优惠金额
        $vipTruePrice = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $key => &$cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
        }
        $orderInfo['vip_true_price'] = $vipTruePrice;
        $orderInfo['total_price'] = floatval(bcsub((string)$orderInfo['total_price'], (string)$vipTruePrice, 2));
        //优惠活动优惠详情
        $orderInfo['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$order['id']);
        if ($orderInfo['give_coupon']) {
            $couponIds = is_string($orderInfo['give_coupon']) ? explode(',', $orderInfo['give_coupon']) : $orderInfo['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderInfo['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        $orderInfo['pinkStatus'] = null;
        if ($orderInfo['type'] == 3) {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $orderInfo['pinkStatus'] = $pinkService->value(['order_id' => $orderInfo['order_id']], 'status');
        }
        $nickname = $userServices->value(['uid' => $orderInfo['uid']], 'nickname');
        $orderInfo['nickname'] = $nickname;

        $refund_num = array_sum(array_column($orderInfo['refund'], 'refund_num'));
        $cart_num = 0;
        foreach ($orderInfo['cartInfo'] as &$items) {
            if (isset($items['is_gift']) && $items['is_gift']) continue;
            $cart_num += $items['cart_num'];
            $cart_ids = [];
            $cart_ids[] = ['cart_id' => $items['id'], 'cart_num' => $items['cart_num']];
            /** @var StoreOrderSplitServices $storeOrderSpliteServices */
            $storeOrderSpliteServices = app()->make(StoreOrderSplitServices::class);
            $cartInfos = $storeOrderSpliteServices->getSplitOrderCartInfo($orderInfo['id'], $cart_ids, $orderInfo);
            $total_price = $pay_postage = 0;
            foreach ($cartInfos as $cart1) {
                $_info = is_string($cart1['cart_info']) ? json_decode($cart1['cart_info'], true) : $cart1['cart_info'];
                $total_price = bcadd((string)$total_price, bcmul((string)($_info['truePrice'] ?? 0), (string)$cart1['cart_num'], 4), 4);
                if (!in_array($orderInfo['shipping_type'], [2, 4])) {
                    $pay_postage = bcadd((string)$pay_postage, (string)($_info['postage_price'] ?? 0), 4);
                }
            }
            //实际退款金额
            $refund_pay_price = bcadd((string)$total_price, (string)$pay_postage, 2);
            $refund_price = $refund_pay_price;
            if (isset($orderInfo['change_price']) && (float)$orderInfo['change_price']) {//有改价 且是拆分
                //订单原实际支付金额
                $order_pay_price = bcadd((string)$orderInfo['change_price'], (string)$orderInfo['pay_price'], 4);
                if ($order_pay_price) {
                    $refund_price = bcmul((string)bcdiv((string)$orderInfo['pay_price'], (string)$order_pay_price, 4), (string)$refund_pay_price, 2);
                }
            }
            $items['refund_price'] = $refund_price;
        }
        $orderInfo['is_all_refund'] = $refund_num == $cart_num;
        return app('json')->successful('ok', $orderInfo);
    }

    /**
     * 订单发货获取订单信息
     * @param UserServices $userServices
     * @param $orderId
     * @return Response
     */
    public function delivery_gain(UserServices $userServices, $orderId): Response
    {
        $order = $this->services->getOne(['order_id' => $orderId], 'real_name,user_phone,user_address,order_id,uid,status,paid,id');
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['paid']) {
            $order['nickname'] = $userServices->value(['uid' => $order['uid']], 'nickname');
            $order = $order->hidden(['uid', 'status', 'paid'])->toArray();
            $order['config_export_open'] = sys_config('config_export_open');
            return app('json')->successful('ok', $order);
        }
        return app('json')->fail('状态错误');
    }

    /**
     * 订单发货
     * @param Request $request
     * @param StoreOrderDeliveryServices $services
     * @param $id
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function delivery_keep(Request $request, StoreOrderDeliveryServices $services, $id): Response
    {
        $data = $request->postMore([
            ['type', 1],
            ['delivery_name', ''],//快递公司id
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码

            ['express_record_type', 2],//发货记录类型
            ['express_temp_id', ""],//电子面单模板
            ['to_name', ''],//寄件人姓名
            ['to_tel', ''],//寄件人电话
            ['to_addr', ''],//寄件人地址

            ['sh_delivery_name', ''],//送货人姓名
            ['sh_delivery_id', ''],//送货人电话
            ['sh_delivery_uid', ''],//送货人ID
            ['delivery_type', 1],//送货类型

            ['fictitious_content', '']//虚拟发货内容
        ]);
        $services->delivery((int)$id, $data);
        return app('json')->successful('发货成功!');
    }

    /**
     * 订单拆单发送货
     * @param StoreOrderDeliveryServices $services
     * @param Request $request
     * @param $id 订单id
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function split_delivery(StoreOrderDeliveryServices $services, Request $request, $id): Response
    {
        $data = $request->postMore([
            ['type', 1],
            ['delivery_name', ''],//快递公司名称
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码

            ['express_record_type', 2],//发货记录类型
            ['express_temp_id', ""],//电子面单模板
            ['to_name', ''],//寄件人姓名
            ['to_tel', ''],//寄件人电话
            ['to_addr', ''],//寄件人地址

            ['sh_delivery_name', ''],//送货人姓名
            ['sh_delivery_id', ''],//送货人电话
            ['sh_delivery_uid', ''],//送货人ID

            ['fictitious_content', ''],//虚拟发货内容

            ['cart_ids', []]
        ]);
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        if (!$data['cart_ids']) {
            return app('json')->fail('请选择发货商品');
        }
        foreach ($data['cart_ids'] as $cart) {
            if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                return app('json')->fail('请重新选择发货商品，或发货件数');
            }
        }
        $services->splitDelivery((int)$id, $data);
        return app('json')->success('SUCCESS');
    }

    /**
     * 获取订单可拆分发货商品列表
     * @param $id
     * @param StoreOrderCartInfoServices $services
     * @return mixed
     */
    public function split_cart_info($id, StoreOrderCartInfoServices $services)
    {
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        return app('json')->success($services->getSplitCartList((int)$id));
    }

    /**
     * 订单改价
     * @param Request $request
     * @param StoreOrderServices $services
     * @return mixed
     * @throws \Exception
     */
    public function price(Request $request, StoreOrderServices $services)
    {
        [$order_id, $price] = $request->postMore([
            ['order_id', ''],
            ['price', '']
        ], true);
        $order = $this->services->getOne(['order_id' => $order_id], 'id,user_phone,id,paid,pay_price,order_id,total_price,total_postage,pay_postage,gain_integral');
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['paid']) {
            return app('json')->fail('订单已支付');
        }
        if ($price === '') return app('json')->fail('请填写实际支付金额');
        if ($price < 0) return app('json')->fail('实际支付金额不能小于0元');
        if ($order['pay_price'] == $price) return app('json')->successful('改价成功');
        $services->updateOrder($order['id'], ['total_price' => $order['total_price'], 'pay_price' => $price]);
        return app('json')->successful('改价成功');
    }

    /**
     * 订单备注
     * @param Request $request
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function remark(Request $request)
    {
        [$order_id, $remark] = $request->postMore([
            ['order_id', ''],
            ['remark', '']
        ], true);
        $order = $this->services->getOne(['order_id' => $order_id], 'id,remark');
        if (!$order) return app('json')->fail('订单不存在');
        if (!strlen(trim($remark))) return app('json')->fail('请填写备注内容');
        $order->remark = $remark;
        if (!$order->save())
            return app('json')->fail('备注失败');
        return app('json')->successful('备注成功');
    }

    /**
     * $type 1 = 今天  7 = 近 7 天  30 = 近 30 天
     * 订单交易额/订单数量时间统计
     * @param Request $request
     * @return bool
     */
    public function time(Request $request)
    {
        [$type] = $request->getMore([
            ['type', 0],
        ], true);
        switch ($type) {
            case 1:
                $start = strtotime(date('Y-m-d'));
                break;
            case 7:
                $start = strtotime(date("Y-m-d", strtotime("-6 day")));
                break;
            case 30:
                $start = strtotime(date("Y-m-d", strtotime("-29 day")));
                break;
        }
        $stop = time();
        //$start 开始时间 $stop 结束时间 $front 上一期限开始时间
        [$start, $stop, $front] = getFrontTime((int)$start, (int)$stop);
        $front_stop = (int)bcsub((string)$start, '1');// 上一期限结束时间

        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        $order_where = ['pid' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];
        $frontPrice = $orderService->sum($order_where + ['time' => [$front, $front_stop]], 'pay_price', true);
        $afterPrice = $orderService->sum($order_where + ['time' => [$start, $stop]], 'pay_price', true);
        $data['after_price'] = $afterPrice;//时间区间营业额
        $increase = (float)bcsub((string)$afterPrice, (string)$frontPrice, 2); //同比上个时间区间增长营业额
        $growthRate = abs($increase);
        if ($growthRate == 0) $data['growth_rate'] = 0;
        else if ($frontPrice == 0) $data['growth_rate'] = (int)bcmul($growthRate, 100, 0);
        else $data['growth_rate'] = (int)bcmul((string)bcdiv((string)$growthRate, (string)$frontPrice, 2), '100', 0);//时间区间增长率
        $data['increase_time'] = abs($increase); //同比上个时间区间增长营业额
        $data['increase_time_status'] = $increase >= 0 ? 1 : 2; //同比上个时间区间增长营业额增长 1 减少 2
        $after_number_arr = $orderService->column($order_where + ['time' => [$start, $stop]], 'id,uid,order_id', 'id'); //订单数
        $data['after_number'] = count($after_number_arr); //订单数
        $uid_arr = array_unique(array_column($after_number_arr, 'uid'));
        $data['after_pay_number'] = count($uid_arr); //支付人数
        /** @var StoreProductLogServices $productLogServices */
        $productLogServices = app()->make(StoreProductLogServices::class);
        $data['today_visits'] = $productLogServices->count(['time' => [$start, $stop], 'type' => 'visit']);
        return app('json')->successful($data);
    }

    /**
     * 统计图表
     * $type 1 = 今天  7 = 近 7 天  30 = 近 30 天
     * @param Request $request
     * @return Response
     */
    public function timeChart(Request $request)
    {
        [$type] = $request->getMore([
            ['type', 0],
        ], true);
        switch ($type) {
            case 1:
                $start = strtotime(date('Y-m-d'));
                break;
            case 7:
                $start = strtotime(date("Y-m-d", strtotime("-6 day")));
                break;
            case 30:
                $start = strtotime(date("Y-m-d", strtotime("-29 day")));
                break;
        }
        $stop = time();
        //$start 开始时间 $stop 结束时间 $front 上一期限开始时间
        [$start, $stop, $front] = getFrontTime((int)$start, (int)$stop);
        $front_stop = (int)bcsub((string)$start, '1'); // 上一期限结束时间
        if ($type == 1) {
            $start = $front;
            $dateList = range($start, $stop, 86400);
        } else {
            $dateList = range($start, $stop, 86400);
        }
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        $chartInfoPrice = $orderService->chartTimePrice($start, $stop);
        $chartInfoNumber = $orderService->chartTimeNumber($start, $stop);

        $chart = [];
        foreach ($dateList as $key => $time) {
            $day = date('m-d', $time);
            $chart[$key]['time'] = $day;
            $chart[$key]['num'] = 0;
            $chart[$key]['price'] = 0;
            foreach ($chartInfoPrice as $p => $price) {
                if ($day == $price['time']) {
                    $chart[$key]['price'] = $price['price'];
                }
            }
            foreach ($chartInfoNumber as $n => $num) {
                if ($day == $num['time']) {
                    $chart[$key]['num'] = $num['num'];
                }
            }
        }
        return app('json')->successful($chart);
    }

    /**
     * 订单支付
     * @param Request $request
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function offline(Request $request, OrderOfflineServices $services)
    {
        [$orderId] = $request->postMore([['order_id', '']], true);
        $orderInfo = $this->services->getOne(['order_id' => $orderId], 'id');
        if (!$orderInfo) return app('json')->fail('参数错误');
        $id = $orderInfo->id;
        $services->orderOffline((int)$id);
        return app('json')->successful('修改成功!');

    }

    /**
     * 订单退款
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refund(Request $request, StoreOrderRefundServices $services, StoreOrderServices $orderServices, StoreOrderCartInfoServices $storeOrderCartInfoServices, StoreOrderCreateServices $storeOrderCreateServices)
    {
        [$orderId, $price, $type, $refuse_reason] = $request->postMore([
            ['order_id', ''],
            ['price', '0'],
            ['type', 1],
            ['refuse_reason', '']
        ], true);
        if (!strlen(trim($orderId))) return app('json')->fail('参数错误');
        //退款订单详情
        $orderRefund = $services->getOne(['order_id' => $orderId]);
        $is_admin = 0;
        if (!$orderRefund) {
            //主动退款主订单详情
            $orderRefund = $orderRefund ?: $orderServices->getOne(['order_id' => $orderId]);
            $is_admin = 1;
            if ($services->count(['store_order_id' => $orderRefund['id'], 'refund_type' => [0, 1, 2, 4, 5], 'is_cancel' => 0, 'is_del' => 0])) {
                return app('json')->fail('请先处理售后申请');
            }
        }
        if (!$is_admin) {
            if (!$orderRefund) {
                return app('json')->fail('数据不存在!');
            }
            if ($orderRefund['is_cancel'] == 1) {
                return app('json')->fail('用户已取消申请');
            }
            $orderInfo = $this->services->get((int)$orderRefund['store_order_id']);
            if (!$orderInfo) {
                return app('json')->fail('数据不存在');
            }
            if (!in_array($orderRefund['refund_type'], [0, 1, 2, 5]) && !($orderRefund['refund_type'] == 4 && $orderRefund['apply_type'] == 3)) {
                return app('json')->fail('售后订单状态不支持该操作');
            }

            if ($type == 1) {
                $data['refund_type'] = 6;
            } else if ($type == 2) {
                $data['refund_type'] = 3;
                $data['refuse_reason'] = $refuse_reason;
            } else {
                return app('json')->fail('退款修改状态错误');
            }
            $data['refunded_time'] = time();
            //拒绝退款
            if ($type == 2) {
                $services->refuseRefund((int)$orderRefund['id'], $data, $orderRefund);
                return app('json')->successful('修改退款状态成功!');
            } else {
                if ($orderRefund['refund_price'] == $orderInfo['refunded_price']) return app('json')->fail('已退完支付金额!不能再退款了');
                if (!$price) {
                    return app('json')->fail('请输入退款金额');
                }
                $data['refunded_price'] = bcadd($price, $orderRefund['refunded_price'], 2);
                $bj = bccomp((float)$orderRefund['refund_price'], (float)$data['refunded_price'], 2);
                if ($bj < 0) {
                    return app('json')->fail('退款金额大于支付金额，请修改退款金额');
                }
                $refundData['pay_price'] = $orderInfo['pay_price'];
                $refundData['refund_price'] = $price;


                //修改订单退款状态
                if ($services->agreeRefund((int)$orderRefund['id'], $refundData)) {
                    $services->update((int)$orderRefund['id'], $data);
                    return app('json')->success('审核成功');
                } else {
                    $services->storeProductOrderRefundYFasle((int)$orderInfo['id'], $price);
                    return app('json')->fail('审核失败');
                }
            }
        } else {
            $order = $orderRefund;
            $data['refund_price'] = $price;
            $data['type'] = $type;
            $id = $order['id'];

            if ($data['type'] == 1) {
                $data['refund_status'] = 2;
                $data['refund_type'] = 6;
            } else if ($data['type'] == 2) {
                $data['refund_status'] = 0;
                $data['refund_type'] = 3;
            }
            $type = $data['type'];
            //拒绝退款
            if ($type == 2) {
                $this->services->update((int)$order['id'], ['refund_status' => 0, 'refund_type' => 3]);
                return app('json')->successful('修改退款状态成功!');
            } else {
                //0元退款
                if ($order['pay_price'] == 0 && in_array($order['refund_status'], [0, 1])) {
                    $refund_price = 0;
                } else {
                    if ($order['pay_price'] == $order['refund_price']) {
                        return app('json')->fail('已退完支付金额!不能再退款了');
                    }
                    if (!$data['refund_price']) {
                        return app('json')->fail('请输入退款金额');
                    }
                    $refund_price = $data['refund_price'];
                    $data['refund_price'] = bcadd($data['refund_price'], $order['refund_price'], 2);
                    $bj = bccomp((string)$order['pay_price'], (string)$data['refund_price'], 2);
                    if ($bj < 0) {
                        return app('json')->fail('退款金额大于支付金额，请修改退款金额');
                    }
                }
                unset($data['type']);
                $refund_data['pay_price'] = $order['pay_price'];
                $refund_data['refund_price'] = $refund_price;

                //生成退款订单
                $refundOrderData['uid'] = $order['uid'];
                $refundOrderData['store_id'] = $order['store_id'];
                $refundOrderData['store_order_id'] = $id;
                $refundOrderData['refund_num'] = $order['total_num'];
                $refundOrderData['refund_type'] = $data['refund_type'];
                $refundOrderData['refund_price'] = $order['pay_price'];
                $refundOrderData['refunded_price'] = $refund_price;
                $refundOrderData['refund_reason'] = '管理员手动退款';
                $refundOrderData['order_id'] = $storeOrderCreateServices->getNewOrderId('');
                $refundOrderData['refunded_time'] = time();
                $refundOrderData['add_time'] = time();
                $cartInfos = $storeOrderCartInfoServices->getCartColunm(['oid' => $id], 'id,cart_id,cart_num,cart_info');
                foreach ($cartInfos as &$cartInfo) {
                    $cartInfo['cart_info'] = is_string($cartInfo['cart_info']) ? json_decode($cartInfo['cart_info'], true) : $cartInfo['cart_info'];
                }
                $refundOrderData['cart_info'] = json_encode(array_column($cartInfos, 'cart_info'));
                $res = $services->save($refundOrderData);


                //修改订单退款状态
                if ($services->agreeRefund((int)$res->id, $refund_data)) {
                    //主动退款清楚原本退款单
                    $services->delete(['store_order_id' => $id]);
                    $this->services->update($id, $data);
                    return app('json')->success('审核成功');
                } else {
                    $services->storeProductOrderRefundYFasle((int)$id, $refund_price);
                    return app('json')->fail('审核失败');
                }
            }
        }

    }

    /**
     * 商家同意退货退款
     * @return mixed
     */
    public function agreeRefund(Request $request, StoreOrderRefundServices $services)
    {
        [$id] = $request->getMore([
            ['id', '']
        ], true);
        $services->agreeRefundProdcut((int)$id);
        return app('json')->success('操作成功');
    }

    /**
     * 扫码获取订单信息
     * $auth 0  管理员  1  客服  2  配送员  3  用户微信扫码
     * @param Request $request
     * @param WriteOffOrderServices $writeOffOrderServices
     * @param StoreOrderCartInfoServices $orderCartInfo
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function order_verific(Request $request, WriteOffOrderServices $writeOffOrderServices, StoreOrderCartInfoServices $orderCartInfo, StoreOrderServices $orderServices)
    {
        [$code, $auth] = $request->postMore([
            ['code', ''],
            ['auth', 0],
        ], true);
        if (!$code) return app('json')->fail('Lack of write-off code');
        $uid = $request->uid();
        /** @var StoreServiceServices $storeService */
        $storeService = app()->make(StoreServiceServices::class);
        $userService = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1, 'account_status' => 1]); //客服权限是否存在
        if ($auth == 2 && !$userService) return app('json')->fail('您没有客服权限！');
        /** @var DeliveryServiceServices $deliveryService */
        $deliveryService = app()->make(DeliveryServiceServices::class);
        $deliveryUser = $deliveryService->checkoutIsService($uid); // 检查用户是否是配送员
        if ($auth == 2 && !$deliveryUser) return app('json')->fail('您不是配送员！');
        if ($auth == 3) {
            if (!$deliveryUser && !$userService) {
                return app('json')->fail('您没有任何权限！');
            } else if ($deliveryUser && !$userService) {
                $auth = 2;
            } else if (!$deliveryUser && $userService) {
                $auth = 1;
            } else {
                $auth = 0;
            }
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getOne(['bar_code' => $code]);
        if ($userInfo) {
            $userInfo = $userInfo->toArray();
            $is_order_code = 0;
        } else {
            $orderInfo = $orderServices->getOne(['verify_code' => $code, 'is_del' => 0]);
            if ($orderInfo) {
                $is_order_code = 1;
            } else {
                return app('json')->fail('暂未获取到订单信息，请先确认传入的码是否正确');
            }
        }
        $product_type = 0;
        if ($is_order_code == 1) {
            $orderInfo = [];
            try {
                $orderInfo = $writeOffOrderServices->writeoffOrderInfo($uid, $code, $auth);
            } catch (\Throwable $e) {
            }
            if ($orderInfo && isset($orderInfo['product_type'])) {
                $product_type = $orderInfo['product_type'];
            }
            $data = [$orderInfo];
        } else {
            $data = [];
            try {
                $data = $writeOffOrderServices->userUnWriteoffOrder($uid, $code, $auth, $userInfo);
            } catch (\Throwable $e) {
            }
            if (count($data) == 1) {
                $is_order_code = 1;
                $orderInfo = $data[0];
                if ($orderInfo && isset($orderInfo['product_type'])) {
                    $product_type = $orderInfo['product_type'];
                }
            }
        }
        $res = [];
        if ($data) {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $res = $orderServices->tidyOrderList($data, false);
            foreach ($res as &$orderInfo) {
                $orderInfo['image'] = '';
                if (isset($orderInfo['cart_id'][0])) {
                    $cartInfo = $orderCartInfo->getOne(['oid' => $orderInfo['id'], 'cart_id' => $orderInfo['cart_id'][0]], 'cart_info');
                    if ($cartInfo) $orderInfo['image'] = $cartInfo['cart_info']['productInfo']['image'] ?? '';
                }
            }
        }
        return app('json')->success(['data' => $res, 'is_order_code' => $is_order_code, 'product_type' => $product_type, 'auth' => $auth]);
    }

    /**
     * 订单核销记录
     * @param Request $request
     * @param StoreOrderWriteOffServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeOffRecords(Request $request, StoreOrderWriteOffServices $services, $id)
    {
        [$product_type] = $request->postMore([
            ['product_type', 0]
        ], true);
        if (!$id) return app('json')->fail('参数错误');
        return app('json')->successful($services->userOrderWriteOffRecords(['oid' => $id], $product_type));
    }


    /**
     * 获取所有配送员列表
     * @param DeliveryServiceServices $services
     * @return mixed
     */
    public function getDeliveryAll(DeliveryServiceServices $services)
    {
        $list = $services->getDeliveryList();
        return app('json')->success($list['list']);
    }

    /**
     * 获取配置信息
     * @return mixed
     */
    public function getDeliveryInfo()
    {
        $data = SystemConfigService::more(['config_export_temp_id', 'config_export_to_name', 'config_export_id', 'config_export_to_tel', 'config_export_to_address']);
        return app('json')->success([
            'express_temp_id' => $data['config_export_temp_id'] ?? '',
            'to_name' => $data['config_export_to_name'] ?? '',
            'id' => $data['config_export_id'] ?? '',
            'to_tel' => $data['config_export_to_tel'] ?? '',
            'to_add' => $data['config_export_to_address'] ?? ''
        ]);
    }

    /**
     * 获取面单信息
     * @param ServeServices $services
     * @return mixed
     */
    public function getExportTemp(Request $request, ServeServices $services)
    {
        [$com] = $request->getMore([
            ['com', ''],
        ], true);
        return app('json')->success($services->express()->temp($com));
    }

    /**
     * 物流公司
     * @param ExpressServices $services
     * @return mixed
     */
    public function getExportAll(ExpressServices $services)
    {
        return app('json')->success($services->expressList());
    }

    /**
     * 移动端订单管理退款列表
     * @param Request $request
     * @param StoreOrderRefundServices $services
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function refundOrderList(Request $request, StoreOrderRefundServices $services)
    {
        $where = $request->getMore([
            ['order_id', ''],
            ['time', ''],
            ['refundTypes', ''],
            ['apply_type', ''],
        ]);
        $data = $services->refundList($where)['list'];
        return app('json')->success($data);
    }

    /**
     * 订单详情
     * @param Request $request
     * @param $uni
     * @return mixed
     */
    public function refundOrderDetail(StoreOrderRefundServices $services, $uni)
    {
        $data = $services->refundDetail($uni);
        return app('json')->successful('ok', $data);
    }

    /**
     * 修改备注
     * @param $id
     * @return mixed
     */
    public function refundRemark(StoreOrderRefundServices $services, Request $request)
    {
        [$remark, $order_id] = $request->postMore([
            ['remark', ''],
            ['order_id', ''],
        ], true);
        if (!$remark)
            return app('json')->fail('请输入要备注的内容');
        if (!$order_id)
            return app('json')->fail('缺少参数');

        if (!$order = $services->get(['order_id' => $order_id])) {
            return app('json')->fail('修改的订单不存在!');
        }
        $order->remark = $remark;
        if ($order->save()) {
            return app('json')->success('备注成功');
        } else
            return app('json')->fail('备注失败');
    }

    /**
     * 商家管理拆单退款
     * @param Request $request
     * @param StoreOrderServices $orderServices
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function open_order_refund(Request $request, StoreOrderServices $orderServices, StoreOrderRefundServices $services, $id)
    {
        $uid = (int)$request->uid();
        if ($services->cacheHander()->has('open_mobile_' . $uid)) {
            return app('json')->fail('请勿重复操作!');
        }
        $services->cacheTag()->set('open_mobile_' . $uid, 1, 1);

        $data = $request->postMore([
            ['refund_price', 0],
            ['type', 1],
            ['is_split_order', 0],
            ['cart_ids', []]
        ]);
        if (!$id) {
            return app('json')->fail('Data does not exist!');
        }
        $data['refund_price'] = sprintf("%.2f", $data['refund_price']);
        $order = $orderServices->get($id);
        if (!$order) {
            return app('json')->fail('Data does not exist!');
        }
        if ($services->count(['store_order_id' => $id, 'refund_type' => [0, 1, 2, 4, 5], 'is_cancel' => 0, 'is_del' => 1])) {
            return app('json')->fail('请先处理售后申请');
        }
        //0元退款
        if ($order['pay_price'] == 0 && in_array($order['refund_status'], [0, 1])) {
            $refund_price = 0;
        } else {
            if ($order['pay_price'] == $order['refund_price']) {
                return app('json')->fail('已退完支付金额!不能再退款了');
            }
            if (!$data['refund_price']) {
                return app('json')->fail('请输入退款金额');
            }
            $refund_price = $data['refund_price'];
            $data['refund_price'] = bcadd($data['refund_price'], $order['refund_price'], 2);
            $bj = bccomp((string)$order['pay_price'], (string)$data['refund_price'], 2);
            if ($bj < 0) {
                return app('json')->fail('退款金额大于支付金额，请修改退款金额');
            }
        }
        if ($data['type'] == 1) {
            $data['refund_status'] = 2;
            $data['refund_type'] = 6;
        } else if ($data['type'] == 2) {
            $data['refund_status'] = 0;
            $data['refund_type'] = 3;
        }
        $type = $data['type'];
        if ($data['is_split_order']) {
            if (!$data['cart_ids']) {
                return app('json')->fail('请选择商品');
            }
            foreach ($data['cart_ids'] as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                    return app('json')->fail('请重新选择商品，或件数');
                }
            }
        }
        //拒绝退款
        if ($type == 2) {
            $orderServices->update((int)$order['id'], ['refund_status' => 0, 'refund_type' => 3]);
            return app('json')->successful('修改退款状态成功!');
        } else {
            unset($data['type']);
            $refund_data['pay_price'] = $order['pay_price'];
            $refund_data['refund_price'] = $refund_price;
            if ($order['refund_price'] > 0) {
                mt_srand();
                $refund_data['refund_id'] = $order['order_id'] . rand(100, 999);
            }
            $refundId = $services->applyRefund((int)$id, (int)$order['uid'], $order, $data['cart_ids'], 4, $refund_price, [
                'refund_reason' => '客服人员主动退款',
                'refund_explain' => '客服人员主动退款',
                'refund_img' => '',
            ]);
            $order_id = '';
            //修改订单退款状态
            if ($services->agreeRefund($refundId, $refund_data)) {
                //主动退款清楚原本退款单
                $services->delete(['store_order_id' => $id]);
                if ($data['is_split_order']) {
                    $services->update($refundId, $data);
                    $oid = $services->value(['id' => $refundId], 'store_order_id');
                    $order_id = $orderServices->value(['id' => $oid], 'order_id');
                } else {
                    $orderServices->update($id, $data);
                }
                return app('json')->success('操作成功', ['order_id' => $order_id]);
            } else {
                $services->storeProductOrderRefundYFasle((int)$id, $refund_price);
                return app('json')->fail('操作失败');
            }
        }
    }

}
