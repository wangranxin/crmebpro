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

namespace app\controller\admin\v1\work;


use app\controller\admin\AuthController;
use app\services\store\SystemStoreStaffServices;
use app\services\user\label\UserLabelServices;
use app\services\work\WorkClientFollowServices;
use app\services\work\WorkDepartmentServices;
use app\services\work\WorkMemberRelationServices;
use app\services\work\WorkMemberServices;
use crmeb\services\wechat\config\WorkConfig;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 企业微信成员
 * Class Member
 * @package app\controller\admin\v1\work
 */
class Member extends AuthController
{

    /**
     * @var WorkMemberServices
     */
    #[Inject]
    protected WorkMemberServices $services;

    /**
     * @param WorkConfig $config
     * @return mixed
     */
    public function index(WorkConfig $config)
    {
        $where = $this->request->getMore([
            ['department', ''],
            ['name', '']
        ]);
        $where['corp_id'] = $config->corpId;
        return $this->success($this->services->getList($where));
    }

    /**
     * 获取企业微信客户标签
     * @param UserLabelServices $services
     * @return mixed
     */
    public function getUserLabel(UserLabelServices $services)
    {
        return $this->success($services->getWorkLabel());
    }

    /**
     * 获取企业微信组织架构
     * @param WorkConfig $config
     * @param WorkDepartmentServices $services
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getMemberTree(WorkConfig $config, WorkDepartmentServices $services)
    {
        return $this->success($services->getMailChildren($config->corpId));
    }

    /**
     * 同步企业微信成员
     * @param WorkDepartmentServices $services
     * @return mixed
     */
    public function synchMember(WorkDepartmentServices $services, WorkClientFollowServices $followService, WorkMemberRelationServices $workMemberRelationServices)
    {
        $workMemberRelationServices->delete([["member_id","<>",0]]);
        $followService->delete([["id","<>",0]]);
        $this->services->delete([["id","<>",0]]);
        $services->delete([["id","<>",0]]);
        $services->authDepartment();
        return $this->success('已加入消息队列,请稍后查看');
    }
}
