<?php
// +----------------------------------------------------------------------
// | 微信服务相关配置
// +----------------------------------------------------------------------


return [
    //请求响应日志
    'logger' => env('APP_DEBUG', false),
    //公用
    'comment' => [
        'url' => [
            'key' => 'site_url'
        ],
    ],
    //小程序配置
    'mini' => [
        'appid' => [
            'key' => 'routine_appId'
        ],
        'secret' => [
            'key' => 'routine_appsecret'
        ],
        'token' => [
            'key' => 'wechat_token'
        ],
        'key' => [
            'key' => 'wechat_encodingaeskey'
        ],
        'notifyUrl' => [
            //必须携带斜杠开头
            'value' => '/api/pay/notify/routine'
        ],
    ],
    //公众号配置
    'official' => [
        'appid' => [
            'key' => 'wechat_appid'
        ],
        'secret' => [
            'key' => 'wechat_appsecret'
        ],
        'token' => [
            'key' => 'wechat_token'
        ],
        'key' => [
            'key' => 'wechat_encodingaeskey'
        ],
        'encode' => [
            'key' => 'wechat_encode'
        ],
    ],
    //开放平台APP
    'app' => [
        'appid' => [
            'key' => 'wechat_app_appid'
        ],
        'secret' => [
            'key' => 'wechat_app_appsecret'
        ],
        'token' => [
            'key' => 'wechat_openapp_app_token'
        ],
        'key' => [
            'key' => 'wechat_openapp_app_aes_key'
        ],
        'notifyUrl' => [
            //必须携带斜杠开头
            'value' => '/api/pay/notify/app'
        ],
    ],
    //开放平台网页应用
    'web' => [
        'appid' => [
            'key' => 'wechat_open_app_id'
        ],
        'secret' => [
            'key' => 'wechat_open_app_secret'
        ],
        'token' => [
            'key' => 'wechat_open_app_token'
        ],
        'key' => [
            'key' => 'wechat_open_app_aes_key'
        ],
    ],
    //企业微信
    'work' => [
        'corp_id' => [
            'key' => 'wechat_work_corpid'
        ],
        'token' => [
            'key' => 'wechat_work_token'
        ],
        'key' => [
            'key' => 'wechat_work_aes_key'
        ],
    ],
    //支付
    'pay' => [
        //商户号
        'mchid' => [
            'key' => 'pay_weixin_mchid'
        ],
        //小程序商户号
        'routine_mchid' => [
            'key' => 'pay_routine_mchid'
        ],
        //支付key
        'key' => [
            'key' => 'pay_weixin_key'
        ],
        //证书
        'client_cert' => [
            'key' => 'pay_weixin_client_cert'
        ],
        //证书
        'client_key' => [
            'key' => 'pay_weixin_client_key'
        ],
        'notifyUrl' => [
            //支付回调,必须携带斜杠开头
            'value' => '/api/pay/notify/wechat'
        ],
        'refundUrl' => [
            //退款回调,必须携带斜杠开头
            'value' => '/api/pay/refund/wechat'
        ],
    ],
    //v3支付新增配置，证书和商户号使用v2支付配置的证书
    'v3_pay' => [
        'key' => [
            //默认使用value值，没有值使用eb_system_config配置中的key的值
            'key' => 'v3_pay_weixin_key',
            //配置值
            'value' => '',
        ],
        'serial_no' => [
            //默认使用value值，没有值使用eb_system_config配置中的key的值
            'key' => 'pay_weixin_serial_no',
            //配置值
            'value' => '',
        ],
        'pay_type' => [
            //默认使用value值，没有值使用eb_system_config配置中的key的值
            'key' => 'pay_wechat_type',
            //配置值
            'value' => '',
        ],
        'public_key'=>[
            //默认使用value值，没有值使用eb_system_config配置中的key的值
            'key' => 'v3_pay_public_key',
            //配置值
            'value' => '',
        ],
        'public_pem'=>[
            //默认使用value值，没有值使用eb_system_config配置中的key的值
            'key' => 'v3_pay_public_pem',
            //配置值
            'value' => '',
        ],
    ],
];
