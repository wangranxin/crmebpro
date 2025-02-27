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
namespace app\controller\api\v1\user;

use app\Request;
use app\services\other\QrcodeServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\config\SystemConfigServices;
use app\services\user\UserBillServices;
use crmeb\services\UploadService;
use think\annotation\Inject;

/**
 * 账单类
 * Class UserBill
 * @package app\api\controller\user
 */
class UserBill
{

    /**
     * @var UserBillServices
     */
    #[Inject]
    protected UserBillServices $services;

    /**
     * 推广佣金明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 充值  3 返佣  4 提现
     * @return mixed
     */
    public function spread_commission(Request $request, $type)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getUserBillList($uid, $type));
    }

    /**
     * 获取小程序二维码
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoutineCode(Request $request)
    {
        $user = $request->user();
        $uid = (int)$request->uid();
		//小程序
        $name = $uid . '_' . $user['is_promoter'] . '_user_routine.jpg';

        /** @var SystemAttachmentServices $systemAttachment */
        $systemAttachment = app()->make(SystemAttachmentServices::class);
        $imageInfo = $systemAttachment->getInfo(['name' => $name]);

        /** @var QrcodeServices $qrCode */
        $qrCode = app()->make(QrcodeServices::class);
        $res1 = $qrCode->qrCodeExist($uid, 'spread');

        //图片在,数据库没有数据,删除图片
        if ($imageInfo && !$res1) {
            try {
                $uploadRes = UploadService::init($imageInfo->image_type);
                if ($imageInfo['image_type'] == 1) {
                    if (strpos($imageInfo['att_dir'], '/') == 0) {
                        $imageInfo['att_dir'] = substr($imageInfo['att_dir'], 1);
                    }
                    if ($imageInfo['att_dir']) $uploadRes->delete(public_path() . $imageInfo['att_dir']);
                } else {
                    if ($imageInfo['name']) $uploadRes->delete($imageInfo['name']);
                }
            } catch (\Throwable $e) {
            }
        }
        if (!$imageInfo || !$res1) {
            /** @var QrcodeServices $qrCode */
			$qrCode = app()->make(QrcodeServices::class);
			$resForever = $qrCode->qrCodeForever($user['uid'], 'spread-' . $user['uid']);
			$id = (int)$resForever['id'];
			/** @var QrcodeServices $QrcodeService */
			$QrcodeService = app()->make(QrcodeServices::class);
			//生成小程序地址
			$urlCode = $QrcodeService->getRoutineQrcodePath($id, $uid, 99, $name);
        } else {
			$urlCode = $imageInfo['att_dir'];
			if ($imageInfo['image_type'] == 1) $urlCode = sys_config('site_url') . $urlCode;
        }
        return app('json')->success(['url' => $urlCode]);
    }

    /**
     * 获取海报详细信息
     * @return mixed
     */
    public function getSpreadInfo(Request $request)
    {
        /** @var SystemConfigServices $systemConfigServices */
        $systemConfigServices = app()->make(SystemConfigServices::class);
        $spreadBanner = $systemConfigServices->getSpreadBanner() ?? [];
        $bannerCount = count($spreadBanner);
        $routineSpreadBanner = [];
        if ($bannerCount) {
            foreach ($spreadBanner as $item) {
                $routineSpreadBanner[] = ['pic' => $item];
            }
        }
		$uid = (int)$request->uid();
        if (sys_config('share_qrcode', 0) && request()->isWechat()) {
            /** @var QrcodeServices $qrcodeService */
            $qrcodeService = app()->make(QrcodeServices::class);
            if (sys_config('spread_share_forever', 0)) {
                $qrcode = $qrcodeService->getForeverQrcode('spread-' . $uid, $uid)->url;
            } else {
                $qrcode = $qrcodeService->getTemporaryQrcode('spread-' . $uid, $uid)->url;
            }
        } else {
            $qrcode = '';
        }
        return app('json')->success([
            'spread' => $routineSpreadBanner,
            'qrcode' => $qrcode,
            'nickname' => $request->user('nickname'),
            'avatar' => $request->user('avatar'),
            'site_name' => sys_config('site_name')
        ]);
    }

    /**
     * 积分记录
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function integral_list(Request $request)
    {
        $uid = (int)$request->uid();
        $data = $this->services->getIntegralList($uid);
        return app('json')->successful($data);
    }
}
