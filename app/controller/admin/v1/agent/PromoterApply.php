<?php

namespace app\controller\admin\v1\agent;

use app\controller\admin\AuthController;
use app\services\agent\PromoterApplyServices;
use think\annotation\Inject;

class PromoterApply extends AuthController
{
    /**
     * @var PromoterApplyServices
     */
    #[Inject]
    protected PromoterApplyServices $services;

    /**
     * 分销员申请列表
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
        return $this->success($this->services->applyList($where));
    }

    /**
     * 申请审核
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2024/8/21
     */
    public function applyExamine($id, $uid, $status)
    {
        $this->services->applyExamine($id, $uid, $status);
        return $this->success($status == 1 ? '审核通过' : '拒绝成功');
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
        $this->services->applyDelete($id);
        return $this->success('删除成功');
    }
}