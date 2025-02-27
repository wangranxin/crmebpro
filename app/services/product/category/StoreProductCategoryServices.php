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
namespace app\services\product\category;

use app\dao\product\category\StoreProductCategoryDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use crmeb\utils\Arr;
use think\annotation\Inject;
use think\facade\Route as Url;

/**
 * Class StoreCategoryService
 * @package app\services\product\product
 * @mixin StoreProductCategoryDao
 */
class StoreProductCategoryServices extends BaseServices
{

	/**
 	* 查询字段定义
	* @var array|string[]
	*/
	public array $cateField = [0 => 'cid', 1 => 'sid', 2 => 'tid'];

    /**
     * @var StoreProductCategoryDao
     */
    #[Inject]
    protected StoreProductCategoryDao $dao;

    /**
     * 获取分类列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTreeList($where)
    {
        $list = $this->dao->getTierList($where);
        if (!empty($list) && ($where['cate_name'] !== '' || $where['pid'] !== '')) {
            $pids = Arr::getUniqueKey($list, 'pid');
            $parentList = $this->dao->getTierList(['id' => $pids]);
            $list = array_merge($list, $parentList);
            foreach ($list as $key => $item) {
                $arr = $list[$key];
                unset($list[$key]);
                if (!in_array($arr, $list)) {
                    $list[] = $arr;
                }
            }
        }
        $list = get_tree_children($list);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取分类列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where, string $field = '*', array $with = ['children'])
    {
        $list = $this->dao->getList($where, $field, 0, 0, $with);
        $count = $this->dao->count($where);
        if ($list) {
            foreach ($list as $key => &$item) {
                if (isset($item['children']) && $item['children']) {
                    $item['children'] = [];
                    $item['loading'] = false;
                    $item['_loading'] = false;
                } else {
                    unset($item['children']);
                }
				if (isset($item['AdminChildren']) && $item['AdminChildren']) {
                    $item['children'] = [];
                    $item['loading'] = false;
                    $item['_loading'] = false;
					unset($item['AdminChildren']);
                }
            }
        }
        return compact('list', 'count');
    }

    /**
     * 商品分类搜索下拉
     * @param string $show
     * @param string $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTierList($show = '', $type = 0)
    {
        $where = [];
        if ($show !== '') $where['is_show'] = $show;
        if (!$type) $where['pid'] = 0;
        return sort_list_tier($this->dao->getTierList($where));
    }

    /**
 	* 获取分类cascader
	* @param int $type
	* @param int $relation_id
	* @param int $level
	* @return mixed
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
    public function cascaderList(int $type = 0, int $relation_id = 0, int $level = -1)
    {
        $where = ['is_show' => 1, 'type' => $type, 'relation_id' => $relation_id, 'level' => $level];
        $data = get_tree_children($this->dao->getTierList($where, ['id as value', 'cate_name as label', 'pid']), 'children', 'value');
//        foreach ($data as &$item) {
//            if (!isset($item['children'])) {
//                $item['disabled'] = true;
//            }
//        }
        return $data;
    }

    /**
     * 设置分类状态
     * @param $id
     * @param $is_show
     */
    public function setShow(int $id, int $is_show)
    {
        $res = $this->dao->update($id, ['is_show' => $is_show]);
        $res = $res && $this->dao->update($id, ['is_show' => $is_show], 'pid');
        if (!$res) {
            throw new AdminException('设置失败');
        } else {
            $this->cacheTag()->clear();
            $this->cacheTag()->set('category_version', uniqid());
            $this->getCategory();
        }
    }

    /**
     * 创建新增表单
     * @param int $type
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm(int $type = 0)
    {
        return create_form('添加分类', $this->form([], $type), Url::buildUrl('/product/category'), 'POST');
    }

    /**
     * 创建编辑表单
     * @param int $id
     * @param int $type
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editForm(int $id, int $type = 0)
    {
        $info = $this->dao->get($id);
        return create_form('编辑分类', $this->form($info, $type), $this->url('/product/category/' . $id), 'PUT');
    }

    /**
     * 生成表单参数
     * @param array $info
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function form($info = [], int $type = 0)
    {
		$menus = $this->cascaderList(0, 0,1);
		array_unshift($menus, ['label' => '顶级分类', 'value' => 0]);
		$value = [0];
		if (isset($info['pid']) && $info['pid']) {
			$value = [$info['pid']];
			$pid = $this->dao->value(['id' => $info['pid']], 'pid');
			if ($pid) {
				array_unshift($value, $pid);
			}
		}
		$f[] = Form::cascader('pid', '父级', $value)->data($menus)->changeOnSelect(true)->filterable(1);
        $url = $type ? 'store/widget.images/index' : 'admin/widget.images/index';

        $f[] = Form::input('cate_name', '分类名称', $info['cate_name'] ?? '')->maxlength(30)->required();
        $f[] = Form::frameImage('pic', '移动端图(180*180)', Url::buildUrl($url, array('fodder' => 'pic')), $info['pic'] ?? '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        $f[] = Form::frameImage('big_pic', 'PC端大图(468*340)', Url::buildUrl($url, array('fodder' => 'big_pic')), $info['big_pic'] ?? '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        $f[] = Form::number('sort', '排序', (int)($info['sort'] ?? 0))->min(0)->min(0);
        $f[] = Form::radio('is_show', '状态', $info['is_show'] ?? 1)->options([['label' => '显示', 'value' => 1], ['label' => '隐藏', 'value' => 0]]);
        return $f;
    }

    /**
     * 获取一级分类组合数据
     * @return array[]
     */
    public function menus()
    {
        $list = $this->dao->getMenus(['pid' => 0]);
        $menus = [['value' => 0, 'label' => '顶级菜单']];
        foreach ($list as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['cate_name']];
        }
        return $menus;
    }

	/**
 	* 获取分类的层级、path
	* @param int $pid
	* @param $pInfo
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
	public function getLevelPath(int $pid, $pInfo = [])
	{
		if (!$pid) {
			return [0, ''];
		}
		if (!$pInfo) {
			$pInfo = $this->dao->get($pid, ['id', 'level', 'pid', 'path']);
		}
		if (!$pInfo) {
			return [0, ''];
		}
		$path = explode(',', $pInfo['path']);
		$path[] = $pid;
		return [$pInfo['level'] +1, trim(implode(',', $path), ',')];
	}


    /**
     * 保存新增数据
     * @param $data
     */
    public function createData($data)
    {
		$pid = $data['pid'] ?? 0;
        if ($this->dao->getOne(['pid' => $pid, 'cate_name' => $data['cate_name']])) {
            throw new AdminException('该分类已经存在');
        }
		[$level, $path] = $this->getLevelPath($pid);
		if ($level > 3) {
			throw new AdminException('不可添加更低阶分类');
		}
		$data['level'] = $level;
		$data['path'] = $path;
        $data['add_time'] = time();
        $res = $this->dao->save($data);
        if (!$res) throw new AdminException('添加失败');

        $this->cacheTag()->clear();
        $this->cacheTag()->set('category_version', uniqid());
        $this->getCategory();

        return $res;
    }

    /**
     * 保存修改数据
     * @param $id
     * @param $data
     */
    public function editData($id, $data)
    {
		$pid = $data['pid'] ?? 0;
        $cate = $this->dao->getOne(['id' => $id]);
        if (!$cate) {
            throw new AdminException('该分类不存在');
        }
		if ($data['pid'] == $id) {
			throw new AdminException('上级分类不能是自己');
		}
        if ($data['pid']) {
			$pcate = $this->dao->getOne(['id' => $pid]);
			if (!$pcate) {
				throw new AdminException('上级分类不存在');
			}
			if ($pcate['pid'] == $id) {
				throw new AdminException('不能互为上级');
			}
			if ($this->dao->count(['pid' => $id]) && $pcate['pid']) {//超过三层层级
				throw new AdminException('最大层级为三级');
			}
            $cate = $this->dao->getOne(['pid' => $data['pid'], 'cate_name' => $data['cate_name']]);
            if ($cate && $cate['id'] != $id) {
                throw new AdminException('该分类已经存在');
            }
        }
		[$level, $path] = $this->getLevelPath($pid);
		if ($level > 2) {
			throw new AdminException('不可添加更低阶分类');
		}
		$data['level'] = $level;
		$data['path'] = $path;
        $res = $this->dao->update($id, $data);
        if (!$res) throw new AdminException('修改失败');

        $this->cacheTag()->clear();
        $this->cacheTag()->set('category_version', uniqid());
        $this->getCategory();
    }

    /**
     * 删除数据
     * @param int $id
     */
    public function del(int $id)
    {
        if ($this->dao->count(['pid' => $id])) {
            throw new AdminException('请先删除子分类!');
        }
        $res = $this->dao->delete($id);
        if (!$res) throw new AdminException('删除失败');
        $this->cacheTag()->clear();
        $this->cacheTag()->set('category_version', uniqid());
        $this->getCategory();
    }

    /**
     * 获取分类版本
     * @return mixed
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/11
     */
    public function getCategoryVersion()
    {
        return $this->dao->cacheTag()->remember('category_version', function () {
            return uniqid();
        });
    }

    /**
     * 获取指定id下的分类,一=以数组形式返回
     * @param string $cateIds
     * @return array
     */
    public function getCateArray(string $cateIds)
    {
        return $this->dao->getCateArray($cateIds);
    }

    /**
     * 前台分类列表
     * @return bool|mixed|null
     */
    public function getCategory(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        if ($limit) {
            return $this->dao->cacheTag()->remember(md5(json_encode($where + ['limit' => $limit])), function () use ($where, $limit) {
                return $this->dao->getALlByIndex($where, 'id,cate_name,pid,pic', $limit);
            });
        } else {
			$name = 'CATEGORY_All_';
			$category = $this->dao->cacheTag()->remember($name, function () {
				$category = $this->dao->getTierList(['is_show' => 1], ['id', 'pid', 'cate_name', 'pic', 'big_pic']);
				return get_tree_children($category);
			});
			foreach ($category as &$item) {
				if (!isset($item['children'])) $item['children'] = [];
			}
			return $category;
        }
    }

	/**
     * 前台分类列表
     * @return bool|mixed|null
     */
    public function getLevelCategory(int $id)
    {
		$info = $this->dao->get($id, ['id', 'pid']);
		$result = [];
		if ($info) {
			$where['pid'] = $info['pid'] ?? 0;
			$result =  $this->dao->cacheTag()->remember(md5('level_category_' . $info['pid']), function () use ($where) {
						return $this->dao->getALlByIndex($where);
					});
		}
		return $result;
    }

	/**
 	* 获取
	* @return array
	* @throws \ReflectionException
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
	public function getRankCategory()
	{
		return $this->dao->getList(['is_show' => 1, 'level' => 0], 'id,pid,cate_name,pic,big_pic', 0, 0, []);
	}

    /**
     * 获取分类列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOutList()
    {
        $list = $this->dao->getTierList();
        if (!empty($list)) {
            $pids = Arr::getUniqueKey($list, 'pid');
            $parentList = $this->dao->getTierList(['id' => $pids]);
            $list = array_merge($list, $parentList);
            foreach ($list as $key => $item) {
                $arr = $list[$key];
                unset($list[$key]);
                if (!in_array($arr, $list)) {
                    $list[] = $arr;
                }
            }
        }
        $list = get_tree_children($list);
        return $list;
    }

    /**
     * 获取一级分类
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOneCategory(array $where = [])
    {
        return $this->dao->getTierList($where + ['pid' => 0, 'is_show' => 1]);
    }

    /**
     * 分类列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCategoryList(array $where)
    {
        return $this->dao->getALlByIndex($where, 'id, cate_name, pid, pic, big_pic, sort, is_show, add_time');
    }

    /**
     * 分类详情
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        $info = $this->dao->get($id, ['id', 'cate_name', 'pid', 'pic', 'big_pic', 'sort', 'is_show']);
        if ($info) {
            $info = $info->toArray();
        }
        return $info;
    }

	/**
	 * 根据IDS获取分类名称
	 * @param array $cateList
	 * @param array $cate_ids
	 * @return array
	 */
	public function getCateName(array $cate_ids, array $cateList = [])
	{
		$cate_name = [];
		if ($cate_ids) {
			if (!$cateList) {
				$cateName = $this->dao->getCateParentAndChildName(implode(',', array_unique($cate_ids)));
			} else {
				$cateName = array_filter($cateList, function ($val) use ($cate_ids) {
					if (in_array($val['id'], $cate_ids)) {
						return $val;
					}
				});
			}
			foreach ($cateName as $k => $v) {
				$str = '';
				if (isset($v['one']) && $v['one']) {
					$str = $v['one'] . '/';
				}
				if (isset($v['two']) && $v['two']) {
					$str .= $v['two'];
				}
				if ($str) $cate_name[] = $str;
			}
		}
		return $cate_name;
	}

	/**
 	* 处理获取分类搜索条件
	* @param int $id
	* @param bool $isWhere
	* @return int|int[]|mixed
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	*/
	public function getCateLevel(int $id, bool $isWhere = true)
	{
		$category = $this->dao->get($id, ['id', 'level']);
		if (!$category) {
			$level = 0;
		} else {
			$level = $category['level'];
		}
		if ($isWhere) {
			$levelArr = [0 => 'cid', '1' => 'sid', 2 => 'tid'];
			return [$levelArr[$level] ?? 'cid' => $id];
		} else {
			return $level;
		}
	}

}
