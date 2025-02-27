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
namespace app\listener\order;

use app\jobs\order\OrderStatusJob;
use app\services\order\StoreOrderStatusServices;
use app\services\order\StoreOrderWriteOffServices;
use app\services\product\product\StoreProductServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 订单核销事件
 * Class PriceRevision
 * @package app\listener\order
 */
class Writeoff implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$orderInfo, $auth, $data, $cartIds, $cartInfo,$admin_id] = $event;
		$staff_id = $data['staff_id'] ?? 0;
		$oid = (int)$orderInfo['id'];

		//核销记录
//		OrderWriteoffJob::dispatch([$oid, $cartIds, $data, $orderInfo]);
		/** @var StoreOrderWriteOffServices $storeOrderWriteoffServices */
		$storeOrderWriteoffServices = app()->make(StoreOrderWriteOffServices::class);
		$storeOrderWriteoffServices->saveWriteOff($oid, $cartIds, $data, $orderInfo,[],$admin_id);

		/** @var StoreOrderStatusServices $statusServices */
		$statusServices = app()->make(StoreOrderStatusServices::class);
		if ($data['status'] == 5) {
			$message = [];
			foreach ($cartInfo as $item) {
				foreach ($cartIds as $value) {
					if ($value['cart_id'] === $item['cart_id']) {
						$message[] = '商品id:' . $item['product_id'] . ',核销数量:' . $value['cart_num'];
					}
				}
			}
            OrderStatusJob::dispatch([$oid, 'writeoff_part', ['change_message' => '订单部分核销，核销成员:' . $staff_id . ',核销商品:' . implode(' ', $message)]]);
		} else {
            OrderStatusJob::dispatch([$oid, 'writeoff', ['change_message' => '订单核销已完成']]);
		}
		$message = [];
		/** @var StoreProductServices $productServices */
		$productServices = app()->make(StoreProductServices::class);
		foreach ($cartInfo as $item) {
			foreach ($cartIds as $value) {
				if ($value['cart_id'] === $item['cart_id']) {
					$store_name = $productServices->value(['id'=>$item['product_id']],'store_name');
					$message[] = substrUTf8($store_name, 10, 'UTF-8', '');
				}
			}
		}
		$data['store_name'] = implode(' ', $message);
		$data['phone'] = $orderInfo['user_phone'];
		$data['uid'] = $orderInfo['uid'];
		event('notice.notice', [$data, 'reminder_verification_status']);
    }
}
