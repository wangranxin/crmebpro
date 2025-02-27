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
namespace crmeb\services\printer\storage;

use crmeb\basic\BasePrinter;
use crmeb\services\printer\AccessToken;

/**
 * Class YiLianYun
 * @package crmeb\services\printer\storage
 */
class YiLianYun extends BasePrinter
{

    /**
     * 初始化
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config)
    {

    }

    /**
     * 开始打印
     * @return bool|mixed|string
     * @throws \Exception
     */
    public function startPrinter()
    {
        if (!$this->printerContent) {
            return $this->setError('Missing print');
        }
        $time = time();
        try {
            $request = $this->accessToken->postRequest($this->accessToken->getApiUrl('print/index'), [
                'client_id' => $this->accessToken->clientId,
                'access_token' => $this->accessToken->getAccessToken(),
                'machine_code' => $this->accessToken->machineCode,
                'content' => $this->printerContent,
                'origin_id' => 'crmeb' . $time,
                'sign' => strtolower(md5($this->accessToken->clientId . $time . $this->accessToken->apiKey)),
                'id' => $this->accessToken->createUuid(),
                'timestamp' => $time
            ]);
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }
        $this->printerContent = null;
        if ($request === false) {
            return $this->setError('request was aborted');
        }
        $request = is_string($request) ? json_decode($request, true) : $request;
        if (isset($request['error']) && $request['error'] != 0) {
            return $this->setError(isset($request['error_description']) ? $request['error_description'] : 'Accesstoken has expired');
        }
        return $request;
    }

    /**
     * 设置打印内容
     * @param $content
     * @param int $times
     * @return YiLianYun
     */
    public function setPrinterContent($content, $times = 1): self
    {
        $this->printerContent = $content;
        return $this;
    }

}