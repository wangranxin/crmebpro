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

namespace app\services\activity\integral;


use app\dao\activity\integral\StoreIntegralOrderDao;
use app\services\BaseServices;
use app\services\user\UserServices;
use app\services\user\UserBillServices;
use crmeb\services\SystemConfigService;
use crmeb\utils\Arr;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;

/**
 * Class StoreIntegralOrderServices
 * @package app\services\activity\integral
 * @mixin StoreIntegralOrderDao
 */
class StoreIntegralOrderServices extends BaseServices
{

    /**
     * @var StoreIntegralOrderDao
     */
    #[Inject]
    protected StoreIntegralOrderDao $dao;

    /**
     * 获取列表
     * @param array $where
     * @param array $field
     * @param array $with
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getOrderList(array $where, array $field = ['*'], array $with = [])
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getOrderList($where, $field, $page, $limit, $with);
        $count = $this->dao->count($where);
        $data = $this->tidyOrderList($data);
        $batch_url = "file/upload/1";
        return compact('data', 'count', 'batch_url');
    }

    /**
     * 获取导出数据
     * @param array $where
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getExportList(array $where, int $limit = 0)
    {
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $data = $this->dao->getOrderList($where, ['*'], $page, $limit);
        $data = $this->tidyOrderList($data);
        return $data;
    }

    /**
     * 前端订单列表
     * @param array $where
     * @param array|string[] $field
     * @param array $with
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getOrderApiList(array $where, array $field = ['*'], array $with = [])
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getOrderList($where, $field, $page, $limit, $with);
        $data = $this->tidyOrderList($data);
        return $data;
    }

    /**
     * 订单详情数据格式化
     * @param $order
     * @return mixed
     */
    public function tidyOrder($order)
    {
        $order['_add_time'] = $order['add_time'] = date('Y-m-d H:i:s', $order['add_time']);
        if ($order['status'] == 1) {
            $order['status_name'] = '未发货';
        } else if ($order['status'] == 2) {
            $order['status_name'] = '待收货';
        } else if ($order['status'] == 3) {
            $order['status_name'] = '已完成';
        }
        return $order;
    }

    /**
     * 数据转换
     * @param array $data
     * @return array
     */
    public function tidyOrderList(array $data)
    {
        foreach ($data as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            if (!$item['paid']) {
                $item['status_name'] = '待付款';
            } else if ($item['status'] == 1) {
                $item['status_name'] = '未发货';
            } else if ($item['status'] == 2) {
                $item['status_name'] = '待收货';
            } else if ($item['status'] == 3) {
                $item['status_name'] = '已完成';
            }
        }
        return $data;
    }

	/**
 	 * 未支付积分订单退积分
     * @return bool|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function rebatePointsOrders()
    {
		//废弃 积分订单合并入统一下单流程
		//先完成支付 后在支付积分
		return true;
        $where['is_del'] = 0;
        $where['is_system_del'] = 0;
        $where['paid'] = 0;
        $where['is_price'] = 1;
        $where['is_integral'] = 1;
        $list = $this->dao->getIntegralOrderList($where);
        //系统预设取消订单时间段
        $keyValue = ['rebate_points_orders_time', 'order_cancel_time'];
        //获取配置
        $systemValue = SystemConfigService::more($keyValue);
        //格式化数据
        $systemValue = Arr::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
        $order_cancel_time = $systemValue['rebate_points_orders_time'] ?: $systemValue['order_cancel_time'];
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
		$order_cancel_time = (int)bcmul((string)$order_cancel_time, '3600', 0);
        foreach ($list as $key => $item) {
            if (($item['add_time'] + $order_cancel_time) < time()) {
                try {
                    $this->transaction(function () use ($item, $userServices, $userBillServices) {
                        $user_integral = $userServices->value(['uid' => $item['uid']], 'integral');
                        $res = $userServices->incUpdate(['uid' => $item['uid']], 'integral', (int)$item['integral']);
                        $integral = bcadd((string)$user_integral, (string)$item['integral']);
                        $res1 = $userBillServices->income('integral_refund', $item['uid'], (int)$item['integral'], (int)$integral, $item['id']);
                        //修改订单状态
                        $res = $res && $res1 && $this->dao->update($item['id'], ['is_del' => 1, 'is_system_del' => 1, 'mark' => '积分订单未支付已超过系统预设时间']);
                    });
                } catch (\Throwable $e) {
                    Log::error('未支付积分订单退积分失败,失败原因:' . $e->getMessage(), $e->getTrace());
                }
            }
        }
        return true;
    }


    /**
     *获取订单数量
     * @param array $where
     * @return mixed
     */
    public function orderCount(array $where)
    {
		$where['type'] = 4;
        //全部订单
        $data['statusAll'] = (string)$this->dao->count($where + ['is_system_del' => 0]);
        //未发货
        $data['unshipped'] = (string)$this->dao->count($where + ['status' => 1, 'is_system_del' => 0]);
        //待收货
        $data['untake'] = (string)$this->dao->count($where + ['status' => 2, 'is_system_del' => 0]);
        //待评价
//        $data['unevaluate'] = (string)$this->dao->count(['status' => 3, 'time' => $where['time'], 'is_system_del' => 0]);
        //交易完成
        $data['complete'] = (string)$this->dao->count($where + ['status' => 3, 'is_system_del' => 0]);
        return $data;
    }



}
