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

namespace app\controller\api\v1\work;


use app\services\work\WorkGroupChatMemberServices;
use app\services\work\WorkGroupChatServices;
use crmeb\services\wechat\config\WorkConfig;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 客户群
 * Class GroupChatController
 * @package app\controller\api\v1\work
 */
class GroupChat extends BaseWork
{

    /**
     * @var WorkGroupChatServices
     */
    #[Inject]
    protected WorkGroupChatServices $service;

    /**
     * @param WorkConfig $config
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getGroupInfo(WorkConfig $config)
    {
        $chatId = $this->request->param('chat_id');
        if (!$chatId) {
            return $this->fail('缺少参数');
        }
        $corpId = $config->corpId;
        return $this->success($this->service->getGroupInfo($chatId, $corpId));
    }

    /**
     * 获取群成员列表
     * @param WorkGroupChatMemberServices $services
     * @param $id
     * @return mixed
     */
    public function getChatMemberList(WorkGroupChatMemberServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $name = $this->request->get('name', '');
        return $this->success($services->getChatMemberList((int)$id, $name));
    }
}
