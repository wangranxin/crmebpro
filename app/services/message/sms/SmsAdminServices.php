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


use app\dao\system\config\SystemConfigDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\HttpService;
use crmeb\services\sms\Sms;
use crmeb\services\SystemConfigService;
use think\annotation\Inject;

/**
 * 短信平台注册登陆
 * Class SmsAdminServices
 * @package app\services\message\sms
 * @mixin SystemConfigDao
 */
class SmsAdminServices extends BaseServices
{
    /**
     * @var SystemConfigDao
     */
    #[Inject]
    protected SystemConfigDao $dao;

    /**
     * 更新短信配置
     * @param string $account
     * @param string $password
     * @return mixed
     */
    public function updateSmsConfig(string $account, string $password)
    {
        return $this->transaction(function () use ($account, $password) {
            $this->dao->update('sms_account', ['value' => json_encode($account)], 'menu_name');
            $this->dao->update('sms_token', ['value' => json_encode($password)], 'menu_name');
            \crmeb\services\SystemConfigService::clear();
        });
    }


}
