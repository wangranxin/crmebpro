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


use app\dao\other\CacheDao;
use app\services\BaseServices;
use think\annotation\Inject;

/**
 * Class CacheServices
 * @package app\services\other
 * @mixin CacheDao
 */
class CacheServices extends BaseServices
{

    /**
     * @var CacheDao
     */
    #[Inject]
    protected CacheDao $dao;

    /**
     * 获取数据缓存
     * @param string $key
     * @param string|callable|int|array $default 默认值不存在则写入
     * @param int $expire
     * @return mixed|null
     */
    public function getDbCache(string $key, $default, int $expire = 0)
    {
        $this->dao->delectDeOverdueDbCache();
        $result = $this->dao->value(['key' => $key], 'result');
        if ($result) {
            return json_decode($result, true);
        } else {
            if ($default instanceof \Closure) {
                // 获取缓存数据
                $value = $default();
                if ($value) {
                    $this->setDbCache($key, $value, $expire);
                    return $value;
                }
            } else {
                $this->setDbCache($key, $default, $expire);
                return $default;
            }
            return null;
        }

    }

    /**
     * 设置数据缓存存在则更新，没有则写入
     * @param string $key
     * @param $result
     * @param $expire
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     * @throws \Exception
     */
    public function setDbCache(string $key, $result, $expire = 0)
    {
        $this->dao->delectDeOverdueDbCache();
        $addTime = $expire ? time() + $expire : 0;
        if ($this->dao->count(['key' => $key])) {
            return $this->dao->update($key, [
                'result' => json_encode($result),
                'expire_time' => $addTime,
                'add_time' => time()
            ], 'key');
        } else {
            return $this->dao->save([
                'key' => $key,
                'result' => json_encode($result),
                'expire_time' => $addTime,
                'add_time' => time()
            ]);
        }
    }


    /**
     * 删除某个缓存
     * @param string $key
     */
    public function delectDbCache(string $key = '')
    {
        if ($key)
            return $this->dao->delete($key, 'key');
        else
            return false;
    }

}
