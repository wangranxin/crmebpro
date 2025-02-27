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

namespace app\services\product\shipping;


use app\dao\product\shipping\ShippingTemplatesRegionCityDao;
use app\services\BaseServices;
use think\annotation\Inject;

/**
 * 根据地区设置邮费
 * Class ShippingTemplatesRegionCityServices
 * @package app\services\product\shipping
 * @mixin ShippingTemplatesRegionCityDao
 */
class ShippingTemplatesRegionCityServices extends BaseServices
{

    /**
     * @var ShippingTemplatesRegionCityDao
     */
    #[Inject]
    protected ShippingTemplatesRegionCityDao $dao;
    
}
