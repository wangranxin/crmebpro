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

namespace app\services\other;


use app\dao\other\CategoryDao;
use app\services\BaseServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * Class CategoryServices
 * @package app\services\other
 * @mixin CategoryDao
 */
class CategoryServices extends BaseServices
{

    //积分分类
    public const  INTEGRAL_CATEGORY_GROUP = 5;

    /**
     * @var string
     */
    protected string $cacheName = 'crmeb_cate';

    /**
     * @var CategoryDao
     */
    #[Inject]
    protected CategoryDao $dao;

    /**
     * 获取分类列表
     * @param array $where
     * @param array $field
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getCateList(array $where = [], array $field = ['*'])
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getCateList($where, $page, $limit, $field);
        $count = $this->dao->count($where);
        return compact('data', 'count');
    }


}
