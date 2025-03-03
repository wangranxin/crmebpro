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

namespace app\controller\admin\v1\user\member;


use think\annotation\Inject;
use app\controller\admin\AuthController;
use app\services\other\AgreementServices;
use app\services\other\QrcodeServices;
use app\services\user\member\MemberCardBatchServices;

/**
 * Class MemberCardBatch
 * @package app\controller\admin\v1\user\member
 */
class MemberCardBatch extends AuthController
{

    /**
     * @var MemberCardBatchServices
     */
    #[Inject]
    protected MemberCardBatchServices $services;

    /**
     * 会员卡批次资源列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['title', '']
        ]);
        $data = $this->services->getList($where);
        return $this->success($data);
    }

    /** 保存卡片资源
     * @param $id
     * @return mixed
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['title', ''],
            ['use_day', 1],
            ['total_num', 1],
            ['status', 0],
            ['remark', '']
        ]);
        $this->services->save((int)$id, $data);
        return $this->success("卡片生成成功");
    }

    /**
     * 列表操作
     * @param $id
     * @return mixed
     */
    public function set_value($id)
    {

        $data = $this->request->getMore([
            ['value', ''],
            ['field', ''],
        ]);
        $this->services->setValue($id, $data);
        return $this->success("修改成功");
    }

    /**
 	 * 会员二维码，兑换卡
     * @return mixed
     */
    public function member_scan()
    {
        //生成h5地址
        $weixinPage = "/pages/annex/vip_active/index";
        $weixinFileName = "wechat_member_card.png";
        /** @var QrcodeServices $QrcodeService */
        $QrcodeService = app()->make(QrcodeServices::class);
        $wechatQrcode = $QrcodeService->getWechatQrcodePath($weixinFileName, $weixinPage, false, false);
        //生成小程序地址
        $routineQrcode = $QrcodeService->getRoutineQrcodePath(4, 6, 104, $weixinFileName, false);
        return $this->success(['wechat_img' => $wechatQrcode, 'routine' => $routineQrcode ? $routineQrcode : ""]);
    }

    /** 添加会员协议
     * @param AgreementServices $agreementServices
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save_member_agreement(AgreementServices $agreementServices, $id = 0)
    {
        $data = $this->request->postMore([
            ['type', 1],
            ['title', ""],
            ['content', ''],
            ['status', ''],
        ]);

        return $this->success($agreementServices->saveAgreement($data, (int)$id));
    }

    /**获取会员协议
     * @param AgreementServices $agreementServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAgreement(AgreementServices $agreementServices)
    {
        $list = $agreementServices->getAgreementBytype(1);
        return $this->success($list);
    }

}
