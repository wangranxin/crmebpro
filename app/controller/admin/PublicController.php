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

namespace app\controller\admin;


use app\Request;
use app\services\other\CacheServices;
use app\services\system\attachment\SystemAttachmentServices;
use crmeb\services\CacheService;
use Psr\SimpleCache\InvalidArgumentException;
use think\Response;
use think\response\File;

class PublicController
{

    /**
     * 下载文件
     * @param string $key
     * @return Response|File
     * @throws InvalidArgumentException
     */
    public function download(string $key = '')
    {
        if (!$key) {
            return Response::create()->code(500);
        }
        $fileName = CacheService::get($key);
        if (is_array($fileName) && isset($fileName['path']) && isset($fileName['fileName']) && $fileName['path'] && $fileName['fileName'] && file_exists($fileName['path'])) {
            CacheService::delete($key);
            return download($fileName['path'], $fileName['fileName']);
        }
        return Response::create()->code(500);
    }

    /**
     * 获取erp开关
     * @return mixed
     */
    public function getErpConfig()
    {
        return app('json')->success(['open_erp' => !!sys_config('erp_open')]);
    }

    /**扫码上传
     * @param Request $request
     * @param $upload_type
     * @return \think\Response
     */
    public function scanUpload(Request $request)
    {
        [$file, $upload_type, $cache, $uploadToken, $type, $relation_id, $pid] = $request->postMore([
            ['file', 'file'],
            ['upload_type', 0],
            ['cache', ''],
            ['uploadToken', ''],
            ['type', 0],
            ['relation_id', 0],
            ['pid', 0]
        ], true);
        if (!$cache || !$uploadToken) {
            return app('json')->fail('参数有误，无法上传！');
        }
        $cacheServices = app()->make(CacheServices::class);
        $service = app()->make(SystemAttachmentServices::class);
        if ($cacheServices->getDbCache($cache, '') == '' || $cacheServices->getDbCache($cache, '') != $uploadToken) {
            return app('json')->fail('请重新扫码上传！');
        }
        $service->storeUpload((int)$pid, $file, $relation_id, $type, $upload_type, $uploadToken);
        return app('json')->success('上传成功！');
    }
}
