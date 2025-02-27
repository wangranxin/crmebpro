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
declare (strict_types=1);

namespace app\services\user;

use app\dao\user\UserMoneyDao;
use app\services\BaseServices;
use app\services\order\StoreOrderRefundServices;
use crmeb\exceptions\AdminException;
use think\annotation\Inject;
use crmeb\services\CacheService;


/**
 * 用户余额
 * Class UserMoneyServices
 * @package app\services\user
 * @mixin UserMoneyDao
 */
class UserMoneyServices extends BaseServices
{


    /**
     * 用户记录模板
     * @var array[]
     */
    protected array $incomeData = [
        'pay_product' => [
            'title' => '余额支付购买商品',
            'type' => 'pay_product',
            'mark' => '余额支付{%num%}元购买商品',
            'status' => 1,
            'pm' => 0
        ],
        'pay_product_refund' => [
            'title' => '商品退款',
            'type' => 'pay_product_refund',
            'mark' => '订单余额退款{%num%}元',
            'status' => 1,
            'pm' => 1
        ],
        'system_add' => [
            'title' => '系统增加余额',
            'type' => 'system_add',
            'mark' => '系统增加{%num%}余额',
            'status' => 1,
            'pm' => 1
        ],
        'system_sub' => [
            'title' => '系统减少余额',
            'type' => 'system_sub',
            'mark' => '系统扣除了{%num%}余额',
            'status' => 1,
            'pm' => 0
        ],
        'user_recharge' => [
            'title' => '用户充值余额',
            'type' => 'recharge',
            'mark' => '成功充值余额{%price%}元,赠送{%give_price%}元',
            'status' => 1,
            'pm' => 1
        ],
        'user_recharge_refund' => [
            'title' => '用户充值退款',
            'type' => 'recharge_refund',
            'mark' => '退款扣除用户余额{%num%}元',
            'status' => 1,
            'pm' => 0
        ],
        'brokerage_to_nowMoney' => [
            'title' => '佣金提现到余额',
            'type' => 'extract',
            'mark' => '佣金提现到余额{%num%}元',
            'status' => 1,
            'pm' => 1
        ],
        'lottery_use_money' => [
            'title' => '参与抽奖使用余额',
            'type' => 'lottery_use',
            'mark' => '参与抽奖使用{%num%}余额',
            'status' => 1,
            'pm' => 0
        ],
        'lottery_give_money' => [
            'title' => '抽奖中奖赠送余额',
            'type' => 'lottery_add',
            'mark' => '抽奖中奖赠送{%num%}余额',
            'status' => 1,
            'pm' => 1
        ],
        'newcomer_give_money' => [
            'title' => '新人礼赠送余额',
            'type' => 'newcomer_add',
            'mark' => '新人礼赠送{%num%}余额',
            'status' => 1,
            'pm' => 1
        ],
        'level_give_money' => [
            'title' => '会员卡激活赠送余额',
            'type' => 'level_add',
            'mark' => '会员卡激活赠送{%num%}余额',
            'status' => 1,
            'pm' => 1
        ],
        'pay_integral_product' => [
            'title' => '余额支付购买积分商品',
            'type' => 'pay_integral_product',
            'mark' => '余额支付{%num%}元购买积分商品',
            'status' => 1,
            'pm' => 0
        ],
    ];

    /**
     * 类型名称
     * @var string[]
     */
    protected array $typeName = [
        'pay_product' => '商城购物',
        'pay_product_refund' => '商城购物退款',
        'system_add' => '系统充值',
        'system_sub' => '系统扣除',
        'recharge' => '用户充值',
        'recharge_refund' => '用户充值退款',
        'extract' => '佣金提现充值',
        'lottery_use' => '抽奖使用',
        'lottery_add' => '抽奖中奖充值',
        'newcomer_add' => '新人礼赠送充值',
        'level_add' => '会员卡激活赠送充值'
    ];

    /**
     * @var UserMoneyDao
     */
    #[Inject]
    protected UserMoneyDao $dao;

    /**
     *  获取用户记录总和
     * @param $uid
     * @param string $category
     * @param array $type
     * @return mixed
     */
    public function getRecordCount(int $uid, $category = 'now_money', $type = [], $time = '', $pm = false)
    {

        $where = [];
        $where['uid'] = $uid;
        $where['category'] = $category;
        $where['status'] = 1;

        if (is_string($type) && strlen(trim($type))) {
            $where['type'] = explode(',', $type);
        }
        if ($time) {
            $where['time'] = $time;
        }
        if ($pm) {
            $where['pm'] = 0;
        }
        return $this->dao->getBillSumColumn($where);
    }

    /**
     * 获取资金列表
     * @param array $where
     * @param string $field
     * @param int $limit
     * @return array
     */
    public function getMoneyList(array $where, string $field = '*', int $limit = 0)
    {
        $where_data = [];
        if (isset($where['uid']) && $where['uid'] != '') {
            $where_data['uid'] = $where['uid'];
        }
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $where_data['time'] = str_replace('-', '/', $where['start_time']) . ' - ' . str_replace('-', '/', $where['end_time']);
        }
        if (isset($where['category']) && $where['category'] != '') {
            $where_data['category'] = $where['category'];
        }
        if (isset($where['type']) && $where['type'] != '') {
            $where_data['type'] = $where['type'];
        }
        $where_data['not_category'] = ['integral', 'exp', 'share'];
        $where_data['not_type'] = ['gain', 'system_sub', 'deduction', 'sign'];
        if (isset($where['nickname']) && $where['nickname'] != '') {
            $where_data['like'] = $where['nickname'];
        }
        if (isset($where['excel']) && $where['excel'] != '') {
            $where_data['excel'] = $where['excel'];
        } else {
            $where_data['excel'] = 0;
        }
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $data = $this->dao->getList($where_data, $field, $page, $limit, [
            'user' => function ($query) {
                $query->field('uid,nickname');
            }]);
        foreach ($data as &$item) {
            $item['nickname'] = $item['user']['nickname'] ?? '';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            unset($item['user']);
        }
        $count = $this->dao->count($where_data);
        return compact('data', 'count');
    }

    /**
     * 用户|所有资金变动列表
     * @param int $uid
     * @param array $where_time
     * @param string $field
     * @return array
     */
    public function getUserMoneyList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $value = array_filter($this->incomeData, function ($value) use ($item) {
                if ($item['type'] == $value['type']) {
                    return $item['title'];
                }
            });
            $item['type_title'] = $value[$item['type']]['title'] ?? '未知类型';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return compact('list', 'count');
    }

    /**
     * 获取用户的充值总数
     * @param int $uid
     * @return float
     */
    public function getRechargeSum(int $uid = 0, $time = [])
    {
        $where = ['uid' => $uid, 'pm' => 1, 'status' => 1, 'type' => ['system_add', 'recharge', 'extract', 'lottery_add', 'newcomer_add', 'level_add']];
        if ($time) $where['add_time'] = $time;
        return $this->dao->sum($where, 'number', true);
    }

    /**
     * 用户|所有充值列表
     * @param int $uid
     * @param string $field
     * @return array
     */
    public function getRechargeList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['category' => 'now_money', 'type' => 'recharge'];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 写入用户记录
     * @param string $type 写入类型
     * @param int $uid
     * @param int|string|array $number
     * @param int|string $balance
     * @param int $link_id
     * @return bool|mixed
     */
    public function income(string $type, int $uid, $number, $balance, $link_id = 0, string $title = '')
    {
        $data = $this->incomeData[$type] ?? null;
        if (!$data) {
            return true;
        }
        $data['uid'] = $uid;
        $data['balance'] = $balance ?? 0;
        $data['link_id'] = $link_id;
        if (is_array($number)) {
            $key = array_keys($number);
            $key = array_map(function ($item) {
                return '{%' . $item . '%}';
            }, $key);
            $value = array_values($number);
            $data['number'] = $number['number'] ?? 0;
            $data['mark'] = str_replace($key, $value, $data['mark']);
        } else {
            $data['number'] = $number;
            $data['mark'] = str_replace(['{%num%}'], (string)$number, $data['mark']);
        }
        if ($title) $data['title'] = $title;
        $data['add_time'] = time();
        if ((float)$data['number']) {
            return $this->dao->save($data);
        }
        return true;
    }

    /**
     * 资金类型
     * @return bool|mixed|null
     */
    public function bill_type()
    {
        return CacheService::get('user_money_type_list', function () {
            return ['list' => $this->dao->getMoneyType([])];
        }, 600);
    }

    /**
     * 用户余额记录列表
     * @param int $uid
     * @param int $type
     * @param array $data
     * @return array
     */
    public function userMoneyList(int $uid, int $type, array $data = [])
    {
        $where = [];
        $where['uid'] = $uid;
        switch ($type) {
            case 1:
                $where['pm'] = 0;
                break;
            case 2:
                $where['pm'] = 1;
                break;
            case 0:
            default:
                break;
        }
        [$page, $limit] = $this->getPageValue();
        if ((isset($data['start']) && $data['start']) || (isset($data['stop']) && $data['stop'])) {
            $where['time'] = [$data['start'], $data['stop']];
        }
        $list = $this->dao->getList($where, '*', $page, $limit);
        $count = $this->dao->count($where);
        $times = [];
        if ($list) {
            $typeName = $this->typeName;
            foreach ($list as &$item) {
                $item['time_key'] = $item['time'] = $item['add_time'] ? date('Y-m', (int)$item['add_time']) : '';
                $item['day'] = $item['add_time'] ? date('Y-m-d', (int)$item['add_time']) : '';
                $item['add_time'] = $item['add_time'] ? date('Y/m/d H:i', (int)$item['add_time']) : '';
                $item['type_name'] = $typeName[$item['type'] ?? ''] ?? '未知类型';
                if ($item['type'] == 'pay_product') {
                    /** @var StoreOrderRefundServices $refundSerives */
                    $refundSerives = app()->make(StoreOrderRefundServices::class);
                    $refund = $refundSerives->getOne(['store_order_id' => $item['link_id']]);
                    $item['refund_status'] = '';
                    if ($refund) {
                        if ($refund['refund_type'] == 6) {
                            $item['refund_status'] = '已退款';
                        } else {
                            $item['refund_status'] = '退款中';
                        }
                    }

                }
                if ($item['type'] == 'recharge') {
                    /** @var UserRechargeServices $rechargeSerives */
                    $orderSerives = app()->make(UserRechargeServices::class);
                    $refund_price = $orderSerives->value(['id' => $item['link_id']], 'refund_price');
                    if ($refund_price) {
                        $item['refund_status'] = '已退款';
                    } else {
                        $item['refund_status'] = '';
                    }
                }
            }
            $times = array_merge(array_unique(array_column($list, 'time_key')));
        }

        return ['count' => $count, 'list' => $list, 'time' => $times];
    }

    /**
     * 根据查询用户充值金额
     * @param array $where
     * @param string $rechargeSumField
     * @param string $selectType
     * @param string $group
     * @return float|mixed
     */
    public function getRechargeMoneyByWhere(array $where, string $rechargeSumField, string $selectType, string $group = "")
    {
        switch ($selectType) {
            case "sum" :
                return $this->dao->getWhereSumField($where, $rechargeSumField);
            case "group" :
                return $this->dao->getGroupField($where, $rechargeSumField, $group);
        }
    }


    /**
     * 新人礼赠送余额
     * @param int $uid
     * @return bool
     */
    public function newcomerGiveMoney(int $uid)
    {
        if (!sys_config('newcomer_status')) {
            return false;
        }
        $status = sys_config('register_money_status');
        if (!$status) {//未开启
            return true;
        }
        $money = (int)sys_config('register_give_money', []);
        if (!$money) {
            return true;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserInfo($uid);
        if (!$userInfo) {
            return true;
        }
        $balance = bcadd((string)$userInfo['now_money'], (string)$money);
        $this->income('newcomer_give_money', $uid, $money, $balance);
        $userServices->update($uid, ['now_money' => $balance]);
        return true;
    }

    /**
     * 会员卡激活赠送余额
     * @param int $uid
     * @return bool
     */
    public function levelGiveMoney(int $uid)
    {
        $status = sys_config('level_activate_status');
        if (!$status) {//是否需要激活
            return true;
        }
        $status = sys_config('level_money_status');
        if (!$status) {//未开启
            return true;
        }
        $money = (int)sys_config('level_give_money', []);
        if (!$money) {
            return true;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserInfo($uid);
        if (!$userInfo) {
            return true;
        }
        $balance = bcadd((string)$userInfo['now_money'], (string)$money);
        $this->income('level_give_money', $uid, $money, $balance);
        $userServices->update($uid, ['now_money' => $balance]);
        return true;
    }

    /**
     * 余额统计基础
     * @param $where
     * @return array
     */
    public function getBasic($where)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $data['now_balance'] = $userServices->sum(['status' => 1], 'now_money', true);
        $data['add_balance'] = $this->dao->sum(['pm' => 1], 'number', true);
        $data['sub_balance'] = $this->dao->sum(['pm' => 0], 'number', true);
        return $data;
    }

    /**
     * 余额趋势
     * @param $where
     * @return array
     */
    public function getTrend($where)
    {
        $time = explode('-', $where['time']);
        if (count($time) != 2) throw new AdminException('参数错误');
        $dayCount = (strtotime($time[1]) - strtotime($time[0])) / 86400 + 1;
        $data = [];
        if ($dayCount == 1) {
            $data = $this->trend($time, 0);
        } elseif ($dayCount > 1 && $dayCount <= 31) {
            $data = $this->trend($time, 1);
        } elseif ($dayCount > 31 && $dayCount <= 92) {
            $data = $this->trend($time, 3);
        } elseif ($dayCount > 92) {
            $data = $this->trend($time, 30);
        }
        return $data;
    }

    /**
     * 余额趋势
     * @param $time
     * @param $num
     * @param false $excel
     * @return array
     */
    public function trend($time, $num, $excel = false)
    {
        if ($num == 0) {
            $xAxis = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
            $timeType = '%H';
        } elseif ($num != 0) {
            $dt_start = strtotime($time[0]);
            $dt_end = strtotime($time[1]);
            while ($dt_start <= $dt_end) {
                if ($num == 30) {
                    $xAxis[] = date('Y-m', $dt_start);
                    $dt_start = strtotime("+1 month", $dt_start);
                    $timeType = '%Y-%m';
                } else {
                    $xAxis[] = date('m-d', $dt_start);
                    $dt_start = strtotime("+$num day", $dt_start);
                    $timeType = '%m-%d';
                }
            }
        }
        $time[1] = date("Y-m-d", strtotime("+1 day", strtotime($time[1])));
        $point_add = array_column($this->dao->getBalanceTrend($time, $timeType, 'add_time', 'sum(number)', 'add'), 'num', 'days');
        $point_sub = array_column($this->dao->getBalanceTrend($time, $timeType, 'add_time', 'sum(number)', 'sub'), 'num', 'days');
        $data = $series = [];
        foreach ($xAxis as $item) {
            $data['余额积累'][] = isset($point_add[$item]) ? floatval($point_add[$item]) : 0;
            $data['余额消耗'][] = isset($point_sub[$item]) ? floatval($point_sub[$item]) : 0;
        }
        foreach ($data as $key => $item) {
            $series[] = [
                'name' => $key,
                'data' => $item,
                'type' => 'line',
            ];
        }
        return compact('xAxis', 'series');
    }

    /**
     * 余额来源
     * @param $where
     * @return array
     */
    public function getChannel($where)
    {
        $bing_xdata = ['系统增加', '用户充值', '佣金提现', '抽奖赠送', '商品退款'];
        $color = ['#64a1f4', '#3edeb5', '#70869f', '#ffc653', '#fc7d6a'];
        $data = ['system_add', 'recharge', 'extract', 'lottery_add', 'pay_product_refund'];
        $bing_data = [];
        foreach ($data as $key => $item) {
            $bing_data[] = [
                'name' => $bing_xdata[$key],
                'value' => $this->dao->sum(['pm' => 1, 'type' => $item, 'time' => $where['time']], 'number', true),
                'itemStyle' => ['color' => $color[$key]]
            ];
        }

        $list = [];
        $count = array_sum(array_column($bing_data, 'value'));
        foreach ($bing_data as $key => $item) {
            $list[] = [
                'name' => $item['name'],
                'value' => $item['value'],
                'percent' => $count != 0 ? bcmul((string)bcdiv((string)$item['value'], (string)$count, 4), '100', 2) : 0,
            ];
        }
        array_multisort(array_column($list, 'value'), SORT_DESC, $list);
        return compact('bing_xdata', 'bing_data', 'list');
    }

    /**
     * 余额类型
     * @param $where
     * @return array
     */
    public function getType($where)
    {
        $bing_xdata = ['系统减少', '充值退款', '购买商品'];
        $color = ['#64a1f4', '#3edeb5', '#70869f'];
        $data = ['system_sub', 'recharge_refund', 'pay_product'];
        $bing_data = [];
        foreach ($data as $key => $item) {
            $bing_data[] = [
                'name' => $bing_xdata[$key],
                'value' => $this->dao->sum(['pm' => 0, 'type' => $item, 'time' => $where['time']], 'number', true),
                'itemStyle' => ['color' => $color[$key]]
            ];
        }

        $list = [];
        $count = array_sum(array_column($bing_data, 'value'));
        foreach ($bing_data as $key => $item) {
            $list[] = [
                'name' => $item['name'],
                'value' => $item['value'],
                'percent' => $count != 0 ? bcmul((string)bcdiv((string)$item['value'], (string)$count, 4), '100', 2) : 0,
            ];
        }
        array_multisort(array_column($list, 'value'), SORT_DESC, $list);
        return compact('bing_xdata', 'bing_data', 'list');
    }
}
