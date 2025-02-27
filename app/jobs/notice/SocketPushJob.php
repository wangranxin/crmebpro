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

namespace app\jobs\notice;


use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use app\webscoket\SocketPush;

/**
 * socket推送
 * Class SocketPushJob
 * @package app\jobs\system
 */
class SocketPushJob extends BaseJobs
{
    use QueueTrait;

	/**
 	* 执行说明
	* @var array|string[]
	*/
	protected array $typeArr = [
		'WAIT_DELIVER_ORDER' => '向供应商后台发送待发货订单消息',
		'ADMIN_NEW_PUSH' => '后台订单下单，评论，支付成功，后台消息提醒',
		'WITHDRAW' => '用户申请提现',
	];

	/**
 	* 推送用户类型
	* @var array|string[]
	*/
	protected array $userType = ['admin', 'kefu', 'supplier', 'user'];

	/**
	 * @return mixed
	 */
	public static function queueName()
	{
		return 'CRMEB_PRO_SOCKET';
	}

	/**
	 * 推送socket 消息
	 * @param $to
	 * @param $type
	 * @param $data
	 * @param $userType
	 * @return bool
	 */
	public function  doJob($to, $type, $data, $userType = 'admin')
	{
		if (!$type) {
			return true;
		}
		if (!in_array($userType, $this->userType)) {
			return true;
		}
		//发送消息
		try {
			if ($to) {
				SocketPush::instance()->to($to)->setUserType($userType)->type($type)->data($data)->push();
			} else {
				SocketPush::instance()->setUserType($userType)->type($type)->data($data)->push();
			}
		} catch (\Throwable $e) {

		}
		return true;
	}

	/**
	 * 订单申请退款发送
	 * @param $order
	 * @return bool
	 */
    public function sendApplyRefund($order)
	{
		if (!$order) {
			return true;
		}
		if ($order['supplier_id']) {
			//向门店后台发送退款订单消息
			try {
				SocketPush::instance()->setUserType('supplier')->to($order['supplier_id'])->data(['order_id' => $order['order_id']])->type('NEW_REFUND_ORDER')->push();
			} catch (\Exception $e) {
			}
		} else {
			//向后台发送退款订单消息
			try {
				SocketPush::admin()->data(['order_id' => $order['order_id']])->type('NEW_REFUND_ORDER')->push();
			} catch (\Exception $e) {
			}
		}
		return true;
	}

}
