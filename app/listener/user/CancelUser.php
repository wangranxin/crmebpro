<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\listener\user;

use app\services\community\CommunityUserServices;
use app\services\message\SystemMessageServices;
use app\services\user\CancelUserServices;
use app\services\work\WorkClientServices;
use app\services\work\WorkMemberServices;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 注销用户事件
 */
class CancelUser implements ListenerInterface
{

    public function handle($event): void
    {
        [$uid] = $event;
        /** @var CancelUserServices $cancelUserServices */
        $cancelUserServices = app()->make(CancelUserServices::class);
        $cancelUserServices->cancelUser((int)$uid);
        /** @var WorkClientServices $service */
        $service = app()->make(WorkClientServices::class);
        $service->unboundUser((int)$uid);
        /** @var WorkMemberServices $memberService */
        $memberService = app()->make(WorkMemberServices::class);
        $memberService->unboundUser((int)$uid);
        /** @var SystemMessageServices $systemMessageServices */
        $systemMessageServices = app()->make(SystemMessageServices::class);
        $systemMessageServices->update(['uid' => $uid], ['is_del' => 1]);
        try {
            //社区
            app()->make(CommunityUserServices::class)->logoutAfter($uid);
        }catch (\Throwable $e){
            Log::error('注销用户事件失败：' . $e->getMessage());
        }

        event('user.update');
    }
}
