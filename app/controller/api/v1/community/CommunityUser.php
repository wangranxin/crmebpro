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
namespace app\controller\api\v1\community;


use app\Request;
use app\services\community\CommunityServices;
use app\services\community\CommunityUserServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;


/**
 * Class CommunityUser
 * @package app\api\controller\v1\community
 */
class CommunityUser
{

    /**
     * @var CommunityUserServices
     */
    #[Inject]
    protected CommunityUserServices $services;

    /**
     * 用户主页
     * @param $uid
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/22 16:30
     */
    public function getInfo($authorUid, Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->success($this->services->getInfo($authorUid, $uid));
    }

    /**
     * 修改简介
     * @param Request $request
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/8/22 16:30
     */
    public function updateDesc(Request $request)
    {
        $uid = (int)$request->uid();
        [$desc] = $request->postMore([['desc', '']], true);
        $info = $this->services->getUserInfo($uid);
        if (!$info) {
            return app('json')->fail('用户不存在');
        }
        $info->desc = $desc;
        $info->save();
        return app('json')->success('修改成功');
    }

    /**
     * 关注/取消关注
     * @param $id
     * @param Request $request
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/8/22 16:31
     */
    public function setInterest($authorUid, Request $request)
    {
        [$status] = $request->postMore([
            ['status', 1]
        ], true);

        $status = $status == 1 ? 1 : 0;
        $uid = (int)$request->uid();
        if ($authorUid == $uid) {
            return app('json')->fail('不能关注自己');
        }

        $this->services->setInterest($authorUid, $uid, $status);
        if ($status) {
            return app('json')->success('关注成功');
        } else {
            return app('json')->success('取消成功');
        }
    }

    public function follow(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->success($this->services->follow($uid));
    }

    /**
     * 关注/粉丝列表
     * @param $type 关注:follow 粉丝:fans
     * @param Request $request
     * @return \think\Response
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/26 11:28
     */
    public function followList($type, Request $request)
    {
        if (!in_array($type, ['follow', 'fans'])) {
            return app('json')->fail('参数错误');
        }
        $uid = (int)$request->uid();
        return app('json')->success($this->services->followList($uid, $type));
    }

    /**
     * 我的好友
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/8/26 12:05
     */
    public function userFriend(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->success($this->services->userFriend($uid));
    }

    /**
     * 推荐
     * @param Request $request
     * @return Response
     * User: liusl
     * DateTime: 2024/8/26 15:18
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function recommendList(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->success($this->services->recommendList($uid));
    }
}
