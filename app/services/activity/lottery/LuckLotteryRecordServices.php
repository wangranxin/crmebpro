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

namespace app\services\activity\lottery;

use app\jobs\system\CapitalFlowJob;
use app\services\BaseServices;
use app\dao\activity\lottery\LuckLotteryRecordDao;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\StoreOrderCreateServices;
use app\services\user\UserBillServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use crmeb\services\wechat\Payment;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Log;

/**
 *  抽奖记录
 * Class LuckLotteryRecordServices
 * @package app\services\activity\lottery
 * @mixin LuckLotteryRecordDao
 */
class LuckLotteryRecordServices extends BaseServices
{
    /**
     * @var LuckLotteryRecordDao
     */
    #[Inject]
    protected LuckLotteryRecordDao $dao;

    /**
     * 获取抽奖记录列表
     * @param array $where
     * @return array
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['not_type'] = 1;
        if (isset($where['factor']) && $where['factor']) {
            /** @var LuckLotteryServices $luckServices */
            $luckServices = app()->make(LuckLotteryServices::class);
            $where['lottery_id'] = $luckServices->value(['factor' => $where['factor'], 'status' => 1], 'id');
            if (!$where['lottery_id']) {
                $list = [];
                $count = 0;
                return compact('list', 'count');
            }
            unset($where['factor']);
        }
        $list = $this->dao->getList($where, '*', ['lottery', 'prize', 'user'], $page, $limit);
        $count = 0;
        if ($list) {
            $prizeType = app()->make(LuckPrizeServices::class)->prize_type;
            foreach ($list as &$item) {
                if (isset($item['prize_info']) && $item['prize_info']) {
                    $prize = json_decode($item['prize_info'], true);
                } else {
                    $prize = $item['prize'];
                }
                if (!$item['user']) {
                    $item['user']['nickname'] = '用户已注销';
                    $item['user']['real_name'] = '用户已注销';
                    $item['user']['phone'] = '';
                    $item['user']['uid'] = 0;
                    $item['user']['delete_time'] = null;
                }
                $prize['type_name'] = $prizeType[$prize['type']] ?? '未知';
                $item['prize'] = $prize;
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            }
            $count = $this->dao->count($where);
        }

        return compact('list', 'count');
    }

    /**
     * 获取中奖记录
     * @param array $where
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWinList(array $where, int $limit = 10)
    {
        //去除未中奖的
        $where = $where + ['not_type' => 1];
        $list = $this->dao->getList($where, 'id,uid,prize_id,lottery_id,receive_time,prize_info,add_time', ['user', 'prize'], 0, $limit);
        foreach ($list as &$item) {
            if (isset($item['prize_info']) && $item['prize_info']) {
                $prize = json_decode($item['prize_info'], true);
                unset($item['prize_info']);
            } else {
                $prize = $item['prize'];
            }
            $item['prize'] = [
                'id' => $prize['id'] ?? 0,
                'type' => $prize['type'] ?? 1,
                'name' => $prize['name'] ?? '',
                'image' => $prize['image'] ?? '',
                'prompt' => $item['prompt'] ?? '',
            ];
            $item['receive_time'] = $item['receive_time'] ? date('Y-m-d H:i:s', $item['receive_time']) : '';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i', $item['add_time']) : '';
        }
        return $list;
    }

    /**
     * 参与抽奖数据统计
     * @param int $lottery_id
     * @return int[]
     */
    public function getLotteryRecordData(int $lottery_id)
    {
        $data = ['all' => 0, 'people' => 0, 'win' => 0];
        if ($lottery_id) {
            $where = [['lottery_id', '=', $lottery_id]];
            $data['all'] = $this->dao->getCount($where);
            $data['people'] = $this->dao->getCount($where, 'uid');
            $data['win'] = $this->dao->getCount($where + [['type', '>', 1]], 'uid');
        }
        return $data;
    }

    /**
     * 写入中奖纪录
     * @param int $uid
     * @param array $prize
     * @return mixed
     */
    public function insertPrizeRecord(int $uid, array $prize, array $userInfo = [])
    {
        if (!$userInfo) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getUserInfo($uid);
        }
        if (!$userInfo) {
            throw new ValidateException('用户不存在');
        }
        if (!$prize) {
            throw new ValidateException('奖品不存在');
        }
        $services = app()->make(StoreOrderCreateServices::class);
        $data = [];
        $data['uid'] = $uid;
        $data['order_id'] = $services->getNewOrderId('hb');
        $data['lottery_id'] = $prize['lottery_id'];
        $data['prize_id'] = $prize['id'];
        $data['type'] = $prize['type'];
        $data['prize_info'] = json_encode($prize);
        $data['add_time'] = time();
        if (!$res = $this->dao->save($data)) {
            throw new ValidateException('写入中奖记录失败');
        }
        return $res;
    }

    /**
     * 领取奖品
     * @param int $uid
     * @param int $lottery_record_id
     * @param string $receive_info
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function receivePrize(int $uid, int $lottery_record_id, array $receive_info = [], string $channel_type = '')
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserCacheInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('用户不存在');
        }
        $lotteryRecord = $this->dao->get($lottery_record_id, ['prize_id', 'lottery_id', 'id', 'is_receive', 'order_id'], ['prize']);
        if (!$lotteryRecord || !isset($lotteryRecord['prize'])) {
            throw new ValidateException('请继续参与活动抽奖');
        }
        if ($lotteryRecord['is_receive'] == 1) {
            throw new ValidateException('已经领取成功');
        }
        $data = ['is_receive' => 1, 'receive_time' => time(), 'receive_info' => $receive_info];
        if (isset($lotteryRecord['prize_info']) && $lotteryRecord['prize_info']) {
            $prize = json_decode($lotteryRecord['prize_info'], true);
        } else {
            $prize = $lotteryRecord['prize'];
        }
        $this->transaction(function () use ($uid, $userInfo, $lottery_record_id, $data, $prize, $userServices, $receive_info, $channel_type, $lotteryRecord) {
            //奖品类型1：未中奖2：积分3:余额4：红包5:优惠券6：站内商品7：等级经验8：用户等级 9：svip天数
            switch ($prize['type']) {
                case 1:
                case 7:
                case 8:
                case 9:
                    break;
                case 2:
                    /** @var UserBillServices $userBillServices */
                    $userBillServices = app()->make(UserBillServices::class);
                    $integral = bcadd((string)$userInfo['integral'], (string)$prize['num'], 0);
                    $userBillServices->income('lottery_give_integral', $uid, (int)$prize['num'], (int)$integral, $prize['id']);
                    $userServices->update($uid, ['integral' => $integral], 'uid');
                    break;
                case 3:
                    /** @var UserMoneyServices $userMoneyServices */
                    $userMoneyServices = app()->make(UserMoneyServices::class);
                    $now_money = bcadd((string)$userInfo['now_money'], (string)$prize['num'], 2);
                    $userMoneyServices->income('lottery_give_money', $uid, $prize['num'], $now_money, $prize['id']);
                    $userServices->update($uid, ['now_money' => $now_money], 'uid');
                    break;
                case 4:
                    $wechat_order_id = $lotteryRecord['order_id'];
                    $transferDetailList = [
                        [
                            'info_type' => '活动名称',
                            'info_content' => '抽奖红包'
                        ],
                        [
                            'info_type' => '奖励说明',
                            'info_content' => '抽奖红包'
                        ],
                    ];
                    $userName = $userInfo['real_name'] ?? '';
                    $res =  Payment::merchantPay($channel_type, $uid, $wechat_order_id, (string)$prize['num'], '抽奖中奖红包', $userName, $transferDetailList);
                    if (sys_config('pay_wechat_type') == 1) {
                        $data = array_merge($data, [
                            'transfer_bill_no' => $res['transfer_bill_no'] ?? '',
                            'wechat_state' => $res['state'] ?? '',
                            'fail_reason' => $res['fail_reason'] ?? '',
                            'package_info' => $res['package_info'] ?? ''
                        ]);
                        event('notice.notice', [[
                            'uid' => $uid,
                            'extract_price' => $prize['num'],
                            'type' => 2,
                            'order_id' => $wechat_order_id
                        ], 'revenue_received']);
                    }
                    //记录资金流水
                    CapitalFlowJob::dispatch([['order_id' => $wechat_order_id, 'store_id' => 0, 'uid' => $uid, 'price' => $prize['num'], 'pay_type' => 'weixin', 'nickname' => $userInfo['nickname'], 'phone' => $userInfo['phone']], 'luck']);
                    break;
                case 5:
                    /** @var StoreCouponIssueServices $couponIssueService */
                    $couponIssueService = app()->make(StoreCouponIssueServices::class);
                    try {
                        $couponIssueService->issueUserCoupon($uid, (int)$prize['coupon_id'], true, 'luck_lottery');
                    } catch (\Throwable $e) {
                        Log::error('抽奖领取优惠券失败，原因：' . $e->getMessage());
                    }
                    break;
                case 6:
                    if (!$receive_info['name'] || !$receive_info['phone'] || !$receive_info['address']) {
                        throw new ValidateException('请输入收货人信息');
                    }
                    if (!check_phone($receive_info['phone'])) {
                        throw new ValidateException('请输入正确的收货人电话');
                    }
                    break;
            }
            $this->dao->update($lottery_record_id, $data, 'id');
        });
        return true;
    }

    /**
     * 发货、备注
     * @param int $lottery_record_id
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setDeliver(int $lottery_record_id, array $data)
    {
        $lotteryRecord = $this->dao->get($lottery_record_id, ['deliver_info', 'type', 'id']);
        if (!$lotteryRecord) {
            throw new ValidateException('抽奖记录不存在');
        }
        $deliver_info = $lotteryRecord['deliver_info'];
        $edit = [];
        //备注
        if (!$data['deliver_name'] && !$data['deliver_number']) {
            $deliver_info['mark'] = $data['mark'];
        } else {
            if ($lotteryRecord['type'] != 6 && ($data['deliver_name'] || $data['deliver_number'])) {
                throw new ValidateException('该奖品不需要发货');
            }
            if ($lotteryRecord['type'] == 6 && (!$data['deliver_name'] || !$data['deliver_number'])) {
                throw new ValidateException('请选择快递公司或输入快递单号');
            }
            $deliver_info['deliver_name'] = $data['deliver_name'];
            $deliver_info['deliver_number'] = $data['deliver_number'];
            $edit['is_deliver'] = 1;
            $edit['deliver_time'] = time();
        }
        $edit['deliver_info'] = $deliver_info;
        if (!$this->dao->update($lottery_record_id, $edit, 'id')) {
            throw new ValidateException('处理失败');
        }
        return true;
    }

    /**
     * 获取中奖记录
     * @param int $uid
     * @return array
     */
    public function getRecord(int $uid, $where = [])
    {
        if (!$where) {
            $where['uid'] = $uid;
            $where['not_type'] = 1;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', ['prize'], $page, $limit);
        foreach ($list as &$item) {
            if (isset($item['prize_info']) && $item['prize_info']) {
                $prize = $item['prize_info'] = json_decode($item['prize_info'], true);
            } else {
                $prize = $item['prize'];
            }
            $item['prize'] = $prize;
            $item['deliver_time'] = $item['deliver_time'] ? date('Y-m-d H:i:s', $item['deliver_time']) : '';
            $item['receive_time'] = $item['receive_time'] ? date('Y-m-d H:i:s', $item['receive_time']) : '';
        }
        return $list;
    }

    /**
     * 获取抽奖次数
     * @param int $uid
     * @param int $lottery_id
     * @return array
     */
    public function getLotteryNum(int $uid, int $lottery_id)
    {
        $where['uid'] = $uid;
        $where['lottery_id'] = $lottery_id;
        $now_day = strtotime(date('Y-m-d'));//今日
        $todayCount = $this->dao->getCount($where + ['add_time' => $now_day]);
        $totalCount = $this->dao->getCount($where);
        return compact('todayCount', 'totalCount');
    }
}
