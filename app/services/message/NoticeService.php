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

namespace app\services\message;


use app\jobs\notice\PrintJob;
use app\services\BaseServices;
use app\services\message\notice\EnterpriseWechatService;
use app\services\message\notice\NoticeSmsService;
use app\services\message\notice\RoutineTemplateListService;
use app\services\message\notice\SystemMsgService;
use app\services\message\notice\WechatTemplateListService;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderRefundServices;
use app\services\system\config\ConfigServices;
use app\services\system\PrintDocumentServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use crmeb\services\printer\Printer;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\helper\Str;
use Throwable;

/**
 * 消息发送
 * Class NoticeService
 * @author 等风来
 * @email 136327134@qq.com
 * @date 2023/9/20
 * @package app\services\message
 */
class NoticeService extends BaseServices
{

    /**
     * 事件名称
     * @var array|string[]
     */
    protected array $eventName = [
        'bind_spread_uid' => '绑定推广关系通知',
        'order_pay_success' => '支付成功给用户通知',
        'order_fictitious_success' => '发货给用户通知',
        'order_deliver_success' => '发货给用户通知',
        'order_postage_success' => '发货快递给用户通知',
        'order_takever' => '确认收货给用户通知',
        'price_revision' => '改价给用户通知',
        'order_refund' => '退款成功通知',
        'send_order_refund_no_status' => '退款未通过通知',
        'recharge_success' => '充值余额通知',
        'recharge_order_refund_status' => '充值退款通知',
        'integral_accout' => '积分通知',
        'order_brokerage' => '佣金通知',
        'bargain_success' => '砍价成功通知',
        'order_user_groups_success' => '拼团成功通知',
        'send_order_pink_clone' => '取消拼团通知',
        'send_order_pink_fial' => '拼团失败通知',
        'can_pink_success' => '参团成功通知',
        'open_pink_success' => '开团成功通知',
        'user_extract' => '提现成功通知',
        'user_balance_change' => '提现失败通知',
        'order_pay_false' => '提醒付款给用户通知',
        'send_order_apply_refund' => '申请退款给客服通知',
        'admin_pay_success_code' => '新订单给客服通知',
        'kefu_send_extract_application' => '提现申请给客服通知',
        'send_admin_confirm_take_over' => '确认收货给客服通知',
        'login_city_error' => '异地登录通知',
        'kami_deliver_goods_code' => '虚拟商品发货通知',
        'reminder_verification_status' => '次卡订单商品核销成功通知',
        'expiration_reminder' => '次卡订单商品过期通知',
        'reminder_brink_death' => '次卡订单商品临期通知',
        'supplier_verify_success' => '供应商入驻审核通过通知',
        'supplier_verify_fail' => '供应商入驻审核未通过通知',
        'sign_remind_time' => '用户签到通知',
        'revenue_received' => '收益到账通知',
    ];

    /**
     * @var array
     */
    protected array $config = [];

    /**
     * 事件类型
     * @var string
     */
    protected string $event;

    /**
     * @var bool
     */
    protected bool $logger = true;

    /**
     * NoticeService constructor.
     */
    public function __construct()
    {
        $this->logger = !!config('log.close');
    }

    /**
     * 缓存消息体
     * @param string $event
     * @return $this
     * @throws Throwable
     */
    public function setEvent(string $event)
    {

        $this->event = $event;

        $config = CacheService::redisHandler('NOTCEINFO')->remember('NOTCE_' . $event, function () use ($event) {
            /** @var SystemNotificationServices $services */
            $services = app()->make(SystemNotificationServices::class);
            $noticeInfo = $services->getOneNotce(['mark' => $event]);
            if ($noticeInfo) {
                return $noticeInfo->toArray();
            } else {
                return [];
            }
        });

        if (is_array($config)) {
            $this->config = $config;
        } else {
            $this->config = [];
        }

        return $this;
    }

    /**
     * 执行消息发送
     * @param array $data
     * @return mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    public function handle(array $data)
    {
        $method = Str::camel($this->event);
        if (method_exists($this, $method)) {
            return $this->{$method}($data);
        } else {
            response_log_write([
                'message' => '发送消息错误未知消息方法:' . __CLASS__ . '::' . $method . '()'
            ]);
        }
    }

    /**
     * 绑定推广关系
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function bindSpreadUid(array $data)
    {
        if (isset($data['spreadUid']) && $data['spreadUid']) {
            $name = $data['nickname'] ?? '';
            //站内信
            $this->system()->sendMsg($data['spreadUid'], ['nickname' => $name]);
            //模板消息小程序订阅消息
            $this->routine()->sendBindSpreadUidSuccess($data['spreadUid'], $name);
        }
    }

    /**
     * 支付成功给用户
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderPaySuccess(array $data)
    {
        $pay_price = $data['pay_price'] ?? 0.00;
        $order_id = $data['order_id'] ?? '';
        //小票打印 排除付费会员订单
        if (isset($data['cart_id']) && $data['cart_id']) {
            $this->orderPrint($data);
        }
        //短信
        $this->sms()->sendSms($data['user_phone'], compact('order_id', 'pay_price'), 'PAY_SUCCESS_CODE');

        $data['total_num'] = $data['total_num'] ?? 1;
        //站内信
        $this->system()->sendMsg($data['uid'], ['order_id' => $data['order_id'], 'total_num' => $data['total_num'], 'pay_price' => $data['pay_price']]);

        $link = '/pages/goods/order_details/index?order_id=' . $order_id;
        if (isset($data['is_vip_order']) && $data['is_vip_order'] == 1) {
            $link = '/pages/annex/vip_paid/index';
        }
        //模板消息公众号模版消息
        $this->wechat()->sendOrderPaySuccess($data['uid'], $data, $link);
        //模板消息小程序订阅消息
        $this->routine()->sendOrderSuccess($data['uid'], $pay_price, $data['order_id']);
    }

    public function revenueReceived(array $data)
    {
        $msg = '您有待收款金额，点击收款';
        $link = '/pages/users/user_spread_money/receiving?order_id=' . $data['order_id'] . '&type=' . $data['type'];
        //模板消息公众号模版消息
        $this->wechat()->sendRevenueReceivedSuccess($data['uid'], $data['extract_price'], $msg, $link);
        //模板消息小程序订阅消息
        $this->routine()->sendRevenueReceived($data['uid'], $data['extract_price'], $msg, $link);
    }

    /**
     * 发货给用户
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderFictitiousSuccess(array $data)
    {
        $orderInfo = $data['orderInfo'];
        $store_name = $data['storeName'];
        $datas = $data['data'];
        $order_id = $orderInfo['order_id'];
        $nickname = app()->make(UserServices::class)->value(['uid' => $orderInfo['uid']], 'nickname');

        //短信
        $this->sms()->sendSms($orderInfo['user_phone'], compact('order_id', 'store_name', 'nickname'), 'ORDER_DELIVER_SUCCESS');
        //站内信
        $this->system()->sendMsg($orderInfo['uid'], [
            'nickname' => $nickname,
            'store_name' => $store_name,
            'order_id' => $orderInfo['order_id'],
            'delivery_name' => $datas['delivery_name'] ?? '',
            'delivery_id' => $datas['delivery_id'] ?? '',
            'user_address' => $orderInfo['user_address']
        ]);
    }

    /**
     * 发货快递给用户
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderPostageSuccess(array $data)
    {
        $orderInfo = $data['orderInfo'];
        $store_name = $data['storeName'];
        $datas = $data['data'];
        $service = app()->make(UserServices::class);
        $nickname = $service->value(['uid' => $orderInfo['uid']], 'nickname');
        $order_id = $orderInfo['order_id'];
        //短信
        $this->sms()->sendSms($orderInfo['user_phone'], compact('order_id', 'store_name', 'nickname'), 'ORDER_DELIVER_SUCCESS');
        //站内信
        $smsdata = [
            'nickname' => $nickname,
            'store_name' => $store_name,
            'order_id' => $orderInfo['order_id'],
            'delivery_name' => $datas['delivery_name'] ?? '',
            'delivery_id' => $datas['delivery_id'] ?? '',
            'user_address' => $orderInfo['user_address']
        ];
        $this->system()->sendMsg($orderInfo['uid'], $smsdata);
        //模板消息公众号模版消息
        $this->wechat()->sendOrderPostage($orderInfo['uid'], $orderInfo, $datas, $store_name);
        //模板消息小程序订阅消息
        $this->routine()->sendOrderPostage($orderInfo['uid'], $orderInfo, $store_name, $datas, 1);
    }

    /**
     * 发货给用户
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderDeliverSuccess(array $data)
    {
        $orderInfo = $data['orderInfo'];
        $store_name = $data['storeName'];
        $datas = $data['data'];
        $service = app()->make(UserServices::class);
        $nickname = $service->value(['uid' => $orderInfo['uid']], 'nickname');
        //短信
        $order_id = $orderInfo['order_id'];
        $this->sms()->sendSms($orderInfo['user_phone'], compact('order_id', 'store_name', 'nickname'), 'ORDER_DELIVER_SUCCESS');
        $isGive = 0;
        //站内信
        $this->system()->sendMsg($orderInfo['uid'], [
            'nickname' => $nickname,
            'store_name' => $store_name,
            'order_id' => $orderInfo['order_id'],
            'delivery_name' => $datas['delivery_name'] ?? '',
            'delivery_id' => $datas['delivery_id'] ?? '',
            'user_address' => $orderInfo['user_address']
        ]);
        //模板消息公众号模版消息
//        $this->wechat()->sendOrderDeliver($orderInfo['uid'], $store_name, $orderInfo, $datas);
        //模板消息小程序订阅消息
        $this->routine()->sendOrderPostage($orderInfo['uid'], $orderInfo, $store_name, $datas, $isGive);
    }

    /**
     * 确认收货给用户
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderTakever(array $data)
    {
        $order = is_object($data['order']) ? $data['order']->toArray() : $data['order'];
        //模板变量
        $store_name = substrUTf8($data['storeTitle'], 20, 'UTF-8', '');
        $order_id = $order['order_id'];
        $this->sms()->sendSms($order['user_phone'], compact('store_name', 'order_id'), 'TAKE_DELIVERY_CODE');
        //站内信
        $this->system()->sendMsg($order['uid'], ['order_id' => $order['order_id'], 'store_name' => $store_name]);
        //模板消息公众号模版消息
        $this->wechat()->sendOrderTakeSuccess($order['uid'], $order, $store_name);
        //模板消息小程序订阅消息
        $this->routine()->sendOrderTakeOver($order['uid'], $order, $store_name);
    }

    /**
     * 改价给用户
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function priceRevision(array $data)
    {
        $order = $data['order'];
        $pay_price = $data['pay_price'];
        $order['storeName'] = app()->make(StoreOrderCartInfoServices::class)->getCarIdByProductTitle((int)$order['id']);
        //短信
        $this->sms()->sendSms($order['user_phone'], ['order_id' => $order['order_id'], 'pay_price' => $pay_price], 'PRICE_REVISION');
        //站内信
        $this->system()->sendMsg($order['uid'], ['order_id' => $order['order_id'], 'pay_price' => $pay_price]);
    }

    /**
     * 退款成功
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderRefund(array $data)
    {
        $datas = $data['data'];
        $order = $data['order'];
        $storeName = app()->make(StoreOrderCartInfoServices::class)->getCarIdByProductTitle((int)$order['id']);
        $storeTitle = substrUTf8($storeName, 20, 'UTF-8', '');
        //站内信
        $this->system()->sendMsg($order['uid'], ['order_id' => $order['order_id'], 'pay_price' => $order['pay_price'], 'refund_price' => $datas['refund_price']]);

        //短信
        $this->sms()->sendSms($order['user_phone'], ['order_id' => $order['order_id'], 'refund_price' => $datas['refund_price']], 'ORDER_REFUND_STATUS');

        /** @var StoreOrderRefundServices $serviceRefun */
        $serviceRefun = app()->make(StoreOrderRefundServices::class);
        $order['order_id'] = $serviceRefun->value(['uid' => $order['uid'], 'store_order_id' => $order['id']], 'order_id');
        //模板消息公众号模版消息
        $this->wechat()->sendOrderRefundSuccess($order['uid'], $datas, $order);
        //模板消息小程序订阅消息
        $this->routine()->sendOrderRefundSuccess($order['uid'], $order, $storeTitle);
    }

    /**
     * 退款未通过
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    public function sendOrderRefundNoStatus(array $data)
    {
        $order = $data['orderInfo'];
        $storeName = app()->make(StoreOrderCartInfoServices::class)->getCarIdByProductTitle((int)$order['id']);
        $storeTitle = substrUTf8($storeName, 20, 'UTF-8', '');
        //站内信
        $this->system()->sendMsg($order['uid'], [
            'order_id' => $order['order_id'],
            'pay_price' => $order['pay_price'],
            'store_name' => $storeTitle
        ]);
        //短信
        $this->sms()->sendSms($order['user_phone'], ['order_id' => $order['order_id']], 'SEND_ORDER_REFUND_NO_STATUS');
        //模板消息公众号模版消息
        $this->wechat()->sendOrderRefundNoStatus($order['uid'], $order);
        //模板消息小程序订阅消息
        $this->routine()->sendOrderRefundFail($order['uid'], $order, $storeTitle);
    }

    /**
     * 充值余额
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function rechargeSuccess(array $data)
    {
        $order = $data['order'];
        $now_money = $data['now_money'];
        //短信
        $this->sms()->sendSms($order['phone'], ['price' => $order['price'], 'now_money' => $now_money], 'RECHARGE_SUCCESS');
        //站内信
        $this->system()->sendMsg($order['uid'], ['order_id' => $order['order_id'], 'price' => $order['price'], 'now_money' => $now_money]);
        //模板消息公众号模版消息
        $this->wechat()->sendRechargeSuccess($order['uid'], $order);
        //模板消息小程序订阅消息
        $this->routine()->sendRechargeSuccess($order['uid'], $order, $now_money);
    }

    /**
     * 充值退款
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function rechargeOrderRefundStatus(array $data)
    {
        $datas = $data['data'];
        $UserRecharge = $data['UserRecharge'];
        $now_money = $data['now_money'];
        //短信
        $this->sms()->sendSms($UserRecharge['phone'], ['refund_price' => $datas['refund_price']], 'RECHARGE_ORDER_REFUND_STATUS');
        //站内信
        $this->system()->sendMsg($UserRecharge['uid'], [
            'refund_price' => $datas['refund_price'],
            'order_id' => $UserRecharge['order_id'],
            'price' => $UserRecharge['price']
        ]);
        //模板消息公众号模版消息
        //$this->wechat()->sendRechargeRefundStatus($UserRecharge['uid'], $datas, $UserRecharge);
        //模板消息小程序订阅消息
        $this->routine()->sendRechargeSuccess($UserRecharge['uid'], $UserRecharge, $now_money);
    }

    /**
     * 积分
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function integralAccout(array $data)
    {
        $order = $data['order'];
        $order['gain_integral'] = $data['give_integral'];
        $storeTitle = substrUTf8($data['storeTitle'], 20, 'UTF-8', '');
        //站内信
        $this->system()->sendMsg($order['uid'], ['order_id' => $order['order_id'], 'store_name' => $storeTitle, 'pay_price' => $order['pay_price'], 'gain_integral' => $data['give_integral'], 'integral' => $data['integral']]);
        //短信
        $this->sms()->sendSms($order['user_phone'], ['gain_integral' => $data['give_integral'], 'integral' => $data['integral']], 'INTEGRAL_ACCOUT');
        //模板消息公众号模版消息
        //$this->wechat()->sendUserIntegral($order['uid'], $order, $data);
        //模板消息小程序订阅消息
        $this->routine()->sendUserIntegral($order['uid'], $data['order'], $storeTitle, $data['give_integral'], $data['integral']);
    }

    /**
     * 佣金
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderBrokerage(array $data)
    {
        $brokeragePrice = $data['brokeragePrice'];
        $goodsName = substrUTf8($data['goodsName'], 20, 'UTF-8', '');
        $goodsPrice = $data['goodsPrice'];
        $spread_uid = $data['spread_uid'];
        $phone = $data['phone'];
        //站内信
        $this->system()->sendMsg($spread_uid, [
            'goods_name' => $goodsName,
            'goods_price' => $goodsPrice,
            'brokerage_price' => $brokeragePrice
        ]);

        if ($phone) {
            //短信
            $this->sms()->sendSms($phone, ['brokerage_price' => $brokeragePrice], 'ORDER_BROKERAGE');
        }
        //模板消息公众号模版消息
        //$this->wechat()->sendOrderBrokerageSuccess($spread_uid, $brokeragePrice, $goodsName, $goodsPrice, $add_time);
        //模板消息小程序订阅消息
        $this->routine()->sendOrderBrokerageSuccess($spread_uid, $brokeragePrice, $goodsName);
    }

    /**
     * 砍价成功
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function bargainSuccess(array $data)
    {
        $uid = $data['uid'];
        $bargainInfo = $data['bargainInfo'];
        $bargainUserInfo = $data['bargainUserInfo'];
        //站内信
        $this->system()->sendMsg($uid, ['title' => substrUTf8($bargainInfo['title'], 20, 'UTF-8', ''), 'min_price' => $bargainInfo['min_price']]);
        //短信
        $phone = app()->make(UserServices::class)->value(['uid' => $uid], 'phone');
        if ($phone) {
            $this->sms()->sendSms($phone, ['title' => substrUTf8($bargainInfo['title'], 20, 'UTF-8', ''), 'min_price' => $bargainInfo['min_price']], 'BARGAIN_SUCCESS');
        }
        //模板消息公众号模版消息
        //$this->wechat()->sendBargainSuccess($uid, $bargainInfo, $bargainUserInfo, $uid);
        //模板消息小程序订阅消息
        $this->routine()->sendBargainSuccess($uid, $bargainInfo, $bargainUserInfo, $uid);
    }

    /**
     * 拼团成功
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderUserGroupsSuccess(array $data)
    {
        $list = $data['list'];
        $title = substrUTf8($data['title'], 20, 'UTF-8', '');
        $nickname = $data['nickname'] ?? '';
        $url = '/pages/goods/order_details/index?order_id=' . $list['order_id'];
        //站内信
        $this->system()->sendMsg($list['uid'], [
            'title' => $title,
            'nickname' => $nickname,
            'count' => $list['people'],
            'pink_time' => date('Y-m-d H:i:s', $list['add_time'])
        ]);
        //短信
        $phone = app()->make(UserServices::class)->value(['uid' => $list['uid']], 'phone');
        if ($phone) {
            $this->sms()->sendSms($phone, ['title' => $title, 'nickname' => $nickname], 'ORDER_USER_GROUPS_SUCCESS');
        }
        //模板消息公众号模版消息
//        $this->wechat()->sendOrderPinkSuccess($list['uid'], $list, $title);
        //模板消息小程序订阅消息
        $this->routine()->sendPinkSuccess($list['uid'], $title, $nickname, $list['add_time'], $list['people'], $url);
    }

    /**
     * 取消拼团
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function sendOrderPinkClone(array $data)
    {
        $uid = $data['uid'];
        $pink = $data['pink'];
        $title = substrUTf8($pink['title'], 20, 'UTF-8', '');
        //站内信
        $this->system()->sendMsg($uid, ['title' => $title, 'count' => $pink->people]);
        $phone = app()->make(UserServices::class)->value(['uid' => $uid], 'phone');
        if ($phone) {
            $this->sms()->sendSms($phone, ['title' => $title], 'SEND_ORDER_PINK_CLONE');
        }
        //模板消息公众号模版消息
        //$this->wechat()->sendOrderPinkClone($uid, $pink, $pink->title);
        //模板消息小程序订阅消息
        $this->routine()->sendPinkFail($uid, $title, $pink->people, '亲，您的拼团取消，点击查看订单详情', '/pages/goods/order_details/index?order_id=' . $pink->order_id);
    }

    /**
     * 拼团失败
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function sendOrderPinkFial(array $data)
    {
        $uid = $data['uid'];
        $pink = $data['pink'];
        $title = substrUTf8($pink['title'], 20, 'UTF-8', '');
        //站内信
        $this->system()->sendMsg($uid, ['title' => $title, 'count' => $pink->people]);
        $phone = app()->make(UserServices::class)->value(['uid' => $uid], 'phone');
        if ($phone) {
            $this->sms()->sendSms($phone, ['title' => $title], 'SEND_ORDER_PINK_FIAL');
        }
        //模板消息公众号模版消息
        $this->wechat()->sendOrderPinkFial($uid, $pink, $pink->title);
        //模板消息小程序订阅消息
        $this->routine()->sendPinkFail($uid, $title, $pink->people, '拼团失败，退款金额为：' . $pink->price, '/pages/goods/order_details/index?order_id=' . $pink->order_id);
    }

    /**
     * 参团成功
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function canPinkSuccess(array $data)
    {
        $orderInfo = $data['orderInfo'];
        $title = substrUTf8($data['title'], 20, 'UTF-8', '');
        $pink = $data['pink'];
        $nickname = app()->make(UserServices::class)->value(['uid' => $orderInfo['uid']], 'nickname');
        //站内信
        $this->system()->sendMsg($orderInfo['uid'], [
            'title' => $title,
            'nickname' => $nickname,
            'count' => $pink['people'],
            'pink_time' => date('Y-m-d H:i:s', $pink['add_time'])
        ]);
        //短信
        $this->sms()->sendSms($orderInfo['user_phone'], [
            'order_id' => $orderInfo['order_id'],
            'title' => $title
        ], 'CAN_PINK_SUCCESS');
        //模板消息公众号模版消息
//        $this->wechat()->sendOrderPinkUseSuccess($orderInfo['uid'], $orderInfo, $title);
        //模板消息小程序订阅消息
        $this->routine()->sendPinkSuccess($orderInfo['uid'], $title, $nickname, $pink['add_time'], $pink['people'], '/pages/goods/order_details/index?order_id=' . $pink['order_id']);
    }

    /**
     * 开团成功
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function openPinkSuccess(array $data)
    {
        $orderInfo = $data['orderInfo'];
        $title = substrUTf8($data['title'], 20, 'UTF-8', '');
        $pink = $data['pink'];
        $nickname = app()->make(UserServices::class)->value(['uid' => $orderInfo['uid']], 'nickname');
        //站内信
        $this->system()->sendMsg($orderInfo['uid'], [
            'title' => $title,
            'nickname' => $nickname,
            'count' => $pink['people'],
            'pink_time' => date('Y-m-d H:i:s', $pink['add_time'])
        ]);
        //短信
        $this->sms()->sendSms($orderInfo['user_phone'], ['title' => $title], 'OPEN_PINK_SUCCESS');
        //模板消息公众号模版消息
        $this->wechat()->sendOrderPinkOpenSuccess($orderInfo['uid'], $pink, $title);
        //模板消息小程序订阅消息
        $this->routine()->sendPinkSuccess($orderInfo['uid'], $title, $nickname, $pink['add_time'], $pink['people'], '/pages/goods/order_details/index?order_id=' . $pink['order_id']);
    }

    /**
     * 提现成功
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function userExtract(array $data)
    {
        $extractNumber = $data['extractNumber'];
        $nickname = $data['nickname'];
        $uid = $data['uid'];
        //站内信
        $this->system()->sendMsg($uid, ['extract_number' => $extractNumber, 'nickname' => $nickname, 'date' => date('Y-m-d H:i:s', time())]);
        //短信
        $phone = app()->make(UserServices::class)->value(['uid' => $uid], 'phone');
        if ($phone) {
            $this->sms()->sendSms($phone, ['extract_number' => $extractNumber], 'USER_EXTRACT');
        }
        //模板消息公众号模版消息
        $this->wechat()->sendUserExtract($uid, $extractNumber);
        //模板消息小程序订阅消息
        $this->routine()->sendExtractSuccess($uid, $extractNumber, $nickname);
    }

    /**
     * 提现失败
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function userBalanceChange(array $data)
    {
        $extract_number = $data['extract_number'];
        $message = $data['message'];
        $uid = $data['uid'];
        $nickname = $data['nickname'];
        //站内信
        $this->system()->sendMsg($uid, ['extract_number' => $extract_number, 'nickname' => $nickname, 'date' => date('Y-m-d H:i:s', time()), 'message' => $message]);
        //短信
        $phone = app()->make(UserServices::class)->value(['uid' => $uid], 'phone');
        if ($phone) {
            $this->sms()->sendSms($phone, ['extract_number' => $extract_number], 'USER_EXTRACT_FAIL');
        }
        //模板消息公众号模版消息
        //$this->wechat()->sendExtractFail($uid, $extract_number, $message);
        //模板消息小程序订阅消息
        $this->routine()->sendExtractFail($uid, $message, $extract_number, $nickname);
    }

    /**
     * 提醒付款给用户
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function orderPayFalse(array $data)
    {
        $order = $data['order'];
        $order_id = $order['order_id'];
        $order['storeName'] = app()->make(StoreOrderCartInfoServices::class)->getCarIdByProductTitle((int)$order['id']);
        //短信
        $this->sms()->sendSms($order['user_phone'], compact('order_id'), 'ORDER_PAY_FALSE');
        //站内信
        $this->system()->sendMsg($order['uid'], ['order_id' => $order_id]);

        //$this->wechat()->sendOrderPayFalse($order['uid'], $order);
    }

    /**
     * 申请退款给客服发消息
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function sendOrderApplyRefund(array $data)
    {
        $order = $data['order'];
        $store_id = 0;
        //给门店店员发送消息
        if ($order['store_id'] != 0 && $order['shipping_type'] != 4) {
            $store_id = $order['store_id'];
        }
        $order['storeName'] = app()->make(StoreOrderCartInfoServices::class)->getCarIdByProductTitle((int)$order['id']);
        //站内信
        $this->system()->kefuSystemSend(['order_id' => $order['order_id']]);
        //短信
        $this->sms()->sendAdminRefund($order, $store_id);
        //公众号
        //$this->wechat()->sendAdminNewRefund($order, $store_id);
        //企业微信通知
        $this->enterprise()->sendMsg(['order_id' => $order['order_id']]);
    }

    /**
     * 新订单给客服
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function adminPaySuccessCode(array $data)
    {
        $order = $data;
        $store_id = 0;
        //给门店店员发送消息
        if ($order['store_id'] != 0 && $order['shipping_type'] != 4) {
            $store_id = $order['store_id'];
        }
        if (isset($order['member_type'])) {//付费会员订单
            $order['storeName'] = '付费会员SVIP';
        } else {
            $order['storeName'] = app()->make(StoreOrderCartInfoServices::class)->getCarIdByProductTitle((int)$order['id']);
        }
        //站内信
        $this->system()->kefuSystemSend(['order_id' => $order['order_id']]);
        //短信
        $this->sms()->sendAdminPaySuccess($order, $store_id);
        //公众号小程序
        //$this->wechat()->sendAdminNewOrder($order, $store_id);
        //企业微信通知
        $this->enterprise()->sendMsg(['order_id' => $order['order_id']]);
    }

    /**
     * 提现申请给客服
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function kefuSendExtractApplication(array $data)
    {
        //站内信
        $this->system()->kefuSystemSend($data);
        //企业微信通知
        $this->enterprise()->sendMsg($data);
    }

    /**
     * 确认收货给客服
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function sendAdminConfirmTakeOver(array $data)
    {
        $order = $data['order'];
        $storeTitle = $data['storeTitle'];
        //站内信
        $this->system()->kefuSystemSend(['storeTitle' => $storeTitle, 'order_id' => $order['order_id']]);
        //短信
        $this->sms()->sendAdminConfirmTakeOver($order);
        //企业微信通知
        $this->enterprise()->sendMsg(['store_title' => $storeTitle, 'order_id' => $order['order_id']]);
    }

    /**
     * 异地登录通知
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function loginCityError(array $data)
    {
        $phone = $data['phone'];
        unset($data['phone']);
        $this->sms()->sendSms($phone, $data, 'LOGIN_CITY_ERROR');
    }

    /**
     * 虚拟商品发货通知
     * @param array $data
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function kamiDeliverGoodsCode(array $data)
    {
        $order_id = $data['order_id'];
        $siteUrl = sys_config('site_url');
        $url = ' ' . $siteUrl . ' ';
        $value = $data['value'];
        $userInfo = app()->make(UserServices::class)->getUserInfo($data['uid'], 'uid,phone');
        if ($userInfo['phone']) {
            //短信
            $this->sms()->sendSms($userInfo['phone'], compact('order_id', 'value', 'url'), 'KAMI_DELIVER_GOODS_CODE');
        }
        //发送站内行
        $this->system()->systemSend($data['uid'], [
            'mark' => 'virtual_info',
            'title' => $data['title'],
            'content' => $data['content']
        ]);
    }

    /**
     * 用户签到提醒
     * @param array $data
     * @return void
     */
    public function signRemindTime(array $data)
    {
        $site_name = sys_config('site_name');
        if ($data['phone']) {
            //短信
            $this->sms()->sendSms($data['phone'], compact('site_name'), 'SIGN_REMIND_TIME');
        }
        //站内信
        $this->system()->sendMsg($data['uid'], ['site_name' => $site_name]);
        //模板消息小程序订阅消息
        $this->routine()->sendSignRemind($data['uid']);
    }

    /**
     * 核销成功提醒 次卡
     * @param array $data
     * @return void
     */
    public function reminderVerificationStatus(array $data)
    {
        $store_name = $data['store_name'];
        $phone = $data['phone'];
        $write_time = date('Y-m-d H:i', time());
        if ($phone) {
            //短信
            $this->sms()->sendSms($phone, compact('store_name', 'write_time'), 'REMINDER_VERIFICATION_STATUS');
        }
        $this->system()->sendMsg((int)$data['uid'], compact('store_name', 'write_time'));
    }

    /**
     * 过期提醒 次卡
     * @param array $data
     * @return void
     */
    public function expirationReminder(array $data)
    {
        $store_name = $data['store_name'];
        $phone = $data['phone'];
        $end_time = $data['end_time'];
        if ($phone) {
            //短信
            $this->sms()->sendSms($phone, compact('store_name', 'end_time'), 'EXPIRATION_REMINDER');
        }
        $this->system()->sendMsg((int)$data['uid'], compact('store_name', 'end_time'));
    }

    /**
     * 临期提醒 次卡
     * @param array $data
     * @return void
     */
    public function reminderBrinkDeath(array $data)
    {
        $store_name = $data['store_name'];
        $phone = $data['phone'];
        $pay_time = $data['pay_time'];
        $end_time = $data['end_time'];
        if ($phone) {
            //短信
            $this->sms()->sendSms($phone, compact('store_name', 'pay_time', 'end_time'), 'REMINDER_BRINK_DEATH');
        }
        $this->system()->sendMsg((int)$data['uid'], compact('store_name', 'pay_time', 'end_time'));
    }

    /**
     * 供应商入驻审核通过
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function supplierVerifySuccess(array $data)
    {
        $supplier = $data['system_name'] ?? '';
        $phone = $data['phone'] ?? '';
        $date = isset($data['add_time']) && $data['add_time'] ? date('Y-m-d H:i', $data['add_time']) : '';
        $site_name = sys_config('site_name');
        if (!$phone) {
            return;
        }
        $pwd = substr($phone, -6);
        //短信
        $this->sms()->sendSms($phone, compact('date', 'supplier', 'phone', 'pwd', 'site_name'), 'SUPPLIER_VERIFY_SUCCESS');
    }

    /**
     * 供应商入驻审核未通过
     * @param array $data
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function supplierVerifyFail(array $data)
    {
        $supplier = $data['system_name'] ?? '';
        $phone = $data['phone'] ?? '';
        $date = isset($data['add_time']) && $data['add_time'] ? date('Y-m-d H:i', $data['add_time']) : '';
        $site_name = sys_config('site_name');
        if (!$phone) {
            return;
        }
        //短信
        $this->sms()->sendSms($phone, compact('date', 'supplier', 'site_name'), 'SUPPLIER_VERIFY_FAIL');
    }

    /**
     * 获取开关
     * @param string $type
     * @return bool
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function boolSwitch(string $type): bool
    {
        return isset($this->config[$type]) && $this->config[$type] == 1;
    }

    /**
     * 短信消息
     * @return NoticeSmsService
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function sms(): NoticeSmsService
    {
        return app()->make(NoticeSmsService::class, [
            $this->config,
            $this->boolSwitch('is_sms'),
            $this->logger
        ], true);
    }

    /**
     * 小程序订阅消息
     * @return RoutineTemplateListService
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function routine(): RoutineTemplateListService
    {
        return app()->make(RoutineTemplateListService::class, [
            $this->config,
            $this->boolSwitch('is_routine'),
            $this->logger
        ], true);
    }

    /**
     * 站内信
     * @return SystemMsgService
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function system(): SystemMsgService
    {
        return app()->make(SystemMsgService::class, [
            $this->config,
            $this->boolSwitch('is_system'),
            $this->logger
        ], true);
    }

    /**
     * 微信消息通知
     * @return WechatTemplateListService
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function wechat(): WechatTemplateListService
    {
        return app()->make(WechatTemplateListService::class, [
            $this->config,
            $this->boolSwitch('is_wechat'),
            $this->logger
        ], true);
    }

    /**
     * 企业微信消息通知
     * @return EnterpriseWechatService
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function enterprise(): EnterpriseWechatService
    {
        return app()->make(EnterpriseWechatService::class, [
            $this->config,
            $this->boolSwitch('is_ent_wechat'),
            $this->logger
        ], true);
    }

    /**
     * 打印订单
     * @param $order
     * @return bool
     */
    public function orderPrint($order): bool
    {
        try {
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $product = $cartServices->getCartInfoPrintProduct((int)$order['id']);
            if (!$product) {
                throw new ValidateException('订单商品获取失败,无法打印!');
            }
            $supplier_id = isset($order['supplier_id']) && $order['supplier_id'] ? $order['supplier_id'] : 0;
            app()->make(PrintDocumentServices::class)->startPrint(is_object($order) ? $order->toArray() : $order, $product, 1, $supplier_id);

//            $type = 0;
//            $relation_id = 0;
//           if (isset($order['supplier_id']) && $order['supplier_id']) {
//                    $supplier_id = (int)$order['supplier_id'];
//                    $type = 2;
//                    $relation_id = $supplier_id;
//                }
//
//            /** @var ConfigServices $configServices */
//            $configServices = app()->make(ConfigServices::class);
//            [$switch, $name, $configData] = $configServices->getPrintingConfig($type, $relation_id);
//            if (!$switch) {
//                throw new ValidateException('请先开启小票打印');
//            }
//            foreach ($configData as $value) {
//                if (!$value) {
//                    throw new ValidateException('请先配置小票打印开发者');
//                }
//            }
//            $printer = new Printer($name, $configData);
//            $printer->setPrinterContent([
//                'name' => sys_config('site_name'),
//                'orderInfo' => is_object($order) ? $order->toArray() : $order,
//                'product' => $product
//            ])->startPrinter();
            return true;
        } catch (Throwable $e) {
            \think\facade\Log::error('小票打印失败，原因：' . $e->getMessage());
            return false;
        }

    }


}
