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
declare (strict_types=1);

namespace app\services\wechat;

use app\services\BaseServices;
use app\dao\wechat\WechatReplyKeyDao;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 *
 * Class UserWechatuserServices
 * @package app\services\user
 * @mixin WechatReplyKeyDao
 */
class WechatReplyKeyServices extends BaseServices
{
    /**
     * @var WechatReplyKeyDao
     */
    #[Inject]
    protected WechatReplyKeyDao $dao;

    /**
     * @param array $where
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getReplyKeyAll(array $where): array
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getReplyKeyList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }
}
