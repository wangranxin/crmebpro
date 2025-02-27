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

namespace app\controller\api\v2\user;


use app\Request;
use app\services\message\service\StoreServiceLogServices;
use app\services\message\service\StoreServiceServices;
use think\annotation\Inject;

/**
 * Class StoreService
 * @package app\api\controller\v2\user
 */
class StoreService
{

    /**
     * @var StoreServiceLogServices
     */
    #[Inject]
    protected StoreServiceLogServices $services;

    /**
     * 客服聊天记录
     * @param Request $request
     * @param StoreServiceServices $services
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function record(Request $request, StoreServiceServices $services)
    {
        [$uidTo, $limit, $toUid] = $request->getMore([
            [['uidTo', 'd'], 0],
            [['limit', 'd'], 10],
            [['toUid', 'd'], 0],
        ], true);
        $uid = (int)$request->uid();
        return app('json')->successful($services->getRecord($uid, $uidTo, $limit, $toUid));
    }
}
