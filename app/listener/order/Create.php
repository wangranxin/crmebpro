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


use app\jobs\activity\LuckLotteryJob;
use app\jobs\activity\StoreBargainJob;
use app\jobs\order\CreateInvoiceJob;
use app\jobs\order\OrderCreateAfterJob;
use app\jobs\order\OrderStatusJob;
use app\jobs\product\ProductLogJob;
use app\jobs\order\UnpaidOrderJob;
use app\jobs\system\SystemFormDataJob;
use app\jobs\user\UserJob;
use app\jobs\user\UserUpdateJob;
use app\services\order\StoreOrderServices;
use crmeb\interfaces\ListenerInterface;


/**
 * 订单创建事件
 * Class Create
 * @package app\listener\order
 */
class Create implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$orderInfo, $userInfo, $group, $activity, $invoice_id] = $event;
        $uid = (int)($userInfo['uid'] ?? 0);
        $orderId = (int)$orderInfo['id'];
		$cartInfo = $group['cartInfo'] ?? [];

        //抽奖中奖
        if (isset($orderInfo['activity_id']) && $orderInfo['activity_id'] && isset($orderInfo['type']) && $orderInfo['type'] == 8) {
            //抽奖订单中奖记录处理
            LuckLotteryJob::dispatchDo('updateLotteryRecord', [$orderInfo['id'], $orderInfo]);
        }

        //计算订单实际金额
        OrderCreateAfterJob::dispatchDo('compute', [$userInfo, $orderInfo, $group, $activity]);
		//设置默认地址、修改用户自提人电话和姓名
        OrderCreateAfterJob::dispatchDo('updateUser', [$orderInfo, $group, $userInfo]);
		//清理购物车
        OrderCreateAfterJob::dispatchDo('delCart', [$group]);
        //清理订单确认生成缓存
        OrderCreateAfterJob::dispatchDo('delOrderCache', [$uid, $orderInfo['unique']], 120);

        //创建发票信息
        if ($invoice_id) {
            CreateInvoiceJob::dispatch([$uid, $orderId, (int)$invoice_id]);
        }
        //下单成功修改砍价状态
        if ($activity['type'] == 2 && $activity['activity_id']) {
            StoreBargainJob::dispatchDo('setBargainUserStatus', [$uid, (int)$activity['activity_id']]);
        }
		//修改用户首单优惠状态
		UserJob::dispatchDo('updateUserNewcomer', [$uid, $orderInfo]);
		//收集系统表单数据
		if (isset($orderInfo['custom_form']) && $orderInfo['custom_form']) {
			$orderInfo['system_form_id'] = $cartInfo[0]['productInfo']['system_form_id'] ?? 0;
			SystemFormDataJob::dispatch([$orderInfo]);
		}
        //写入订单记录表
        OrderStatusJob::dispatch([$orderId, 'create', ['change_message' => '订单生成', 'change_manager_type' => 'user']]);
        //下单记录
        ProductLogJob::dispatch(['order', ['uid' => $uid, 'order_id' => $orderId]]);

		//订单创建对外接口推送
        event('out.outPush', ['order_create_push', ['order_id' => $orderId]]);

        //订单自动取消
        $this->pushJob($orderId, (int)$activity['type']);
    }

    /**
     * 订单自动取消加入延迟消息队列
     * @param int $orderId
     * @param int $type
     * @return mixed
     */
    public function pushJob(int $orderId, int $type)
    {
        //未支付10分钟后发送短信
        UnpaidOrderJob::dispatchSece(600, 'sendNotice', [$orderId]);

        //未支付根据系统设置事件取消订单
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
		$secs = $storeOrderServices->getOrderCancelTime($type);
        UnpaidOrderJob::dispatchSece((int)($secs * 3600), 'cancelOrder', [$orderId]);
    }
}
