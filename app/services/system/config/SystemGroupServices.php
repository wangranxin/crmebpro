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

namespace app\services\system\config;

use app\dao\system\config\SystemGroupDao;
use app\services\BaseServices;
use think\annotation\Inject;

/**
 * 组合数据
 * Class SystemGroupServices
 * @package app\services\system\config
 * @mixin SystemGroupDao
 */
class SystemGroupServices extends BaseServices
{

    /**
     * @var SystemGroupDao
     */
    #[Inject]
    protected SystemGroupDao $dao;

    /**
     * 获取组合数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGroupList(array $where, array $field = ['*'])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getGroupList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as $key => $value) {
            if (isset($value['fields'])) {
                $list[$key]['typelist'] = $value['fields'];
                unset($list[$key]['fields']);
            }
        }
        return compact('list', 'count');
    }

    /**
     * 获取组合数据tab下的header头部
     * @param int $id
     * @return array
     */
    public function getGroupDataTabHeader(int $id)
    {
        $data = $this->getValueFields($id);
        $header = [];
        foreach ($data as $key => $item) {
            if ($item['type'] == 'upload' || $item['type'] == 'uploads') {
                $header[$key]['key'] = $item['title'];
                $header[$key]['minWidth'] = 60;
                $header[$key]['type'] = 'img';
            } elseif ($item['title'] == 'url' || $item['title'] == 'wap_url' || $item['title'] == 'link' || $item['title'] == 'wap_link') {
                $header[$key]['key'] = $item['title'];
                $header[$key]['minWidth'] = 200;
            } else {
                $header[$key]['key'] = $item['title'];
                $header[$key]['minWidth'] = 100;
            }
            $header[$key]['title'] = $item['name'];
        }
        array_unshift($header, ['key' => 'id', 'title' => '编号', 'width' => 55]);
        array_push($header, ['slot' => 'status', 'title' => '是否可用', 'minWidth' => 80], ['key' => 'sort', 'title' => '排序', 'minWidth' => 80], ['slot' => 'action', 'fixed' => 'right', 'title' => '操作', 'minWidth' => 120]);
        return compact('header');
    }

    /**
     * 获取组合数据fields字段
     * @param int $id
     * @return array|mixed
     */
    public function getValueFields(int $id)
    {
        return json_decode($this->dao->value(['id' => $id], 'fields'), true) ?: [];
    }

}
