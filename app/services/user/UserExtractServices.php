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

use app\jobs\system\CapitalFlowJob;
use app\services\BaseServices;
use app\dao\user\UserExtractDao;
use app\services\order\StoreOrderCreateServices;
use app\services\wechat\WechatUserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use crmeb\services\AliPayService;
use crmeb\services\wechat\Payment;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 *
 * Class UserExtractServices
 * @package app\services\user
 * @mixin UserExtractDao
 */
class UserExtractServices extends BaseServices
{
    public array $wechatState = ['ACCEPTED' => '转账已受理', 'PROCESSING' => '余额不足', 'WAIT_USER_CONFIRM' => '待收款用户确认', 'TRANSFERING' => '转账中', 'SUCCESS' => '转账成功', 'FAIL' => '转账失败', 'CANCELING' => '商户撤销请求受理成功', 'CANCELLED' => '转账撤销完成'];

    /**
     * @var UserExtractDao
     */
    #[Inject]
    protected UserExtractDao $dao;

    /**
     * 获取一条提现记录
     * @param int $id
     * @param array $field
     * @return array|\think\Model|null
     */
    public function getExtract(int $id, array $field = [])
    {
        return $this->dao->get($id, $field);
    }

    /**
     * 获取某个用户提现总数
     * @param int $uid
     * @return float
     */
    public function getUserExtract(int $uid)
    {
        return $this->dao->getWhereSum(['uid' => $uid, 'status' => [0, 1]]);
    }

    /**
     * 获取某些用户的提现总数列表
     * @param array $uids
     */
    public function getUsersSumList(array $uids)
    {
        return $this->dao->getWhereSumList(['uid' => $uids, 'status' => [0, 1]]);
    }

    public function getCount(array $where = [])
    {
        return $this->dao->getCount($where);
    }

    /**
     * 获取提现列表
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserExtractList(array $where, string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getExtractList($where, $field, $page, $limit);
        foreach ($list as &$item) {
            $item['nickname'] = $item['user']['nickname'] ?? '';
            $item['wechat_state_text'] = $this->wechatState[$item['wechat_state']] ?? '';
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取提现总数
     * @param array $where
     */
    public function getExtractSum(array $where)
    {
        //提现金额 + 手续费
        $price = $this->dao->getExtractMoneyByWhere($where, 'extract_price');
        $fee = $this->dao->getExtractMoneyByWhere($where, 'extract_fee');
        return (float)bcadd((string)$price, (string)$fee, 2);
    }

    /**
     * 拒绝提现申请
     * @param $id
     * @param $fail_msg
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeFail(int $id, $userExtract, $message)
    {
        $fail_time = time();
        $extract_number = bcadd((string)$userExtract['extract_price'], (string)$userExtract['extract_fee'], 2);
        $mark = '提现失败,退回佣金' . $extract_number . '元';
        $uid = $userExtract['uid'];
        $status = -1;
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $this->transaction(function () use ($user, $userBrokerageServices, $uid, $id, $extract_number, $message, $userServices, $status, $fail_time) {
            $now_brokerage = bcadd((string)$user['brokerage_price'], (string)$extract_number, 2);
            //增加佣金记录
            $userBrokerageServices->income('extract_fail', $uid, $extract_number, $now_brokerage, $id);
            //修改用户佣金
            if (!$userServices->update($uid, ['brokerage_price' => $now_brokerage], 'uid'))
                throw new AdminException('增加用户佣金失败');
            if (!$this->dao->update($id, ['fail_time' => $fail_time, 'fail_msg' => $message, 'status' => $status])) {
                throw new AdminException('修改失败');
            }
        });
        //消息推送
        event('notice.notice', [['uid' => $uid, 'userType' => strtolower($user['user_type']), 'extract_number' => $extract_number, 'nickname' => $user['nickname'], 'message' => $message], 'user_balance_change']);
        return true;
    }

    /**
     * 通过提现申请
     * @param $id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeSuccess(int $id, $userExtract)
    {
        $extractNumber = $userExtract['extract_price'];
        $order_id = $this->transaction(function () use ($id, $userExtract, $extractNumber) {
            if (!$this->dao->update($id, ['status' => 1])) {
                throw new AdminException('修改失败');
            }
            switch ($userExtract['extract_type']) {
                case 'bank':
                    $order_id = $userExtract['bank_code'];
                    break;
                case 'weixin':
                    $order_id = $userExtract['wechat'];
                    $this->processWechatExtract($userExtract, $extractNumber);
                    break;
                case 'alipay':
                    $order_id = $userExtract['alipay_code'];
                    $this->processAlipayExtract($userExtract, $extractNumber);
                    break;
                case 'balance'://用户余额账号
                    /** @var UserRechargeServices $userRechargeServices */
                    $userRechargeServices = app()->make(UserRechargeServices::class);
                    $userRechargeServices->importNowMoney((int)$userExtract['uid'], $extractNumber, 'extract');
                    $order_id = $userExtract['id'];
                    break;
                default:
                    $order_id = '';
                    break;
            }
            return $order_id;
        });
        if ($userExtract['extract_type'] == 'weixin' && sys_config('pay_wechat_type') == 1) {
            return true;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userType = $userServices->value(['uid' => $userExtract['uid']], 'user_type');
        $nickname = $userServices->value(['uid' => $userExtract['uid']], 'nickname');
        $phone = $userServices->value(['uid' => $userExtract['uid']], 'phone');

        //记录资金流水队列
        CapitalFlowJob::dispatch([['order_id' => $order_id, 'store_id' => 0, 'uid' => $userExtract['uid'], 'nickname' => $nickname, 'phone' => $phone, 'price' => $extractNumber, 'pay_type' => $userExtract['extract_type']], 'extract']);

        //消息推送
        event('notice.notice', [['uid' => $userExtract['uid'], 'userType' => strtolower($userType), 'extractNumber' => $extractNumber, 'nickname' => $nickname], 'user_extract']);
        return true;
    }

    protected function processWechatExtract($userExtract, $extractNumber)
    {
        if (sys_config('brokerage_type', 0)) {
            /** @var StoreOrderCreateServices $services */
            $services = app()->make(StoreOrderCreateServices::class);
            $wechat_order_id = $userExtract['order_id'] ?: $services->getNewOrderId();
            $transferDetailList = [
                [
                    'info_type' => '活动名称',
                    'info_content' => '分销员'
                ],
                [
                    'info_type' => '奖励说明',
                    'info_content' => '佣金提现'
                ],
            ];
            $res = Payment::merchantPay($userExtract['channel_type'], $userExtract['uid'], $wechat_order_id, $extractNumber, '提现佣金到零钱', $userExtract['user_name'], $transferDetailList);
            if (!$res) {
                throw new ValidateException('企业付款到零钱失败，请稍后再试');
            }
            if (sys_config('pay_wechat_type') == 1) {
                $updateData = [
                    'transfer_bill_no' => $res['transfer_bill_no'] ?? '',
                    'wechat_state' => $res['state'] ?? '',
                    'fail_reason' => $res['fail_reason'] ?? '',
                    'package_info' => $res['package_info'] ?? ''
                ];
                $this->dao->update($userExtract['id'], $updateData);
                event('notice.notice', [[
                    'uid' => $userExtract['uid'],
                    'extract_price' => $userExtract['extract_price'],
                    'type' => 1,
                    'order_id' => $userExtract['order_id']
                ], 'revenue_received']);
            }
        }
        return true;
    }

    protected function processAlipayExtract($userExtract, $extractNumber)
    {
        if (sys_config('alipay_extract_type', 0)) {
            // 构造支付宝提现参数
            $bizParams = [
                'out_biz_no' => $userExtract['alipay_code'], // 商户订单号
                'payee_type' => 'ALIPAY_LOGONID', // 收款方账户类型，ALIPAY_LOGONID-支付宝登录账号
                'payee_account' => $userExtract['alipay_code'], // 收款方账户，实名认证的支付宝账号
                'amount' => $extractNumber, // 提现金额
                'payer_show_name' => sys_config('site_name'), // 付款方姓名/个人名称
                'payee_real_name' => $userExtract['real_name'], // 收款方真实姓名/个人名称
                'remark' => '提现 ¥' . $extractNumber . ' 到支付宝', // 业务备注
            ];
            // 调用支付宝服务发起支付宝提现请求
            $res = AliPayService::instance()->merchantPay($bizParams);
            // 如果支付宝提现请求失败，则抛出异常
            if (!$res) {
                throw new ValidateException('企业付款到零钱失败，请稍后再试');
            }
        }
        return true;
    }


    /**
     * 显示资源列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(array $where)
    {
        $list = $this->getUserExtractList($where);
        //待提现金额
        $where['status'] = 0;
        $extract_statistics['price'] = $this->getExtractSum($where);
        //已提现金额
        $where['status'] = 1;
        $extract_statistics['priced'] = $this->getExtractSum($where);
        //佣金总金额
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $extract_statistics['brokerage_count'] = $userBrokerageServices->getUsersBokerageSum(array_merge($where, ['pm' => 1, 'not_type' => ['extract_fail', 'refund']]));
        //未提现金额
        $extract_statistics['brokerage_not'] = $extract_statistics['brokerage_count'] > $extract_statistics['priced'] ? bcsub((string)$extract_statistics['brokerage_count'], (string)$extract_statistics['priced'], 2) : 0.00;
        return compact('extract_statistics', 'list');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit(int $id)
    {
        $UserExtract = $this->getExtract($id);
        if (!$UserExtract) {
            throw new AdminException('数据不存在!');
        }
        $f = array();
        $f[] = Form::input('real_name', '姓名', $UserExtract['real_name']);
        $f[] = Form::number('extract_price', '提现金额', (float)$UserExtract['extract_price'])->disabled(true);
        if ($UserExtract['extract_type'] == 'alipay') {
            $f[] = Form::input('alipay_code', '支付宝账号', $UserExtract['alipay_code']);
        } else if ($UserExtract['extract_type'] == 'weixin') {
            $f[] = Form::input('wechat', '微信号', $UserExtract['wechat']);
        } else {
            $f[] = Form::input('bank_code', '银行卡号', $UserExtract['bank_code']);
            $f[] = Form::input('bank_address', '开户行', $UserExtract['bank_address']);
        }
        $f[] = Form::input('mark', '备注', $UserExtract['mark'])->type('textarea');
        return create_form('编辑', $f, Url::buildUrl('/finance/extract/' . $id), 'PUT');
    }

    public function remarkForm($id)
    {
        $UserExtract = $this->getExtract($id);
        if (!$UserExtract) {
            throw new AdminException('数据不存在!');
        }
        $f = array();
        $f[] = Form::input('mark', '备注', $UserExtract['mark'])->type('textarea');
        return create_form('编辑', $f, Url::buildUrl('/finance/extract/remark/' . $id), 'PUT');
    }

    public function update(int $id, array $data)
    {
        if (!$this->dao->update($id, $data))
            throw new AdminException('修改失败');
        else
            return true;
    }

    /**
     * 拒绝
     * @param $id
     * @return mixed
     */
    public function refuse(int $id, string $message)
    {
        $extract = $this->getExtract($id);
        if (!$extract) {
            throw new AdminException('操作记录不存在!');
        }
        if ($extract->status == 1) {
            throw new AdminException('已经提现,错误操作');
        }
        if ($extract->status == -1) {
            throw new AdminException('您的提现申请已被拒绝,请勿重复操作!');
        }
        $res = $this->changeFail($id, $extract, $message);
        if ($res) {
            return true;
        } else {
            throw new AdminException('操作失败!');
        }
    }

    /**
     * 通过
     * @param $id
     * @return mixed
     */
    public function adopt(int $id)
    {
        $extract = $this->getExtract($id);
        if (!$extract) {
            throw new AdminException('操作记录不存!');
        }
        if ($extract->status == 1) {
            throw new AdminException('您已提现,请勿重复提现!');
        }
        if ($extract->status == -1) {
            throw new AdminException('您的提现申请已被拒绝!');
        }
        if ($this->changeSuccess($id, $extract)) {
            return true;
        } else {
            throw new AdminException('操作失败!');
        }
    }

    /**待提现的数量
     * @return int
     */
    public function userExtractCount()
    {
        return $this->dao->count(['status' => 0]);
    }

    /**
     * 银行卡提现
     * @param int $uid
     * @return mixed
     */
    public function bank(int $uid)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $user = $userService->getUserInfo($uid, 'brokerage_price');
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $data['broken_commission'] = $userBrokerageServices->getUserFrozenPrice($uid);
        if ($data['broken_commission'] < 0)
            $data['broken_commission'] = '0';
        $data['brokerage_price'] = $user['brokerage_price'];
        //可提现佣金
        $data['commissionCount'] = bcsub((string)$data['brokerage_price'], (string)$data['broken_commission'], 2);
        $extractBank = sys_config('user_extract_bank') ?? []; //提现银行
        $extractBank = str_replace("\r\n", "\n", $extractBank);//防止不兼容
        $data['extractBank'] = explode("\n", is_array($extractBank) ? (isset($extractBank[0]) ? $extractBank[0] : $extractBank) : $extractBank);
        $data['minPrice'] = sys_config('user_extract_min_price');//提现最低金额
        $data['maxPrice'] = sys_config('user_extract_max_price');//提现最高金额
        $data['withdraw_fee'] = sys_config('withdraw_fee');//提现手续费
        $data['extract_wechat_type'] = sys_config('brokerage_type');//微信提现到账方式
        $data['user_extract_balance_status'] = sys_config('user_extract_balance_status', 1);
        return $data;
    }

    /**
     * 提现申请
     * @param int $uid
     * @param array $data
     */
    public function cash(int $uid, array $data)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $user = $userService->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $data['broken_commission'] = $userBrokerageServices->getUserFrozenPrice($uid);
        if ($data['broken_commission'] < 0)
            $data['broken_commission'] = 0;
        $data['brokerage_price'] = $user['brokerage_price'];
        //可提现佣金
        $commissionCount = (float)bcsub((string)$data['brokerage_price'], (string)$data['broken_commission'], 2);
        if ($data['money'] > $commissionCount) {
            throw new ValidateException('可提现佣金不足');
        }

        $extractPrice = $user['brokerage_price'];
        $userExtractMinPrice = sys_config('user_extract_min_price');
        if ($data['money'] < $userExtractMinPrice) {
            throw new ValidateException('提现金额不能小于' . $userExtractMinPrice . '元');
        }
        $userExtractMaxPrice = sys_config('user_extract_max_price');
        if ($data['money'] > $userExtractMaxPrice) {
            throw new ValidateException('提现金额不能大于' . $userExtractMaxPrice . '元');
        }
        if ($extractPrice < 0) {
            throw new ValidateException('提现佣金不足' . $data['money']);
        }
        if ($data['money'] > $extractPrice) {
            throw new ValidateException('提现佣金不足' . $data['money']);
        }
        if ($data['money'] <= 0) {
            throw new ValidateException('提现佣金大于0');
        }
        $extract_fee = bcmul((string)$data['money'], bcdiv(sys_config('withdraw_fee'), '100', 4), 2);
        if ($data['extract_type'] == 'balance') {//提现至余额不要手续费
            $extract_fee = 0;
        }
        $insertData = [
            'uid' => $user['uid'],
            'order_id' => app()->make(StoreOrderCreateServices::class)->getNewOrderId('tx'),
            'extract_type' => $data['extract_type'],
            'extract_price' => bcsub((string)$data['money'], (string)$extract_fee, 2),
            'extract_fee' => $extract_fee,
            'add_time' => time(),
            'balance' => $user['brokerage_price'],
            'status' => 0,
            'channel_type' => $data['channel_type']
        ];
        if (isset($data['name']) && strlen(trim($data['name']))) $insertData['real_name'] = $data['name'];
        else $insertData['real_name'] = $user['nickname'];
        if (isset($data['cardnum'])) $insertData['bank_code'] = $data['cardnum'];
        else $insertData['bank_code'] = '';
        if (isset($data['bankname'])) $insertData['bank_address'] = $data['bankname'];
        else $insertData['bank_address'] = '';
        if (isset($data['weixin'])) $insertData['wechat'] = $data['weixin'];
        else $insertData['wechat'] = $user['nickname'];
        if ($data['extract_type'] == 'alipay') {
            $insertData['alipay_code'] = $data['alipay_code'];
            $insertData['qrcode_url'] = $data['qrcode_url'];
            $mark = '使用支付宝';
        } else if ($data['extract_type'] == 'bank') {
            $mark = '使用银联卡' . $insertData['bank_code'];
        } else if ($data['extract_type'] == 'weixin') {
            $insertData['qrcode_url'] = $data['qrcode_url'];
            $mark = '使用微信提现';
            /** @var WechatUserServices $wechatServices */
            $wechatServices = app()->make(WechatUserServices::class);
            $openid = $wechatServices->getWechatOpenid($uid, 'wechat');
            if (sys_config('brokerage_type', 0) && $openid) {
                if ($insertData['extract_price'] < 0.01) {
                    throw new ValidateException('扣除手续费后，提现金额不足1元；而企业微信付款到零钱最低金额为0.01元');
                }
            }
        } elseif ($data['extract_type'] == 'balance') {
            $mark = '提现至余额账号';
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $res1 = $this->transaction(function () use ($insertData, $data, $uid, $userService, $user, $userBrokerageServices, $mark) {
            if (!$res1 = $this->dao->save($insertData)) {
                throw new ValidateException('提现失败');
            }
            //修改用户佣金
            $balance = bcsub((string)$user['brokerage_price'], (string)$data['money'], 2) ?? 0;
            if (!$userService->update($uid, ['brokerage_price' => $balance], 'uid')) {
                throw new ValidateException('修改用户信息失败');
            }
            //保存佣金记录
            $userBrokerageServices->income('extract', $uid, ['mark' => $mark, 'number' => $data['money']], $balance, $res1['id']);

            //提现值余额 不用审核，直接通过
            if ($data['extract_type'] == 'balance') {
                $this->adopt((int)$res1['id']);
            }
            return $res1;
        });
        //用户申请体现事件
        event('user.extract', [$user, $data, $res1]);
        return true;
    }

    /**
     * @param array $where
     * @param string $SumField
     * @param string $selectType
     * @param string $group
     * @return float|mixed
     */
    public function getOutMoneyByWhere(array $where, string $SumField, string $selectType, string $group = "")
    {
        switch ($selectType) {
            case "sum" :
                return $this->dao->getWhereSumField($where, $SumField);
            case "group" :
                return $this->dao->getGroupField($where, $SumField, $group);
        }
    }
}
