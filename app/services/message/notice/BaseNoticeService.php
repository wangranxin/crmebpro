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

namespace app\services\message\notice;


use app\services\wechat\WechatUserServices;
use think\facade\Log;

/**
 * Class BaseNoticeService
 * @author 等风来
 * @email 136327134@qq.com
 * @date 2023/9/20
 * @package app\services\message\notice
 */
abstract class BaseNoticeService
{

    /**
     * 配置
     * @var array
     */
    protected array $config;

    /**
     * 发送数据
     * @var array
     */
    protected array $data;

    /**
     * @var
     */
    protected bool $switchLog = true;

    /**
     * @var bool
     */
    protected bool $noticeSwitch = false;

    /**
     * BaseNoticeService constructor.
     * @param array $config
     * @param bool $noticeSwitch
     * @param bool $switchLog
     */
    public function __construct(array $config, bool $noticeSwitch, bool $switchLog = true)
    {
        $this->config = $config;
        $this->noticeSwitch = $noticeSwitch;
        $this->switchLog = $switchLog;
    }

    /**
     * 记录日志
     * @param string $message
     * @param array $content
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function log(string $message, array $content = [])
    {
        $this->switchLog && Log::error($message, $content);
    }

    /**
     * 执行任务
     * @param callable $callable
     * @param array $params
     * @return false
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/9/20
     */
    protected function handle(callable $callable, array $params = [])
    {
        try {

            if ($this->noticeSwitch) {
                return $callable(...$params);
            } else {
                return false;
            }

        } catch (\Throwable $e) {
            $this->log($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * 根据UID,user_type获取openid
     * @param int $uid
     * @param string $userType
     * @return mixed
     */
    public function getOpenidByUid(int $uid, string $userType = 'wechat')
    {
        /** @var WechatUserServices $wechatServices */
        $wechatServices = app()->make(WechatUserServices::class);
        return $wechatServices->uidToOpenid($uid, $userType);
    }
}
