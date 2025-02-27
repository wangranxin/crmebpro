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

namespace app\services\supplier\finance;


use app\dao\supplier\finance\SupplierFlowingWaterDao;
use app\services\BaseServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use think\annotation\Inject;

/**
 * 供应商流水
 * Class SupplierFlowingWaterServices
 * @package app\services\supplier\finance
 * @mixin SupplierFlowingWaterDao
 */
class SupplierFlowingWaterServices extends BaseServices
{
    /**
     * 支付类型
     * @var string[]
     */
    public array $pay_type = ['weixin' => '微信支付', 'yue' => '余额支付', 'offline' => '线下支付', 'alipay' => '支付宝支付', 'cash' => '现金支付', 'automatic' => '自动转账', 'store' => '微信支付'];

    /**
     * 交易类型
     * @var string[]
     */
    public array $type = [
        1 => '支付订单',
        2 => '退款订单'
    ];

    /**
     * @var SupplierFlowingWaterDao
     */
    #[Inject]
    protected SupplierFlowingWaterDao $dao;


    /**
     * 显示资源列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit, ['user', 'supplierName']);
        foreach ($list as &$item) {
            $item['type_name'] = isset($this->type[$item['type']]) ? $this->type[$item['type']] : '其他类型';
            $item['pay_type_name'] = isset($this->pay_type[$item['pay_type']]) ? $this->pay_type[$item['pay_type']] : '其他方式';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            $item['finish_time'] = $item['finish_time'] ? date('Y-m-d H:i:s', $item['finish_time']) : '';
            $item['trade_time'] = $item['trade_time'] ? date('Y-m-d H:i:s', $item['trade_time']) : $item['add_time'];
            $item['user_nickname'] = $item['user_nickname'] ?: '游客';
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 供应商账单
     * @param $where
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFundRecord($where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $data = $this->dao->getFundRecord($where, $page, $limit);
        $i = 1;
        foreach ($data['list'] as &$item) {
            $item['id'] = $i;
            $i++;
            $item['entry_num'] = bcsub($item['income_num'], $item['exp_num'], 2);
            switch ($where['timeType']) {
                case "day" :
                    $item['title'] = "日账单";
                    $item['add_time'] = date('Y-m-d', $item['add_time']);
                    break;
                case "week" :
                    $item['title'] = "周账单";
                    $item['add_time'] = '第' . $item['day'] . '周(' . date('m', $item['add_time']) . '月)';
                    break;
                case "month" :
                    $item['title'] = "月账单";
                    $item['add_time'] = date('Y-m', $item['add_time']);
                    break;
            }
        }
        return $data;
    }

    /**
     * 获取百分比
     * @param $num
     * @return string|null
     */
    public function getPercent($num)
    {
        return bcdiv($num, '100', 4);
    }

    /**
     * 写入流水账单
     * @param $oid
     * @param $type
     * @return bool|void
     * @throws \Exception
     */
    public function setSupplierFinance($oid, $type = 1)
    {
        /** @var SupplierTransactionsServices $transactionsServices */
        $transactionsServices = app()->make(SupplierTransactionsServices::class);
        /** @var StoreOrderCartInfoServices $cartInfoServices */
        $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
        /** @var StoreOrderRefundServices $storeOrderRefundServices */
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        $order = $storeOrderServices->get($oid);
        if (!$order) {
            return true;
        }
        if ($order['supplier_id'] <= 0) return true;
        $data = $cartInfoServices->getOrderCartInfoSettlePrice($order['id']);
        $pay_postage = 0;
        if (isset($order['shipping_type']) && !in_array($order['shipping_type'], [2, 4])) {
            $pay_postage = floatval($storeOrderRefundServices->getOrderSumPrice($data['info'], 'postage_price', false));
        }
        if ($order['type'] == 8) {
            $order['pay_price'] = $order['total_price'];
        }
        $append = [
            'pay_price' => $order['pay_price'],
            'pay_postage' => $pay_postage,
            'total_price' => $order['total_price'],
        ];
        switch ($type) {
            case 1 ://支付
                $number = bcadd((string)$data['settlePrice'], $pay_postage, 2);
                //支付订单
                $this->savaData($order, $number, 1, 1, $append);

                //交易订单记录
                $transactionsServices->savaData($order, 1, 1, $append);
                break;
            case 2://退款
                $number = bcadd((string)$data['refundSettlePrice'], $pay_postage, 2);
                $this->savaData($order, $number, 0, 2, $append);

                //交易订单记录
                $transactionsServices->savaData($order, 0, 2, $append);
                break;
        }
    }

    /**
     * 写入数据
     * @param $order
     * @param $number
     * @param $pm
     * @param $type
     * @param $trade_type
     * @param array $append
     * @throws \Exception
     */
    public function savaData($order, $number, $pm, $type, array $append = [])
    {
        /** @var StoreOrderCreateServices $storeOrderCreateServices */
        $storeOrderCreateServices = app()->make(StoreOrderCreateServices::class);
        $order_id = $storeOrderCreateServices->getNewOrderId('ls');
        $data = [
            'supplier_id' => $order['supplier_id'] ?? 0,
            'uid' => $order['uid'] ?? 0,
            'order_id' => $order_id,
            'link_id' => $order['order_id'] ?? '',
            'pay_type' => $order['pay_type'] ?? '',
            'trade_time' => $order['pay_time'] ?? $order['add_time'] ?? '',
            'pm' => $pm,
            'number' => $number ?: 0,
            'type' => $type,
            'add_time' => time()
        ];
        if ($type == 2) {
            $data['status'] = 1;
            $data['finish_time'] = time();
            //更改正常订单状态
            $this->dao->update(['link_id' => $order['order_id'], 'type' => 1], ['status' => 1, 'finish_time' => time()]);
        }
        $data = array_merge($data, $append);
        $this->dao->save($data);
    }

    /**
     * 关联门店店员
     * @param $link_id
     * @param int $staff_id
     * @return mixed
     */
    public function setStaff($link_id, int $staff_id)
    {
        return $this->dao->update(['link_id' => $link_id], ['staff_id' => $staff_id]);
    }

    /**
     * 可提现金额
     * @param array $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSumFinance(array $where, array $whereData)
    {
        $field = 'sum(if(pm = 1,number,0)) as income_num,sum(if(pm = 0,number,0)) as exp_num';
        $whereData['status'] = 1;
        $data = $this->dao->getList($whereData, $field);
        if (!$data) return 0;
        $income_num = $data[0]['income_num'] ?? 0;
        $exp_num = $data[0]['exp_num'] ?? 0;
        $number = bcsub($income_num, $exp_num, 2);
        //已提现金额
        /** @var SupplierExtractServices $extractServices */
        $extractServices = app()->make(SupplierExtractServices::class);
        $where['not_status'] = -1;
        $extract_price = $extractServices->getExtractMoneyByWhere($where, 'extract_price');
        $price_not = bcsub((string)$number, (string)$extract_price, 2);
        return $price_not;
    }
}
