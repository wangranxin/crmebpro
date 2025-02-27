<?php

namespace app\controller\admin\v1\agent;

use app\controller\admin\AuthController;
use app\services\agent\DivisionApplyServices;
use app\services\agent\DivisionServices;
use think\annotation\Inject;

class Division extends AuthController
{
    #[Inject]
    protected DivisionServices $services;

    /**
     * 事业部/代理商/员工列表
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionList()
    {
        $where = $this->request->getMore([
            ['division_type', 0],
            ['keyword', '', '', 'division_name'],
        ]);
        if (!in_array($where['division_type'], [1, 2, 3])) {
            return $this->fail('参数错误');
        }
        $data = $this->services->getDivisionList($where);
        return $this->success($data);
    }

    /**
     * 下级代理商/员工列表
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionDownList()
    {
        $where = $this->request->getMore([
            ['division_type', 0],
            ['nickname', ''],
            ['uid', 0],
        ]);
        if ($where['division_type'] == 2) {
            $where['division_id'] = $where['uid'];
        } elseif ($where['division_type'] == 3) {
            $where['agent_id'] = $where['uid'];
        }
        unset($where['uid']);
        $data = $this->services->getDivisionList($where);
        return $this->success($data);
    }

    /**
     * 事业部添加表单
     * @param $uid
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getDivisionForm($uid)
    {
        return $this->success($this->services->getDivisionForm($uid));
    }

    /**
     * 保存事业部
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function saveDivision()
    {
        $data = $this->request->postMore([
            ['uid', 0],
            ['aid', 0],
            ['division_percent', 0],
            ['division_end_time', ''],
            ['division_status', 1],
            ['account', ''],
            ['phone', ''],
            ['pwd', ''],
            ['conf_pwd', ''],
            ['division_name', ''],
            ['roles', []],
            ['image', []]
        ]);
        $this->services->saveDivision($data);
        return $this->success('保存成功');
    }

    /**
     * 代理商添加表单
     * @param $uid
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getDivisionAgentForm($uid)
    {
        return $this->success($this->services->getDivisionAgentForm($uid));
    }

    /**
     * 保存代理商
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function saveDivisionAgent()
    {
        $data = $this->request->postMore([
            ['division_name', ''],
            ['division_id', 0],
            ['uid', 0],
            ['image', []],
            ['division_percent', 0],
            ['division_end_time', ''],
            ['division_status', 1],
        ]);
        $this->services->saveDivisionAgent($data);
        return $this->success('保存成功');
    }

    /**
     * 员工添加表单
     * @param $uid
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getDivisionStaffForm($uid)
    {
        return $this->success($this->services->getDivisionStaffForm($uid));
    }

    /**
     * 保存员工
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function saveDivisionStaff()
    {
        $data = $this->request->postMore([
            ['uid', 0],
            ['division_percent', 0],
            ['agent_id', 0],
            ['image', []],
        ]);
        $this->services->saveDivisionStaff($data);
        return $this->success('保存成功');
    }

    /**
     * 删除事业部/代理商/员工
     * @param $uid
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function deleteDivision($uid)
    {
        $this->services->deleteDivision($uid);
        return $this->success('删除成功');
    }

    /**
     * 设置状态
     * @param $uid
     * @param $status
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function setStatus($uid, $status)
    {
        $this->services->setStatus($uid, $status);
        return $this->success('设置成功');
    }

    /**
     * 代理商申请列表
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyList()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['keyword', ''],
        ]);
        $where['division_id'] = $this->adminInfo['division_id'];
        return $this->success(app()->make(DivisionApplyServices::class)->applyList($where));
    }

    /**
     * 申请审核表单
     * @param $id
     * @param $type
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyExamine($id, $type)
    {
        return $this->success(app()->make(DivisionApplyServices::class)->applyExamine($id, $type));
    }

    /**
     * 审核保存
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyExamineSave()
    {
        $data = $this->request->postMore([
            ['type', 0],
            ['id', 0],
            ['division_percent', ''],
            ['division_end_time', ''],
            ['division_status', ''],
            ['refusal_reason', 0]
        ]);
        app()->make(DivisionApplyServices::class)->applyExamineSave($data);
        return $this->success($data['type'] == 1 ? '审核通过' : '拒绝成功');
    }

    /**
     * 申请删除
     * @param $id
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyDelete($id)
    {
        app()->make(DivisionApplyServices::class)->applyDelete($id);
        return $this->success('删除成功');
    }

    /**
     * 事业部订单
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionOrder()
    {
        $where = $this->request->getMore([
            ['time', '',],
            ['field_key', ''],
            ['real_name', ''],
            ['division_id', ''],
            ['division_agent_id', ''],
        ]);
        $adminInfo = $this->request->adminInfo();
        return $this->success($this->services->divisionOrder($adminInfo, $where));
    }

    public function divisionOption()
    {
        return $this->success($this->services->divisionOption());
    }

    public function agentOption($division_id)
    {
        return $this->success($this->services->agentOption($division_id));
    }

    /**
     * 事业部统计基础信息
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionStatistics()
    {
        $adminInfo = $this->request->adminInfo();
        $divisionId = $adminInfo['division_id'];
        return $this->success($this->services->divisionStatistics($divisionId));
    }

    /**
     * 事业部统计趋势
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionTrend()
    {
        [$time] = $this->request->getMore([
            ['time', '']
        ], true);
        $adminInfo = $this->request->adminInfo();
        $divisionId = $adminInfo['division_id'];
        return $this->success($this->services->divisionTrend($time, $divisionId));
    }

    /**
     * 事业部统计排行
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/26
     */
    public function divisionRanking()
    {
        $adminInfo = $this->request->adminInfo();
        $divisionId = $adminInfo['division_id'];
        return $this->success($this->services->divisionRanking($divisionId));
    }
}