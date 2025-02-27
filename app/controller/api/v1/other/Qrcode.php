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

namespace app\controller\api\v1\other;


use app\Request;
use app\services\order\StoreOrderServices;
use app\services\other\QrcodeServices;
use think\annotation\Inject;

/**
 * 登录
 * Class Login
 * @package app\api\controller
 */
class Qrcode
{

    /**
     * @var QrcodeServices
     */
    #[Inject]
    protected QrcodeServices $services;

    /**
     * 获取商品、秒杀、砍价、分销等海报二维码
     * @param Request $request
     * @param $type
     * @param $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQrcode(Request $request, $type, $id)
    {
        $id = (int)$id;
        $uid = $request->hasMacro('user') ? (int)$request->uid() : 0;
        $user = $request->hasMacro('user') ? $request->user() : [];
        $is_promoter = $user['is_promoter'] ?? 1;
        $name = $third_type = $link = '';
        switch ($type) {
            case -1://分销
                $third_type = 'spread-' . $uid;
                $name = 'spread-' . $uid . '_' . $is_promoter . '.jpg';
                $link = '/pages/index/index?spid=' . $uid;
                break;
            case 0://普通商品
                $name = 'product_' . $id . '_' . $uid . '_' . $is_promoter . '.jpg';
                $third_type = 'product-' . $id . '-' . $uid;
                $link = '/pages/goods_details/index?id=' . $id . '&spid=' . $uid;
                break;
            case 1://秒杀
                $time = $request->param('time', '');
                $status = $request->param('status', '');
                $name = 'seckill_' . $id . '_' . $uid . '.jpg';
                $third_type = 'seckill-' . $id . '-' . $uid . '-' . $time . '-' . $status;
                $link = '/pages/activity/goods_details/index?type=1&id=' . $id . '&time=' . $time . '&status=' . $status . '&spid=' . $uid;
                break;
            case 2://砍价
                $third_type = 'bargain-' . $id . '-' . $uid;
                $name = 'bargain_' . $id . '_' . $uid . '_' . $is_promoter . '.jpg';
                $link = '/pages/activity/goods_bargain_details/index?id=' . $id . '&spid=' . $uid;
                break;
            case 3://拼团
                $third_type = 'combination-' . $id . '-' . $uid;
                $name = 'combination_' . $id . '_' . $uid . '_' . $is_promoter . '.jpg';
                $link = '/pages/activity/goods_details/index?type=3&id=' . $id . '&spid=' . $uid;
                break;
            case 4://积分
                $third_type = 'integral-' . $id . '-' . $uid;
                $name = 'integral_' . $id . '_' . $uid . '_' . $is_promoter . '.jpg';
                $link = '/pages/activity/goods_details/index?type=4&id=' . $id . '&spid=' . $uid;
                break;
            case 6://预售
                $third_type = 'presale-' . $id . '-' . $uid;
                $name = 'presale_' . $id . '_' . $uid . '_' . $is_promoter . '.jpg';
                $link = '/pages/activity/goods_details/index?type=6&id=' . $id . '&spid=' . $uid;
                break;
            case 7://新人专享
                $third_type = 'newcomer-' . $id . '-' . $uid;
                $name = 'newcomer_' . $id . '_' . $uid . '_' . $is_promoter . '.jpg';
                $link = '/pages/activity/goods_details/index?type=7&id=' . $id . '&spid=' . $uid;
                break;
            case 90://用户码
                $code = $user['bar_code'];
                $third_type = 'user-' . $code . '-' . $uid;
                $name = 'user_' . $code . '_' . $uid . '.jpg';
                $id = $code;
                $link = '/pages/admin/order_cancellation/index?auth=3&code=' . $code;
                break;
            case 91://核销码
                $order_id = $request->param('order_id', '');
                /** @var StoreOrderServices $orderServices */
                $orderServices = app()->make(StoreOrderServices::class);
                $code = $orderServices->value(['order_id' => $order_id], 'verify_code');
                $third_type = 'write-off-' . $code . '-' . $uid;
                $name = 'write_off_' . $code . '_' . $uid . '.jpg';
                $id = $code;
                $link = '/pages/admin/order_cancellation/index?auth=3&code=' . $code;
                break;
        }
        if (sys_config('share_qrcode', 0) && request()->isWechat()) {
            if ($type == -1 && sys_config('spread_share_forever', 0)) {
                $wechatUrl = $this->services->getForeverQrcode($third_type, $uid)->url;
            } else {
                $wechatUrl = $this->services->getTemporaryQrcode($third_type, $uid)->url;
            }
        } else {
            $wechatUrl = $this->services->getWechatQrcodePath($name, $link);
        }
        $routineUrl = $this->services->getRoutineQrcodePath((int)$id, (int)$uid, (int)$type, $name);
        return app('json')->success(['routineUrl' => $routineUrl, 'wechatUrl' => $wechatUrl, 'site_name' => sys_config('site_name'), 'nickname' => $user['nickname'] ?? '']);
    }

}
