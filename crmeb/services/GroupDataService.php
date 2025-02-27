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

use app\services\system\config\SystemGroupDataServices;use think\facade\Config;

/**
 * 获取组合数据配置
 * Class GroupDataService
 * @package crmeb\services
 */
class GroupDataService
{

	/**
     * 缓存前缀字符
     */
    const CACHE_SYSTEM = 'system_group_data';
    /**
     * 过期时间
     */
    const EXPIRE_TIME = 30 * 24 * 3600;

	 /**
     * 获取配置缓存前缀
     * @return string
     */
    public static function getTag()
    {
        return Config::get('cache.stores.redis.tag_prefix') . 'cache_' . self::CACHE_SYSTEM;
    }


    /**
     * 获取单个值
     * @param string $config_name 配置名称
     * @param int $limit 截取多少条
     * @param bool $isCaChe 是否读取缓存
     * @return array
     */
    public static function getData(string $config_name, int $limit = 0, bool $isCaChe = false): array
    {
        $callable = function () use ($config_name, $limit) {
            event('get.config');
            try {
                /** @var SystemGroupDataServices $service */
                $service = app()->make(SystemGroupDataServices::class);
                return $service->getConfigNameValue($config_name, $limit);
            } catch (\Exception) {
                return [];
            }
        };
        try {
            $cacheName = $limit ? "data_{$config_name}_{$limit}" : "data_{$config_name}";

            if ($isCaChe)
                return $callable();

            return CacheService::redisHandler(self::getTag())->remember($cacheName, $callable, self::EXPIRE_TIME);

        } catch (\Throwable) {

            try {
                return $callable();
            } catch (\Throwable) {
                return [];
            }

        }
    }

    /**
     * 根据id 获取单个值
     * @param int $id
     * @param bool $isCaChe 是否读取缓存
     * @return array
     */
    public static function getDataNumber(int $id, bool $isCaChe = false): array
    {
        $callable = function () use ($id) {
            try {

                /** @var SystemGroupDataServices $service */
                $service = app()->make(SystemGroupDataServices::class);
                $data = $service->getDateValue($id);
                if (is_object($data))
                    $data = $data->toArray();
                return $data;
            } catch (\Exception) {
                return [];
            }
        };
        try {
            $cacheName = "data_number_{$id}";

            if ($isCaChe)
                return $callable();

            return CacheService::redisHandler(self::getTag())->remember($cacheName, $callable, self::EXPIRE_TIME);

        } catch (\Throwable) {

            try {
                return $callable();
            } catch (\Throwable) {
                return [];
            }
        }
    }

	/**
     * 清空配置缓存
     * @return bool|void
     */
    public static function clear()
    {
        try {
            return CacheService::redisHandler(self::getTag())->clear();
        } catch (\Throwable $e) {
            \think\facade\Log::error('清空配置缓存失败：原因：' . $e->getMessage());
            return false;
        }
    }
}
