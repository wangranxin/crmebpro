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

namespace app\services\other\Import;


use app\dao\other\Import\ImportRecordErrorDao;
use app\services\BaseServices;
use think\annotation\Inject;

/**
 * 导入
 * Class ImportRecordErrorServices
 * @package app\services\other\import
 * @mixin ImportRecordErrorDao
 */
class ImportRecordErrorServices extends BaseServices
{

    /**
     * @var ImportRecordErrorDao
     */
    #[Inject]
    protected ImportRecordErrorDao $dao;

    /**
     * 列表
     * @param $where
     * @param bool $is_limit
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2024/12/19 下午5:08
     */
    public function getErrorList($where, bool $is_limit = false)
    {
        $page = $limit = 0;
        if ($is_limit) {
            [$page, $limit] = $this->getPageValue();
        }
        $query = $this->dao->search($where);
        $count = $query->count();
        $list = $query->when($page, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->select()->toArray();
        foreach ($list as &$item) {
            $item['original_data'] = json_decode($item['original_data'], true);
        }
        return compact('list', 'count');
    }
}
