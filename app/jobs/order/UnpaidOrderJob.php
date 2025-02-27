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

namespace app\jobs\order;


use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 未支付订单队列
 * Class UnpaidOrderSend
 * @package app\jobs
 */
class UnpaidOrderJob extends BaseJobs
{
    use QueueTrait;

    /**
 	 * 提醒付款给用户通知
     * @param $id
     * @return bool
     */
    public function sendNotice($id)
    {
        try {
            /** @var StoreOrderServices $services */
            $services = app()->make(StoreOrderServices::class);
            $orderInfo = $services->get($id);
            if (!$orderInfo) {
                return true;
            }
            if ($orderInfo->paid) {
                return true;
            }
            if ($orderInfo->is_del) {
                return true;
            }
            //未支付用户发送消息
            event('notice.notice', [['order' => $orderInfo], 'order_pay_false']);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '未支付订单发送短信失败，原因：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

	/**
 	* 取消未支付订单
     * @param $orderId
     * @return bool
     */
    public function cancelOrder($orderId)
    {
        /** @var StoreOrderServices $services */
        $services = app()->make(StoreOrderServices::class);
        $orderInfo = $services->get($orderId);
        if (!$orderInfo) {
            return true;
        }
        if ($orderInfo->paid) {
            return true;
        }
        if ($orderInfo->is_del) {
            return true;
        }
        if ($orderInfo->pay_type == 'offline') {
            return true;
        }
        try {
           $services->cancelOrder((int)$orderId, 0, '订单未支付已超过系统预设时间');
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '自动取消订单失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
		return true;
    }

	 /**
 	 * 自动取消未支付订单
     * @param $page
     * @param $limit
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function autoCancelOrder($page, $limit)
    {
		/** @var StoreOrderServices $service */
		$service = app()->make(StoreOrderServices::class);
		$service->runOrderUnpaidCancel($page, $limit);
        return true;
    }

}
