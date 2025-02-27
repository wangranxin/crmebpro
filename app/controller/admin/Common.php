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
namespace app\controller\admin;

use app\services\order\StoreOrderRefundServices;
use app\services\other\CityAreaServices;
use app\services\system\SystemAuthServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\user\UserExtractServices;
use app\services\system\SystemMenusServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

/**
 * 公共接口基类 主要存放公共接口
 * Class Common
 * @package app\controller\admin
 */
class Common extends AuthController
{
    /**
     * 获取logo
     * @param SystemConfigService $services
     * @return Response
     */
    public function getLogo(SystemConfigService $services): Response
    {
        $data = $services->more(['site_logo', 'site_logo_square', 'site_name']);
        return $this->success($data);
    }

    /**
     * @return mixed
     */
    public function check_auth()
    {
        return $this->checkAuthDecrypt();
    }

    /**
     * @return mixed
     */
    public function auth()
    {
        return $this->getAuth();
    }

    /**
     * 查询购买版权
     * @return Response
     */
    public function crmeb_copyright(): Response
    {
        $this->__6j3nfcwmWqrsDx8F0MjZGeQyWvLsqeFXww();
        return $this->success('查询成功');
    }

    /**
     * 保存版权
     * @return Response
     */
    public function saveCopyright(): Response
    {
        $copyright = $this->request->post('copyright');
        $copyrightImg = $this->request->post('copyright_img');

        $this->__qsG71NREI01vix2OkjH($copyright, $copyrightImg);

        return $this->success('保存成功');
    }

    /**
     * 获取版权
     * @return Response
     */
    public function getCopyright(): Response
    {
        try {
            $copyright = $this->__z6uxyJQ4xYa5ee1mx5();
        } catch (\Throwable $e) {
            $copyright = ['copyrightContext' => '', 'copyrightImage' => ''];
        }
        $copyright['version'] = get_crmeb_version();
        return $this->success($copyright);
    }

    /**
     * 申请授权
     * @param SystemAuthServices $services
     * @return Response
     */
    public function auth_apply(SystemAuthServices $services): Response
    {
        $version = get_crmeb_version();
        $data = $this->request->postMore([
            ['company_name', ''],
            ['domain_name', ''],
            ['order_id', ''],
            ['phone', ''],
            ['label', strripos($version, 'min') === false ? 3 : 2],
            ['captcha', ''],
        ]);
        if (!$data['company_name']) {
            return $this->fail('请填写公司名称');
        }
        if (!$data['domain_name']) {
            return $this->fail('请填写授权域名');
        }

        if (!$data['phone']) {
            return $this->fail('请填写手机号码');
        }
        if (!$data['order_id']) {
            return $this->fail('请填写订单id');
        }
        $datas = explode('.', $data['domain_name']);
        $n = count($datas);
        $preg = '/[\w].+\.(com|net|org|gov|edu)\.cn$/';
        if (($n > 2) && preg_match($preg, $data['domain_name'])) {
            //双后缀取后3位
            $domain_name = $datas[$n - 3] . '.' . $datas[$n - 2] . '.' . $datas[$n - 1];
        } else {
            //非双后缀取后两位
            $domain_name = $datas[$n - 2] . '.' . $datas[$n - 1];
        }
        $sec = trim(str_replace($domain_name, '', $data['domain_name']), '.');
        if ($sec) {
            if ($sec == 'www') {
                $data['domain_name'] = $domain_name;
            }
        }
        $header = $this->__k0dUcnKjRUs9lfEllqO9J($data['phone']);
        if ($header) {
            $headerData = ['Authori-zation:Bearer ' . $this->__k0dUcnKjRUs9lfEllqO9J($data['phone'])];
        } else {
            $headerData = false;
        }
        $services->authApply($data, $headerData);
        return $this->success("申请授权成功!");

    }

    /**
     * 首页头部统计数据
     * @return Response
     */
    public function homeStatics(): Response
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $info = $orderServices->homeStatics();
        return $this->success(compact('info'));
    }

    /**
     * 订单图表
     * @return Response
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/28
     */
    public function orderChart(): Response
    {
        $cycle = $this->request->param('cycle') ?: 'thirtyday';//默认30天
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $chartdata = $orderServices->orderCharts($cycle);
        return $this->success($chartdata);
    }

    /**
     * 用户图表
     * @return Response
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/28
     */
    public function userChart(): Response
    {
        /** @var UserServices $uServices */
        $uServices = app()->make(UserServices::class);
        $chartdata = $uServices->userChart();
        return $this->success($chartdata);
    }

    /**
     * 交易额排行
     * @return Response
     */
    public function purchaseRanking(): Response
    {
//        /** @var StoreProductAttrValueServices $valueServices */
//        $valueServices = app()->make(StoreProductAttrValueServices::class);
//        $list = $valueServices->purchaseRanking();
        $list = [];
        return $this->success(compact('list'));
    }

    /**
     * 待办事统计
     * @return Response
     */
    public function jnotice(): Response
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $orderNum = $orderServices->storeOrderCount();
        $store_stock = sys_config('store_stock');
        if ($store_stock < 0) $store_stock = 2;
        /** @var StoreProductServices $storeServices */
        $storeServices = app()->make(StoreProductServices::class);
        /** @var StoreProductReplyServices $replyServices */
        $replyServices = app()->make(StoreProductReplyServices::class);
        $commentNum = $replyServices->replyCount();
        /** @var UserExtractServices $extractServices */
        $extractServices = app()->make(UserExtractServices::class);
        $reflectNum = $extractServices->userExtractCount();//提现
        /** @var StoreOrderRefundServices $refundServices */
        $refundServices = app()->make(StoreOrderRefundServices::class);
        $orderRefundNum = $refundServices->count(['is_cancel' => 0, 'refund_type' => [0, 1, 2, 4, 5]]);
//        $orderServices->update(['status' => 1, 'is_remind' => 0], ['is_remind' => 1]);
        $value = [];
        if ($orderNum) {
            $value[] = [
                'title' => '您有' . $orderNum . '个待发货的订单',
                'type' => 'bulb',
                'url' => '/admin/order/list?status=1'
            ];
        }
        $inventory = $storeServices->count(['is_police' => 1, 'status' => 5, 'pid' => 0, 'store_stock' => 0]);
        if ($inventory) {
            $value[] = [
                'title' => '您有' . $inventory . '个商品库存预警',
                'type' => 'information',
                'url' => '/admin/product/product_list?type=5',
            ];
        }
        if ($commentNum) {
            $value[] = [
                'title' => '您有' . $commentNum . '条评论待回复',
                'type' => 'bulb',
                'url' => '/admin/product/product_reply?is_reply=0'
            ];
        }
        if ($reflectNum) {
            $value[] = [
                'title' => '您有' . $reflectNum . '个提现申请待审核',
                'type' => 'bulb',
                'url' => '/admin/finance/user_extract/index?status=0',
            ];
        }
        if ($orderRefundNum) {
            $value[] = [
                'title' => '您有' . $orderRefundNum . '个售后订单待处理',
                'type' => 'bulb',
                'url' => '/admin/order/refund'
            ];
        }
        return $this->success($this->noticeData($value));
    }

    /**
     * 消息返回格式
     * @param array $data
     * @return array
     */
    public function noticeData(array $data): array
    {
        // 消息图标
        $iconColor = [
            // 邮件 消息
            'mail' => [
                'icon' => 'md-mail',
                'color' => '#3391e5'
            ],
            // 普通 消息
            'bulb' => [
                'icon' => 'md-bulb',
                'color' => '#87d068'
            ],
            // 警告 消息
            'information' => [
                'icon' => 'md-information',
                'color' => '#fe5c57'
            ],
            // 关注 消息
            'star' => [
                'icon' => 'md-star',
                'color' => '#ff9900'
            ],
            // 申请 消息
            'people' => [
                'icon' => 'md-people',
                'color' => '#f06292'
            ],
        ];
        // 消息类型
        $type = array_keys($iconColor);
        // 默认数据格式
        $default = [
            'icon' => 'md-bulb',
            'iconColor' => '#87d068',
            'title' => '',
            'url' => '',
            'type' => 'bulb',
            'read' => 0,
            'time' => 0
        ];
        $value = [];
        foreach ($data as $item) {
            $val = array_merge($default, $item);
            if (isset($item['type']) && in_array($item['type'], $type)) {
                $val['type'] = $item['type'];
                $val['iconColor'] = $iconColor[$item['type']]['color'] ?? '';
                $val['icon'] = $iconColor[$item['type']]['icon'] ?? '';
            }
            $value[] = $val;
        }
        return $value;
    }

    /**
     * 格式化菜单
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function menusList(): Response
    {
        [$keyword] = $this->request->getMore([
            ['keyword', '']
        ], true);
        $keyword = trim($keyword);
        $cacheKey = $keyword ? md5('admin_common_menu_list_' . $keyword) : md5('admin_common_menu_list');
        $list = CacheService::redisHandler('system_menus')->remember($cacheKey, function () use ($keyword) {
            /** @var SystemMenusServices $menusServices */
            $menusServices = app()->make(SystemMenusServices::class);
            return $menusServices->getSelectList(1, $keyword);
        });
        return app('json')->success($list);
    }


    /**
     * @param CityAreaServices $services
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function city(CityAreaServices $services): Response
    {
        $pid = $this->request->get('pid', 0);
        return $this->success($services->getCityTreeList((int)$pid));
    }
}
