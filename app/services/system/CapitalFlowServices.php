<?php

namespace app\services\system;

use app\dao\system\CapitalFlowDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use think\annotation\Inject;

/**
 * 资金流水
 * Class SystemFileServices
 * @package app\services\system
 * @mixin CapitalFlowDao
 */
class CapitalFlowServices extends BaseServices
{

    protected array $typeName = [
        1 => '商城购物',
        2 => '商城购物退款',
        3 => '用户充值',
        4 => '用户充值退款',
        5 => '抽奖中奖',
        6 => '佣金提现',
        7 => '购买会员',
        8 => '线下支付',
    ];

    /**
     * @var CapitalFlowDao
     */
    #[Inject]
    protected CapitalFlowDao $dao;

    /**
     * 添加资金流水
     * @param $orderInfo
     * @param string $type
     */
    public function setFlow($data, $type = '')
    {
        $data['flow_id'] = 'ZJ' . date('Ymdhis', time()) . rand('1000', '9999');
        switch ($type) {
            case 'order':
                $data['trading_type'] = 1;
                break;
            case 'refund':
                $data['price'] = bcmul('-1', $data['price'], 2);
                $data['trading_type'] = 2;
                break;
            case 'recharge':
                $data['trading_type'] = 3;
                break;
            case 'refund_recharge':
                $data['price'] = bcmul('-1', $data['price'], 2);
                $data['trading_type'] = 4;
                break;
            case 'luck':
                $data['price'] = bcmul('-1', $data['price'], 2);
                $data['trading_type'] = 5;
                break;
            case 'extract':
                $data['price'] = bcmul('-1', $data['price'], 2);
                $data['trading_type'] = 6;
                break;
            case 'pay_member':
                $data['trading_type'] = 7;
                break;
            case 'offline_scan':
                $data['trading_type'] = 8;
                break;
            default:
                break;
        }
        $data['add_time'] = time();
        $this->dao->save($data);
    }

    /**
     * 获取资金流水
     * @param $where
     * @return array
     */
    public function getFlowList($where)
    {
        $export = $where['export'] ?? 0;
        unset($where['export']);
        [$page, $limit] = $this->getPageValue();
        $status = ['未知', '支付订单', '订单退款', '充值订单', '充值退款', '抽奖红包', '佣金提现', '购买会员', '线下收银'];
        $pay_type = ['weixin' => '微信支付', 'routine' => '小程序', 'alipay' => '支付宝', 'offline' => '线下支付', 'bank' => '银行'];
        $list = $this->dao->getList($where, '*', $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['trading_type'] = $status[$item['trading_type']];
            $item['pay_type'] = $pay_type[$item['pay_type']] ?? '';
        }
        $count = $this->dao->count($where);
        if ($export) {
            $fileKey = ['flow_id', 'order_id', 'nickname', 'phone', 'price', 'trading_type', 'pay_type', 'add_time', 'mark'];
            $header = ['交易单号', '关联订单', '用户', '电话', '金额', '订单类型', '支付类型', '交易时间', '备注'];
            $fileName = '账单导出' . date('YmdHis') . rand(1000, 9999);
            return compact('list', 'fileKey', 'header', 'fileName');
        } else {
            return compact('list', 'count', 'status');
        }
    }

    /**
     * 添加备注
     * @param $id
     * @param $data
     * @return bool
     */
    public function setMark($id, $data)
    {
        $res = $this->dao->update($id, $data);
        if ($res) {
            return true;
        } else {
            throw new AdminException('备注失败');
        }
    }

    /**
     * 获取账单记录
     * @param $where
     * @return array
     */
    public function getFlowRecord($where)
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getRecordList($where, $page, $limit);
        $i = 1;
        foreach ($data['list'] as &$item) {
            $item['id'] = $i;
            $i++;
            $item['entry_price'] = bcadd($item['income_price'], $item['exp_price'], 2);
            switch ($where['type']) {
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
     * 用户资金记录
     * @param int $uid
     * @param array $data
     * @return array
     */
    public function userCapitalList(int $uid, array $data)
    {
        $where = [];
        $where['uid'] = $uid;
        $where['trading_type'] = [1, 7];
        if ((isset($data['start']) && $data['start']) || (isset($data['stop']) && $data['stop'])) {
            $where['time'] = [$data['start'], $data['stop']];
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit);
        $times = [];
        if ($list) {
            $typeName = $this->typeName;
            foreach ($list as &$item) {
                $item['time_key'] = $item['add_time'] ? date('Y-m', (int)$item['add_time']) : '';
                $item['day'] = $item['add_time'] ? date('Y-m-d', (int)$item['add_time']) : '';
                $item['add_time'] = $item['add_time'] ? date('Y/m/d H:i', (int)$item['add_time']) : '';
                $item['type'] = $item['trading_type'];
                $item['type_name'] = $typeName[$item['type'] ?? ''] ?? '未知类型';
                $item['title'] = $item['type_name'];
            }
            $times = array_merge(array_unique(array_column($list, 'time_key')));
        }
        return ['list' => $list, 'time' => $times];
    }
}
