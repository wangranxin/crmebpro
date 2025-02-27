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

namespace crmeb\services\wechat\config;

use crmeb\services\wechat\contract\ConfigHandlerInterface;
use crmeb\services\wechat\DefaultConfig;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

/**
 * 支付配置
 * Class PaymentConfig
 * @package crmeb\services\wechat\config
 */
class PaymentConfig implements ConfigHandlerInterface
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
     * 小程序商户号
     * @var string
     */
    public string $routineMchId;

    /**
     * API密钥
     * @var string
     */
    public string $key;

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
     * @var HttpCommonConfig
     */
    protected HttpCommonConfig $httpConfig;

    /**
     * @var bool
     */
    protected bool $init = false;

    /**
     * PaymentConfig constructor.
     * @param HttpCommonConfig $commonConfig
     */
    public function __construct(HttpCommonConfig $commonConfig)
    {
        $this->httpConfig = $commonConfig;
        $this->init();
    }

    /**
     * 初始化
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/18
     */
    protected function init()
    {
        if ($this->init) {
            return;
        }
        $this->init = true;
        $this->appId = $this->httpConfig->getConfig(DefaultConfig::OFFICIAL_APPID, '');
        $this->mchId = $this->httpConfig->getConfig(DefaultConfig::PAY_MCHID, '');
        $this->routineMchId = $this->httpConfig->getConfig('pay.routine_mchid', '');
        $this->key = $this->httpConfig->getConfig('pay.key', '');

        $certPath = env('RECEPTACLE_ENABLE', false) ? env('RECEPTACLE_PAYCERT', '') : $this->httpConfig->getConfig('pay.client_cert', '');
        $keyPath =  env('RECEPTACLE_ENABLE', false) ? env('RECEPTACLE_PAYKEY', '') : $this->httpConfig->getConfig('pay.client_key', '');

        $this->certPath = str_replace('//', '/', public_path() . $certPath);
        $this->keyPath = str_replace('//', '/', public_path() . $keyPath);
        $this->notifyUrl = trim($this->httpConfig->getConfig(DefaultConfig::COMMENT_URL)) . DefaultConfig::value('pay.notifyUrl');
        $this->refundUrl = trim($this->httpConfig->getConfig(DefaultConfig::COMMENT_URL)) . DefaultConfig::value('pay.refundUrl');
    }

    /**
     * 获取配置
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->httpConfig->getConfig($key, $default);
    }

    /**
     * 全部配置
     * @return array
     */
    #[Pure]
    #[ArrayShape([
        'app_id' => "string",
        'mch_id' => "string",
        'v2_secret_key' => "string",
        'private_key' => "string",
        'certificate' => "string",
        'notify_url' => "string",
        'log' => "array",
        'http' => "bool[]"
    ])]
    public function all(): array
    {
        return [
            'app_id' => $this->appId,
            'mch_id' => $this->mchId,
            'v2_secret_key' => $this->key,
            'private_key' => $this->keyPath,
            'certificate' => $this->certPath,
            'notify_url' => $this->notifyUrl,
            'http' => $this->httpConfig->all()
        ];
    }
}
