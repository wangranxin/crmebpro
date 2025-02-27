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

namespace app\services\community;

use app\dao\community\CommunityRelevanceDao;
use app\services\BaseServices;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 社区关联
 * Class CommunityRelevanceServices
 * @package app\services\community
 * @mixin CommunityRelevanceDao
 */
class CommunityRelevanceServices extends BaseServices
{

    //社区关联商品
    const TYPE_COMMUNITY_PRODUCT = 'community_product';
    //社区关联话题
    const TYPE_COMMUNITY_TOPIC = 'community_topic';

    //社区帖子点赞
    const TYPE_COMMUNITY_LIKE = 'community_like';

    //帖子浏览
    const TYPE_COMMUNITY_BROWSE = 'community_browse';

    //社区评论点赞
    const TYPE_COMMUNITY_COMMENT_LIKE = 'community_comment_like';

    //关注
    const TYPE_COMMUNITY_INTEREST = 'community_interest';

    /**
     * @var CommunityRelevanceDao
     */
    #[Inject]
    protected CommunityRelevanceDao $dao;

}
