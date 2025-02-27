<?php

namespace app\controller\api\v1\user;

use app\Request;
use app\services\agent\DivisionApplyServices;
use app\services\agent\PromoterApplyServices;
use crmeb\services\CacheService;
use think\annotation\Inject;

class PromoterApply
{
    /**
     * @var PromoterApplyServices
     */
    #[Inject]
    protected PromoterApplyServices $services;


    public function applyInfo(Request $request)
    {
        $user = $request->user();
        $uid = $request->uid();
        $data = $this->services->applyInfo($uid, $user);
        return app('json')->success($data);
    }

    public function applyPromoter(Request $request, $id)
    {
        $data = $request->postMore([
            ['uid', 0],
            ['nickname', ''],
            ['real_name', ''],
            ['phone', ''],
            ['code', 0]
        ]);
        $data['uid'] = $request->uid();
        $userInfo = $request->user();
        $verifyCode = CacheService::get('code_' . $data['phone']);
        if ($verifyCode != $data['code']) return app('json')->fail('验证码错误');
        unset($data['code']);
        $id = $this->services->applyPromoter($data, $id, $userInfo);
        return app('json')->success('申请成功', ['id' => $id]);
    }
}