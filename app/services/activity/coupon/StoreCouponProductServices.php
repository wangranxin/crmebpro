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

namespace app\services\activity\coupon;

use app\services\BaseServices;
use app\dao\activity\coupon\StoreCouponProductDao;
use think\annotation\Inject;

/**
 * Class StoreCouponProductServices
 * @package app\services\activity\coupon
 * @mixin StoreCouponProductDao
 */
class StoreCouponProductServices extends BaseServices
{
    /**
     * @var StoreCouponProductDao
     */
    #[Inject]
    protected StoreCouponProductDao $dao;
    

}
