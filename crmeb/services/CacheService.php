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

namespace crmeb\services;

use think\facade\Cache as CacheStatic;

/**
 * crmeb 缓存类
 * Class CacheService
 * @package crmeb\services
 * @mixin \Redis
 */
class CacheService
{

    const CACHE_EXPIRE_NAME = 'cache_expire';

    //副屏相关
    const CASHIER_AUX_SCREEN_TAG = 'auxScreen';

    /**
     * 标签名
     * @var string
     */
    protected static $globalCacheName = '_cached_1515146130';

    /**
     * 缓存队列key
     * @var string[]
     */
    protected static $redisQueueKey = [
        0 => 'product',
        1 => 'seckill',
        2 => 'bargain',
        3 => 'combination',
        4 => 'integral',
        5 => 'discounts',
        6 => 'lottery'
    ];

    /**
     * 过期时间
     * @var int
     */
    protected static $expire;

    /**
     * 获取缓存过期时间
     * @param int|null $expire
     * @return int
     */
    protected static function getExpire(int $expire = null): int
    {
        if (self::$expire) {
            return (int)self::$expire;
        }
        $expire = !is_null($expire) ? $expire : CacheStatic::store('redis')->get(self::CACHE_EXPIRE_NAME, 360);
        if (!is_int($expire))
            $expire = (int)$expire;
        return self::$expire = $expire;
    }

    /**
     * 判断缓存是否存在
     * @param string $name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function has(string $name): bool
    {
        try {
            return CacheStatic::has($name);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 写入缓存
     * @param string $name 缓存名称
     * @param mixed $value 缓存值
     * @param int $expire 缓存时间，为0读取系统缓存时间
     * @return bool
     */
    public static function set(string $name, $value, int $expire = null): bool
    {
        try {
            return self::handler()->set($name, $value, $expire ?? self::getExpire($expire));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 如果不存在则写入缓存
     * @param string $name
     * @param bool $default
     * @return mixed
     */
    public static function get(string $name, $default = false, int $expire = null)
    {
        try {
            return self::handler()->remember($name, $default, $expire ?? self::getExpire($expire));
        } catch (\Throwable $e) {
            try {
                if (is_callable($default)) {
                    return $default();
                } else {
                    return $default;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    /**
     * 如果不存在则写入缓存
     * @param string $name
     * @param bool $default
     * @return mixed
     */
    public static function remember(string $name, $default = false, int $expire = null)
    {
        try {
            return self::handler()->remember($name, $default, $expire ?? self::getExpire($expire));
        } catch (\Throwable $e) {
            try {
                if (is_callable($default)) {
                    return $default();
                } else {
                    return $default;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }
    }

    /**
     * 删除缓存
     * @param string $name
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function delete(string $name)
    {
        return CacheStatic::delete($name);
    }

    /**
     * 缓存句柄
     *
     * @return \think\cache\TagSet|CacheStatic
     */
    public static function handler(?string $cacheName = null)
    {
        return CacheStatic::tag($cacheName ?: self::$globalCacheName);
    }

    /**
     * 清空缓存池
     * @return bool
     */
    public static function clear()
    {
        return self::handler()->clear();
    }

    /**
     * Redis缓存句柄
     *
     * @return \think\cache\TagSet|CacheStatic
     */
    public static function redisHandler($type = null)
    {
        if ($type) {
            return CacheStatic::store('redis')->tag($type);
        } else {
            return CacheStatic::store('redis');
        }
    }

    /**
     * 放入令牌桶
     * @param string $key
     * @param array $value
     * @param string $type
     * @return bool
     */
    public static function setTokenBucket(string $key, $value, $expire = null, string $type = 'admin')
    {
        try {
            $redisCahce = self::redisHandler($type);
            return $redisCahce->set($key, $value, $expire);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除所有令牌桶
     * @param string $type
     * @return bool
     */
    public static function clearTokenAll(string $type = 'admin')
    {
        try {
            return self::redisHandler($type)->clear();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 清除令牌桶
     * @param string $type
     * @return bool
     */
    public static function clearToken(string $key)
    {
        try {
            return self::redisHandler()->delete($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 查看令牌是否存在
     * @param string $key
     * @return bool
     */
    public static function hasToken(string $key)
    {
        try {
            return self::redisHandler()->has($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取token令牌桶
     * @param string $key
     * @return mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getTokenBucket(string $key)
    {
        try {
            return self::redisHandler()->get($key, null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 获取指定分数区间的成员
     * @param $key
     * @param int $start
     * @param int $end
     * @param array $options
     * @return mixed
     */
    public static function zRangeByScore($key, $start = '-inf', $end = '+inf', array $options = [])
    {
        return self::redisHandler()->zRangeByScore($key, $start, $end, $options);
    }


    /**
     * 设置redis入库队列
     * @param string $unique
     * @param int $number
     * @param int $type
     * @param bool $isPush true :重置 false：累加
     * @return bool
     */
    public static function setStock(string $unique, int $number, int $type = 1, bool $isPush = true)
    {
        if (!$unique || !$number) return false;
        $name = (self::$redisQueueKey[$type] ?? '') . '_' . $type . '_' . $unique;
        /** @var self $cache */
        $cache = self::redisHandler();
        $res = true;
        if ($isPush) {
            $cache->del($name);
        }
        $data = [];
        for ($i = 1; $i <= $number; $i++) {
            $data[] = $i;
        }
        $res = $res && $cache->lPush($name, ...$data);
        return $res;
    }

    /**
     * 弹出redis队列中的库存条数
     * @param string $unique
     * @param int $number
     * @param int $type
     * @return bool
     */
    public static function popStock(string $unique, int $number, int $type = 1)
    {
        if (!$unique || !$number) return false;
        $name = (self::$redisQueueKey[$type] ?? '') . '_' . $type . '_' . $unique;
        /** @var self $cache */
        $cache = self::redisHandler();
        $res = true;
        if ($number > $cache->lLen($name)) {
            return false;
        }
        for ($i = 1; $i <= $number; $i++) {
            $res = $res && $cache->lPop($name);
        }

        return $res;
    }

    /**
     * 是否有库存|返回库存
     * @param string $unique
     * @param int $number
     * @param int $type
     * @return bool
     */
    public static function checkStock(string $unique, int $number = 0, int $type = 1)
    {
        $name = (self::$redisQueueKey[$type] ?? '') . '_' . $type . '_' . $unique;
        if ($number) {
            return self::redisHandler()->lLen($name) >= $number;
        } else {
            return self::redisHandler()->lLen($name);
        }
    }

    /**
     * 检查锁
     * @param int $uid
     * @param int $timeout
     * @return bool
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/22
     */
    public static function setMutex(string $key, int $timeout = 10)
    {
        $curTime = time();
        $readMutexKey = "redis:mutex:{$key}";
        $mutexRes = self::redisHandler()->handler()->setnx($readMutexKey, $curTime + $timeout);
        if ($mutexRes) {
            return true;
        }
        //就算意外退出，下次进来也会检查key，防止死锁
        $time = self::redisHandler()->handler()->get($readMutexKey);
        if ($curTime > $time) {
            self::redisHandler()->handler()->del($readMutexKey);
            return self::redisHandler()->handler()->setnx($readMutexKey, $curTime + $timeout);
        }
        return false;
    }

    /**
     * 删除锁
     * @param $uid
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/11/22
     */
    public static function delMutex(string $key)
    {
        $readMutexKey = "redis:mutex:{$key}";
        self::redisHandler()->handler()->del($readMutexKey);
    }

	/**
	 * 数据库锁
	 * @param $key
	 * @param $fn
	 * @param int $ex
	 * @return bool|mixed
	 */
	public static function lock($key, $fn = [], int $ex = 10)
	{
		$service = app()->make(LockService::class);
		if ($fn instanceof \Closure) {
			return $service->exec($key, $fn, $ex);
		} else {
			return $service->lock($key, 1, $ex);
		}
	}

	/**
	 * 销毁锁
	 * @param $key
	 * @return bool
	 */
	public static function unLock($key)
	{
		return app()->make(LockService::class)->unlock($key);
	}

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return self::redisHandler()->{$name}(...$arguments);
    }

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return self::redisHandler()->{$name}(...$arguments);
    }

}
