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
namespace app\controller\api\admin\user;

use app\Request;
use app\services\user\UserAddressServices;
use think\annotation\Inject;


/**
 * 用户地址类
 * Class UserAddress
 * @package app\api\controller\store
 */
class UserAddress
{

    /**
     * @var UserAddressServices
     */
    #[Inject]
    protected UserAddressServices $services;


    /**
 	* 地址列表
	* @param Request $request
	* @param $uid
	* @return \think\Response
	*/
    public function address_list(Request $request, $uid)
    {
        $uid = (int)$uid;
		$list = [];
		if ($uid) {
			$list = $this->services->getUserAddressList($uid);
		}
        return app('json')->successful($list);
    }

	/**
 	* 获取默认地址
	* @param Request $request
	* @param $uid
	* @return \think\Response
	* @throws \throwable
	*/
    public function address_default(Request $request, $uid)
    {
        $uid = (int)$uid;
		$defaultAddress = [];
        if ($uid) $defaultAddress = $this->services->getUserDefaultAddressCache($uid);
        if ($defaultAddress) {
            return app('json')->successful('ok', $defaultAddress);
        }
        return app('json')->successful('empty', []);
    }

}
