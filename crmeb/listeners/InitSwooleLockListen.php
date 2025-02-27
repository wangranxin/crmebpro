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


namespace crmeb\listeners;


use app\listener\system\AutoConfig;
use crmeb\interfaces\ListenerInterface;
use crmeb\utils\Start;
use think\facade\Event;
use think\swoole\Manager;
use Swoole\Process\Pool;

/**
 * swoole 初始化
 */
class InitSwooleLockListen implements ListenerInterface
{

    public function handle($event): void
    {
        //注入事件
        Event::listen('get.config', AutoConfig::class);
        //启动时输出内容
        app()->make(Start::class)->show();
        //增加定时任务进程
        app()->make(Manager::class)->addBatchWorker(1, [$this, 'createCronServer'], 'cron server');
    }

    /**
     * 创建定时任务服务 - 单独启动一个进程做为定时任务执行进程
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/10/7
     */
    public function createCronServer(Pool $pool, $workerId)
    {
        event('crontab', $workerId);
    }
}
