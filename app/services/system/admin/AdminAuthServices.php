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

namespace app\services\system\admin;


use app\dao\system\admin\AdminAuthDao;
use app\services\BaseServices;
use app\services\other\CacheServices;
use crmeb\exceptions\AuthException;
use crmeb\services\CacheService;
use crmeb\utils\ApiErrorCode;
use crmeb\utils\JwtAuth;
use Firebase\JWT\ExpiredException;
use Psr\SimpleCache\InvalidArgumentException;
use think\annotation\Inject;

/**
 * admin授权service
 * Class AdminAuthServices
 * @package app\services\system\admin
 * @mixin AdminAuthDao
 */
class AdminAuthServices extends BaseServices
{

    /**
     * @var AdminAuthDao
     */
    #[Inject]
    protected AdminAuthDao $dao;

    /**
     * 获取Admin授权信息
     * @param string $token
     * @return array
     * @throws InvalidArgumentException
     */
    public function parseToken(string $token): array
    {
        /** @var CacheService $cacheService */
        $cacheService = app()->make(CacheService::class);

        if (!$token || $token === 'undefined') {
            throw new AuthException(ApiErrorCode::ERR_LOGIN);
        }
        /** @var JwtAuth $jwtAuth */
        $jwtAuth = app()->make(JwtAuth::class);
        //设置解析token
        [$id, $type, $auth] = $jwtAuth->parseToken($token);

        //检测token是否过期
        $md5Token = md5($token);
        if (!$cacheService->hasToken($md5Token) || !($cacheToken = $cacheService->getTokenBucket($md5Token))) {
            $this->authFailAfter($id, $type);
            throw new AuthException(ApiErrorCode::ERR_LOGIN);
        }
        //是否超出有效次数
        if (isset($cacheToken['invalidNum']) && $cacheToken['invalidNum'] >= 3) {
            if (!request()->isCli()) {
                $cacheService->clearToken($md5Token);
            }
            $this->authFailAfter($id, $type);
            throw new AuthException(ApiErrorCode::ERR_LOGIN_INVALID);
        }


        //验证token
        try {
            $jwtAuth->verifyToken();
            $cacheService->setTokenBucket($md5Token, $cacheToken, $cacheToken['exp']);
        } catch (ExpiredException $e) {
            $cacheToken['invalidNum'] = isset($cacheToken['invalidNum']) ? $cacheToken['invalidNum']++ : 1;
            $cacheService->setTokenBucket($md5Token, $cacheToken, $cacheToken['exp']);
        } catch (\Throwable $e) {
            if (!request()->isCli()) {
                $cacheService->clearToken($md5Token);
            }
            $this->authFailAfter($id, $type);
            throw new AuthException(ApiErrorCode::ERR_LOGIN_INVALID);
        }

        //获取管理员信息
        $adminInfo = $this->dao->get($id);
        if (!$adminInfo || !$adminInfo->id) {
            if (!request()->isCli()) {
                $cacheService->clearToken($md5Token);
            }
            $this->authFailAfter($id, $type);
            throw new AuthException(ApiErrorCode::ERR_LOGIN_STATUS);
        }
        //修改密码后token立刻过期
        if ($auth !== md5($adminInfo['pwd'])) {
            throw new AuthException(ApiErrorCode::ERR_LOGIN_STATUS);
        }

        $adminInfo->type = $type;
        return $adminInfo->hidden(['pwd', 'is_del', 'status'])->toArray();
    }

    /**
     * token验证失败后事件
     */
    protected function authFailAfter($id, $type)
    {
        try {
            $postData = request()->post();
            $rule = trim(strtolower(request()->rule()->getRule()));
            $method = trim(strtolower(request()->method()));
            //添加商品退出后事件
            if ($rule === 'product/product/<id>' && $method === 'post') {
                $this->saveProduct($id, $postData);
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * 保存提交数据
     * @param $adminId
     * @param $postData
     */
    protected function saveProduct($adminId, $postData)
    {
        /** @var CacheServices $cacheService */
        $cacheService = app()->make(CacheServices::class);
        $cacheService->setDbCache($adminId . '_product_data', $postData, 68400);
    }
}
