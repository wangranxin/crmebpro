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
namespace app\services\message\notice;

use app\jobs\notice\SystemMsgJob;
use app\services\message\service\StoreServiceServices;
use app\services\message\SystemMessageServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;

/**
 * 短信发送消息列表
 * Created by PhpStorm.
 * User: xurongyao <763569752@qq.com>
 * Date: 2021/9/22 1:23 PM
 */
class SystemMsgService extends BaseNoticeService
{
    /**
     * 发送消息
     * @param int $uid uid
     * @param array $data 模板内容
     */
    public function sendMsg(int $uid, $data)
    {
        if ($uid) {
            $this->handle(fn() => SystemMsgJob::dispatch([$uid, $this->config, $data]));
        }
    }

    /**
     * 给客服发站内信
     * @param array $data
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function kefuSystemSend($data)
    {
        if (!$this->noticeSwitch) {
            return false;
        }
        /** @var StoreServiceServices $StoreServiceServices */
        $StoreServiceServices = app()->make(StoreServiceServices::class);
        $adminList = $StoreServiceServices->getStoreServiceOrderNotice();
        try {
            if ($adminList) {
                foreach ($adminList as $item) {
                    $uid = $item['uid'] ?? 0;
                    $data['admin_name'] = $item['staff_name'] ?? ($item['nickname'] ?? '');
                    //放入队列执行
                    SystemMsgJob::dispatch([$uid, $this->config, $data, 2]);
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }


    /**
     * 站内信发放
     * @param int $uid
     * @param array $noticeInfo
     * @return \crmeb\basic\BaseModel|\think\Model
     */
    public function systemSend(int $uid, array $noticeInfo)
    {
        $data = [];
        $data['mark'] = $noticeInfo['mark'];
        $data['uid'] = $uid;
        $data['title'] = $noticeInfo['title'];
        $data['content'] = $noticeInfo['content'];
        $data['type'] = 1;
        $data['add_time'] = time();
        return app()->make(SystemMessageServices::class)->save($data);
    }


}
