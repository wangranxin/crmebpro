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
namespace app\controller\api\v1\diy;


use app\Request;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\activity\newcomer\StoreNewcomerServices;
use app\services\activity\video\VideoServices;
use app\services\diy\DiyServices;
use app\services\product\product\StoreProductLogServices;
use app\services\product\product\StoreProductRankServices;use app\services\user\level\SystemUserLevelServices;
use app\services\user\UserRelationServices;
use app\services\user\UserServices;
use app\services\user\UserSignServices;
use think\annotation\Inject;

/**
 * Class Diy
 * @package app\controller\api\v1\diy
 */
class Diy
{
    /**
     * @var DiyServices
     */
    #[Inject]
    protected DiyServices $services;

	/**
 	* 获取页面数据
	* @param $id
	* @return \think\Response
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function getDiy($id = 0)
    {
        return app('json')->successful($this->services->getDiyInfo((int)$id));
    }

    /**
 	* 获取diy缓存版本
	* @param $id
	* @return \think\Response
	* @throws \Throwable
	*/
    public function getDiyVersion($id = 0)
    {
        return app('json')->successful(['version' => $this->services->getDiyVersion((int)$id)]);
    }

	/**
 	* 获取底部导航
	* @param $template_name
	* @return \think\Response
	*/
    public function getNavigation($template_name = '')
    {
        return app('json')->success($this->services->getNavigation((string)$template_name));
    }

	/**
 	* 获取diy用户数据
	* @param Request $request
	* @param UserServices $userServices
	* @return mixed
	 */
	public function userInfo(Request $request, UserServices $userServices)
	{
		$uid = (int)$request->uid();
		$userInfo = [];
		if ($uid) {
			$userInfo = $userServices->getUserInfo($uid, 'uid,nickname,phone,avatar,level,integral,now_money,exp,is_money_level,bar_code');
			if ($userInfo) {
				$userInfo = $userInfo->toArray();
				/** @var StoreCouponUserServices $storeCoupon */
        		$storeCoupon = app()->make(StoreCouponUserServices::class);
				$userInfo['coupon_num'] = $storeCoupon->getUserValidCouponCount((int)$uid);
				$userInfo['vip_name'] = '';
				if ($userInfo['level']) {
					/** @var SystemUserLevelServices $systemUserLevel */
					$systemUserLevel = app()->make(SystemUserLevelServices::class);
					$levelList = $systemUserLevel->getList(['is_del' => 0, 'is_show' => 1], 'id,name,exp_num');
					$i = 0;
					foreach ($levelList as &$level) {
						if ($level['id'] == $userInfo['level']) {
							$userInfo['vip_name'] = $level['name'];
						}
						$level['next_exp_num'] = $levelList[$i + 1]['exp_num'] ?? $level['exp_num'];
						$i++;
					}
					$levelList = array_combine(array_column($levelList,'id'), $levelList);
					$userInfo['next_exp'] = $levelList[$userInfo['level']]['next_exp_num'] ?? 0;
				} else {
					/** @var SystemUserLevelServices $systemUserLevel */
					$systemUserLevel = app()->make(SystemUserLevelServices::class);
					$levelList = $systemUserLevel->getList(['is_del' => 0, 'is_show' => 1], 'id,name,exp_num');
					$userInfo['next_exp'] = $levelList[0]['exp_num'] ?? 0;
				}
                /** @var UserRelationServices $productRelation */
                $productRelation = app()->make(UserRelationServices::class);
                $collectCategory = sys_config('video_func_status', 1) ? '' : 'product';
                $userInfo['collectCount'] = $productRelation->getUserCount($uid, 0, 'collect', $collectCategory);
                /** @var StoreProductLogServices $storeProductLogServices */
                $storeProductLogServices = app()->make(StoreProductLogServices::class);
                $userInfo['visit_num'] = $storeProductLogServices->getDistinctCount(['uid' => $uid, 'type' => 'visit'], 'product_id');
			}
		}
		return app('json')->success($userInfo);
	}

	/**
 	* 获取diy短视频
	* @param Request $request
	* @param VideoServices $videoServices
	* @return mixed
	 */
	public function videoList(Request $request, VideoServices $videoServices)
	{
		$uid = (int)$request->uid();
		return app('json')->success($videoServices->getDiyVideoList($uid));
	}

	/**
 	* 获取新人礼商品
	* @param Request $request
	* @param StoreNewcomerServices $newcomerServices
	* @return mixed
	 */
	public function newcomerList(Request $request, StoreNewcomerServices $newcomerServices)
	{
		$where = $request->getMore([
            ['priceOrder', ''],
            ['salesOrder', ''],
        ]);
		$uid = (int)$request->uid();
		return app('json')->success($newcomerServices->getDiyNewcomerList($uid, $where));
	}

    /**
     * 首页diy签到数据
     * @param Request $request
     * @return \think\Response
     */
    public function diySign(Request $request, UserSignServices $services)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($services->homeDiysignData($uid));
    }

	/**
 	* 商品排行榜
	* @param Request $request
	* @param StoreProductRankServices $productRankServices
	* @return \think\Response
	* @throws \ReflectionException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	* @throws \throwable
	*/
	public function productRank(Request $request, StoreProductRankServices $productRankServices)
	{
		[$limit] = $request->getMore([
            ['limit', 3]
        ], true);
		$uid = 0;
        if ($request->hasMacro('uid')) $uid = (int)$request->uid();
		$data = [];
		$data['sales'] = $productRankServices->getProductRankList($uid, 1, [], $limit);
		$data['star'] = $productRankServices->getProductRankList($uid, 2, [], $limit);
		$data['collect'] = $productRankServices->getProductRankList($uid, 3, [], $limit);
		return app('json')->success($data);
	}

    /**
     * 悬浮框
     * @return \think\Response
     * User: liusl
     * DateTime: 2024/9/6 10:17
     */
    public function getSuspendedDiy()
    {
        $data = $this->services->getSuspendedDiy();
        return app('json')->success($data);
    }
}
