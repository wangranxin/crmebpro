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
namespace app\controller\api\v1\user;

use app\Request;
use app\services\community\CommunityRecordServices;
use app\services\message\service\StoreServiceRecordServices;
use app\services\message\SystemMessageServices;
use think\annotation\Inject;

/**
 * 站内信类
 * Class SystemMessage
 * @package app\api\controller\store
 */
class SystemMessage
{

    /**
     * @var SystemMessageServices
     */
    #[Inject]
    protected SystemMessageServices $services;


    /**
     * 获取用户消息 站内信+客服消息
     * @param Request $request
     * @param StoreServiceRecordServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function message(Request $request, StoreServiceRecordServices $services, CommunityRecordServices $community)
    {
        $uid = (int)$request->uid();
        $where['is_del'] = 0;
        $where['uid'] = $uid;
        $list = $this->services->getMessageList($where, '*', 0, 1);
        $list = $list[0] ?? [];
        if ($list) {
            $list['add_time'] = time_tran($list['add_time']);
            $list['message_num'] = $this->services->count(['uid' => $uid, 'look' => 0, 'is_del' => 0]);
        }

        $service = $services->getServiceList($uid);
        $community = $community->getList(['uid' => $uid], '*', 0, 1);
        $community = $community[0] ?? [];
        return app('json')->successful(['system' => $list, 'service' => $service, 'community' => $community]);
    }


    /**
     * 站内信列表
     * @param Request $request
     * @return mixed
     */
    public function message_list(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getMessageSystemList($uid));
    }

    /**
     * 站内信详情
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function detail(Request $request, $id)
    {
        if (!$id) {
            app('json')->fail('缺少参数');
        }
        $uid = (int)$request->uid();
        $where['uid'] = $uid;
        $where['id'] = $id;
        return app('json')->successful($this->services->getInfo($where));
    }
}
