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
namespace app\listener\system\timer;

use app\services\activity\combination\StorePinkServices;
use app\services\activity\integral\StoreIntegralOrderServices;
use app\services\activity\live\LiveGoodsServices;
use app\services\activity\live\LiveRoomServices;
use app\services\agent\AgentManageServices;
use app\services\message\sms\SmsRecordServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCommentServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderTakeServices;
use app\services\product\product\StoreProductServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\timer\SystemTimerServices;
use app\services\user\UserIntegralServices;
use app\services\user\UserSignServices;
use app\services\user\UserServices;
use app\services\work\WorkChannelCodeServices;
use app\services\work\WorkGroupTemplateServices;
use app\services\work\WorkMomentServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;

/**
 * 定时任务
 * Class Create
 * @package app\listener\system\timer
 */
class SystemTimer extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        $this->setWorkerId($event)->tick(1000, function () {
            $time = time();
            /** @var SystemTimerServices $timerServices */
            $timerServices = app()->make(SystemTimerServices::class);
            $cacheCount = $timerServices->cacheCount();
            if (!$cacheCount) {
                $timerServices->setAllTimerCache();
            }
            $list = $timerServices->cacheList();
            foreach ($list as $item) {
				$mark = (string)($item['mark'] ?? '');
                if (!$mark || !isset($item['is_open'])) {
					\think\facade\Log::error('定时任务数据异常，数据：' . json_encode($item));
					continue;
				}
                if ($item['is_open'] == 1) {
                    $data = $timerServices->getTimerCycleTime($item['type'], $item['cycle'], $time, $item['update_execution_time']);
                    if ($time == $data['cycle_time']) {
                        $this->after(1000, function () use ($timerServices, $mark, $time) {
                            $timerServices->cacheTag()->set($mark, $time);//上次执行时间保存
                            $this->implement_timer($mark);
                        });
                    }
                }
            }
        });
    }

    /**
 	* 执行定时任务
	* @param string $mark
	* @return bool|void
	*/
    public function implement_timer(string $mark)
    {
		try {
			switch ($mark) {
				case 'auto_cancel': //自动取消订单
					/** @var StoreOrderServices $orderServices */
					$orderServices = app()->make(StoreOrderServices::class);
					return $orderServices->orderUnpaidCancel();
					break;
				case 'auto_take' : //自动确认收货
					/** @var StoreOrderTakeServices $services */
					$services = app()->make(StoreOrderTakeServices::class);
					return $services->autoTakeOrder();
					break;
				case 'auto_comment' : //自动好评
					/** @var StoreOrderCommentServices $services */
					$services = app()->make(StoreOrderCommentServices::class);
					return $services->autoCommentOrder();
					break;
				case 'auto_clear_integral' : // 自动清空用户积分
					/** @var UserIntegralServices $userIntegralServices */
					$userIntegralServices = app()->make(UserIntegralServices::class);
					[$clear_time, $start_time, $end_time] = $userIntegralServices->getTime();
					//到清空积分的最后一天
					if ($clear_time == strtotime(date('Y-m-d', time()))) {
						return $userIntegralServices->clearExpireIntegral();
					}
					return true;
					break;
				case 'auto_off_user_svip' : //自动取消用户到期svip
					/** @var UserServices $userServices */
					$userServices = app()->make(UserServices::class);
					$userServices->offUserSvip();
					return true;
					break;
				case 'auto_agent' : // 自动解绑上下级
					/** @var AgentManageServices $agentManage */
					$agentManage = app()->make(AgentManageServices::class);
					$agentManage->removeSpread();
					return true;
					break;
				case 'auto_clear_poster' : // 自动清除昨日海报
					/** @var SystemAttachmentServices $attach */
					$attach = app()->make(SystemAttachmentServices::class);
					return $attach->emptyYesterdayAttachment();
					break;
				case 'auto_sms_code' : // 更新短信状态
					/** @var SmsRecordServices $smsRecord */
					$smsRecord = app()->make(SmsRecordServices::class);
					return $smsRecord->modifyResultCode();
					break;
				case 'auto_live' : // 自动更新直播产品状态和直播间状态
					/** @var LiveGoodsServices $liveGoods */
					$liveGoods = app()->make(LiveGoodsServices::class);
					$liveGoods->syncGoodStatus();
					//更新直播间状态
					/** @var LiveRoomServices $liveRoom */
					$liveRoom = app()->make(LiveRoomServices::class);
					$liveRoom->syncRoomStatus();
					return true;
					break;
				case 'auto_pink' : // 拼团状态自动更新
					/** @var StorePinkServices $storePinkServices */
					$storePinkServices = app()->make(StorePinkServices::class);
					$storePinkServices->useStatusPink();
					return true;
					break;
				case 'auto_show' :  // 自动上下架商品
					/** @var StoreProductServices $storeProductServices */
					$storeProductServices = app()->make(StoreProductServices::class);
					return $storeProductServices->autoUpperShelves();
					break;
				case 'auto_channel' : // 渠道码定时任务
					/** @var WorkChannelCodeServices $service */
					$service = app()->make(WorkChannelCodeServices::class);
					$service->cronHandle();
					break;
				case 'auto_moment' : // 定时创建发送朋友圈任务
					/** @var WorkMomentServices $make */
					$make = app()->make(WorkMomentServices::class);
					$make->cronHandle();
					break;
				case 'auto_group_task' : // 定时发送群发任务
					/** @var WorkGroupTemplateServices $service */
					$service = app()->make(WorkGroupTemplateServices::class);
					$service->cornHandle();
					break;
				case 'rebate_points_orders' : // 未支付积分订单退积分
					/** @var StoreIntegralOrderServices $service */
					$service = app()->make(StoreIntegralOrderServices::class);
					return $service->rebatePointsOrders();
					break;
				case 'reminder_unverified_remind' : // 次卡商品未核销短信提醒
					/** @var StoreOrderCartInfoServices $service */
					$service = app()->make(StoreOrderCartInfoServices::class);
					return $service->reminderUnverifiedRemind();
					break;
                case 'sign_remind_time' : // 用户签到提醒
                    /** @var UserSignServices $service */
                    $service = app()->make(UserSignServices::class);
                    return $service->userSignRemind();
                    break;
                case 'auto_presale_product':// 定时处理预售商品
                    return app()->make(StoreProductServices::class)->autoPresaleProduct();
                    break;
			}
	 	} catch (\Throwable $e) {
			/** @var SystemTimerServices $timerServices */
            $timerServices = app()->make(SystemTimerServices::class);
			$taskName = $timerServices->getTasKName();
			response_log_write([
				'message' => '定时任务：['. $taskName[$mark] ?? '未知' .'],失败原因:[' . class_basename($this) . ']',
				'file' => $e->getFile(),
				'line' => $e->getLine(),
                'msg'  => $e->getMessage()
			]);
		}
    }
}
