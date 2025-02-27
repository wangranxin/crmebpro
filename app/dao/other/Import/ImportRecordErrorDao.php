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

namespace app\dao\other\Import;


use app\dao\BaseDao;
use app\model\other\import\ImportRecordError;

/**
 * 导入数据错误
 * Class ImportRecordDao
 * @package app\dao\other
 */
class ImportRecordErrorDao extends BaseDao
{

    /**
     * @return string
     */
    public function setModel(): string
    {
        return ImportRecordError::class;
    }

}
