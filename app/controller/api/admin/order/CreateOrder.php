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
use app\services\activity\combination\StorePinkServices;
use app\services\order\cashier\CashierOrderServices;
use app\services\order\StoreCartServices;use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\pay\OrderPayServices;
use app\services\pay\PayServices;
use app\services\order\StoreOrderWapServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\user\UserAddressServices;
use crmeb\services\CacheService;
use think\annotation\Inject;
use think\Response;


/**
 * 代客下单
 * Class CreateOrder
 * @package app\api\controller\admin\order
 */
class CreateOrder
{
    /**
     * @var StoreOrderWapServices
     */
    #[Inject]
    protected StoreOrderWapServices $services;

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
 	* 订单列表
	* @param Request $request
	* @return Response
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
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
        $where['plat_type'] = 0;
		$where['staff_id'] = $request->uid();
        return app('json')->successful($this->services->getOrderApiList($where));
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
    public function confirm(Request $request, CashierOrderServices $services, $uid)
    {
        [$cartId, $new, $addressId, $shipping_type, $couponId] = $request->postMore([
            'cartId',
            'new',
            ['addressId', 0],
            ['shipping_type', 1],
            ['couponId', 0]
        ], true);
        if (!is_string($cartId) || !$cartId) {
            return app('json')->fail('请提交购买的商品');
        }
        return app('json')->successful($services->getOrderConfirmData((int)$uid, $cartId, !!$new, (int)$addressId, (int)$shipping_type, (int)$couponId));
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
    public function computedOrder(Request $request, CashierOrderServices $services, $key, $uid)
    {
        if (!$key) return app('json')->fail('参数错误!');
        if ($checkOrder = $this->services->getOne(['order_id|unique' => $key, 'uid' => $uid, 'is_del' => 0], 'id,order_id'))
            return app('json')->status('extend_order', '订单已生成', ['orderId' => $checkOrder['order_id'], 'key' => $key]);
        [$addressId, $couponId, $payType, $useIntegral, $shipping_type] = $request->postMore([
            'addressId',
            'couponId',
            ['payType', 'yue'],
            ['useIntegral', 0],
            ['shipping_type', 1],
        ], true);
        $payType = strtolower($payType);
        $cartGroup = $services->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) return app('json')->fail('订单已过期,请刷新当前页面!');
        $priceGroup = $services->computeOrder((int)$uid, $cartGroup, (int)$addressId, $payType, !!$useIntegral, (int)$shipping_type);

        if ($priceGroup)
            return app('json')->status('NONE', 'ok', $priceGroup);
        else
            return app('json')->fail('计算失败');
    }

    /**
     * 订单创建
     * @param Request $request
     * @param $createServices $createServices
     * @param $key
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(Request $request, CashierOrderServices $createServices, $key, $uid)
    {
        if (!$key) return app('json')->fail('参数错误!');
        if ($checkOrder = $this->services->getOne(['unique' => $key, 'uid' => $uid, 'is_del' => 0], 'id,order_id'))
            return app('json')->status('extend_order', '订单已创建，请点击查看完成支付', ['orderId' => $checkOrder['order_id'], 'key' => $key]);
        [$addressId, $couponId, $payType, $useIntegral, $mark, $from, $shipping_type, $real_name, $phone, $address, $news] = $request->postMore([
            [['addressId', 'd'], 0],
            [['couponId', 'd'], 0],
            ['payType', 'weixin'],
            ['useIntegral', 0],
            ['mark', ''],
            ['from', 'weixin'],
            [['shipping_type', 'd'], 1],
            ['real_name', ''],
            ['phone', ''],
			['address', ''],
            ['new', 0],
        ], true);
        $cartGroup = $createServices->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) {
            return app('json')->fail('请不重复提交或订单已过期,请刷新当前页面!');
        }
        $cartInfo = $cartGroup['cartInfo'];
        if (!$cartInfo) {
            return app('json')->fail('订单已过期或提交的商品不在送达区域,请刷新当前页面或重新选择商品下单!');
        }
        $payType = strtolower($payType);
        if ($uid && $shipping_type == 1) {
            $cartInfo = $cartGroup['cartInfo'];
            $product_type = $cartInfo[0]['productInfo']['product_type'] ?? 0;
            //普通商品 验证地址
            if ($product_type == 0 && !$addressId && !$address) {
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
            //游客下单可以不需要自提信息
            if ($uid && (!$real_name || !$phone)) {
                return app('json')->fail('请填写姓名和电话');
            }
            $addressInfo = $this->addressInfo;
            $addressInfo['real_name'] = $real_name;
            $addressInfo['phone'] = $phone;
			if ($address) {//创建地址下单
				$addressInfo['addressInfo'] = $address;
			}
        }
        try {
            $order = $createServices->createOrder($uid, $key, $cartGroup, (int)$addressId, $payType, $addressInfo, (int)$request->uid(), !!$useIntegral, $couponId, $mark, $shipping_type, $from);
			$orderId = $order['order_id'];
			return app('json')->status('success', '订单创建成功', ['order_id' => $orderId, 'key' => $key, 'pay_price' => $order['pay_price']]);
        } catch (\Throwable $e) {
            return app('json')->fail('订单生成失败，原因：' . $e->getMessage());
        }
    }


    /**
 	* 订单支付
	* @param Request $request
	* @param StorePinkServices $services
	* @param OrderPayServices $payServices
	* @return \think\Response
	* @throws \think\Exception
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function pay(Request $request, StorePinkServices $services, OrderPayServices $payServices, $uid)
    {
        [$uni, $paytype, $quitUrl] = $request->postMore([
            ['uni', ''],
            ['paytype', 'weixin'],
            ['quitUrl', '']
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $this->services->getUserOrderDetail($uni, (int)$uid);
        if (!$order)
            return app('json')->fail('订单不存在!');
        if ($order['paid'])
            return app('json')->fail('该订单已支付!');
        if ($order['pink_id'] && $services->isPinkStatus($order['pink_id'])) {
            return app('json')->fail('该订单已失效!');
        }
		if (!in_array($paytype, ['weixin', 'alipay', 'cash'])) {
			return app('json')->fail('支付方式错误!');
		}
		$order = $order->toArray();
        //只要重新支付就更新订单号
        if (in_array($paytype, [PayServices::ALIAPY_PAY, PayServices::WEIXIN_PAY])) {
            mt_srand();
            $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
            if (sys_config('pay_routine_open', 0)) {
                /** @var StoreOrderCreateServices $orderCreateServices */
                $orderCreateServices = app()->make(StoreOrderCreateServices::class);
                $order['order_id'] = $orderCreateServices->getNewOrderId();
                $this->services->update($order['id'], ['unique' => $order['order_id']], 'id');
            }
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
					$jsConfig = $payServices->orderPay($order, 'pc');
					return app('json')->status('wechat_pc_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id'], 'pay_price' => $order['pay_price']]);
					break;
				case PayServices::ALIAPY_PAY:
					if (!$quitUrl) {
						return app('json')->fail('请传入支付宝支付回调URL');
					}
					$jsConfig = $payServices->alipayOrder($order, $quitUrl, true);
					if (!($jsConfig->invalid ?? false)) $jsConfig->invalid = time() + 60;
					$payKey = md5($order['order_id']);
					CacheService::set($payKey, ['order_id' => $order['order_id'], 'other_pay_type' => false], 300);
					return app('json')->status(PayServices::ALIAPY_PAY . '_pay', '订单创建成功', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id'], 'pay_key' => $payKey, 'pay_price' => $order['pay_price']]);
					break;
				case PayServices::CASH_PAY://收银台现金支付
				 	/** @var StoreOrderSuccessServices $orderService */
        			$orderService = app()->make(StoreOrderSuccessServices::class);
					if (!$orderService->paySuccess($order, $order['pay_type'])) {
						return app('json')->status('pay_error', '支付失败');
					} else {
						return app('json')->status('success', '支付成功');
					}
					break;
			}
			return app('json')->fail('支付方式错误');
		}
    }

	/**
 	* 轮训订单状态
	* @param Request $request
	* @return Response
	*/
	public function checkOrderStatus(Request $request)
    {
        [$order_id, $end_time] = $request->getMore([
            ['order_id', ''],
            ['end_time', 0],
        ], true);
		if (($count = strpos($order_id, '_')) !== false) {
			$order_id = substr($order_id, $count + 1);
		}
		$storeOrderServices = app()->make(StoreOrderServices::class);
		$data['status'] = (bool)$storeOrderServices->count(['order_id' => $order_id, 'paid' => 1]);
        $time = $end_time - time();
        $data['time'] = $time > 0 ? $time : 0;
        return app('json')->successful($data);
    }

}
