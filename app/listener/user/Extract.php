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

namespace app\listener\user;

use app\jobs\notice\SocketPushJob;
use app\services\user\UserExtractServices;
use crmeb\interfaces\ListenerInterface;
use app\services\system\admin\SystemAdminServices;

/**
 * 用户申请提现事件
 * Class Recharge
 * @package app\listener\user
 */
class Extract implements ListenerInterface
{
    /**
     * 用户申请提现事件
     * @param $event
     */
    public function handle($event): void
    {
        [$user, $data, $res] = $event;
        //提醒推送
        event('notice.notice', [['nickname' => $user['nickname'], 'money' => $data['money']], 'kefu_send_extract_application']);

        //支付宝微信自动转账
        $verify = false;
        switch ($res['extract_type']) {
            case 'weixin':
                //微信自动转零钱开启,并且不需要审核
                $verify = sys_config('brokerage_type', 0) && sys_config('wechat_verify_type', 0) == 2;
                break;
            case 'alipay':
                //支付宝自动转银行卡开启,并且不需要审核
                $verify = sys_config('alipay_extract_type', 0) && sys_config('alipay_verify_type', 0) == 2;
                break;
            default:
                break;
        }
        if ($verify) {
            app()->make(UserExtractServices::class)->changeSuccess($res['id'], $res);
        }

        /** @var SystemAdminServices $systemAdmin */
        $systemAdmin = app()->make(SystemAdminServices::class);
        $systemAdmin->adminNewPush();
        SocketPushJob::dispatch(['', 'WITHDRAW', ['id' => $res->id], 'admin']);
    }
}
