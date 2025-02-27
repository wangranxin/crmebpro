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

use app\jobs\notice\EnterpriseWechatJob;

/**
 * 企业微信发送消息
 * Created by PhpStorm.
 * User: xurongyao <763569752@qq.com>
 * Date: 2021/9/22 1:23 PM
 */
class EnterpriseWechatService extends BaseNoticeService
{

    /**
     * 发送消息
     * @param array $data 模板内容
     * @return bool
     */
    public function sendMsg($data)
    {
        return $this->handle(function ($data) {
            $url = $this->config['url'] ?? '';
            $ent_wechat_text = $this->config['ent_wechat_text'] ?? '';
            EnterpriseWechatJob::dispatchDo('doJob', [$data, $url, $ent_wechat_text]);
        }, [$data]);
    }


}
