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
namespace app\controller\admin\v1\finance;


use app\common\controller\Recharge;
use app\controller\admin\AuthController;
use app\services\user\UserRechargeServices;
use think\annotation\Inject;

/**
 * Class UserRecharge
 * @package app\controller\admin\v1\finance
 */
class UserRecharge extends AuthController
{

    use Recharge;

    /**
     * @var UserRechargeServices
     */
    #[Inject]
    protected UserRechargeServices $services;

}
