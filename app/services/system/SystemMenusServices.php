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

use app\dao\system\SystemMenusDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use crmeb\utils\Arr;
use think\annotation\Inject;

/**
 * 权限菜单
 * Class SystemMenusServices
 * @package app\services\system
 * @mixin SystemMenusDao
 */
class SystemMenusServices extends BaseServices
{

    /**
     * @var SystemMenusDao
     */
    #[Inject]
    protected SystemMenusDao $dao;

    /**
     * 获取菜单没有被修改器修改的数据
     * @param $menusList
     * @return array
     */
    public function getMenusData($menusList)
    {
        $data = [];
        foreach ($menusList as $item) {
//            $item['expand'] = true;
            $item['selected'] = false;
            $item['title'] = $item['menu_name'];
            $data[] = $item->getData();
        }
        return $data;
    }

    /**
     * 获取后台权限菜单和权限
     * @param $rouleId
     * @param int $level
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMenusList($rouleId, int $level, int $type = 1)
    {
        /** @var SystemRoleServices $systemRoleServices */
        $systemRoleServices = app()->make(SystemRoleServices::class);
        $rules = $systemRoleServices->getRoleArray(['status' => 1, 'id' => $rouleId], 'rules');
        $rulesStr = Arr::unique($rules);
        $menusList = $this->dao->getMenusRoule(['type' => $type, 'route' => $level ? $rulesStr : '']);
        $unique = $this->dao->getMenusUnique(['type' => $type, 'unique' => $level ? $rulesStr : '']);
        return [Arr::getMenuIviewList($this->getMenusData($menusList)), $unique];
    }

    /**
     * 获取后台菜单树型结构列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        $menusList = $this->dao->getMenusList($where);
        $menusList = $this->getMenusData($menusList);
        return get_tree_children($menusList);
    }

    /**
     * 获取form表单所需要的所要的菜单列表
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getFormSelectMenus()
    {
        $menuList = $this->dao->getMenusRoule(['is_del' => 0], ['id', 'pid', 'menu_name']);
        $list = get_tree_children($this->getMenusData($menuList), '0', 'pid', 'id');
        $menus = [['value' => 0, 'label' => '顶级按钮']];
        foreach ($list as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['html'] . $menu['menu_name']];
        }
        return $menus;
    }

    /**
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getFormCascaderMenus(int $value = 0, int $type = 1)
    {
        $menuList = $this->dao->getMenusRoule(['is_del' => 0, 'type' => $type], ['id as value', 'pid', 'menu_name as label']);
        $menuList = $this->getMenusData($menuList);
        if ($value) {
            $data = get_tree_value($menuList, $value);
        } else {
            $data = [];
        }
        return [get_tree_children($menuList, 'children', 'value'), array_reverse($data)];
    }

    /**
     * 创建权限规格生表单
     * @param array $formData
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createMenusForm(array $formData = [], int $type = 1)
    {
        $field[] = Form::hidden('type', $type);
        $field[] = Form::input('menu_name', '按钮名称', $formData['menu_name'] ?? '')->required('按钮名称必填');
        // $field[] = Form::select('pid', '父级id', $formData['pid'] ?? 0)->setOptions($this->getFormSelectMenus())->filterable(1);
        $field[] = Form::input('menu_path', '路由名称', $formData['menu_path'] ?? '')->placeholder('请输入前台跳转路由地址')->required('请填写前台路由地址');
        $field[] = Form::input('unique_auth', '权限标识', $formData['unique_auth'] ?? '')->placeholder('不填写则后台自动生成');
        $params = $formData['params'] ?? '';
        $field[] = Form::input('params', '参数', is_array($params) ? '' : $params)->placeholder('举例:a/123/b/234');
        $field[] = Form::frameInput('icon', '图标', $this->url('admin/widget.widgets/icon', ['fodder' => 'icon']), $formData['icon'] ?? '')->icon('md-add')->height('500px');
        $field[] = Form::number('sort', '排序', (int)($formData['sort'] ?? 0))->min(0);
        $field[] = Form::radio('auth_type', '类型', $formData['auth_type'] ?? 1)->options([['value' => 2, 'label' => '接口'], ['value' => 1, 'label' => '菜单(菜单只显示三级)']]);
        $field[] = Form::radio('is_show', '状态', $formData['is_show'] ?? 1)->options([['value' => 0, 'label' => '关闭'], ['value' => 1, 'label' => '开启']]);
        $field[] = Form::radio('is_show_path', '是否为前端隐藏菜单', $formData['is_show_path'] ?? 0)->options([['value' => 1, 'label' => '是'], ['value' => 0, 'label' => '否']]);
        [$menuList, $data] = $this->getFormCascaderMenus((int)($formData['pid'] ?? 0), $type);
        $field[] = Form::cascader('menu_list', '父级id', $data)->data($menuList)->filterable(true);
        return $field;
    }

    /**
     * 新增权限表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createMenus(int $type = 1)
    {
        return create_form('添加权限', $this->createMenusForm([], $type), $this->url('/setting/save'));
    }

    /**
     * 修改权限菜单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateMenus(int $id)
    {
        $menusInfo = $this->dao->get($id);
        if (!$menusInfo) {
            throw new AdminException('数据不存在');
        }
        $menusInfo = $menusInfo->getData();
        return create_form('修改权限', $this->createMenusForm($menusInfo, $menusInfo['type'] ?? 1), $this->url('/setting/update/' . $id), 'PUT');
    }

    /**
     * 获取一条数据
     * @param int $id
     * @return mixed
     */
    public function find(int $id)
    {
        $menusInfo = $this->dao->get($id);
        if (!$menusInfo) {
            throw new AdminException('数据不存在');
        }
        $menu = $menusInfo->getData();
        $menu['pid'] = (int)$menu['pid'];
        $menu['auth_type'] = (int)$menu['auth_type'];
        $menu['is_header'] = (int)$menu['is_header'];
        $menu['is_show'] = (int)$menu['is_show'];
        $menu['is_show_path'] = (int)$menu['is_show_path'];
        if (!$menu['path']) {
            [$menuList, $data] = $this->getFormCascaderMenus($menu['pid']);
            $menu['path'] = $data;
        } else {
            $menu['path'] = explode('/', $menu['path']);
            if (is_array($menu['path'])) {
                $menu['path'] = array_map(function ($item) {
                    return (int)$item;
                }, $menu['path']);
            }
        }
        return $menu;
    }

    /**
     * 删除菜单
     * @param int $id
     * @return mixed
     */
    public function delete(int $id)
    {
        if ($this->dao->count(['pid' => $id])) {
            throw new AdminException('请先删除改菜单下的子菜单');
        }
        return $this->dao->delete($id);
    }

    /**
     * 获取添加身份规格
     * @param $roles
     * @param int $type
     * @param int $is_show
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMenus($roles, int $type = 1, int $is_show = 1): array
    {
        $field = ['menu_name', 'pid', 'id'];
        $where = ['is_del' => 0, 'type' => $type];
        if ($is_show) $where['is_show'] = 1;
        if (!$roles) {
            $menus = $this->dao->getMenusRoule($where, $field);
        } else {
            /** @var SystemRoleServices $service */
            $service = app()->make(SystemRoleServices::class);
            //拼接有长度限制
//            $ids = $service->value([['id', 'in', $roles]], 'GROUP_CONCAT(rules) as ids');
            $roles = is_string($roles) ? explode(',', $roles) : $roles;
            $ids = $service->getRoleIds($roles);
            $menus = $this->dao->getMenusRoule(['rule' => $ids] + $where, $field);
        }
        return $this->tidyMenuTier(false, $menus);
    }

    /**
     * 组合菜单数据
     * @param bool $adminFilter
     * @param $menusList
     * @param int $pid
     * @param array $navList
     * @return array
     */
    public function tidyMenuTier(bool $adminFilter = false, $menusList = [], int $pid = 0, array $navList = []): array
    {
        foreach ($menusList as $k => $menu) {
            $menu = $menu->getData();
            $menu['title'] = $menu['menu_name'];
            unset($menu['menu_name']);
            if ($menu['pid'] == $pid) {
                unset($menusList[$k]);
                $menu['children'] = $this->tidyMenuTier($adminFilter, $menusList, $menu['id']);
                if ($pid == 0 && !count($menu['children'])) continue;
                if ($menu['children']) $menu['expand'] = true;
                $navList[] = $menu;
            }
        }
        return $navList;
    }

    public function getSelectList(int $type = 1, string $keyword = '')
    {
        // 获取搜索列表
        $_list = $this->dao->getSearchList($type, $keyword);
        $list = [];
        $ids = [];
        foreach ($_list as $key => $v) {
            $menu = json_decode($v, true);
            $pid = $v->getData('pid');
            $menu['p_id'] = $pid;
            $list[$key] = $menu;

            $path = explode('/', $menu['path']);
            $ids = array_merge($ids, $path);
        }

        // 去除重复的id
        $ids = array_filter(array_unique($ids));

        // 合并PID列表查询
        $pidList = [];
        if ($ids) {
            $_pidList = $this->dao->search(['id' => $ids])->select();
            foreach ($_pidList as $key => $v) {
                $menu = json_decode($v, true);
                $pid = $v->getData('pid');
                $menu['p_id'] = $pid;
                $pidList[$key] = $menu;
            }
        }

        // 合并pidList到原始列表
        $list = array_merge($list, $pidList);

        // 获取符合条件的counts（当前显示的有效菜单项）
        $counts = $this->dao->getColumn([
            'is_show' => 1,
            'auth_type' => 1,
            'is_del' => 0,
            'is_show_path' => 0,
            'keywords' => $keyword
        ], 'pid', '', true);

        // 获取p_counts（菜单路径中有效的PID）
        $p_counts = $this->dao->getColumn([
            'id' => $ids
        ], 'pid', '', true);

        // 合并counts和p_counts
        $counts = array_merge($counts, $p_counts);

        // 对菜单列表进行排序处理
        $sortList = $this->sortListTier($list);

        // 返回最终结果
        $data = [];
        foreach ($sortList as $item) {
            if (!in_array($item['id'], $counts)) {
                $data[$item['id']] = $item;
                $data[$item['id']]['menu_name'] = isset($item['p_name']) && $item['p_name'] ? $item['p_name'] : $item['menu_name'];
                $data[$item['id']]['type'] = 0;
            }
        }

        return $data;
    }

    public function sortListTier(&$menuData, $pid = 0, $p_name = '')
    {
        foreach ($menuData as &$menu) {
            if ($menu['p_id'] == $pid) {
                // 拼接父级名称
                $menu['p_name'] = $p_name ? $p_name . '-' . $menu['menu_name'] : $menu['menu_name'];
                // 递归查找子菜单
                $this->sortListTier($menuData, $menu['id'], $menu['p_name']);
            }
        }
        return $menuData;
    }
}
