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

namespace app\controller\out;


use app\Request;
use app\services\out\OutAccountServices;
use crmeb\basic\BaseController;
use app\validate\out\LoginValidate;
use think\annotation\Inject;

/**
 * Class Login
 * @package app\kefu\controller
 */
class OutAccount extends BaseController
{

    /**
     * @var OutAccountServices
     */
    #[Inject]
    protected OutAccountServices $services;

    /**
     * 客服登录
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getToken(Request $request)
    {
        [$appid, $appsecret] = $request->postMore([
            ['appid', ''],
            ['appsecret', ''],
        ], true);
        $this->validate(['appid' => $appid, 'appsecret' => $appsecret], LoginValidate::class);

        $token = $this->services->authLogin($appid, $appsecret);

        return $this->success('获取成功', $token);
    }

    /**
     * 刷新token
     * @return void
     */
    public function refreshToken(Request $request)
    {
        [$token] = $request->postMore([
            ['access_token', ''],
        ], true);
        $token = $this->services->refresh($token);
        return app('json')->success('操作成功', $token);
    }

}
