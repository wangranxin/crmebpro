<?php

namespace app\services\agent;

use app\dao\agent\DivisionApplyDao;
use app\services\BaseServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use think\annotation\Inject;
use crmeb\services\FormBuilder as Form;

class DivisionApplyServices extends BaseServices
{
    /**
     * @var DivisionApplyDao
     */
    #[Inject]
    protected DivisionApplyDao $dao;

    /**
     * 申请列表
     * @param $where
     * @return array
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyList($where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->applyList($where, $page, $limit);
        $divisionIds = array_column($list, 'division_id');
        $divisionNames = app()->make(UserServices::class)->getColumn([['uid', 'in', $divisionIds]], 'division_name', 'uid');
        foreach ($list as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['status_time'] = date('Y-m-d H:i:s', $item['status_time']);
            $item['images'] = json_decode($item['images'], true);
            $item['spread_name'] = $divisionNames[$item['division_id']] ?? '';
        }
        $count = $this->dao->applyCount($where);
        return compact('list', 'count');
    }

    /**
     * 申请审核
     * @param $id
     * @param $type
     * @return mixed
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyExamine($id, $type)
    {
        $field = [];
        $field[] = Form::hidden('type', $type);
        $field[] = Form::hidden('id', $id);
        if ($type) {
            $field[] = Form::number('division_percent', '佣金比例', '')->placeholder('代理商佣金比例1-100')->info('填写1-100，如填写50代表返佣50%,但是不能高于上级的比例')->style(['width' => '173px'])->min(0)->max(100)->required();
            $field[] = Form::date('division_end_time', '到期时间', '')->placeholder('代理商代理到期时间');
            $field[] = Form::radio('division_status', '代理状态', 1)->options([['label' => '开通', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
            $title = '同意申请';
        } else {
            $field[] = Form::textarea('refusal_reason', '拒绝原因', '')->rows(5);
            $title = '拒绝申请';
        }
        return create_form($title, $field, $this->url('/agent/division/apply/examine/save'), 'POST');
    }

    /**
     * 申请审核保存
     * @param $data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyExamineSave($data)
    {
        return $this->transaction(function () use ($data) {
            $applyInfo = $this->dao->get($data['id']);
            if ($data['type'] == 1) {
                $agentData = [
                    'spread_uid' => $applyInfo['division_id'],
                    'spread_time' => time(),
                    'division_name' => $applyInfo['division_name'],
                    'division_id' => $applyInfo['division_id'],
                    'division_status' => 1,
                    'division_percent' => $data['division_percent'],
                    'division_change_time' => time(),
                    'division_end_time' => strtotime($data['division_end_time']),
                    'division_type' => 2,
                    'agent_id' => $applyInfo['uid'],
                    'is_promoter' => 1,
                ];
                /** @var UserServices $userServices */
                $userServices = app()->make(UserServices::class);
                $divisionInfo = $userServices->getUserInfo($applyInfo['division_id'], 'division_end_time,division_percent');
                if ($divisionInfo) {
                    if ($agentData['division_percent'] > $divisionInfo['division_percent']) throw new AdminException('代理商比例不能大于上级区域代理');
                    if ($agentData['division_end_time'] > $divisionInfo['division_end_time']) throw new AdminException('代理商到期时间不能大于上级区域代理');
                }
                $applyInfo->status = 1;
                $applyInfo->status_time = time();
                $res = $applyInfo->save();
                $res = $res && $userServices->update($applyInfo['uid'], $agentData);
            } else {
                $applyInfo->status = 2;
                $applyInfo->refusal_reason = $data['refusal_reason'];
                $applyInfo->status_time = time();
                $res = $applyInfo->save();
            }
            if (!$res) throw new AdminException($data['type'] == 1 ? '通过失败' : '拒绝失败');
            return true;
        });
    }

    /**
     * 删除申请
     * @param $id
     * @return bool
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyDelete($id)
    {
        $res = $this->dao->update(['id' => $id], ['is_del' => 1]);
        if (!$res) throw new AdminException('删除失败');
        return true;
    }

    /**
     * 申请代理商
     * @param $data
     * @param $id
     * @return bool
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyAgent($data, $id)
    {
        $data['images'] = json_encode($data['images']);
        $data['add_time'] = time();
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $divisionId = $userServices->value(['division_invite' => $data['division_invite']], 'division_id');
        if (!$divisionId) throw new ApiException('区域代理不存在');
        $data['division_id'] = $divisionId;
        if ($id) {
            $data['status'] = 0;
            $res = $this->dao->update(['id' => $id], $data);
        } else {
            $this->dao->update(['uid' => $data['uid']], ['is_del' => 1]);
            $res = $this->dao->save($data);
        }
        if (!$res) throw new ApiException('申请失败');
        return true;
    }

    /**
     * 申请代理商详情
     * @param $uid
     * @return array|int[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyInfo($uid)
    {
        $data = $this->dao->get(['uid' => $uid, 'is_del' => 0]);
        if (!$data) return ['status' => -1];
        $data = $data->toArray();
        $data['images'] = json_decode($data['images'], true);
        $data['add_time'] = date('Y/m/d H:i', $data['add_time']);
        $data['status_time'] = $data['status_time'] ? date('Y/m/d H:i', $data['status_time']) : '';
        return $data;
    }
}
