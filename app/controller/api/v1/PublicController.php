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
namespace app\controller\api\v1;


use app\services\activity\combination\StorePinkServices;
use app\services\diy\DiyServices;
use app\services\message\service\StoreServiceServices;
use app\services\other\AgreementServices;
use app\services\product\product\StoreProductWordsServices;
use app\services\store\DeliveryServiceServices;
use app\services\other\CacheServices;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\other\ExpressServices;
use app\services\order\StoreOrderServices;
use app\services\other\SystemCityServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\config\SystemConfigServices;
use app\services\store\SystemStoreServices;
use app\services\user\UserBillServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use Joypack\Tencent\Map\Bundle\Location;
use Joypack\Tencent\Map\Bundle\LocationOption;
use app\Request;
use crmeb\services\CacheService;
use crmeb\services\UploadService;

/**
 * 公共类
 * Class PublicController
 * @package app\api\controller
 */
class PublicController
{
    /**
     * 主页获取
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \throwable
     */
    public function index(Request $request)
    {
        $uid = $request->hasMacro('uid') ? (int)$request->uid() : 0;
        $banner = sys_data('routine_home_banner') ?: [];// 首页banner图
        $menus = sys_data('routine_home_menus') ?: [];// 首页按钮
        $roll = sys_data('routine_home_roll_news') ?: [];// 首页滚动新闻
        $activity = sys_data('routine_home_activity', 3) ?: [];// 首页活动区域图片
        $explosive_money = sys_data('index_categy_images') ?: [];// 首页超值爆款
        $site_name = sys_config('site_name');
        $routine_index_page = sys_data('routine_index_page');
        $info['fastInfo'] = $routine_index_page[0]['fast_info'] ?? '';// 快速选择简介
        $info['bastInfo'] = $routine_index_page[0]['bast_info'] ?? '';// 精品推荐简介
        $info['firstInfo'] = $routine_index_page[0]['first_info'] ?? '';// 首发新品简介
        $info['salesInfo'] = $routine_index_page[0]['sales_info'] ?? '';// 促销单品简介
        $logoUrl = sys_config('routine_index_logo');// 促销单品简介
        if (strstr($logoUrl, 'http') === false && $logoUrl) {
            $logoUrl = sys_config('site_url') . $logoUrl;
        }
        $logoUrl = str_replace('\\', '/', $logoUrl);
        $fastNumber = (int)sys_config('fast_number', 0);// 快速选择分类个数
        $bastNumber = (int)sys_config('bast_number', 0);// 精品推荐个数
        $firstNumber = (int)sys_config('first_number', 0);// 首发新品个数
        $promotionNumber = (int)sys_config('promotion_number', 0);// 首发新品个数

        /** @var StoreProductCategoryServices $categoryService */
        $categoryService = app()->make(StoreProductCategoryServices::class);
        $info['fastList'] = $fastNumber ? $categoryService->byIndexList($fastNumber, 'id,cate_name,pid,pic') : [];// 快速选择分类个数
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $where = ['product_type' => [0, 1, 2, 3]];
        $info['bastList'] = $bastNumber ? $storeProductServices->getRecommendProduct($uid, $where + ['is_best' => 1], $bastNumber) : [];// 精品推荐个数
        $info['firstList'] = $firstNumber ? $storeProductServices->getRecommendProduct($uid, $where + ['is_new' => 1], $firstNumber) : [];// 首发新品个数
        $info['bastBanner'] = sys_data('routine_home_bast_banner') ?? [];// 首页精品推荐图片
        $benefit = $promotionNumber ? $storeProductServices->getRecommendProduct($uid, $where + ['is_benefit' => 1], $promotionNumber) : [];// 首页促销单品
        $lovely = sys_data('routine_home_new_banner') ?: [];// 首发新品顶部图
        $likeInfo = $storeProductServices->getRecommendProduct($uid, $where + ['is_hot' => 1], 3);// 热门榜单 猜你喜欢

        if ($request->uid()) {
            /** @var WechatUserServices $wechatUserService */
            $wechatUserService = app()->make(WechatUserServices::class);
            $subscribe = (bool)$wechatUserService->value(['uid' => $request->uid()], 'subscribe');
        } else {
            $subscribe = true;
        }
        $newGoodsBananr = sys_config('new_goods_bananr');
        $tengxun_map_key = sys_config('tengxun_map_key');
        return app('json')->successful(compact('banner', 'menus', 'roll', 'info', 'activity', 'lovely', 'benefit', 'likeInfo', 'logoUrl', 'site_name', 'subscribe', 'newGoodsBananr', 'tengxun_map_key', 'explosive_money'));
    }

    /**
     * 获取分享配置
     * @return mixed
     */
    public function share()
    {
        $data['img'] = sys_config('wechat_share_img');
        if ($data['img'] && strstr($data['img'], 'http') === false) {
            $data['img'] = sys_config('site_url') . $data['img'];
        }
        $data['img'] = str_replace('\\', '/', $data['img']);
        $data['title'] = sys_config('wechat_share_title');
        $data['synopsis'] = sys_config('wechat_share_synopsis');
        return app('json')->successful($data);
    }

    /**
     * 获取网站配置
     * @return mixed
     */
    public function getSiteConfig()
    {
        $data['record_No'] = sys_config('record_No');
        return app('json')->success($data);
    }

    /**
     * 获取个人中心菜单
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function menu_user(Request $request)
    {
        $menusInfo = sys_data('routine_my_menus') ?? [];
        $uid = 0;
        $userInfo = [];
        if ($request->hasMacro('user')) $userInfo = $request->user();
        if ($request->hasMacro('uid')) $uid = (int)$request->uid();

        $vipOpen = sys_config('member_func_status');
        $brokerageFuncStatus = sys_config('brokerage_func_status');
        $brokerageType = sys_config('brokerage_page_type');
        $storeBrokerageApply = sys_config('store_brokerage_apply');
        $balanceFuncStatus = sys_config('balance_func_status');
        $vipCard = sys_config('member_card_status', 0);
        $svipOpen = (bool)sys_config('member_card_status');
        $isDivisionOpen = (bool)sys_config('division_open');
        $isDivisionApplyOpen = (bool)sys_config('division_apply_open');
        $userService = $invoiceStatus = $deliveryUser = $isUserPromoter = $userVerifyStatus = $userOrder = $isStaff = $isDelivery = true;
        $levelStatus = true;
        $userDivisionStatus = false;

        if ($uid && $userInfo) {
            /** @var StoreServiceServices $storeService */
            $storeService = app()->make(StoreServiceServices::class);
            $userService = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1, 'account_status' => 1]);
            $userOrder = $storeService->checkoutIsService(['uid' => $uid, 'account_status' => 1, 'customer' => 1]);
            /** @var UserServices $user */
            $user = app()->make(UserServices::class);
            /** @var UserInvoiceServices $userInvoice */
            $userInvoice = app()->make(UserInvoiceServices::class);
            $invoiceStatus = $userInvoice->invoiceFuncStatus(false);
            /** @var DeliveryServiceServices $deliveryService */
            $deliveryService = app()->make(DeliveryServiceServices::class);
            $deliveryUser = $deliveryService->checkoutIsService($uid);
            $isUserPromoter = $user->checkUserPromoter($uid, $userInfo);
            try {
                $isDelivery = $deliveryService->getDeliveryInfoByUid($uid);
            } catch (\Throwable $e) {
                $isDelivery = false;
            }
            $levelStatus = (bool)($userInfo['level_status'] ?? 1);
            if ($userInfo['division_type'] > 0) {
                $userDivisionStatus = $userInfo['division_status'] == 1 && $userInfo['division_end_time'] > time();
            } else {
                $userDivisionStatus = true;
            }
        }
        $auth = [];
        $auth['/pages/users/user_vip/index'] = !$vipOpen;
        $auth['/pages/users/user_spread_user/index'] = !$brokerageFuncStatus || !$isUserPromoter || $uid == 0 || !$userDivisionStatus;
        $auth['/pages/users/agent/apply'] = !$brokerageFuncStatus || !$isUserPromoter || $uid == 0 || !$isDivisionOpen || !$isDivisionApplyOpen || !$userInfo['division_type'] == 0;
        $auth['/pages/users/distributor/apply'] = !$brokerageFuncStatus || $isUserPromoter || $uid == 0 || !$storeBrokerageApply;
        $auth['/pages/users/user_money/index'] = !$balanceFuncStatus;
        $auth['/pages/admin/order/index'] = true;
        $auth['/pages/admin/order_cancellation/index'] = (!$userService && !$deliveryUser) || $uid == 0;
        $auth['/pages/users/user_invoice_list/index'] = !$invoiceStatus;
        $auth['/pages/annex/vip_paid/index'] = !$vipCard || !$svipOpen;
        $auth['/kefu/mobile_list'] = !$userService || $uid == 0;
        $auth['/pages/admin/distribution/index'] = $uid == 0 || !$isDelivery;
        $auth['/pages/store_spread/index'] = true;
        $auth['/pages/admin/store/index'] = true;
        $auth['/pages/admin/work/index'] = !$userOrder || $uid == 0;
        foreach ($menusInfo as $key => &$value) {
            $value['pic'] = set_file_url($value['pic'] ?? '');
            $value['url'] = $value['url'] ?? '';
            if (isset($auth[$value['url']]) && $auth[$value['url']]) {
                unset($menusInfo[$key]);
                continue;
            }
            if ($value['url'] == '/kefu/mobile_list') {
                $value['url'] = sys_config('site_url') . $value['url'];
                if ($request->isRoutine()) {
                    $value['url'] = str_replace('http://', 'https://', $value['url']);
                }
            }
            //用户等级 未激活地址改为激活页面
            if ($value['url'] == '/pages/users/user_vip/index' && sys_config('level_activate_status') && !$levelStatus) {
                $value['url'] = '/pages/annex/vip_grade_active/index';
            }

            if ($value['url'] == '/pages/users/user_spread_user/index') {
                if($brokerageType == 2){
                    $value['url'] = '/pages/users/spreadData/index';
                }
                if ($userInfo['division_type'] == 1 && $isDivisionOpen) {
                    $value['name'] = '区域代理';
                    if (!$userDivisionStatus) unset($menusInfo[$key]);
                } elseif ($userInfo['division_type'] == 2 && $isDivisionOpen) {
                    $value['name'] = '代理商';
                    if (!$userDivisionStatus) unset($menusInfo[$key]);
                }
            }
        }
        /** @var SystemConfigServices $systemConfigServices */
        $systemConfigServices = app()->make(SystemConfigServices::class);
        $bannerInfo = $systemConfigServices->getSpreadBanner() ?? [];
        $my_banner = sys_data('routine_my_banner');
        $routine_contact_type = sys_config('routine_contact_type', 0);
        /** @var DiyServices $diyServices */
        $diyServices = app()->make(DiyServices::class);
        $member = $diyServices->cacheRemember('diy_data_member_4', function () use ($diyServices) {
            $member = $diyServices->get(['template_name' => 'member', 'type' => 3], ['value', 'status']);
            $member = $member ? $member->toArray() : [];
            return $member;
        });
        if ($member) {
            $diy_data = json_decode($member['value'], true);
            $diy_data = $diy_data ?: $diyServices->member;
        } else {
            $diy_data = $diyServices->member;
        }
        if (isset($diy_data['merMenu']['list']) && $diy_data['merMenu']['list']) {
            foreach ($diy_data['merMenu']['list'] as $key => &$item) {
                if (isset($auth[$item['url']]) && $auth[$item['url']]) {
                    unset($diy_data['merMenu']['list'][$key]);
                    continue;
                }
                if ($item['url'] == '/kefu/mobile_list') {
                    $item['url'] = sys_config('site_url') . $item['url'];
                    if ($request->isRoutine()) {
                        $item['url'] = str_replace('http://', 'https://', $item['url']);
                    }
                }
            }
        }
        if (isset($diy_data['menu']['list']) && $diy_data['menu']['list']) {
            foreach ($diy_data['menu']['list'] as $key => &$item) {
                if (isset($auth[$item['url']]) && $auth[$item['url']]) {
                    unset($diy_data['menu']['list'][$key]);
                    continue;
                }
                //用户等级 未激活地址改为激活页面
                if ($item['url'] == '/pages/users/user_vip/index' && sys_config('level_activate_status') && !$levelStatus) {
                    $item['url'] = '/pages/annex/vip_grade_active/index';
                }
                if ($item['url'] == '/pages/users/user_spread_user/index') {
                    if($brokerageType == 2){
                        $item['url'] = '/pages/users/spreadData/index';
                    }
                    if ($userInfo['division_type'] == 1 && $isDivisionOpen) {
                        $item['name'] = '区域代理';
                        if (!$userDivisionStatus) unset($diy_data['menu']['list'][$key]);
                    } elseif ($userInfo['division_type'] == 2 && $isDivisionOpen) {
                        $item['name'] = '代理商';
                        if (!$userDivisionStatus) unset($diy_data['menu']['list'][$key]);
                    }
                }
            }
        }
        return app('json')->successful(['routine_my_menus' => array_merge($menusInfo), 'routine_my_banner' => $my_banner, 'routine_spread_banner' => $bannerInfo, 'routine_contact_type' => $routine_contact_type, 'diy_data' => $diy_data]);
    }

    /**
     * 个人中心数据
     * @param Request $request
     * @param UserServices $services
     * @param StoreOrderServices $orderServices
     * @return \think\Response
     */
    public function menu_user_data(Request $request, UserServices $services, StoreOrderServices $orderServices)
    {
        $uid = 0;
        $userInfo = [];
        $commission = [];
        $order = [];
        $not_pay_order = [];
        if ($request->hasMacro('user')) $userInfo = $request->user();
        if ($request->hasMacro('uid')) $uid = $request->uid();
        if (!$uid && !$userInfo) return app('json')->successful(['commission' => $commission, 'order' => $order, 'not_pay_order' => $not_pay_order]);
        if ($userInfo['is_promoter']) {
            $commission['brokerage_price'] = $userInfo['brokerage_price'];
            $uids = $services->getColumn(['spread_uid' => $uid], 'uid');
            $commission['number'] = count($uids);
            $commission['order_num'] = $orderServices->getCount([['uid', 'in', $uids], ['pid', '=', 0], ['paid', '=', 1], ['is_del', '=', 0]]);
        }
        /** @var StoreServiceServices $storeService */
        $storeService = app()->make(StoreServiceServices::class);
        $userOrder = $storeService->checkoutIsService(['uid' => $uid, 'account_status' => 1, 'status' => 1, 'customer' => 1]);
        $order['user_order'] = $userOrder;
        if ($userOrder) {
            $where = ['pid' => [0, -1], 'paid' => 1, 'is_del' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]];
            $order['price'] = $orderServices->together($where, 'pay_price', 'sum');
            $order['num'] = $orderServices->count($where);
            $default_where = [
                'pid' => [0, -1],
                'status' => 1,
                'is_system_del' => 0
            ];
            $order['consignment'] = $orderServices->count($default_where);
        }
        $not_pay_order = $orderServices->getUserNotPayOrder($uid);

        return app('json')->successful(['commission' => $commission, 'order' => $order, 'not_pay_order' => $not_pay_order]);
    }

    /**
     * 搜索热词：用户搜索记录
     * @param StoreProductWordsServices $wordsServices
     * @return \think\Response
     */
    public function hotKeywords(StoreProductWordsServices $wordsServices)
    {
        [$page, $limit] = $wordsServices->getPageValue();
        return app('json')->success($wordsServices->getList(['is_show' => 1, 'is_del' => 0], 'id,name,color,bg_color,border_color,icon', $page, $limit));
    }

    /**
     * 搜索关键字关联
     * @param Request $request
     * @param StoreProductServices $services
     * @return \think\Response
     */
    public function searchWords(Request $request, StoreProductServices $services)
    {
        [$keyword] = $request->getMore([
            [['keyword', 's'], ''],
        ], true);
        $list = [];
        if (!empty($keyword) && $keyword !== '%') {
            $where = ['is_vip_product' => 0, 'is_verify' => 1, 'pid' => 0, 'is_show' => 1, 'is_del' => 0, 'store_name' => $keyword, 'field_key' => 'store_name'];
            $list = $services->getSearchList($where, 0, 10, ['id', 'store_name'], 'sort desc,sales desc,id desc', []);
        }
        return app('json')->success(compact('keyword', 'list'));
    }


    /**
     * 图片上传
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function uploadImage(Request $request, SystemAttachmentServices $services)
    {
        $data = $request->postMore([
            ['filename', 'file'],
        ]);
        if (!$data['filename']) return app('json')->fail('参数有误');
        $uid = (int)$request->uid();
        if (CacheService::has('start_uploads_' . $uid) && CacheService::get('start_uploads_' . $uid) >= 100) return app('json')->fail('非法操作');
        $upload = UploadService::init();
        $info = $upload->to('store/comment')->validate()->move($data['filename']);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        $services->attachmentAdd($res['name'], $res['size'], $res['type'], $res['dir'], $res['thumb_path'], 1, (int)sys_config('upload_type', 1), $res['time'], 3);
        if (CacheService::has('start_uploads_' . $uid))
            $start_uploads = (int)CacheService::get('start_uploads_' . $uid);
        else
            $start_uploads = 0;
        $start_uploads++;
        CacheService::set('start_uploads_' . $uid, $start_uploads, 86400);
        $res['dir'] = path_to_url($res['dir']);
        if (strpos($res['dir'], 'http') === false) $res['dir'] = sys_config('site_url') . $res['dir'];
        return app('json')->successful('图片上传成功!', ['name' => $res['name'], 'url' => $res['dir']]);
    }

    /**
     * 上传视频
     * @param Request $request
     * @param SystemAttachmentServices $services
     * @return \think\Response
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * User: liusl
     * DateTime: 2024/8/8 09:49
     */
    public function uploadVideo(Request $request, SystemAttachmentServices $services)
    {
        $data = $request->postMore([
            ['filename', 'file'],
        ]);
        if (!$data['filename']) return app('json')->fail('参数有误');
        $uid = (int)$request->uid();
        if (CacheService::has('start_uploads_' . $uid) && CacheService::get('start_uploads_' . $uid) >= 100) return app('json')->fail('非法操作');
        $upload = UploadService::init();
        $info = $upload->to('store/video')->validate()->move($data['filename']);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        $services->attachmentAdd($res['name'], $res['size'], $res['type'], $res['dir'], $res['thumb_path'], 1, (int)sys_config('upload_type', 1), $res['time'], 3, 2);
        if (CacheService::has('start_uploads_' . $uid))
            $start_uploads = (int)CacheService::get('start_uploads_' . $uid);
        else
            $start_uploads = 0;
        $start_uploads++;
        CacheService::set('start_uploads_' . $uid, $start_uploads, 86400);
        $res['dir'] = path_to_url($res['dir']);
        if (strpos($res['dir'], 'http') === false) $res['dir'] = sys_config('site_url') . $res['dir'];
        return app('json')->successful('视频上传成功!', ['name' => $res['name'], 'url' => $res['dir']]);
    }

    /**
     * 物流公司
     * @return mixed
     */
    public function logistics(Request $request, ExpressServices $services)
    {
        [$status] = $request->getMore([
            ['status', ''],
        ], true);
        if ($status == 1) $data['status'] = $status;
        $data['is_show'] = 1;
        $expressList = $services->expressList($data);
        return app('json')->successful($expressList ?? []);
    }

    /**
     * 反向解析地址
     * @param Request $request
     * @return mixed
     */
    public function geoLbscoder(Request $request)
    {
        [$data] = $request->getMore([
            ['location', '']
        ], true);
        $locationOption = new LocationOption(sys_config('tengxun_map_key'));
        $data = explode(',', $data);
        $locationOption->setLocation($data[0] ?? '', $data[1] ?? '');
        $location = new Location($locationOption);
        $res = $location->request();
        if ($res->error) {
            return app('json')->fail($res->error);
        }
        if ($res->status) {
            return app('json')->fail($res->message);
        }
        if (!$res->result) {
            return app('json')->fail('获取失败');
        }
        return app('json')->success($res->result);
    }

    /**
     * 记录用户分享
     * @param Request $request
     * @param UserBillServices $services
     * @return mixed
     */
    public function user_share(Request $request, UserBillServices $services)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($services->setUserShare($uid));
    }

    /**
     * 获取图片base64
     * @param Request $request
     * @return mixed
     */
    public function get_image_base64(Request $request)
    {
        [$imageUrl, $codeUrl] = $request->postMore([
            ['image', ''],
            ['code', ''],
        ], true);
        if ($imageUrl !== '' && !(preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $imageUrl) || strpos($imageUrl, 'https://mp.weixin.qq.com/cgi-bin/showqrcode') !== false || strpos($imageUrl, 'https://thirdwx.qlogo.cn/mmopen') !== false)) {
            return app('json')->success(['code' => false, 'image' => false]);
        }
        if ($codeUrl !== '' && !(preg_match('/.*(\.png|\.jpg|\.jpeg|\.gif)$/', $codeUrl) || strpos($codeUrl, 'https://mp.weixin.qq.com/cgi-bin/showqrcode') !== false || strpos($imageUrl, 'https://thirdwx.qlogo.cn/mmopen') !== false)) {
            return app('json')->success(['code' => false, 'image' => false]);
        }
        $imageUrlHost = $imageUrl ? (parse_url($imageUrl)['host'] ?? $imageUrl) : $imageUrl;
        $codeUrlHost = $codeUrl ? (parse_url($codeUrl)['host'] ?? $codeUrl) : $codeUrl;

        /** @var SystemStorageServices $systemStorageServices */
        $systemStorageServices = app()->make(SystemStorageServices::class);
        $domainArr = $systemStorageServices->getColumn([], 'domain');
        $domainArr = array_merge($domainArr, [$request->host()]);
        $domainArr = array_unique(array_diff($domainArr, ['']));
        if (count($domainArr)) {
            $domainArr = array_map(function ($item) {
                return str_replace(['https://', 'http://'], '', $item);
            }, $domainArr);
        }
        if ($domainArr && (($imageUrlHost && !in_array($imageUrlHost, $domainArr)) || ($codeUrlHost && !in_array($codeUrlHost, $domainArr)))) {
            return app('json')->success(['code' => false, 'image' => false]);
        }
        try {
            $code = CacheService::get($codeUrl, function () use ($codeUrl) {
                $codeTmp = $code = $codeUrl ? image_to_base64($codeUrl) : false;
                if (!$codeTmp) {
                    $codeUrl = explode('?', $codeUrl)[0] ?? $codeUrl;
                    $putCodeUrl = put_image($codeUrl);
                    $code = $putCodeUrl ? image_to_base64(public_path() . $putCodeUrl) : false;
                    $code ?? unlink(public_path() . $putCodeUrl);
                }
                return $code;
            });
            $image = CacheService::get($imageUrl, function () use ($imageUrl) {
                $imageTmp = $image = $imageUrl ? image_to_base64($imageUrl) : false;
                if (!$imageTmp) {
                    $imageUrl = explode('?', $imageUrl)[0] ?? $imageUrl;
                    $putImageUrl = put_image($imageUrl);
                    $image = $putImageUrl ? image_to_base64(public_path() . $putImageUrl) : false;
                    $image ?? unlink(public_path() . $putImageUrl);
                }
                return $image;
            });
            return app('json')->successful(compact('code', 'image'));
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 门店列表
     * @param Request $request
     * @param SystemStoreServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function store_list(Request $request, SystemStoreServices $services)
    {
        [$latitude, $longitude, $product_id, $is_store] = $request->getMore([
            ['latitude', ''],
            ['longitude', ''],
            ['product_id', 0],
            ['is_store', 1]    //前端传值为 1|商城配送 2|门店自提 3|门店配送
        ], true);
        if (!checkCoordinates($longitude, $latitude)) {
            return app('json')->fail('参数错误');
        }
        //判断是否门店自提
        $is_store == 2 ? $is_store = 1 : $is_store = '';
        $where = ['type' => 0, 'is_store' => $is_store];
        $data['list'] = $services->getStoreList($where, ['*'], $latitude, $longitude, (int)$product_id);
        $data['tengxun_map_key'] = sys_config('tengxun_map_key');
        $data['site_logo'] = sys_config('site_logo');
        return app('json')->successful($data);
    }

    /**
     * 查找城市数据
     * @param Request $request
     * @return mixed
     */
    public function city_list(Request $request)
    {
        /** @var SystemCityServices $systemCity */
        $systemCity = app()->make(SystemCityServices::class);
        return app('json')->successful($systemCity->cityList());
    }


    /**
     * 复制口令接口
     * @return mixed
     */
    public function copy_words()
    {
        $data['words'] = sys_config('copy_words');
        return app('json')->successful($data);
    }

    /**
     * 生成口令关键字
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function copy_share_words(Request $request)
    {
        [$productId] = $request->getMore([
            ['product_id', ''],
        ], true);
        /** @var StoreProductServices $productService */
        $productService = app()->make(StoreProductServices::class);
        $keyWords['key_words'] = $productService->getProductWords($productId);
        return app('json')->successful($keyWords);
    }

    /**
     * 获取用户协议内容
     * @return mixed
     */
    public function getUserAgreement(Request $request, $type = 1)
    {
        /** @var CacheServices $cache */
        $cache = app()->make(CacheServices::class);
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $content = $cache->getDbCache($type, '');
        $uid = $request->uid() ?? 0;
        $userInfo = $userService->getUserCacheInfo($uid);
        $name = $userInfo['nickname'] ?? '';
        $avatar = $userInfo['avatar'] ?? '';
        return app('json')->success(compact('content', 'uid', 'name', 'avatar'));
    }

    public function getAgreement(Request $request, $type = 1)
    {
        /** @var AgreementServices $agreementService */
        $agreementService = app()->make(AgreementServices::class);
        $member_explain = $agreementService->getAgreementBytype($type);
        return app('json')->success(compact('member_explain'));
    }

    /**
     * 统计代码
     * @return array|string
     */
    public function getScript()
    {
        return sys_config('system_statistics', '');
    }

    /**
     * 首页开屏广告
     * @return mixed
     */
    public function getOpenAdv()
    {
        /** @var CacheServices $cache */
        $cache = app()->make(CacheServices::class);
        $data = $cache->getDbCache('open_adv', '');
        $data['time'] = (float)($data['time'] ?? 3);
        $data['interval_time'] = (float)($data['interval_time'] ?? 24);
        return app('json')->success($data);
    }


}
