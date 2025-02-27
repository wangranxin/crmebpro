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

namespace app\controller\api\v2\user;


use app\services\user\UserSearchServices;
use think\annotation\Inject;
use think\Request;

/**
 * Class UserSearch
 * @package app\api\controller\v2\user
 */
class UserSearch
{
    /**
     * @var UserSearchServices
     */
    #[Inject]
    protected UserSearchServices $services;

    /**
     * @param Request $request
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/19
     */
    public function getUserSearchList(Request $request)
    {
        return app('json')->successful($this->services->getUserList((int)$request->uid()));
    }

    public function cleanUserSearch(Request $request)
    {
        $uid = (int)$request->uid();
        $this->services->update(['uid' => $uid], ['is_del' => 1]);
        return app('json')->successful('删除成功');
    }
}
