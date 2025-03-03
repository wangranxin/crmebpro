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

namespace crmeb\services\wechat\contract;

/**
 * 配置
 * Interface ConfigHandlerInterface
 * @package crmeb\services\wechat\contract
 */
interface ConfigHandlerInterface
{
    
    /**
     * 获取全部
     * @return array
     */
    public function all(): array;
}
