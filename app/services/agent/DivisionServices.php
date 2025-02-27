<?php

namespace app\services\agent;

use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\other\QrcodeServices;
use app\services\system\admin\SystemAdminServices;
use app\services\system\SystemRoleServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use crmeb\services\FormBuilder as Form;

class DivisionServices extends BaseServices
{
    /**
     * 获取事业部列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getDivisionList($where)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $data = $userServices->getUserList($where + ['status' => 1, 'is_del' => 0],
            'uid,nickname,avatar,division_name,division_type,division_percent,division_end_time,division_change_time,division_status,division_invite',
            'division_change_time desc'
        );
        $divisionCount = $userServices->getDivisionCount($where['division_type'], array_column($data['list'], 'uid'));
        foreach ($data['list'] as &$item) {
            $item['division_end_time'] = $item['division_end_time'] ? date('Y-m-d', $item['division_end_time']) : '';
            $item['division_change_time'] = $item['division_change_time'] ? date('Y-m-d', $item['division_change_time']) : '';
            $item['down_num'] = $divisionCount[$item['uid']] ?? 0;
            $item['division_percent'] = $item['division_percent'] . '%';
        }
        return $data;
    }

    /**
     * 获取事业部表单
     * @param $uid
     * @return mixed
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getDivisionForm($uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var SystemAdminServices $adminService */
        $adminService = app()->make(SystemAdminServices::class);
        $userInfo = $userServices->get($uid);
        if ($uid && !$userInfo) throw new AdminException('用户数据不存在');
        if ($uid) {
            $adminInfo = $adminService->get(['division_id' => $uid]);
            if ($adminInfo) $adminInfo = $adminInfo->toArray();
            if (isset($adminInfo['roles'])) {
                foreach ($adminInfo['roles'] as &$item) {
                    $item = intval($item);
                }
            }
        }
        $field = [];
        $field[] = Form::input('division_name', '区域代理名称', $userInfo['division_name'] ?? '')->required('请输入区域代理名称');
        if ($uid) {
            $field[] = Form::hidden('uid', $uid);
        } else {
            $field[] = Form::frameImage('image', '关联用户', $this->url('admin/system.user/list', ['fodder' => 'image'], true))->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
        }
        $field[] = Form::hidden('aid', $adminInfo['id'] ?? 0);
        $field[] = Form::number('division_percent', '佣金比例', $userInfo['division_percent'] ?? '')->placeholder('区域代理佣金比例1-100')->info('填写1-100，如填写50代表返佣50%')->style(['width' => '173px'])->min(0)->max(100)->required();
        $field[] = Form::date('division_end_time', '到期时间', isset($userInfo['division_end_time']) && $userInfo['division_end_time'] != 0 ? date('Y-m-d', $userInfo['division_end_time']) : '')->placeholder('区域代理到期时间')->required();
        $field[] = Form::radio('division_status', '代理状态', $userInfo['division_status'] ?? 1)->options([['label' => '开通', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
        $field[] = Form::input('account', '管理员账号', $adminInfo['account'] ?? '')->required('请填写管理员账号');
        $field[] = Form::input('phone', '管理员手机号', $adminInfo['phone'] ?? '')->required('请填写管理员手机号');
        $field[] = Form::input('pwd', '管理密码')->type('password')->required('请填写管理员密码');
        $field[] = Form::input('conf_pwd', '确认密码')->type('password')->required('请输入确认密码');
        $options = app()->make(SystemRoleServices::class)->getRoleFormSelect(1);
        $field[] = Form::select('roles', '管理员身份', $adminInfo['roles'] ?? [])->setOptions(Form::setOptions($options))->multiple(true)->required('请选择管理员身份');
        return create_form('区域代理', $field, $this->url('/agent/division/save'), 'POST');
    }

    /**
     * 保存事业部
     * @param $data
     * @return mixed
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function saveDivision($data)
    {
        if ((int)$data['uid'] == 0) $data['uid'] = $data['image']['uid'] ?? 0;
        if ((int)$data['uid'] == 0) throw new AdminException('请选择用户');
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if ($data['aid'] == 0) {
            $divisionType = $userServices->value(['uid' => $data['uid']], 'division_type');
            if (is_null($divisionType)) throw new AdminException('用户不存在');
            if ($divisionType == 1) throw new AdminException('此用户是区域代理，请勿重复添加');
            if ($divisionType == 2) throw new AdminException('此用户是代理商，无法添加为区域代理');
            if ($divisionType == 3) throw new AdminException('此用户是下级员工，无法添加为区域代理');
        }
        $uid = $data['uid'];
        $aid = $data['aid'];
        $divisionData = [
            'division_name' => $data['division_name'],
            'division_type' => 1,
            'division_status' => $data['division_status'],
            'division_id' => $uid,
            'agent_id' => 0,
            'staff_id' => 0,
            'division_percent' => $data['division_percent'],
            'division_end_time' => strtotime($data['division_end_time']),
            'division_change_time' => time(),
            'division_invite' => $userServices->value(['uid' => $uid], 'division_invite') ?: rand(10000000, 99999999),
            'spread_uid' => 0,
            'spread_time' => 0,
            'is_promoter' => 1,
        ];
        $adminData = [
            'account' => $data['account'],
            'phone' => $data['phone'],
            'pwd' => $data['pwd'],
            'conf_pwd' => $data['conf_pwd'],
            'real_name' => $data['division_name'],
            'roles' => $data['roles'],
            'status' => 1,
            'level' => 1,
            'division_id' => $uid
        ];
        return $this->transaction(function () use ($uid, $divisionData, $adminData, $aid, $userServices) {
            $userServices->update($uid, $divisionData);
            /** @var SystemAdminServices $adminService */
            $adminService = app()->make(SystemAdminServices::class);
            if (!$aid) {
                if (!$adminData['pwd']) throw new AdminException('请输入管理员密码');
                if (!$adminData['conf_pwd']) throw new AdminException('请输入确认密码');
                if ($adminData['pwd'] != $adminData['conf_pwd']) throw new AdminException('两次密码不一致');
                $adminService->create($adminData);
            } else {
                $adminInfo = $adminService->get($aid);
                if (!$adminInfo) throw new AdminException('管理员不存在');
                if ($adminData['pwd']) {
                    if (!$adminData['conf_pwd']) throw new AdminException('请输入确认密码');
                    if ($adminData['pwd'] != $adminData['conf_pwd']) throw new AdminException('两次密码不一致');
                    $adminInfo->pwd = $this->passwordHash($adminData['pwd']);
                }
                $adminInfo->real_name = $adminData['real_name'];
                $adminInfo->account = $adminData['account'];
                $adminInfo->roles = implode(',', $adminData['roles']);
                if (!$adminInfo->save()) throw new AdminException('保存管理员失败');
            }
            return true;
        });
    }

    /**
     * 获取代理商表单
     * @param $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getDivisionAgentForm($uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->get($uid);
        if ($uid && !$userInfo) throw new AdminException(400214);
        $field = [];
        $options = [];
        $divisionList = $userServices->getUserList(['status' => 1, 'is_del' => 0, 'division_type' => 1], 'uid,division_name');
        foreach ($divisionList['list'] as $item) {
            $options[] = ['value' => $item['uid'], 'label' => $item['division_name']];
        }
        $field[] = Form::input('division_name', '代理商名称', $userInfo['division_name'] ?? '')->required('请输入代理商名称');
        if ($uid) {
            $field[] = Form::hidden('uid', $uid);
            $field[] = Form::hidden('division_id', $userInfo['division_id']);
        } else {
            $field[] = Form::select('division_id', '区域代理', $info['file_name'] ?? '')->setOptions(Form::setOptions($options))->filterable(1);
            $field[] = Form::frameImage('image', '关联用户', $this->url('admin/system.user/list', ['fodder' => 'image'], true))->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
        }
        $field[] = Form::number('division_percent', '佣金比例', $userInfo['division_percent'] ?? '')->placeholder('代理商佣金比例1-100')->info('填写1-100，如填写50代表返佣50%,但是不能高于上级区域代理的比例')->style(['width' => '173px'])->min(0)->max(100)->required();
        $field[] = Form::date('division_end_time', '到期时间', isset($userInfo['division_end_time']) && $userInfo['division_end_time'] != 0 ? date('Y-m-d', $userInfo['division_end_time']) : '')->placeholder('代理商代理到期时间');
        $field[] = Form::radio('division_status', '代理状态', $userInfo['division_status'] ?? 1)->options([['label' => '开通', 'value' => 1], ['label' => '关闭', 'value' => 0]]);
        return create_form('代理商', $field, $this->url('/agent/division_agent/save'), 'POST');
    }

    /**
     * 保存代理商
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function saveDivisionAgent($data)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if ((int)$data['uid'] == 0) {
            $data['uid'] = $data['image']['uid'] ?? 0;
            if ((int)$data['uid'] == 0) throw new AdminException('请选择用户');
            $divisionType = $userServices->value(['uid' => $data['uid']], 'division_type');
            if ($divisionType == 1) throw new AdminException('此用户是区域代理，无法添加为代理商');
            if ($divisionType == 2) throw new AdminException('此用户是代理商，无法重复添加');
            if ($divisionType == 3) throw new AdminException('此用户是下级员工，无法添加为代理商');
        }
        $uid = $data['uid'];
        $agentData = [
            'spread_uid' => $data['division_id'],
            'spread_time' => time(),
            'division_name' => $data['division_name'],
            'division_id' => $data['division_id'],
            'division_status' => $data['division_status'],
            'division_percent' => $data['division_percent'],
            'division_change_time' => time(),
            'division_end_time' => strtotime($data['division_end_time']),
            'division_type' => 2,
            'agent_id' => $uid,
            'is_promoter' => 1,
        ];
        $division_info = $userServices->getUserInfo($data['division_id'], 'division_end_time,division_percent');
        if ($division_info) {
            if ($agentData['division_percent'] > $division_info['division_percent']) throw new AdminException('代理商比例不能大于上级区域代理');
            if ($agentData['division_end_time'] > $division_info['division_end_time']) throw new AdminException('代理商到期时间不能大于上级区域代理');
        }
        $res = $userServices->update($uid, $agentData);
        if ($res) return true;
        throw new AdminException('保存失败');
    }

    /**
     * 获取员工表单
     * @param $uid
     * @return mixed
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function getDivisionStaffForm($uid)
    {
        $field = [];
        $field[] = Form::frameImage('image', '关联用户', $this->url('admin/system.user/list', ['fodder' => 'image'], true))->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
        $field[] = Form::number('division_percent', '佣金比例', '')->placeholder('员工佣金比例1-100')->info('填写1-100，如填写50代表返佣50%,但是不能高于上级代理商的比例')->style(['width' => '173px'])->min(0)->max(100)->required();
        $field[] = Form::hidden('agent_id', $uid);
        return create_form('添加员工', $field, $this->url('/agent/division_staff/save'), 'POST');
    }

    /**
     * 保存员工
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function saveDivisionStaff($data)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if ((int)$data['uid'] == 0) {
            $data['uid'] = $data['image']['uid'] ?? 0;
            if ((int)$data['uid'] == 0) throw new AdminException('请选择用户');
            $divisionType = $userServices->value(['uid' => $data['uid']], 'division_type');
            if ($divisionType == 1) throw new AdminException('此用户是区域代理，无法添加为员工');
            if ($divisionType == 2) throw new AdminException('此用户是代理商，无法添加为员工');
            if ($divisionType == 3) throw new AdminException('此用户是员工，无法重复添加');
        }
        $agentInfo = $userServices->getUserInfo($data['agent_id'], 'division_id,agent_id,division_end_time,division_percent');
        $staffData = [
            'spread_uid' => $data['agent_id'],
            'spread_time' => time(),
            'division_type' => 3,
            'division_status' => 1,
            'division_id' => $agentInfo['division_id'],
            'agent_id' => $agentInfo['agent_id'],
            'staff_id' => $data['uid'],
            'division_percent' => $data['division_percent'],
            'division_change_time' => time(),
            'division_end_time' => $agentInfo['division_end_time'],
            'is_promoter' => 1,
        ];
        if ($staffData['division_percent'] > $agentInfo['division_percent']) throw new AdminException('员工比例不能大于上级代理商');
        $res = $userServices->update($data['uid'], $staffData);
        if ($res) return true;
        throw new AdminException('保存失败');
    }

    /**
     * 删除事业部/代理商/员工
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function deleteDivision($uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserInfo($uid);
        if (!$userInfo) throw new AdminException('用户不存在');
        $userInfo = $userInfo->toArray();
        $data = [
            'division_name' => '',
            'division_type' => 0,
            'division_status' => 0,
            'division_id' => 0,
            'agent_id' => 0,
            'staff_id' => 0,
            'division_percent' => 0,
            'division_end_time' => 0,
            'division_change_time' => time(),
            'division_invite' => 0
        ];
        $userServices->update(['uid' => $uid], $data);
        if ($userInfo['division_type'] == 1) {
            app()->make(SystemAdminServices::class)->delete(['division_id' => $uid]);
            $userServices->update(['division_id' => $uid], $data);
        } elseif ($userInfo['division_type'] == 2) {
            app()->make(DivisionApplyServices::class)->delete(['uid' => $uid]);
            $userServices->update(['agent_id' => $uid], $data);
        } elseif ($userInfo['division_type'] == 3) {
            $userServices->update(['staff_id' => $uid], $data);
        }
        return true;
    }

    /**
     * 设置状态
     * @param $uid
     * @param $status
     * @return bool
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function setStatus($uid, $status)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userServices->update($uid, ['division_status' => $status]);
        return true;
    }

    /**
     * 分成比例
     * @param $uid
     * @param $storeBrokerageRatio
     * @param $storeBrokerageRatioTwo
     * @return array
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionBrokerage($uid, $storeBrokerageRatio, $storeBrokerageRatioTwo)
    {
        if (!sys_config('brokerage_func_status')) {
            return [0, 0, 0, 0, 0];
        }
        $isSelfBrokerage = (int)sys_config('is_self_brokerage', 0);
        $division_open = (int)sys_config('division_status', 1);
        if (!$division_open) {
            /** 代理商关闭 */
            $storeBrokerageOne = $storeBrokerageRatio;
            $storeBrokerageTwo = $storeBrokerageRatioTwo;
            $staffPercent = 0;
            $agentPercent = 0;
            $divisionPercent = 0;
        } else {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->get($uid);
            if ($userInfo['division_type'] == 1) {
                /** 自己是事业部 */
                $storeBrokerageOne = 0;
                $storeBrokerageTwo = 0;
                $staffPercent = 0;
                $agentPercent = 0;
                if ($userInfo['division_status'] == 1 && $userInfo['division_end_time'] > time()) {
                    $divisionPercent = $isSelfBrokerage ? $userInfo['division_percent'] : 0;
                } else {
                    $divisionPercent = 0;
                }
            } elseif ($userInfo['division_type'] == 2) {
                /** 自己是代理商 */
                $divisionInfo = $userServices->get($userInfo['division_id']);
                $storeBrokerageOne = 0;
                $storeBrokerageTwo = 0;
                $staffPercent = 0;
                if ($userInfo['division_status'] == 1 && $userInfo['division_end_time'] > time()) {
                    $agentPercent = $isSelfBrokerage ? $userInfo['division_percent'] : 0;
                } else {
                    $agentPercent = 0;
                }
                if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                    $divisionPercent = bcsub($divisionInfo['division_percent'], $agentPercent, 2);
                } else {
                    $divisionPercent = 0;
                }
            } elseif ($userInfo['division_type'] == 3) { // 自己是员工
                /** 自己是员工 */
                $agentInfo = $userServices->get($userInfo['agent_id']);
                $divisionInfo = $userServices->get($userInfo['division_id']);
                $storeBrokerageOne = 0;
                $storeBrokerageTwo = 0;
                if ($userInfo['division_status'] == 1 && $userInfo['division_end_time'] > time()) {
                    $staffPercent = $isSelfBrokerage ? $userInfo['division_percent'] : 0;
                } else {
                    $staffPercent = 0;
                }
                if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                    $agentPercent = bcsub($agentInfo['division_percent'], $staffPercent, 2);
                } else {
                    $agentPercent = 0;
                }
                if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                    $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd($staffPercent, $agentPercent, 2), 2);
                } else {
                    $divisionPercent = 0;
                }
            } else {
                /** 自己是普通用户 */
                $staffInfo = $userServices->get($userInfo['staff_id']);
                $agentInfo = $userServices->get($userInfo['agent_id']);
                $divisionInfo = $userServices->get($userInfo['division_id']);

                if ($userInfo['staff_id']) {
                    /** 该用户为员工推广 */
                    if ($userInfo['staff_id'] == $userInfo['spread_uid']) {
                        /** 员工直接下级 */
                        $storeBrokerageOne = $isSelfBrokerage ? $storeBrokerageRatio : 0;
                        $storeBrokerageTwo = 0;
                        if ($staffInfo['division_status'] == 1 && $staffInfo['division_end_time'] > time()) {
                            $staffPercent = bcsub($staffInfo['division_percent'], $storeBrokerageOne, 2);
                        } else {
                            $staffPercent = 0;
                        }
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], bcadd($storeBrokerageOne, $staffPercent, 2), 2);
                        } else {
                            $agentPercent = 0;
                        }
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd(bcadd($storeBrokerageOne, $staffPercent, 2), $agentPercent, 2), 2);
                        } else {
                            $divisionPercent = 0;
                        }
                    } else {
                        $storeBrokerageOne = $storeBrokerageRatio;
                        $storeBrokerageTwo = $userServices->value(['uid' => $userInfo['spread_uid']], 'spread_uid') == $userInfo['staff_id'] && !$isSelfBrokerage ? 0 : $storeBrokerageRatioTwo;

                        $brokerageOneTwo = bcadd($storeBrokerageRatio, $storeBrokerageTwo, 2);

                        if ($staffInfo['division_status'] == 1 && $staffInfo['division_end_time'] > time()) {
                            $staffPercent = bcsub($staffInfo['division_percent'], $brokerageOneTwo, 2);
                        } else {
                            $staffPercent = 0;
                        }
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], bcadd($brokerageOneTwo, $staffPercent, 2), 2);
                        } else {
                            $agentPercent = 0;
                        }
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd(bcadd($brokerageOneTwo, $staffPercent, 2), $agentPercent, 2), 2);
                        } else {
                            $divisionPercent = 0;
                        }
                    }
                } elseif ($userInfo['agent_id']) {
                    /** 该用户为代理商推广 */
                    if ($userInfo['agent_id'] == $userInfo['spread_uid']) {
                        $storeBrokerageOne = $isSelfBrokerage ? $storeBrokerageRatio : 0;
                        $storeBrokerageTwo = 0;
                        $staffPercent = 0;
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], $storeBrokerageOne, 2);
                        } else {
                            $agentPercent = 0;
                        }
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd($storeBrokerageOne, $agentPercent, 2), 2);
                        } else {
                            $divisionPercent = 0;
                        }
                    } else {
                        $storeBrokerageOne = $storeBrokerageRatio;
                        $storeBrokerageTwo = $userServices->value(['uid' => $userInfo['spread_uid']], 'spread_uid') == $userInfo['agent_id'] && !$isSelfBrokerage ? 0 : $storeBrokerageRatioTwo;
                        $brokerageOneTwo = bcadd($storeBrokerageRatio, $storeBrokerageTwo, 2);
                        $staffPercent = 0;
                        if ($agentInfo['division_status'] == 1 && $agentInfo['division_end_time'] > time()) {
                            $agentPercent = bcsub($agentInfo['division_percent'], $brokerageOneTwo, 2);
                        } else {
                            $agentPercent = 0;
                        }
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], bcadd($brokerageOneTwo, $agentPercent, 2), 2);
                        } else {
                            $divisionPercent = 0;
                        }
                    }
                } elseif ($userInfo['division_id']) {
                    /** 该用户为事业部推广 */
                    if ($userInfo['division_id'] == $userInfo['spread_uid']) {
                        /** 事业部直接下级 */
                        $storeBrokerageOne = $isSelfBrokerage ? $storeBrokerageRatio : 0;
                        $storeBrokerageTwo = 0;
                        $staffPercent = 0;
                        $agentPercent = 0;
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], $storeBrokerageOne, 2);
                        } else {
                            $divisionPercent = 0;
                        }
                    } else {
                        $storeBrokerageOne = $storeBrokerageRatio;
                        $storeBrokerageTwo = $userServices->value(['uid' => $userInfo['spread_uid']], 'spread_uid') == $userInfo['division_id'] && !$isSelfBrokerage ? 0 : $storeBrokerageRatioTwo;
                        $brokerageOneTwo = bcadd($storeBrokerageRatio, $storeBrokerageTwo, 2);
                        $staffPercent = 0;
                        $agentPercent = 0;
                        if ($divisionInfo['division_status'] == 1 && $divisionInfo['division_end_time'] > time()) {
                            $divisionPercent = bcsub($divisionInfo['division_percent'], $brokerageOneTwo, 2);
                        } else {
                            $divisionPercent = 0;
                        }
                    }
                } else {
                    /** 没有任何代理商关系 */
                    $storeBrokerageOne = $storeBrokerageRatio;
                    $storeBrokerageTwo = $storeBrokerageRatioTwo;
                    $staffPercent = 0;
                    $agentPercent = 0;
                    $divisionPercent = 0;
                }
            }
        }
        return [max($storeBrokerageOne, 0), max($storeBrokerageTwo, 0), max($staffPercent, 0), max($agentPercent, 0), max($divisionPercent, 0)];
    }

    /**
     * 获取代理商下级员工列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function staffList($where)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        [$page, $limit] = $this->getPageValue();
        $whereData = [
            'agent_id' => $where['agent_id'],
            'division_type' => 3,
            'is_del' => 0,
            'nickname' => $where['keyword'],
        ];
        if ($where['start'] != 0) {
            $whereData['spread_time'] = [$where['start'], $where['stop']];
        }
        $count = $userServices->count($whereData);
        $brokerage = $orderService->sum(['division_agent_id' => $where['agent_id']], 'division_agent_brokerage');
        $list = $userServices->getList($whereData, 'avatar,nickname,uid,spread_uid,spread_time,division_percent,pay_count', $page, $limit);
        foreach ($list as &$item) {
            $item['spread_time'] = date('Y/m/d', $item['spread_time']);
            $item['childCount'] = $userServices->getCount(['agent_id' => $where['agent_id'], 'spread_uid' => $item['uid']]);
            $item['orderCount'] = $orderService->count(['uid' => $item['uid'], 'refund_status' => 0]);
            $item['numberCount'] = $orderService->sum(['uid' => $item['uid'], 'refund_status' => 0], 'pay_price');
        }
        return compact('list', 'count', 'brokerage');
    }

    /**
     * 修改员工返佣比例
     * @param $uid
     * @param $agentId
     * @param $divisionPercent
     * @return bool|\think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function staffPercent($uid, $agentId, $divisionPercent)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if ($divisionPercent >= $userServices->value(['uid' => $agentId], 'division_percent')) {
            throw new ApiException('返佣比例不能大于代理商返佣比例');
        }
        $userServices->update(['uid' => $uid, 'agent_id' => $agentId], ['division_percent' => $divisionPercent]);
        return true;
    }

    /**
     * 删除员工
     * @param $uid
     * @param $agentId
     * @return bool
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function delStaff($uid, $agentId)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $data = [
            'division_name' => '',
            'division_type' => 0,
            'division_status' => 0,
            'division_id' => 0,
            'agent_id' => 0,
            'staff_id' => 0,
            'division_percent' => 0,
            'division_end_time' => 0,
            'division_change_time' => time(),
            'division_invite' => 0
        ];
        $userServices->update(['uid' => $uid, 'agent_id' => $agentId], $data);
        $userServices->update(['staff_id' => $uid, 'agent_id' => $agentId], $data);
        return true;
    }

    /**
     * 获取事业部订单列表
     * @param $adminInfo
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionOrder($adminInfo, $where)
    {
        if ($adminInfo['level'] != 0) {
            $where['division_id'] = $adminInfo['division_id'] ?: -1;
        }
        $where['division_id'] = $where['division_id'] != '' ? $where['division_id'] : -1;
        $where['division_agent_id'] = $where['division_agent_id'] != '' ? $where['division_agent_id'] : -1;
        $where['pid'] = 0;
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        return $orderServices->getOrderList($where);
    }

    public function divisionOption()
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $list = $userServices->getColumn(['division_type' => 1], 'division_name', 'uid');
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = ['value' => $key, 'label' => $value];
        }
        return $data;
    }

    public function agentOption($division_id)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $list = $userServices->getColumn(['division_type' => 2, 'division_id' => $division_id], 'division_name', 'uid');
        $data = [];
        foreach ($list as $key => $value) {
            $data[] = ['value' => $key, 'label' => $value];
        }
        return $data;
    }

    /**
     * 获取事业部统计数据
     * @param $divisionId
     * @return array
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionStatistics($divisionId)
    {
        $where = $divisionId != 0 ? ['division_id' => $divisionId] : [];
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $divisionNum = $userServices->count(['division_type' => 1]);
        $agentNum = $userServices->count(['division_type' => 2] + $where);
        $staffNum = $userServices->count(['division_type' => 3] + $where);
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $divisionOrderNum = $orderServices->count(['division_type' => 1, 'pid' => 0, 'paid' => 1] + $where);
        $orderPrice = $orderServices->sum(['division_type' => 1, 'pid' => 0, 'paid' => 1] + $where, 'pay_price', true);
        $divisionBrokerage = $orderServices->sum(['division_type' => 1, 'pid' => 0, 'paid' => 1] + $where, 'division_brokerage', true);
        $divisionAgentBrokerage = $orderServices->sum(['division_type' => 1, 'pid' => 0, 'paid' => 1] + $where, 'division_agent_brokerage', true);
        $divisionStaffBrokerage = $orderServices->sum(['division_type' => 1, 'pid' => 0, 'paid' => 1] + $where, 'division_staff_brokerage', true);
        $divisionBrokeragePrice = bcadd(bcadd($divisionBrokerage, $divisionAgentBrokerage, 2), $divisionStaffBrokerage, 2);
        return [
            [
                'name' => '区域代理数量',
                'count' => $divisionNum,
                'className' => 'md-contacts',
                'col' => 4,
            ],
            [
                'name' => '代理商数量',
                'count' => $agentNum,
                'className' => 'md-contact',
                'col' => 4,
            ],
            [
                'name' => '员工数量',
                'count' => $staffNum,
                'className' => 'md-cart',
                'col' => 4,
            ],
            [
                'name' => '总订单数',
                'count' => $divisionOrderNum,
                'className' => 'md-bug',
                'col' => 4,
            ],
            [
                'name' => '总订单金额',
                'count' => $orderPrice,
                'className' => 'md-basket',
                'col' => 4,
            ],
            [
                'name' => '获得佣金金额',
                'count' => $divisionBrokeragePrice,
                'className' => 'ios-at-outline',
                'col' => 4,
            ],
        ];
    }

    /**
     * 获取事业部趋势数据
     * @param $time
     * @param $divisionId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function divisionTrend($time, $divisionId)
    {
        $timeArr = explode('-', $time);
        if (count($timeArr) != 2) throw new AdminException('参数错误');
        $dayCount = (strtotime($timeArr[1]) - strtotime($timeArr[0])) / 86400 + 1;
        $data = [];
        if ($dayCount == 1) {
            $data = $this->trend($timeArr, 0, $divisionId);
        } elseif ($dayCount > 1 && $dayCount <= 31) {
            $data = $this->trend($timeArr, 1, $divisionId);
        } elseif ($dayCount > 31 && $dayCount <= 92) {
            $data = $this->trend($timeArr, 3, $divisionId);
        } elseif ($dayCount > 92) {
            $data = $this->trend($timeArr, 30, $divisionId);
        }
        return $data;
    }

    /**
     * 获取事业部趋势图数据
     * @param $time
     * @param $num
     * @param $divisionId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function trend($time, $num, $divisionId)
    {
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);

        if ($num == 0) {
            $xAxis = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];
            $timeType = '%H';
        } elseif ($num != 0) {
            $dt_start = strtotime($time[0]);
            $dt_end = strtotime($time[1]);
            while ($dt_start <= $dt_end) {
                if ($num == 30) {
                    $xAxis[] = date('Y-m', $dt_start);
                    $dt_start = strtotime("+1 month", $dt_start);
                    $timeType = '%Y-%m';
                } else {
                    $xAxis[] = date('Y-m-d', $dt_start);
                    $dt_start = strtotime("+$num day", $dt_start);
                    $timeType = '%Y-%m-%d';
                }
            }
        }
        $pay_price = array_column($storeOrder->getDivisionTrend($time, $timeType, $divisionId, 'sum(pay_price)'), 'num', 'days');
        $pay_count = array_column($storeOrder->getDivisionTrend($time, $timeType, $divisionId, 'count(id)'), 'num', 'days');
        $data = $series = [];
        foreach ($xAxis as $item) {
            $data['订单金额'][] = isset($pay_price[$item]) ? floatval($pay_price[$item]) : 0;
            $data['订单量'][] = isset($pay_count[$item]) ? floatval($pay_count[$item]) : 0;
        }
        foreach ($data as $key => $item) {
            $series[] = [
                'name' => $key,
                'data' => $item,
                'type' => 'line',
            ];
        }
        return compact('xAxis', 'series');
    }

    /**
     * 事业部统计排行
     * @param $divisionId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/26
     */
    public function divisionRanking($divisionId)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        if ($divisionId == 0) {
            $data = $userServices->getUserList(['division_type' => 1, 'status' => 1, 'is_del' => 0], 'uid,nickname');
        } else {
            $data = $userServices->getUserList(['division_type' => 2, 'division_id' => $divisionId, 'status' => 1, 'is_del' => 0], 'uid,nickname');
        }
        $list = [];
        foreach ($data['list'] as $item) {
            $divisionBrokerage = $orderServices->sum(['division_id' => $item['uid'], 'pid' => 0, 'paid' => 1], 'division_brokerage', true);
            $divisionAgentBrokerage = $orderServices->sum(['division_id' => $item['uid'], 'pid' => 0, 'paid' => 1], 'division_agent_brokerage', true);
            $divisionStaffBrokerage = $orderServices->sum(['division_id' => $item['uid'], 'pid' => 0, 'paid' => 1], 'division_staff_brokerage', true);
            $list[] = [
                'nickname' => $item['nickname'],
                'spread_agent' => $userServices->count(['division_type' => 2, 'division_id' => $item['uid'], 'status' => 1, 'is_del' => 0]),
                'spread_staff' => $userServices->count(['division_type' => 3, 'division_id' => $item['uid'], 'status' => 1, 'is_del' => 0]),
                'order_price' => $orderServices->sum(['division_id' => $item['uid'], 'pid' => 0, 'paid' => 1], 'pay_price', true),
                'brokerage_price' => bcadd(bcadd($divisionBrokerage, $divisionAgentBrokerage, 2), $divisionStaffBrokerage, 2),
                'order_num' => $orderServices->count(['division_id' => $item['uid'], 'pid' => 0, 'paid' => 1])
            ];
        }
        return compact('list');
    }

    /**
     * 获取代理商小程序推广码
     * @param $uid
     * @return false|mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function agentSpreadCode($uid)
    {
        $userInfo = app()->make(UserServices::class)->getUserInfo($uid);
        if (!$userInfo) {
            throw new AdminException('数据不存在');
        }
        $name = 'division_agent_' . $userInfo['uid'] . '.jpg';
        /** @var QrcodeServices $QrcodeService */
        $QrcodeService = app()->make(QrcodeServices::class);
        $resForever = $QrcodeService->qrCodeForever($uid, 'division_agent');
        $url = $QrcodeService->getRoutineQrcodePath((int)$resForever['id'], $uid, 110, $name);
        return compact('url');
    }

    /**
     * 绑定员工
     * @param $uid
     * @param int $agentId
     * @param int $agentCode
     * @return false|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function agentSpreadStaff($uid, int $agentId = 0, int $agentCode = 0)
    {
        if ($agentCode && !$agentId) {
            /** @var QrcodeServices $qrCode */
            $qrCode = app()->make(QrcodeServices::class);
            if ($info = $qrCode->getOne(['id' => $agentCode, 'third_type' => 'division_agent', 'status' => 1])) {
                $agentId = $info['third_id'];
            }
        }
        if (!$agentId) throw new ApiException('上级代理商不存在');
        if ($uid == $agentId) throw new ApiException('自己不能推荐自己');
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $agentInfo = $userServices->getUserInfo($agentId, 'division_id,agent_id,division_type');
        if (!$agentInfo) throw new ApiException('上级用户不存在');
        $userInfo = $userServices->getUserInfo($uid, 'division_type');
        if (!$userInfo) throw new ApiException('用户不存在');
        if ($userInfo['division_type'] == 1) throw new ApiException('您是区域代理,不能绑定成为别人的员工');
        if ($userInfo['division_type'] == 2) throw new ApiException('您是代理商,不能绑定成为别人的员工');
        if ($userInfo['division_type'] == 3) throw new ApiException('您已经是员工,不能绑定成为别人的员工');
        $staffData = [
            'spread_uid' => $agentId,
            'spread_time' => time(),
            'division_type' => 3,
            'division_status' => 1,
            'division_id' => $agentInfo['division_id'],
            'agent_id' => $agentInfo['agent_id'],
            'staff_id' => $uid,
            'division_end_time' => $agentInfo['division_end_time'],
            'division_change_time' => time(),
            'is_promoter' => 1,
        ];
        $res = $userServices->update($uid, $staffData);
        if ($res) return true;
        throw new ApiException('绑定员工失败');
    }
}
