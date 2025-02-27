<?php

namespace app\services\agent;

use app\dao\agent\PromoterApplyDao;
use app\services\BaseServices;
use app\services\other\AgreementServices;
use app\services\user\UserServices;
use crmeb\exceptions\ApiException;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;

class PromoterApplyServices extends BaseServices
{
    /**
     * @var PromoterApplyDao
     */
    #[Inject]
    protected PromoterApplyDao $dao;

    public function applyInfo($uid, $user)
    {
        $applyInfo = $this->dao->get(['uid' => $uid, 'is_del' => 0]);
        $user = [
            'id' => $applyInfo['id'] ?? 0,
            'uid' => $uid,
            'nickname' => $user['nickname'] ?? '',
            'real_name' => $user['real_name'] ?? '',
            'phone' => $user['phone'] ?? '',
            'status' => $applyInfo ? $applyInfo['status'] : -1,
            'add_time' => $applyInfo ? date('Y/m/d H:i', $applyInfo['add_time']) : '',
            'status_time' => $applyInfo && $applyInfo['status_time'] ? date('Y/m/d H:i', $applyInfo['status_time']) : '',
        ];
        $agreement = app()->make(AgreementServices::class)->getAgreementBytype(2);
        return compact('user', 'agreement');
    }

    public function applyPromoter($data, $id, $userInfo)
    {
        if (!sys_config('brokerage_func_status')) throw new ApiException('未开启推广功能');
        if (sys_config('store_brokerage_statu') != 1) throw new ApiException('非指定分销模式无需申请推广员');
        if ($userInfo['is_promoter']) throw new ApiException('您已经是推广员');
        if ($data['phone'] != $userInfo['phone']) {
            $phoneUsed = app()->make(UserServices::class)->count(['phone' => $data['phone']]);
            if ($phoneUsed) throw new ApiException('该手机号已被使用');
        }
        if ($id) {
            $data['status'] = 0;
            $res = $this->dao->update(['id' => $id], $data);
        } else {
            $data['add_time'] = time();
            $this->dao->update(['uid' => $data['uid']], ['is_del' => 1]);
            $res = $this->dao->save($data);
            $id = $res->id;
        }
        if (!$res) throw new ApiException('申请失败');
        return $id;
    }

    public function applyList($where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->applyList($where, $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['status_time'] = date('Y-m-d H:i:s', $item['status_time']);
        }
        $count = $this->dao->applyCount($where);
        return compact('list', 'count');
    }

    public function applyExamine($id, $uid, $status)
    {
        $this->dao->update(['id' => $id], ['status' => $status, 'status_time' => time()]);
        if ($status == 1) {
            app()->make(UserServices::class)->update(['uid' => $uid], ['is_promoter' => 1]);
        }
        return true;
    }

    public function applyDelete($id)
    {
        $this->dao->update(['id' => $id], ['is_del' => 1]);
        return true;
    }
}
