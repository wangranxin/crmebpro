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

namespace app\services\system;

use app\dao\system\SystemMenusRelevanceDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use think\annotation\Inject;

/**
 * 权限菜单关联
 * Class SystemMenusRelevanceServices
 * @package app\services\system
 * @mixin SystemMenusRelevanceDao
 */
class SystemMenusRelevanceServices extends BaseServices
{

    /**
     * @var SystemMenusRelevanceDao
     */
    #[Inject]
    protected SystemMenusRelevanceDao $dao;

    /**
     * 添加关键词
     * @param $menuId
     * @param $data
     * @return true
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/25 上午10:04
     */
    public function updateData($menuId, $data)
    {
        $haveData = $this->search(['menu_id' => $menuId])->column('keyword');

        $delData = array_diff($haveData, $data);
        $newData = array_diff($data, $haveData);

        if ($delData) {
            $this->dao->delete(['menu_id' => $menuId, 'keyword' => $delData]);
        }

        if ($newData) {
            $newData = array_map(function ($item) use ($menuId) {
                return ['menu_id' => $menuId, 'keyword' => $item];
            }, $newData);
            $this->dao->saveAll($newData);
        }
        CacheService::redisHandler('system_menus')->clear();
        return true;
    }

    /**
     * 获取关键词
     * @param $menuId
     * @return array
     * @throws \ReflectionException
     * User: liusl
     * DateTime: 2024/12/25 上午10:04
     */
    public function getKeyword($menuId): array
    {
        return $this->search(['menu_id' => $menuId])->column('keyword');
    }
}
