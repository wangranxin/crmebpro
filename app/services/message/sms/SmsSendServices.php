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

namespace app\services\message\sms;

use app\services\BaseServices;
use app\services\message\SystemNotificationServices;
use app\services\serve\ServeServices;
use crmeb\services\CacheService;
use think\exception\ValidateException;

/**
 * 短信发送
 * Class SmsSendServices
 * @package app\services\message\sms
 */
class SmsSendServices extends BaseServices
{
    private $smsType = ['yihaotong', 'aliyun', 'tencent'];

    /**
     * 发送短信
     * @param bool $switch
     * @param $phone
     * @param array $data
     * @param string $template
     * @return bool
     */
    public function send(bool $switch, $phone, array $data, string $template)
    {
        if ($switch && $phone) {
            /** @var ServeServices $services */
            $services = app()->make(ServeServices::class);

            $type = $this->smsType[sys_config('sms_type', 0)];
            //获取短信ID
            $templateId = CacheService::handler('TEMPLATE')->remember('NOTICE_SMS_' . $type . '_' . $template, function () use ($services, $template) {
                /** @var SystemNotificationServices $notifyServices */
                $notifyServices = app()->make(SystemNotificationServices::class);
                return $notifyServices->value(['mark' => $template], 'sms_id') ?? 0;
            });
            //获取发送短信驱动类型
            $smsMake = $services->sms($type);
            $res = $smsMake->send($phone, $templateId, $data);
            if ($res === false) {
                throw new ValidateException($services->getError());
            }
            return true;
        } else {
            return false;
        }
    }

}
