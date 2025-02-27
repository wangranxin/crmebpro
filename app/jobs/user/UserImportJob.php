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

namespace app\jobs\user;


use app\services\other\Import\ImportRecordServices;
use app\services\user\UserServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 导入用户数据
 * Class UserImportJob
 * @package app\jobs\user
 */
class UserImportJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 导入用户数据
     * @return bool
     */
    public function doJob(int $id, array $data, bool $end)
    {
        if (!$id || !is_array($data)) {
            return true;
        }
        try {
            app()->make(ImportRecordServices::class)->userSingleImport($data, $id, $end);
        } catch (\Throwable $e) {

            response_log_write([
                'message' => '导入用户数据失败,失败原因:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }
}
