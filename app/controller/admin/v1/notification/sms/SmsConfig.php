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
namespace app\controller\admin\v1\notification\sms;

use think\annotation\Inject;
use app\services\message\sms\SmsAdminServices;
use app\services\serve\ServeServices;
use crmeb\services\CacheService;
use app\controller\admin\AuthController;
use crmeb\services\SystemConfigService;

/**
 * 短信配置
 * Class SmsConfig
 * @package app\admin\controller\sms
 */
class SmsConfig extends AuthController
{

    /**
     * @var SmsAdminServices
     */
    #[Inject]
    protected SmsAdminServices $services;


    /**
     * 保存短信配置
     * @return mixed
     */
    public function save_basics()
    {
        [$account, $token] = $this->request->postMore([
            ['sms_account', ''],
            ['sms_token', '']
        ], true);

        $this->validate(['sms_account' => $account, 'sms_token' => $token], \app\validate\admin\notification\SmsConfigValidate::class);
        if(sys_config('sms_account') && sys_config('sms_token')){
            return $this->success('保存成功');
        }
        if ($this->services->updateSmsConfig($account, $token)) {
            return $this->success('保存成功');
        } else {
            return $this->fail('保存失败');
        }
    }

}
