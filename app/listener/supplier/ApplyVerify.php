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

namespace app\listener\supplier;


use crmeb\interfaces\ListenerInterface;

/**
 * 供应商入驻审核事件
 * Class ApplyVerify
 * @package app\listener\supplier
 */
class ApplyVerify implements ListenerInterface
{

    public function handle($event): void
    {
		[$supplierInfo, $verifyStatus] = $event;

		if ($verifyStatus == 1) {//通过
			$mark = 'supplier_verify_success';
		} else {//未通过
			$mark = 'supplier_verify_fail';
		}
		//发送消息
		event('notice.notice', [$supplierInfo, $mark]);
    }
}
