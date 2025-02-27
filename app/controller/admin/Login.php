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
namespace app\controller\admin;

use app\Request;
use app\services\user\LoginServices;
use app\validate\api\user\RegisterValidates;
use crmeb\services\CacheService;
use crmeb\utils\Captcha;
use app\services\system\admin\SystemAdminServices;
use Psr\SimpleCache\InvalidArgumentException;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Config;
use think\Response;

/**
 * 后台登陆
 * Class Login
 * @package app\controller\admin
 */
class Login
{
    /**
     * @var SystemAdminServices
     */
    #[Inject]
    protected SystemAdminServices $services;

    /**
     * 验证码
     * @return Response
     */
    public function captcha(): Response
    {
        return app()->make(Captcha::class)->create();
    }

    /**
     * @param Request $request
     * @return Response
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/10/11
     */
    public function getAjCaptcha(Request $request): Response
    {
        [$account] = $request->postMore([
            'account',
        ], true);

        $key = 'login_captcha_' . $account;

        return app('json')->success(['is_captcha' => Cache::get($key) > 2]);
    }

    /**
     * 安全验证
     * @param Request $request
     * @return Response
     * User: liusl
     * DateTime: 2024/8/2 10:27
     */
    public function secure(Request $request)
    {
        [$account, $password] = $request->postMore([
            'account',
            'pwd',
        ], true);
        validate(\app\validate\admin\setting\SystemAdminValidate::class)->scene('get')->check(['account' => $account, 'pwd' => $password]);
        $res = $this->services->secure($account, $password);
        return app('json')->success($res);
    }

    /**
     * 登陆
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function login(Request $request)
    {
        [$account, $password, $captchaType, $captchaVerification] = $request->postMore([
            'account',
            'pwd',
            ['captchaType', ''],
            ['captchaVerification', ''],
        ], true);
        $key = 'login_captcha_' . $account;
        if (Cache::has($key) && Cache::get($key) > 2) {
            if (!$captchaType || !$captchaVerification) {
                return app('json')->fail('请拖动滑块验证');
            }
            //二次验证
            try {
                aj_captcha_check_two($captchaType, $captchaVerification);
            } catch (\Throwable $e) {
                return app('json')->fail($e->getError());
            }
        }

        validate(\app\validate\admin\setting\SystemAdminValidate::class)->scene('get')->check(['account' => $account, 'pwd' => $password]);
        $res = $this->services->login($account, $password, 'admin');
        if ($res) {
            Cache::delete($key);
        }
        return app('json')->success($res);
    }

    /**
     * 获取短信验证码
     * @param Request $request
     * @return int|object|Response
     * @throws InvalidArgumentException
     * User: liusl
     * DateTime: 2024/8/2 11:36
     */
    public function verify(Request $request, LoginServices $services)
    {
        [$secure_key,$phone, $type, $key, $captchaType, $captchaVerification] = $request->postMore([
            ['secure_key', ''],
            ['phone', 0],
            ['type', ''],
            ['key', ''],
            ['captchaType', ''],
            ['captchaVerification', ''],
        ], true);

        if(!$phone){
            $phone = CacheService::get($secure_key);
            if (!$phone) {
                return app('json')->fail('手机号码不存在');
            }
        }

        $keyName = 'sms.key.' . $key;
        $nowKey = 'sms.' . date('YmdHi');

        if (!CacheService::has($keyName))
            return app('json')->make(401, '发送验证码失败,请刷新页面重新获取');

        $total = 1;
        if (CacheService::has($nowKey)) {
            $total = CacheService::get($nowKey);
            if ($total > Config::get('sms.maxMinuteCount', 20))
                return app('json')->success('已发送');
        }

        //二次验证
        try {
            aj_captcha_check_two($captchaType, $captchaVerification);
        } catch (\Throwable $e) {
            return app('json')->fail($e->getError());
        }

        try {
            validate(RegisterValidates::class)->scene('code')->check(['phone' => $phone]);
        } catch (ValidateException $e) {
            return app('json')->fail($e->getError());
        }

        $time = sys_config('verify_expire_time', 1);
        $smsCode = $services->verify($phone, $type, $time, app()->request->ip());
        if ($smsCode) {
            CacheService::set('code_' . $phone, $smsCode, $time * 60);
            CacheService::set($nowKey, $total, 61);
            event('sms.sendAfter', [$smsCode, $phone]);
            return app('json')->success('发送成功');
        } else {
            return app('json')->fail('发送失败');
        }

    }

    /**
     * 短信验证码登录
     * @param Request $request
     * @return mixed
     * @throws InvalidArgumentException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function mobileLogin(Request $request)
    {
        [$phone, $code,$secure_key] = $request->postMore([
            'phone', 'code','secure_key'
        ], true);
        if (!$code) {
            return app('json')->fail('请输入验证码');
        }
        if(!$phone){
            $phone = CacheService::get($secure_key);
            if (!$phone) {
                return app('json')->fail('手机号码不存在');
            }
        }
        //验证验证码
        $verifyCode = CacheService::get('code_' . $phone);
        if (!$verifyCode)
            return app('json')->fail('请先获取验证码');
        $verifyCode = substr($verifyCode, 0, 6);
        if ($verifyCode != $code) {
            CacheService::delete('code_' . $phone);
            return app('json')->fail('验证码错误');
        }
        $res = $this->services->login($phone, $code, 'admin', true);
        if ($res) {
            CacheService::delete('code_' . $phone);
            CacheService::delete($secure_key);
        }
        return app('json')->success($res);
    }

    /**
     * 短信重置密码
     * @param Request $request
     * @return mixed
     * @throws InvalidArgumentException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function resetPwd(Request $request)
    {
        [$phone, $code, $newPwd] = $request->postMore([
            'phone', 'code', 'new_pwd'
        ], true);
        if (!$phone) {
            return app('json')->fail('请输入手机号');
        }
        if (!check_phone($phone)) {
            return app('json')->fail('请输入正确的手机号!');
        }
        if (!$code) {
            return app('json')->fail('请输入验证码');
        }
        if (!$newPwd) {
            return app('json')->fail('请输入新密码');
        }
        //验证验证码
        $verifyCode = CacheService::get('code_' . $phone);
        if (!$verifyCode)
            return app('json')->fail('请先获取验证码');
        $verifyCode = substr($verifyCode, 0, 6);
        if ($verifyCode != $code) {
            CacheService::delete('code_' . $phone);
            return app('json')->fail('验证码错误');
        }
        $this->services->resetPwd($phone, $newPwd);
        return app('json')->success('重置成功，请重新登录');
    }

    /**
     * 获取后台登录页轮播图以及LOGO
     * @return mixed
     */
    public function info()
    {
        return app('json')->success($this->services->getLoginInfo());
    }

    /**
     * @return mixed
     */
    public function ajcaptcha(Request $request)
    {
        $captchaType = $request->get('captchaType');
        return app('json')->success(aj_captcha_create($captchaType));
    }

    /**
     * 一次验证
     * @return mixed
     */
    public function ajcheck(Request $request)
    {
        [$token, $pointJson, $captchaType] = $request->postMore([
            ['token', ''],
            ['pointJson', ''],
            ['captchaType', ''],
        ], true);
        try {
            aj_captcha_check_one($captchaType, $token, $pointJson);
            return app('json')->success();
        } catch (\Throwable $e) {
            return app('json')->fail(400336);
        }
    }
}
