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
namespace app\controller\api\v1\order;

use app\jobs\order\OrderStatusJob;
use app\Request;
use app\services\pay\IntegralPayServices;
use app\services\pay\PayServices;
use app\services\other\ExpressServices;
use app\services\product\product\StoreProductCouponServices;
use app\services\supplier\SystemSupplierServices;
use app\services\user\UserAddressServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;
use crmeb\services\wechat\MiniProgram;
use app\services\activity\{discounts\StoreDiscountsServices,
    lottery\LuckLotteryServices,
    bargain\StoreBargainServices,
    combination\StorePinkServices
};
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\{OtherOrderServices,
    StoreCartServices,
    StoreDeliveryOrderServices,
    StoreOrderCartInfoServices,
    StoreOrderCommentServices,
    StoreOrderComputedServices,
    StoreOrderCreateServices,
    StoreOrderEconomizeServices,
    StoreOrderInvoiceServices,
    StoreOrderRefundServices,
    StoreOrderServices,
    StoreOrderStatusServices,
    StoreOrderSuccessServices,
    StoreOrderTakeServices,
    StoreOrderPromotionsServices,
    StoreOrderWriteOffServices
};
use app\services\pay\OrderPayServices;
use app\services\pay\YuePayServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\store\SystemStoreServices;
use crmeb\services\CacheService;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Log;
use think\Response;

/**
 * 订单控制器
 * Class StoreOrder
 * @package app\api\controller\order
 */
class StoreOrder
{

    /**
     * @var StoreOrderServices
     */
    #[Inject]
    protected StoreOrderServices $services;

    /**
     * @var int[]
     */
    protected array $getChennel = [
        'weixin' => 0,
        'routine' => 1,
        'weixinh5' => 2,
        'pc' => 3,
        'app' => 4
    ];

    /**
     * 地址信息
     * @var string[]
     */
    protected array $addressInfo = [
        'id' => 0,
        'real_name' => '',
        'phone' => '',
        'province' => '',
        'city' => '',
        'district' => '',
        'street' => '',
        'detail' => '',
        'longitude' => '',
        'latitude' => ''
    ];

    /**
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function checkShipping(Request $request)
    {
        [$cartId, $new] = $request->postMore(['cartId', 'new'], true);
        return app('json')->successful($this->services->checkShipping($request->uid(), $cartId, $new));
    }


    /**
     * 订单确认
     * @param Request $request
     * @param ShippingTemplatesServices $services
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function confirm(Request $request, ShippingTemplatesServices $services)
    {
        if (!$services->get(1, ['id'])) {
            return app('json')->fail('默认模板未配置，无法下单');
        }
        [$cartId, $new, $addressId, $shipping_type, $storeId, $couponId] = $request->postMore([
            'cartId',
            'new',
            ['addressId', 0],
            ['shipping_type', 1],
            ['store_id', 0],
            ['couponId', 0],
            ['delivery_type', 1],
        ], true);
        if (!is_string($cartId) || !$cartId) {
            return app('json')->fail('请提交购买的商品');
        }
        $user = $request->user()->toArray();
        return app('json')->successful($this->services->getOrderConfirmData($user, $cartId, !!$new, (int)$addressId, (int)$shipping_type, (int)$storeId, (int)$couponId));
    }

    /**
     * 计算订单金额
     * @param Request $request
     * @param $key
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function computedOrder(Request $request, StoreOrderComputedServices $computedServices, $key)
    {
        if (!$key) return app('json')->fail('参数错误!');
        $uid = $request->uid();
        if ($checkOrder = $this->services->getOne(['order_id|unique' => $key, 'uid' => $uid, 'is_del' => 0], 'id,order_id'))
            return app('json')->status('extend_order', '订单已生成', ['orderId' => $checkOrder['order_id'], 'key' => $key]);
        [$addressId, $couponId, $payType, $useIntegral, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $newcomerId, $storeIntegralId, $shipping_type, $delivery_type] = $request->postMore([
            'addressId', 'couponId', ['payType', 'yue'], ['useIntegral', 0], 'mark', ['combinationId', 0], ['pinkId', 0], ['seckill_id', 0], ['bargainId', ''], ['newcomerId', 0], ['storeIntegralId', 0],
            ['shipping_type', 1], ['delivery_type', 1],
        ], true);
        $payType = strtolower($payType);
        $cartGroup = $this->services->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) return app('json')->fail('订单已过期,请刷新当前页面!');
        $priceGroup = $computedServices->computedOrder($request->uid(), $request->user()->toArray(), $cartGroup, (int)$addressId, $payType, !!$useIntegral, (int)$couponId, (int)$shipping_type);
        if ($priceGroup)
            return app('json')->status('NONE', 'ok', $priceGroup);
        else
            return app('json')->fail('计算失败');
    }

    /**
     * 订单创建
     * @param Request $request
     * @param StoreOrderCreateServices $createServices
     * @param $key
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(Request $request, StoreOrderCreateServices $createServices, $key)
    {
        if (!$key) return app('json')->fail('参数错误!');
        $uid = (int)$request->uid();
        if ($checkOrder = $this->services->getOne(['unique' => $key, 'uid' => $uid, 'is_del' => 0], 'id,order_id'))
            return app('json')->status('extend_order', '订单已创建，请点击查看完成支付', ['orderId' => $checkOrder['order_id'], 'key' => $key]);
        [$addressId, $couponId, $payType, $useIntegral, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $storeIntegralId, $newcomerId, $from, $shipping_type, $real_name, $phone, $storeId, $news, $invoice_id, $quitUrl, $discountId, $customForm] = $request->postMore([
            [['addressId', 'd'], 0],
            [['couponId', 'd'], 0],
            ['payType', ''],
            ['useIntegral', 0],
            ['mark', ''],
            [['combinationId', 'd'], 0],
            [['pinkId', 'd'], 0],
            [['seckill_id', 'd'], 0],
            [['bargainId', 'd'], ''],
            [['storeIntegralId', 'd'], 0],//积分商品ID
            [['newcomerId', 'd'], ''],
            ['from', 'weixin'],
            [['shipping_type', 'd'], 1],
            ['real_name', ''],
            ['phone', ''],
            [['store_id', 'd'], 0],
            ['new', 0],
            [['invoice_id', 'd'], 0],
            ['quitUrl', ''],
            [['discountId', 'd'], 0],
            ['custom_form', []],
        ], true);
        $cartGroup = $this->services->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) {
            return app('json')->fail('请不重复提交或订单已过期,请刷新当前页面!');
        }
        $cartInfo = $cartGroup['cartInfo'];
        if (!$cartInfo) {
            return app('json')->fail('订单已过期或提交的商品不在送达区域,请刷新当前页面或重新选择商品下单!');
        }
        $payType = strtolower($payType);
        if ($shipping_type == 1) {
            $cartInfo = $cartGroup['cartInfo'];
            $product_type = $cartInfo[0]['productInfo']['product_type'] ?? 0;
            //普通商品 验证地址
            if ($product_type == 0 && !$addressId) {
                return app('json')->fail('请选择收货地址!');
            }
            $addressInfo = ($cartGroup['addr'] ?? []) ?: $this->addressInfo;
            if ($addressId && (!$addressInfo || !isset($addressInfo['id']) || $addressInfo['id'] != $addressId)) {
                /** @var UserAddressServices $addressServices */
                $addressServices = app()->make(UserAddressServices::class);
                if (!$addressInfo = $addressServices->getOne(['uid' => $uid, 'id' => $addressId, 'is_del' => 0]))
                    return app('json')->fail('地址选择有误!');
                $addressInfo = $addressInfo->toArray();
            }
        } else {
            if (!$real_name || !$phone) {
                return app('json')->fail('请填写姓名和电话');
            }
            $addressInfo = $this->addressInfo;
            $addressInfo['real_name'] = $real_name;
            $addressInfo['phone'] = $phone;
        }
        //下单前砍价验证
        if ($bargainId) {
            /** @var StoreBargainServices $bargainServices */
            $bargainServices = app()->make(StoreBargainServices::class);
            $bargainServices->checkBargainUser((int)$bargainId, $uid);
        }
        //下单前发票验证
        if ($invoice_id) {
            /** @var UserInvoiceServices $userInvoiceServices */
            $userInvoiceServices = app()->make(UserInvoiceServices::class);
            $userInvoiceServices->checkInvoice((int)$invoice_id, $uid);
        }
        if ($pinkId) {
            $pinkId = (int)$pinkId;
            /** @var StorePinkServices $pinkServices */
            $pinkServices = app()->make(StorePinkServices::class);
            if ($pinkServices->isPink($pinkId, $uid))
                return app('json')->status('ORDER_EXIST', '订单生成失败，你已经在该团内不能再参加了', ['orderId' => $this->services->getStoreIdPink($pinkId, $uid)]);
            if ($this->services->getIsOrderPink($pinkId, $uid))
                return app('json')->status('ORDER_EXIST', '订单生成失败，你已经参加该团了，请先支付订单', ['orderId' => $this->services->getStoreIdPink($pinkId, $uid)]);
            if (!CacheService::checkStock(md5($pinkId), 1, 3) || !CacheService::popStock(md5($pinkId), 1, 3)) {
                return app('json')->fail('该团人员已满');
            }
        }
//        if ($from != 'pc') {
//            if (!$this->services->checkPaytype($payType)) {
//                return app('json')->fail('暂不支持该支付方式，请刷新页面或者联系管理员');
//            }
//        }
        $isChannel = $this->getChennel[$from] ?? ($request->isApp() ? 4 : 1);
        if ($seckill_id || $combinationId || $discountId || $bargainId || $storeIntegralId) {
            //套餐限量库
            if ($discountId) {
                /** @var StoreDiscountsServices $discountService */
                $discountService = app()->make(StoreDiscountsServices::class);
                $discounts = $discountService->get((int)$discountId, ['is_limit']);
                if (!$discounts) {
                    return app('json')->fail('套餐商品未找到！');
                }
                //套餐限量
                if ($discounts['is_limit'] && !CacheService::popStock(md5($discountId), 1, 5)) {
                    return app('json')->fail('您购买的套餐不足');
                }
            }
            foreach ($cartInfo as $item) {
                if (!isset($item['product_attr_unique']) || !$item['product_attr_unique']) continue;
                $type = $item['type'];
                if (in_array($type, [1, 2, 3, 4]) && (!CacheService::checkStock($item['product_attr_unique'], (int)$item['cart_num'], $type) || !CacheService::popStock($item['product_attr_unique'], (int)$item['cart_num'], $type))) {
                    return app('json')->fail('您购买的商品库存已不足' . $item['cart_num'] . $item['productInfo']['unit_name']);
                }
            }
        }
        try {
            $msg = '';
            $order = $createServices->createOrder($uid, $key, $cartGroup, (int)$addressId, $payType, $addressInfo, $request->user()->toArray(), !!$useIntegral, $couponId, $mark, $pinkId, $isChannel, $shipping_type, $storeId, !!$news, $customForm, (int)$invoice_id, $from);
        } catch (\Throwable $e) {
            $order = false;
            $msg = $e->getMessage();
            \think\facade\Log::error('订单生成失败，原因：' . $msg . $e->getFile() . $e->getLine());
        }
        if ($order === false) {
            if ($seckill_id || $combinationId || $discountId || $bargainId || $storeIntegralId) {
                //回退套餐限量库
                if ($discountId) CacheService::setStock(md5($discountId), 1, 5, false);
                foreach ($cartInfo as $item) {
                    if (!isset($item['product_attr_unique']) || !$item['product_attr_unique']) continue;
                    $type = $item['type'];
                    if (in_array($type, [1, 2, 3, 4])) CacheService::setStock($item['product_attr_unique'], (int)$item['cart_num'], $type, false);
                }
            }
            return app('json')->fail($msg ?: '订单生成失败');
        }
        $orderId = $order['order_id'];
        return app('json')->status('success', '订单创建成功', ['order_id' => $orderId, 'key' => $key]);
    }

    /**
     * 订单 再次下单
     * @param Request $request
     * @return mixed
     */
    public function again(Request $request, StoreCartServices $services)
    {
        [$uni] = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order) return app('json')->fail('订单不存在!');
        if (in_array($order['type'], [1, 2, 3, 4, 5])) {
            $msg = match ($order['type']) {
                1 => '秒杀',
                2 => '砍价',
                3 => '拼团',
                4 => '积分',
                5 => '套餐',
                default => '',
            };
            return app('json')->fail($msg . '商品不能再来一单，请在' . $msg . '商品内自行下单!');
        }
        $order = $this->services->tidyOrder($order, true);
        $cateIds = [];
        foreach ($order['cartInfo'] as $v) {
            if ($v['type'] == 0) {
                [$cartId, $cartNum] = $services->setCart($request->uid(), (int)$v['product_id'], (int)$v['cart_num'], isset($v['productInfo']['attrInfo']['unique']) ? $v['productInfo']['attrInfo']['unique'] : '');
                $cateIds[] = $cartId;
            }
        }
        if (!$cateIds) return app('json')->fail('再来一单失败，请重新下单!');
        return app('json')->successful('ok', ['cateId' => implode(',', $cateIds)]);
    }

    /**
     * 订单收银台信息
     * @param Request $request
     * @param StoreOrderCreateServices $createServices
     * @param $orderId
     * @param $type
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cashier(Request $request, StoreOrderCreateServices $createServices, $orderId, $type = 'order')
    {
        if (!$orderId) {
            return app('json')->fail('参数错误');
        }
        return app('json')->success($createServices->getCashierInfo((int)$request->uid(), $orderId, $type));
    }


    /**
     * 订单支付
     * @param Request $request
     * @param StorePinkServices $services
     * @param OrderPayServices $payServices
     * @param YuePayServices $yuePayServices
     * @return mixed
     */
    public function pay(Request $request, StorePinkServices $services, OrderPayServices $payServices, YuePayServices $yuePayServices)
    {
        [$uni, $paytype, $from, $quitUrl] = $request->postMore([
            ['uni', ''],
            ['paytype', 'weixin'],
            ['from', 'weixin'],
            ['quitUrl', '']
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $uid = (int)$request->uid();
        $order = $this->services->getUserOrderDetail($uni, $uid);
        if (!$order)
            return app('json')->fail('订单不存在!');
        if ($order['paid'])
            return app('json')->fail('该订单已支付!');
        if ($order['pink_id'] && $services->isPinkStatus($order['pink_id'])) {
            return app('json')->fail('该订单已失效!');
        }
        $order = $order->toArray();
        $isChannel = $this->getChennel[$from] ?? ($request->isApp() ? 4 : 1);
        $updateData = ['is_channel' => $isChannel];
        //只要重新支付就更新订单号
        if (in_array($paytype, [PayServices::ALIAPY_PAY, PayServices::WEIXIN_PAY])) {
            mt_srand();
            $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
//            if (sys_config('pay_routine_open', 0)) {
//                /** @var StoreOrderCreateServices $orderCreateServices */
//                $orderCreateServices = app()->make(StoreOrderCreateServices::class);
//                $order['order_id'] = $orderCreateServices->getNewOrderId();
//				$updateData['order_id'] = $order['order_id'];
//            }
        }
        $this->services->update($order['id'], $updateData, 'id');
        //积分兑换订单
        if ($order['type'] == 4 && isset($order['pay_integral']) && $order['pay_integral']) {
            /** @var IntegralPayServices $integralPayServices */
            $integralPayServices = app()->make(IntegralPayServices::class);
            $integralPayServices->checkIntegralPay($uid, $order);
        }

        $order['pay_type'] = $paytype; //重新支付选择支付方式
        //支付金额为0
        if (bcsub((string)$order['pay_price'], '0', 2) <= 0) {
            /** @var StoreOrderSuccessServices $success */
            $success = app()->make(StoreOrderSuccessServices::class);
            $payPriceStatus = $success->zeroYuanPayment($order, $uid, $paytype);
            if ($payPriceStatus)//0元支付成功
                return app('json')->status('success', '支付成功');
            else
                return app('json')->status('pay_error');
        } else {
            switch ($order['pay_type']) {
                case PayServices::WEIXIN_PAY:
                    $jsConfig = $payServices->orderPay($order, $from);
                    if ($from == 'weixinh5') {
                        return app('json')->status('wechat_h5_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                    } elseif ($from == 'weixin' || $from == 'routine') {
                        return app('json')->status('wechat_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                    } elseif ($from == 'pc') {
                        return app('json')->status('wechat_pc_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                    }
                    break;
                case PayServices::ALIAPY_PAY:
                    if (!$quitUrl && $from != 'routine') {
                        return app('json')->fail('请传入支付宝支付回调URL');
                    }
                    $isCode = $from == 'routine' || $from == 'pc';
                    $jsConfig = $payServices->alipayOrder($order, $quitUrl, $isCode);
                    if ($isCode && !($jsConfig->invalid ?? false)) $jsConfig->invalid = time() + 60;
                    $payKey = md5($order['order_id']);
                    CacheService::set($payKey, ['order_id' => $order['order_id'], 'other_pay_type' => false], 300);
                    return app('json')->status(PayServices::ALIAPY_PAY . '_pay', '订单创建成功', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id'], 'pay_key' => $payKey]);
                    break;
                case PayServices::YUE_PAY:
                    $pay = $yuePayServices->yueOrderPay($order, $uid);
                    if ($pay['status'] === true)
                        return app('json')->status('success', '余额支付成功');
                    else {
                        if (is_array($pay))
                            return app('json')->status($pay['status'], $pay['msg']);
                        else
                            return app('json')->status('pay_error', $pay);
                    }
                    break;
                case PayServices::OFFLINE_PAY:
                    if ($this->services->setOrderTypePayOffline($order))
                        return app('json')->status('success', '订单创建成功');
                    else
                        return app('json')->status('success', '支付失败');
                    break;
            }
            return app('json')->fail('支付方式错误');
        }
    }

    /**
     * 支付宝单独支付
     * @param OrderPayServices $payServices
     * @param OtherOrderServices $services
     * @param string $key
     * @param string $quitUrl
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function aliPay(OrderPayServices $payServices, OtherOrderServices $services, string $key, string $quitUrl)
    {
        if (!$key || !($orderCache = CacheService::get($key))) {
            return app('json')->fail('该订单无法支付');
        }
        if (!isset($orderCache['order_id'])) {
            return app('json')->fail('该订单无法支付');
        }
        $order_id = $orderCache['order_id'];
        if (strpos($orderCache['order_id'], '_')) {
            $orderArr = explode('_', $orderCache['order_id']);
            if (count($orderArr) == 2) {
                $order_id = $orderArr[1] ?? $order_id;
            }
        }
        $payType = isset($orderCache['other_pay_type']) && $orderCache['other_pay_type'] == true;
        if ($payType) {
            $orderInfo = $services->getOne(['order_id' => $order_id, 'is_del' => 0, 'paid' => 0]);
        } else {
            $orderInfo = $this->services->get(['order_id' => $order_id, 'paid' => 0, 'is_del' => 0]);
        }

        if (!$orderInfo) {
            return app('json')->fail('订单支付状态有误，无法进行支付');
        }
        if (!$quitUrl) {
            return app('json')->fail('请传入支付宝支付回调URL');
        }
        $payInfo = $payServices->alipayOrder($orderInfo->toArray(), $quitUrl);
        return app('json')->success(['pay_content' => $payInfo]);
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['type', ''],//订单类型
            ['status', ''],//订单状态
            [['search', 's'], '', '', 'real_name'],//筛选关键词
            ['refund_type', '', '', 'refundTypes'],
        ]);
        $where['uid'] = $request->uid();
        if ($where['status'] !== '') {
            $where['is_del'] = 0;
        } else {
            $where['is_del'] = [0, 1];
        }
        $where['is_system_del'] = 0;
        if ($where['status'] === '') {
            $where['pid'] = 0;
        } elseif (in_array($where['status'], [-1, -2, -3])) {
            $where['not_pid'] = 1;
        } elseif (in_array($where['status'], [0, 1, 2, 3, 4])) {
            $where['pid'] = 0;
        }

        $field = ['id', 'type', 'pid', 'order_id', 'uid', 'spread_uid', 'pink_id', 'store_id', 'supplier_id', 'shipping_type', 'delivery_type', 'paid', 'pay_type', 'pay_price', 'pay_integral', 'total_num', 'add_time', 'pay_time', 'status', 'refund_status', 'is_del'];
        $list = $this->services->getOrderApiList($where, $field, ['refund' => function ($query) {
            $query->whereIn('refund_type', [0, 1, 2, 4, 5])->where('is_cancel', 0)->where('is_del', 0)->field('id,store_order_id,refund_num');
        }]);
        return app('json')->successful($list);
    }

    /**
     * 订单详情
     * @param Request $request
     * @param StoreOrderEconomizeServices $services
     * @param StoreOrderPromotionsServices $storeOrderPromotiosServices
     * @param $uni
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(Request $request, StoreOrderEconomizeServices $services, StoreOrderPromotionsServices $storeOrderPromotiosServices, $uni)
    {
        if (!strlen(trim($uni))) return app('json')->fail('参数错误');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid(), ['refund' => function ($query) {
            $query->field('id,order_id,store_order_id,refunded_price,refund_explain');
        }]);
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['pid'] == -1) return app('json')->make(403, '订单已被拆分为多个订单，请查看订单列表');
        $order = $order->toArray();
        $order['split'] = [];
        //门店是否开启 ｜｜ 门店自提是否开启
        if (!sys_config('store_func_status', 1) || !sys_config('store_self_mention')) {
            //关闭门店自提后 订单隐藏门店信息
            $order['shipping_type'] = 1;
        }
        if ($order['verify_code']) {
            $verify_code = $order['verify_code'];
            $verify[] = substr($verify_code, 0, 4);
            $verify[] = substr($verify_code, 4, 4);
            $verify[] = substr($verify_code, 8);
            $order['_verify_code'] = implode(' ', $verify);
        }
        //收银台订单 用户无信息 手机号
        if ($order['shipping_type'] == 4 && $order['uid'] && !$order['real_name']) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getUserCacheInfo((int)$order['uid']);
            $order['real_name'] = $userInfo['nickname'];
            $order['user_phone'] = $userInfo['phone'];
        }
        $order['add_time_y'] = date('Y-m-d', $order['add_time']);
        $order['add_time_h'] = date('H:i:s', $order['add_time']);
        $order['system_store'] = false;
        if (!$order['store_id'] && $order['shipping_type'] == 2) {
            $order['store_id'] = $this->services->value(['pid' => $order['id']], 'store_id');
        }
        if ($order['store_id']) {
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $system_store = $storeServices->getStoreDisposeCache($order['store_id']);
            if ($system_store) {
                $system_store['image'] = $system_store['image'] ?: sys_config('site_logo', '');
                $order['system_store'] = $system_store;
            }
        }
        $order['mapKey'] = sys_config('tengxun_map_key');
        $order['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $order['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $order['ali_pay_status'] = (bool)sys_config('ali_pay_status');//支付包支付 1 开启 0 关闭

        $orderData = $this->services->tidyOrder($order, true, true);
        //核算优惠金额
        $vipTruePrice = 0;
        $refund_num = 0;
        foreach ($orderData['cartInfo'] ?? [] as $key => &$cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
            $refund_num = bcadd((string)$refund_num, (string)$cart['refund_num'], 0);
        }
        $orderData['vip_true_price'] = $vipTruePrice;
        $orderData['total_price'] = floatval(bcsub((string)$orderData['total_price'], (string)$vipTruePrice, 2));
        //优惠活动优惠详情
        $orderData['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$order['id']);
        //同步查询订单商品为查询到 查询缓存信息
        if (!$orderData['cartInfo']) {
            $cartGroup = $this->services->getCacheOrderInfo((int)$order['uid'], $order['unique']);
            $orderData['cartInfo'] = $cartGroup['cartInfo'] ?? [];
        }

        $economize = $services->get(['order_id' => $order['order_id']], ['postage_price', 'member_price']);
        if ($economize) {
            $orderData['postage_price'] = $economize['postage_price'];
            $orderData['member_price'] = $economize['member_price'];
        } else {
            $orderData['postage_price'] = 0;
            $orderData['member_price'] = 0;
        }
        $orderData['routine_contact_type'] = sys_config('routine_contact_type', 0);
        $storeOrderInvoiceServices = app()->make(StoreOrderInvoiceServices::class);
        $invoice = $storeOrderInvoiceServices->search(['order_id' => $order['id']])->order('id DESC')->find();
        $orderData['invoice'] = $invoice ? $invoice->toArray() : [];
        /** @var UserInvoiceServices $userInvoice */
        $userInvoice = app()->make(UserInvoiceServices::class);
        $invoice_func = $userInvoice->invoiceFuncStatus();
        $orderData['invoice_func'] = $invoice_func['invoice_func'];
        $orderData['special_invoice'] = $invoice_func['special_invoice'];
        $orderData['refund_cartInfo'] = [];
        $orderData['refund_total_num'] = $orderData['total_num'];
        $orderData['refund_pay_price'] = $orderData['pay_price'];
        $orderData['is_apply_refund'] = !($refund_num >= $orderData['total_num']) && $this->services->isRefundAvailable((int)$order['id']);
        $orderData['is_batch_refund'] = count($orderData['cartInfo']) > 1;
        $orderData['pinkStatus'] = null;
        if ($orderData['type'] == 3) {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $orderData['pinkStatus'] = $pinkService->value(['order_id' => $orderData['order_id']], 'status');
        }

        /** @var StoreOrderStatusServices $statusServices */
        $statusServices = app()->make(StoreOrderStatusServices::class);
        $log = $statusServices->getColumn(['oid' => $order['id']], 'change_time', 'change_type');
        if (isset($log['delivery'])) {
            $delivery = date('Y-m-d', $log['delivery']);
        } elseif (isset($log['delivery_goods'])) {
            $delivery = date('Y-m-d', $log['delivery_goods']);
        } elseif (isset($log['delivery_fictitious'])) {
            $delivery = date('Y-m-d', $log['delivery_fictitious']);
        } else {
            $delivery = '';
        }
        $orderData['order_log'] = [
            'create' => isset($log['cache_key_create_order']) ? date('Y-m-d', $log['cache_key_create_order']) : '',
            'pay' => isset($log['pay_success']) ? date('Y-m-d', $log['pay_success']) : '',
            'delivery' => $delivery,
            'take' => isset($log['take_delivery']) ? date('Y-m-d', $log['take_delivery']) : '',
            'complete' => isset($log['check_order_over']) ? date('Y-m-d', $log['check_order_over']) : '',
        ];
        if ($orderData['give_coupon']) {
            $couponIds = is_string($orderData['give_coupon']) ? explode(',', $orderData['give_coupon']) : $orderData['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderData['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        $orderData['write_off'] = $orderData['write_times'] = 0;
        $orderData['write_day'] = '';
        $cart = $orderData['cartInfo'][0] ?? [];
        if ($orderData['product_type'] == 4 && $cart) {//次卡商品
            $orderData['write_off'] = $cart['write_off'] ?? max(bcsub((string)$cart['write_times'], (string)$cart['write_surplus_times'], 0), 0);
            $orderData['write_times'] = $cart['write_times'] ?? 0;
            $start = $cart['write_start'] ?? 0;
            $end = $cart['write_end'] ?? 0;
            if (!$start && !$end) {
                $orderData['write_day'] = '不限时';
            } else {
                $orderData['write_day'] = ($start ? date('Y-m-d', $start) : '') . '/' . ($end ? date('Y-m-d', $end) : '');
            }
        }
        $orderData['delivery_info'] = [];
        //UU或者达达
        if ($orderData['delivery_type'] == 'send' && $orderData['shipping_type'] == 2) {
            $storeDeliveryOrderServices = app()->make(StoreDeliveryOrderServices::class);
            $orderData['delivery_info'] = $storeDeliveryOrderServices->orderDetail($order['id']);
        }
        // 判断是否开启小程序订单管理
        $orderData['order_shipping_open'] = false;
        if (sys_config('order_shipping_open', 0) && MiniProgram::isManaged() && $order['is_channel'] == 1 && $order['pay_type'] == 'weixin') {
            // 判断是否存在子未收货子订单
            if ($order['pid'] > 0) {
                if ($this->services->checkSubOrderNotTake((int)$order['pid'], (int)$order['id'])) {
                    $orderData['order_shipping_open'] = true;
                }
            } else {
                $orderData['order_shipping_open'] = true;
            }

        }
        return app('json')->successful('ok', $orderData);
    }

    /**
     * 获取下单奖励
     * @param Request $request
     * @param StoreProductCouponServices $storeProductCouponServices
     * @param $orderId
     * @return \think\Response
     */
    public function getOrderPrize(Request $request, StoreProductCouponServices $storeProductCouponServices, $orderId)
    {
        $uid = (int)$request->uid();
        if (!$orderId) {
            return app('json')->fail('参数错误');
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->getOne(['order_id' => $orderId]);
        if (!$order || $order['uid'] != $uid) {
            throw new ValidateException('订单不存在');
        }
        $list = $storeProductCouponServices->getOrderProductCoupon($uid, $orderId, $order);
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartList = $storeOrderCartInfoServices->getCartInfoList(['is_gift' => 1, 'oid' => $order['id']], ['id', 'cart_info']);
        $gift = [];
        if ($cartList) {
            foreach ($cartList as $item) {
                $cartInfo = is_string($item['cart_info']) ? json_decode($item['cart_info']) : $item['cart_info'];
                $gift[] = ['product_id' => $cartInfo['productInfo']['id'] ?? 0, 'store_name' => $cartInfo['productInfo']['store_name'] ?? 0];
            }
        }
        return app('json')->success(['coupons' => $list, 'integral' => 0, 'exp' => 0, 'gift' => $gift]);
    }

    /**
     *    配送订单详情
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function deliveryOrderDetail(Request $request, $id)
    {
        if (!strlen(trim($id))) return app('json')->fail('参数错误');
        $uid = (int)$request->uid();
        $order = $this->services->getOne(['id' => $id, 'uid' => $uid, 'is_del' => 0], 'id,pid,order_id,status,delivery_type,delivery_id,delivery_name', ['deliveryOrder' => function ($query) {
            $query->field('id,oid,station_type,order_id,user_name,receiver_phone,to_address,delivery_no,finish_code,distance,fee,status');
        }]);
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        /** @var StoreOrderStatusServices $statusServices */
        $statusServices = app()->make(StoreOrderStatusServices::class);
        $log = $statusServices->getColumn(['oid' => $order['id']], 'change_time', 'change_type');
        /** @var StoreDeliveryOrderServices $deliverOrderSerives */
        $deliverOrderSerives = app()->make(StoreDeliveryOrderServices::class);
        $message = $deliverOrderSerives->getStatusMsg();
        $city_delivery = [];
        foreach ($log as $key => $value) {
            if (strpos($key, 'city_delivery') !== false) {
                $key = str_replace('city_delivery_', '', $key);
                $city_delivery[] = [
                    'time' => date('Y-m-d H:i:s', $value),
                    'label' => $message[$key] ?? '配送中',
                ];
            }
        }
        $order['order_log'] = [
            'create' => isset($log['cache_key_create_order']) ? date('Y-m-d', $log['cache_key_create_order']) : '',
            'pay' => isset($log['pay_success']) ? date('Y-m-d', $log['pay_success']) : '',
            'city_delivery' => $city_delivery,
            'take' => isset($log['take_delivery']) ? date('Y-m-d', $log['take_delivery']) : '',
            'complete' => isset($log['check_order_over']) ? date('Y-m-d', $log['check_order_over']) : '',
        ];
        return app('json')->successful('ok', $order);
    }

    /**
     * 订单删除
     * @param Request $request
     * @return mixed
     */
    public function del(Request $request)
    {
        [$uni] = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $res = $this->services->removeOrder($uni, (int)$request->uid(), 2);
        if ($res) {
            return app('json')->successful();
        } else {
            return app('json')->fail('删除失败');
        }
    }

    /**
     * 订单收货
     * @param Request $request
     * @return mixed
     */
    public function take(Request $request, StoreOrderTakeServices $services, StoreCouponIssueServices $issueServices)
    {
        [$uni] = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $services->takeOrder($uni, (int)$request->uid());
        if ($order) {
            return app('json')->successful('收货成功');
        } else
            return app('json')->fail('收货失败');
    }


    /**
     * 订单 查看物流
     * @param Request $request
     * @param StoreOrderCartInfoServices $services
     * @param ExpressServices $expressServices
     * @param $uni
     * @param string $type
     * @return mixed
     */
    public function express(Request $request, StoreOrderCartInfoServices $services, ExpressServices $expressServices, $uni, $type = '')
    {
        if (!$uni) return app('json')->fail('参数错误');
        if ($type == 'refund') {
            /** @var StoreOrderRefundServices $refundService */
            $refundService = app()->make(StoreOrderRefundServices::class);
            $order = $refundService->refundDetail($uni);
            $express = $order['refund_express'] ?? '';
            $cacheName = $uni . $express;
            $cartInfo = $order['cartInfo'] ?? [];
        } else {
            $order = $this->services->getUserOrderDetail($uni);
            if (!$order) return app('json')->fail('查询订单不存在!');
            if ($order['delivery_type'] != 'express') return app('json')->fail('该订单不是快递发货，无法查询物流信息');
            if (!$order['delivery_id']) return app('json')->fail('该订单不存在快递单号!');
            $express = $order['delivery_id'] ?? '';
            $cacheName = $uni . $express;
            $cartInfo = $services->getCartColunm(['oid' => $order['id']], 'cart_info', 'unique');
        }
        $info = [];
        $cartNew = [];
        foreach ($cartInfo as $k => $cart) {
            $cart = is_string($cart) ? json_decode($cart, true) : $cart;
            $cartNew['cart_num'] = $cart['cart_num'];
            $cartNew['truePrice'] = $order['type'] == 8 ? 0 : $cart['truePrice'];
            $cartNew['productInfo']['image'] = $cart['productInfo']['image'] ?? '';
            $cartNew['productInfo']['store_name'] = $cart['productInfo']['store_name'] ?? $cart['productInfo']['title'] ?? '';
            $cartNew['productInfo']['unit_name'] = $cart['productInfo']['unit_name'] ?? '';
            $info[] = $cartNew;
            unset($cart);
        }
        if ($order['store_id']) {
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $storeInfo = $storeServices->get($order['store_id']);
            $refund_address = ($storeInfo['address'] ?? '') . ($storeInfo['detailed_address'] ?? '');
        } elseif ($order['supplier_id']) {
            /** @var SystemSupplierServices $supplierServices */
            $supplierServices = app()->make(SystemSupplierServices::class);
            $supplierIno = $supplierServices->get($order['supplier_id']);
            $refund_address = $supplierIno['detailed_address'] ?? '';
        } else {
            $refund_address = sys_config('refund_address', '');
        }

        $orderInfo['send_address'] = $refund_address;
        $orderInfo['send_city'] = $services->addressHandle($refund_address)['city'] ?? '';
        $orderInfo['delivery_id'] = $express;
        $orderInfo['delivery_name'] = $type == 'refund' ? ($order['refund_express_name'] ?? '用户退回') : $order['delivery_name'] ?? '';
        $orderInfo['delivery_code'] = $type == 'refund' ? '' : $order['delivery_code'] ?? '';
        $orderInfo['delivery_type'] = $order['delivery_type'] ?? 1;
        $orderInfo['user_address'] = $order['user_address'] ?? '';
        $address = explode(' ', $orderInfo['user_address']);
        $orderInfo['user_city'] = isset($address[0]) && in_array($address[0], ['北京', '上海', '天津', '重庆', '香港', '澳门', '台湾']) ? $address[0] : ($address[1] ?? '');
        $orderInfo['user_mark'] = $order['mark'] ?? '';
        $orderInfo['user_phone'] = $order['user_phone'] ?? $order['refund_phone'] ?? '';
        $orderInfo['user_name'] = $order['real_name'] ?? '';
        $orderInfo['cartInfo'] = $info;
        $delivery_id = $orderInfo['delivery_id'];
        if (!str_contains($orderInfo['delivery_id'], ':')
            && $orderInfo['user_phone']
            && ($orderInfo['delivery_code'] == 'shunfengkuaiyun' || $orderInfo['delivery_code'] == 'zhongtong'
                || ($type == 'refund' && ($order['express_name'] ?? '') == '顺丰快运'))) {
            $delivery_id = $orderInfo['delivery_id'] . ':' . substr($orderInfo['user_phone'], -4);
        }
        return app('json')->successful([
            'order' => $orderInfo,
            'express' => $delivery_id ? $expressServices->query($cacheName, $delivery_id) : []
        ]);
    }


    /**
     * 订单评价
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function comment(Request $request, StoreOrderCartInfoServices $cartInfoServices, StoreProductReplyServices $replyServices, StoreOrderCommentServices $storeOrderCommentServices)
    {

        $group = $request->postMore([
            ['unique', ''], ['comment', ''], ['pics', ''], ['reply_score', 3], ['product_score', 5], ['service_score', 5], ['delivery_score', 5], ['is_sync', 0]
        ]);
        $unique = $group['unique'];
        unset($group['unique']);
        if (!$unique) return app('json')->fail('参数错误!');
        $cartInfo = $cartInfoServices->getOne(['unique' => $unique], 'id,oid,uid');
        $uid = (int)$request->uid();
        if (!$cartInfo || $uid != $cartInfo['uid']) return app('json')->fail('评价商品不存在!');
        $oid = (int)$cartInfo['oid'];
        if ($replyServices->be(['oid' => $oid, 'unique' => $unique])) return app('json')->fail('该商品已评价!');

        //保存评价
        $storeOrderCommentServices->comment($oid, $uid, $group, $unique);

        //缓存抽奖次数
        /** @var LuckLotteryServices $luckLotteryServices */
        $luckLotteryServices = app()->make(LuckLotteryServices::class);
        $luckLotteryServices->setCacheLotteryNum($uid, 'comment');

        $lottery = $luckLotteryServices->getFactorLottery(4);
        if (!$lottery) {
            return app('json')->successful(['to_lottery' => false]);
        }
        $lottery = $lottery->toArray();
        try {
            $luckLotteryServices->checkoutUserAuth($uid, (int)$lottery['id'], [], $lottery);
            $lottery_num = $luckLotteryServices->getLotteryNum($uid, (int)$lottery['id'], [], $lottery);
            if ($lottery_num > 0) return app('json')->successful(['to_lottery' => true]);
        } catch (\Exception $e) {
            return app('json')->successful(['to_lottery' => false]);
        }
    }

    /**
     * 订单统计数据
     * @param Request $request
     * @return mixed
     */
    public function data(Request $request)
    {
        $where = $request->getMore([
            ['type', ''],//订单类型
            ['status', ''],//订单状态
            [['search', 's'], '', '', 'real_name'],//筛选关键词
            ['refund_type', '', '', 'refundTypes'],
        ]);
        return app('json')->successful($this->services->getOrderData((int)$request->uid(), $where));
    }

    /**
     * 订单退款理由
     * @return mixed
     */
    public function refund_reason()
    {
        $reason = sys_config('stor_reason') ?: [];//退款理由
        $reason = str_replace("\r\n", "\n", $reason);//防止不兼容
        $reason = explode("\n", $reason);
        return app('json')->successful($reason);
    }

    /**
     * 获取退货商品列表
     * @param StoreOrderCartInfoServices $services
     * @param $id
     * @return mixed
     */
    public function refundCartInfo(Request $request, StoreOrderCartInfoServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        [$cart_ids] = $request->postMore([
            ['cart_ids', []]
        ], true);
        $list = $services->getRefundCartList((int)$id);
        if ($cart_ids) {
            foreach ($cart_ids as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num'] || $cart['cart_num'] <= 0) {
                    return app('json')->fail('请重新选择退款商品，或件数');
                }
            }
            $cart_ids = array_combine(array_column($cart_ids, 'cart_id'), $cart_ids);
            foreach ($list as &$item) {
                if (isset($cart_ids[$item['cart_id']]['cart_num'])) $item['cart_num'] = $cart_ids[$item['cart_id']]['cart_num'];
            }
        }
        return app('json')->success($list);
    }

    /**
     * 获取退货商品列表
     * @param StoreOrderCartInfoServices $services
     * @param $id
     * @return mixed
     */
    public function refundCartInfoList(Request $request)
    {
        [$cart_ids, $id] = $request->postMore([
            ['cart_ids', []],
            ['id', 0],
        ], true);
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        return app('json')->success($this->services->refundCartInfoList((array)$cart_ids, (int)$id));
    }

    /**
     * 用户申请退款
     * @param Request $request
     * @return mixed
     */
    public function applyRefund(Request $request, StoreOrderRefundServices $services, StoreOrderServices $storeOrderServices, $id)
    {

        $uid = (int)$request->uid();
        if ($services->cacheHander()->has((string)$uid)) {
            return app('json')->fail('请勿重复操作!');
        }
        $services->cacheTag()->set((string)$uid, 1, 1);

        if (!$id) {
            return app('json')->fail('缺少参数!');
        }
        $data = $request->postMore([
            ['text', ''],
            ['refund_reason_wap_img', ''],
            ['refund_reason_wap_explain', ''],
            ['refund_type', 1],
            ['refund_price', 0.00],
            ['cart_ids', []]
        ]);
        if ($data['text'] == '') return app('json')->fail('参数错误!');
        if ($data['cart_ids']) {
            foreach ($data['cart_ids'] as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                    return app('json')->fail('请重新选择退款商品，或件数');
                }
            }
        }

        $order = $storeOrderServices->get($id);

        if (!$order || $uid != $order['uid']) {
            return app('json')->fail('订单不存在!');
        }
        if (!$order['paid']) {
            return app('json')->fail('请先完成支付!');
        }

        if ($order['refund_status'] == 2) {
            return app('json')->fail('订单已完成退款，请勿重复申请!');
        }

        $refundData = [
            'refund_reason' => $data['text'],
            'refund_explain' => $data['refund_reason_wap_explain'],
            'refund_img' => json_encode($data['refund_reason_wap_img'] != '' ? explode(',', $data['refund_reason_wap_img']) : []),
        ];
        $res = $services->applyRefund((int)$id, $uid, $order, $data['cart_ids'], (int)$data['refund_type'], (float)$data['refund_price'], $refundData);
        if ($res) {
            return app('json')->successful('提交申请成功');
        } else
            return app('json')->fail('提交失败');
    }

    /**
     * 用户申请退款
     * @param Request $request
     * @return mixed
     */
    public function refund_verify(Request $request, StoreOrderRefundServices $services)
    {
        $data = $request->postMore([
            ['text', ''],
            ['refund_reason_wap_img', ''],
            ['refund_reason_wap_explain', ''],
            ['uni', ''],
            ['refund_type', 1],
            ['refund_price', 0.00],
            ['cart_ids', []]
        ]);
        $uni = $data['uni'];
        unset($data['uni']);
        if (!$uni || $data['text'] == '') return app('json')->fail('参数错误!');

        $refundData = [
            'refund_reason' => $data['text'],
            'refund_explain' => $data['refund_reason_wap_explain'],
            'refund_img' => json_encode($data['refund_reason_wap_img'] != '' ? explode(',', $data['refund_reason_wap_img']) : []),
        ];
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order) {
            return app('json')->fail('订单不存在!');
        }
        if (!$order['paid']) {
            return app('json')->fail('请先完成支付!');
        }
        $uid = (int)$request->uid();
        $res = $services->applyRefund((int)$order['id'], $uid, $order, $data['cart_ids'], (int)$data['refund_type'], (float)$data['refund_price'], $refundData);
        if ($res)
            return app('json')->successful('提交申请成功');
        else
            return app('json')->fail('提交失败');
    }

    /**
     * 用户退货提交快递单号
     * @param Request $request
     * @param StoreOrderRefundServices $services
     * @return mixed
     */
    public function refund_express(Request $request, StoreOrderRefundServices $services)
    {
        $data = $request->postMore([
            ['id', ''],
            ['refund_express', ''],
            ['refund_phone', ''],
            ['refund_express_name', ''],
            ['refund_img', '', '', 'refund_goods_img'],
            ['refund_explain', '', '', 'refund_goods_explain'],
        ]);
        if ($data['id'] == '') return app('json')->fail('参数错误!');
        $data['refund_goods_img'] = json_encode($data['refund_goods_img'] != '' ? explode(',', $data['refund_goods_img']) : []);
        $res = $services->editRefundExpress($data);
        if ($res)
            return app('json')->successful('提交成功');
        else
            return app('json')->fail('提交失败');
    }

    /**
     * 订单取消   未支付的订单回退积分,回退优惠券,回退库存
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel(Request $request)
    {
        [$id] = $request->postMore([['id', 0]], true);
        if (!$id) return app('json')->fail('参数错误');
        $uid = (int)$request->uid();
        $order = $this->services->getOne(['order_id' => $id, 'uid' => $uid, 'is_del' => 0], 'id');
        if (!$order) {
            throw new ValidateException('没有查到此订单');
        }
        if ($this->services->cancelOrder((int)$order['id'], $uid))
            return app('json')->successful('取消订单成功');
        return app('json')->fail('取消订单失败');
    }


    /**
     * 订单商品信息
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function product(Request $request, StoreOrderCartInfoServices $services)
    {
        [$unique] = $request->postMore([['unique', '']], true);
        if (!$unique || !($cartInfo = $services->getOne(['unique' => $unique]))) return app('json')->fail('评价商品不存在!');
        $cartInfo = $cartInfo->toArray();
        $cartProduct = [];
        $cartProduct['cart_num'] = $cartInfo['cart_info']['cart_num'];
        $cartProduct['productInfo']['image'] = get_thumb_water($cartInfo['cart_info']['productInfo']['image'] ?? '');
        $cartProduct['productInfo']['price'] = $cartInfo['cart_info']['productInfo']['price'] ?? 0;
        $cartProduct['productInfo']['store_name'] = $cartInfo['cart_info']['productInfo']['store_name'] ?? '';
        if (isset($cartInfo['cart_info']['productInfo']['attrInfo'])) {
            $cartProduct['productInfo']['attrInfo']['product_id'] = $cartInfo['cart_info']['productInfo']['attrInfo']['product_id'] ?? '';
            $cartProduct['productInfo']['attrInfo']['suk'] = $cartInfo['cart_info']['productInfo']['attrInfo']['suk'] ?? '';
            $cartProduct['productInfo']['attrInfo']['price'] = $cartInfo['cart_info']['productInfo']['attrInfo']['price'] ?? '';
            $cartProduct['productInfo']['attrInfo']['image'] = get_thumb_water($cartInfo['cart_info']['productInfo']['attrInfo']['image'] ?? '');
        }
        $cartProduct['product_id'] = $cartInfo['cart_info']['product_id'] ?? 0;
        $cartProduct['type'] = $cartInfo['cart_info']['type'] ?? 0;
        $cartProduct['activity_id'] = $cartInfo['cart_info']['activity_id'] ?? 0;
        $cartProduct['order_id'] = $this->services->value(['id' => $cartInfo['oid']], 'order_id');
        return app('json')->successful($cartProduct);
    }

    /**
     * 门店线上支付订单详情
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function payCashierOrder(Request $request)
    {
        [$store_id] = $request->postMore([['store_id', '']], true);
        $uid = $request->uid();
        return app('json')->successful($this->services->payCashierOrder((int)$store_id, (int)$uid));
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
        if (!$id) return app('json')->fail('参数错误');
        $uid = $request->uid();
        return app('json')->successful($services->userOrderWriteOffRecords(['oid' => $id, 'uid' => $uid]));
    }

    /**
     * 商家寄件回调
     * @param Request $request
     * @return Response
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/6/12
     */
    public function callBack(Request $request)
    {
        $data = $request->postMore([
            ['type', ''],
            ['data', ''],
        ]);
        Log::info('商家寄件回调:' . json_encode($data));
        $decryptData = $this->decrypt($data['data'], sys_config('sms_token'));
        $data['data'] = $decryptData ? json_decode($decryptData, true) : [];
        switch ($data['type']) {
            case 'order_success'://下单成功
                $update = [
                    'label' => $data['data']['label'] ?? '',
                ];
                //韵达会异步推送单号
                if (isset($data['kuaidinum'])) {
                    $update['delivery_id'] = $data['kuaidinum'];
                }
                if (isset($data['task_id'])) {
                    $this->services->update(['task_id' => $data['task_id']], $update);
                }
                break;
            case 'order_take'://取件
                if (isset($data['data']['task_id'])) {
                    $orderInfo = $this->services->get(['kuaidi_task_id' => $data['data']['task_id']]);
                    if (!$orderInfo) {
                        return app('json')->fail('订单不存在');
                    }
                    $this->services->transaction(function () use ($data, $orderInfo) {
                        $this->services->update(['kuaidi_task_id' => $data['data']['task_id']], [
                            'status' => 1,
                            'is_stock_up' => 0
                        ]);
                        OrderStatusJob::dispatch([$orderInfo->id, 'delivery_goods', ['change_manager_type' => 'admin', 'change_message' => '已发货 快递公司：' . $orderInfo->delivery_name . ' 快递单号：' . $orderInfo->delivery_id]]);
                    });
                }
                break;
            case 'order_cancel'://取消寄件
                if (isset($data['data']['task_id'])) {
                    $orderInfo = $this->services->get(['kuaidi_task_id' => $data['data']['task_id']]);
                    if (!$orderInfo) {
                        return app('json')->fail('订单不存在');
                    }
                    if ($orderInfo->is_stock_up && $orderInfo->status == 0) {
                        //写入订单状态
                        OrderStatusJob::dispatch([$orderInfo->id, 'delivery_goods_cancel', ['change_message' => '已取消发货，取消原因：用户手动取消', 'change_manager_type' => 'user']]);


                        $orderInfo->status = 0;
                        $orderInfo->is_stock_up = 0;
                        $orderInfo->kuaidi_task_id = '';
                        $orderInfo->kuaidi_order_id = '';
                        $orderInfo->express_dump = '';
                        $orderInfo->kuaidi_label = '';
                        $orderInfo->delivery_id = '';
                        $orderInfo->delivery_code = '';
                        $orderInfo->delivery_name = '';
                        $orderInfo->delivery_type = '';
                        $orderInfo->save();
                    } else {
                        Log::error('商家寄件自动回调，订单状态不正确：', [
                            'kuaidi_task_id' => $data['data']['task_id']
                        ]);
                    }
                }
                break;
        }
        return app('json')->success();
    }

    /**
     * 解密商家寄件回调
     * @param string $encryptedData
     * @param string $key
     * @return false|string
     * @author: 吴汐
     * @email: 442384644@qq.com
     * @date: 2023/8/31
     */
    function decrypt(string $encryptedData, string $key)
    {
        $key = substr($key, 0, 32);
        $decodedData = base64_decode($encryptedData);
        $iv = substr($decodedData, 0, 16);
        $encrypted = substr($decodedData, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }
}
