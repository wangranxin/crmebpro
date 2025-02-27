<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------
 */

namespace crmeb\services\wechat\config;


use crmeb\services\wechat\contract\ConfigHandlerInterface;
use crmeb\services\wechat\DefaultConfig;

/**
 * Class V3PaymentConfig
 * @author 等风来
 * @email 136327134@qq.com
 * @date 2022/9/30
 * @package crmeb\services\wechat\config
 */
class V3PaymentConfig implements ConfigHandlerInterface
{

    /**
     * appid
     * @var string
     */
    public string $appId;

    /**
     * 商户密钥
     * @var string
     */
    public string $mchId;

    /**
     * API密钥
     * @var string
     */
    public string $key;

    /**
     * 证书序列号
     * @var string
     */
    public string $serialNo;

    /**
     * 证书cert
     * @var string
     */
    public string $certPath;

    /**
     * 证书key
     * @var string
     */
    public string $keyPath;

    /**
     * 支付异步回调地址
     * @var string
     */
    public string $notifyUrl;

    /**
     * 退款异步通知
     * @var string
     */
    public string $refundUrl;

    /**
     * 小程序appid
     * @var string
     */
    public string $miniprogAppid;

    /**
     * 应用appid
     * @var string
     */
    public string $appAppid;

    /**
     * 微信公众号appid
     * @var string
     */
    public string $wechatAppid;

    /**
     * v3支付公钥
     * @var string
     */
    public string $publicKey;

    /**
     * v3支付公钥证书
     * @var string
     */
    public string $publicPem;

    /**
     * web appid
     * @var string
     */
    public string $webAppid;

    /**
     * 是否v3支付
     * @var bool
     */
    public bool $isV3PAy = true;

    /**
     * @var HttpCommonConfig
     */
    protected HttpCommonConfig $httpConfig;

    /**
     * @var bool
     */
    protected bool $init = false;

    /**
     * V3PaymentConfig constructor.
     */
    public function __construct()
    {
        $this->httpConfig = app()->make(HttpCommonConfig::class);
        $this->init();
    }

    /**
     * 初始化
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/15
     */
    protected function init()
    {
        if ($this->init) {
            return;
        }
        $this->init = true;
        $this->appId = $this->httpConfig->getConfig(DefaultConfig::OFFICIAL_APPID, '');
        $this->mchId = $this->httpConfig->getConfig(DefaultConfig::PAY_MCHID, '');
        $this->serialNo = $this->httpConfig->getConfig('v3_pay.serial_no', '');
        $this->key = $this->httpConfig->getConfig('v3_pay.key', '');
        $this->isV3PAy = !!$this->httpConfig->getConfig('v3_pay.pay_type', false);
        $this->certPath = str_replace('//', '/', public_path() . $this->httpConfig->getConfig('pay.client_cert', ''));
        $this->keyPath = str_replace('//', '/', public_path() . $this->httpConfig->getConfig('pay.client_key', ''));
        $this->notifyUrl = trim($this->httpConfig->getConfig(DefaultConfig::COMMENT_URL)) . DefaultConfig::value('pay.notifyUrl');
        $this->refundUrl = trim($this->httpConfig->getConfig(DefaultConfig::COMMENT_URL)) . DefaultConfig::value('pay.refundUrl');
        $this->appAppid = $this->httpConfig->getConfig(DefaultConfig::APP_APPID, '');
        $this->webAppid = $this->httpConfig->getConfig(DefaultConfig::WEB_APPID, '');
        $this->wechatAppid = $this->httpConfig->getConfig(DefaultConfig::OFFICIAL_APPID, '');
        $this->miniprogAppid = $this->httpConfig->getConfig(DefaultConfig::MINI_APPID, '');
        $this->publicKey = $this->httpConfig->getConfig('v3_pay.public_key', '');
        $this->publicPem = str_replace('//', '/', public_path() . $this->httpConfig->getConfig('v3_pay.public_pem', ''));
    }

    /**
     * @return array
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/9/30
     */
    public function all(): array
    {
        return [
            'app_id' => $this->appId,
            'serial_no' => $this->serialNo,
            'mch_id' => $this->mchId,
            'key' => $this->key,
            'cert_path' => $this->certPath,
            'key_path' => $this->keyPath,
            'notify_url' => $this->notifyUrl,
            'http' => $this->httpConfig->all(),
            'other' => [
                'wechat' => [
                    'appid' => $this->wechatAppid,
                ],
                'web' => [
                    'appid' => $this->webAppid,
                ],
                'app' => [
                    'appid' => $this->appAppid,
                ],
                'miniprog' => [
                    'appid' => $this->miniprogAppid,
                ]
            ],
        ];
    }
}
