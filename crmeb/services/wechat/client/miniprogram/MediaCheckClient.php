<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------
 */

namespace crmeb\services\wechat\client\miniprogram;


use crmeb\services\wechat\client\BaseClient;
use crmeb\services\wechat\config\MiniProgramConfig;
use crmeb\services\wechat\util\AES;
use crmeb\services\wechat\WechatException;
use EasyWeChat\Kernel\HttpClient\AccessTokenAwareClient;
use EasyWeChat\Kernel\HttpClient\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use think\annotation\Inject;
use think\exception\ValidateException;


class MediaCheckClient extends BaseClient
{

    const MSG_API = 'wxa/msg_sec_check';
    const MEDIA_API = 'wxa/media_check_async';
    const LABEL = [
        100   => '正常',
        10001 => '广告',
        20001 => '时政',
        20002 => '色情',
        20003 => '辱骂',
        20006 => '违法犯罪',
        20008 => '欺诈',
        20012 => '低俗',
        20013 => '版权',
        21000 => '其他'
    ];
    //scene 场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）

    /**
     * 文本敏感词检测
     * @param $content
     * @return Response|ResponseInterface
     * @throws TransportExceptionInterface
     * User: liusl
     * DateTime: 2024/11/26 下午5:28
     */
    public function msgSecCheck($content)
    {
        $params = [
            'content' => $content
        ];
        return $this->api->postJson(self::MSG_API, $params);

    }
}
