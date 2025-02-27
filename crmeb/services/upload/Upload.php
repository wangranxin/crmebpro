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
namespace crmeb\services\upload;

use think\facade\Config;
use crmeb\basic\BaseManager;
use crmeb\services\upload\storage\COS;
use crmeb\services\upload\storage\Local;
use crmeb\services\upload\storage\OSS;
use crmeb\services\upload\storage\Qiniu;

/**
 * Class Upload
 * @package crmeb\services\upload
 * @mixin Local
 * @mixin OSS
 * @mixin COS
 * @mixin Qiniu
 */
class Upload extends BaseManager
{
    /**
     * 空间名
     * @var string
     */
    protected string $namespace = '\\crmeb\\services\\upload\\storage\\';

    /**
     * 设置默认上传类型
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        return Config::get('upload.default', 'local');
    }


}
