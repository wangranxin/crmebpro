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

return [
    //默认短信发送模式
    'default' => 'yihaotong',
    //单个手机每日发送上限
    'maxPhoneCount' => 10,
    //验证码每分钟发送上线
    'maxMinuteCount' => 20,
    //单个IP每日发送上限
    'maxIpCount' => 50,
    //驱动模式
    'stores' => [
        //一号通
        'yihaotong' => [
            'sms_account' => '',
            'sms_token' => ''
        ],
        //阿里云
        'aliyun' => [
            'aliyun_SignName' => '',
            'aliyun_AccessKeyId' => '',
            'aliyun_AccessKeySecret' => '',
            'aliyun_RegionId' => '',
        ],
    ]
];
