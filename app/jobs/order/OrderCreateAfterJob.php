<?php


namespace app\jobs\order;


use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderComputedServices;
use app\services\user\UserServices;
use crmeb\basic\BaseJobs;
use crmeb\services\CacheService;
use crmeb\traits\QueueTrait;

/**
 * 订单创建
 * Class OrderCreateAfterJob
 * @package app\jobs
 */
class OrderCreateAfterJob extends BaseJobs
{
    use QueueTrait;

    /**
 	* 清理订单确认生成缓存
	* @param int $uid
	* @param string $unique
	* @param string $key
	* @return bool
	*/
    public function delOrderCache(int $uid, string $unique, string $key = 'user_order_')
    {
        CacheService::redisHandler()->delete($key . $uid . $unique);
        return true;
    }

    /**
     * 设置默认地址、更新自提人信息
     * @return bool
     */
    public function updateUser($orderInfo, $group, $userInfo)
    {
        try {
            /** @var StoreOrderCreateServices $orderCreate */
            $orderCreate = app()->make(StoreOrderCreateServices::class);
            $orderCreate->updateUserAddress($orderInfo, $group);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '设置用户默认地址信息失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

		try {
			$uid = $orderInfo['uid'] ?? 0;
			 //记录自提人电话和姓名
			if ($uid && ($userInfo['real_name'] != $orderInfo['real_name'] || $userInfo['record_phone'] != $orderInfo['user_phone'])) {
				/** @var UserServices $userService */
				$userService = app()->make(UserServices::class);
				$userService->update(['uid' => $uid], ['real_name' => $orderInfo['real_name'], 'record_phone' => $orderInfo['user_phone']]);
			}
		} catch (\Throwable $e) {
			response_log_write([
                'message' => '更新用户自提人信息失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
		}
        return true;
    }

	/**
     * 删除购物车
     * @return bool
     */
    public function delCart($group)
    {
        try {
            /** @var StoreOrderCreateServices $orderCreate */
            $orderCreate = app()->make(StoreOrderCreateServices::class);
            $orderCreate->delCart($group);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '删除购物车失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 订单后置处理
     * @param $userInfo
     * @param $orderInfo
     * @param $data
     * @param $activity
     * @return bool
     */
    public function compute($userInfo, $orderInfo, $data, $activity)
    {
        if (($orderInfo['uid'] && !$userInfo) || !$orderInfo) {
            return true;
        }
        $uid = (int)$orderInfo['uid'];
        $orderId = (int)$orderInfo['id'];
        try {
            $spread_uid = $spread_two_uid = 0;
            $cartInfo = $data['cartInfo'] ?? [];
            $priceData = $data['priceData'] ?? [];
            $addressId = $data['addressId'] ?? 0;
            /** @var StoreOrderCreateServices $createService */
            $createService = app()->make(StoreOrderCreateServices::class);
            $spread_ids = [];
            if ($cartInfo && $priceData) {
                /** @var StoreOrderCartInfoServices $cartServices */
                $cartServices = app()->make(StoreOrderCartInfoServices::class);
                [$cartInfo, $spread_ids] = $createService->computeOrderProductTruePrice($orderInfo, $cartInfo, $priceData, $uid, $userInfo);
                $cartServices->updateCartInfo($orderId, $cartInfo);
            }
            $orderData = [];
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            if ($spread_ids) {
                [$spread_uid, $spread_two_uid] = $spread_ids;
                $orderData['spread_uid'] = $spread_uid;
                $orderData['spread_two_uid'] = $spread_two_uid;
            } else {
                $spread_uid = $userServices->getSpreadUid($uid);
                if ($spread_uid > 0 && sys_config('brokerage_level', 2) == 2) {
                    $spread_two_uid = $userServices->getSpreadUid($spread_uid, [], false);
                }
            }
			if ($spread_uid > 0) {
				$orderData['spread_uid'] = $spread_uid;
			}
			if ($spread_two_uid > 0) {
				$orderData['spread_two_uid'] = $spread_two_uid;
			}
            if ($cartInfo && (isset($activity['type']) && $activity['type'] == 0)) {
                /** @var StoreOrderComputedServices $orderComputed */
                $orderComputed = app()->make(StoreOrderComputedServices::class);
                if ($userServices->checkUserPromoter($spread_uid)) $orderData['one_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'one_brokerage', false);
                if ($userServices->checkUserPromoter($spread_two_uid)) $orderData['two_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'two_brokerage', false);
                $orderData['division_staff_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'division_staff_brokerage', false);
                $orderData['division_agent_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'division_agent_brokerage', false);
                $orderData['division_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'division_brokerage', false);
            }
            if ($orderData) $createService->update(['id' => $orderId], $orderData);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '计算订单实际优惠、积分、邮费、佣金失败，原因：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return true;
    }
}
