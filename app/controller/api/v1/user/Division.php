<?php

namespace app\controller\api\v1\user;

use app\Request;
use app\services\agent\DivisionApplyServices;
use app\services\agent\DivisionServices;
use crmeb\services\CacheService;

class Division
{
    /**
     * 获取代理商申请信息
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyInfo(Request $request)
    {
        $uid = $request->uid();
        $data = app()->make(DivisionApplyServices::class)->applyInfo($uid);
        return app('json')->success($data);
    }

    /**
     * 代理商申请
     * @param Request $request
     * @param $id
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyAgent(Request $request, $id)
    {
        $data = $request->postMore([
            ['division_name', ''],
            ['name', ''],
            ['phone', 0],
            ['code', 0],
            ['division_invite', 0],
            ['images', []]
        ]);
        $data['uid'] = $request->uid();
        $verifyCode = CacheService::get('code_' . $data['phone']);
        if ($verifyCode != $data['code']) return app('json')->fail('验证码错误');
        if ($data['division_invite'] == 0) return app('json')->fail('邀请码错误');
        app()->make(DivisionApplyServices::class)->applyAgent($data, $id);
        return app('json')->success('申请成功');
    }

    /**
     * 代理商员工列表
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function staffList(Request $request)
    {
        $where = $request->postMore([
            ['keyword', ''],
            ['start', 0],
            ['stop', 0],
        ]);
        $where['agent_id'] = $request->uid();
        $where['division_type'] = 3;
        $where['is_del'] = 0;
        return app('json')->success(app()->make(DivisionServices::class)->staffList($where));
    }

    /**
     * 设置员工提成
     * @param Request $request
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function staffPercent(Request $request)
    {
        [$divisionPercent, $uid] = $request->postMore([
            ['division_percent', ''],
            ['uid', 0],
        ], true);
        $agentId = $request->uid();
        if (!$uid) return app('json')->fail('参数错误');
        app()->make(DivisionServices::class)->staffPercent($uid, $agentId, $divisionPercent);
        return app('json')->success('设置成功');
    }

    /**
     * 删除员工
     * @param Request $request
     * @param $uid
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function delStaff(Request $request, $uid)
    {
        $agentId = $request->uid();
        if (!$uid) return app('json')->fail('参数错误');
        app()->make(DivisionServices::class)->delStaff($uid, $agentId);
        return app('json')->success('删除成功');
    }

    /**
     * 代理商推广码
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function agentSpreadCode(Request $request)
    {
        return app('json')->success(app()->make(DivisionServices::class)->agentSpreadCode($request->uid()));
    }

    /**
     * 代理商推广
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function agentSpread(Request $request)
    {
        [$agentId, $agentCode] = $request->postMore([
            ['agent_id', 0],
            ['agent_code', 0],
        ], true);
        $res = app()->make(DivisionServices::class)->agentSpreadStaff($request->uid(), (int)$agentId, (int)$agentCode);
        if ($res) {
            return app('json')->success('绑定成功');
        } else {
            return app('json')->fail('无操作');
        }
    }
}