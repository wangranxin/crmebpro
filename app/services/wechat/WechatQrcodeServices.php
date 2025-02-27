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
declare (strict_types=1);

namespace app\services\wechat;

use app\dao\wechat\WechatQrcodeDao;
use app\services\BaseServices;
use app\services\other\QrcodeServices;
use crmeb\services\DownloadImageService;
use crmeb\services\UploadService;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\label\UserLabelServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\wechat\message\Image;
use crmeb\services\wechat\message\News;
use crmeb\services\wechat\message\Text;
use crmeb\services\wechat\message\Voice;
use crmeb\services\wechat\Messages;
use think\facade\Log;
use think\annotation\Inject;
/**
 *
 * Class WechatQrcodeServices
 * @package app\services\wechat
 * @mixin WechatQrcodeDao
 */
class WechatQrcodeServices extends BaseServices
{
    /**
     * @var WechatQrcodeDao
     */
    #[Inject]
    protected  WechatQrcodeDao $dao;

    /**
     * 获取渠道码列表
     * @param $where
     * @return array
     */
    public function qrcodeList(array $where)
    {
        /** @var UserLabelServices $userLabel */
        $userLabel = app()->make(UserLabelServices::class);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        foreach ($list as &$item) {
            $item['y_follow'] = $item['record']['y_follow'] ?? 0;
            $item['stop'] = $item['end_time'] ? $item['end_time'] > time() ? 1 : -1 : 0;
            $item['label_name'] = $userLabel->getColumn([['id', 'in', $item['label_id']]], 'label_name');
            $item['end_time'] = date('Y-m-d H:i:s', $item['end_time']);
			unset($item['record']);
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取详情
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function qrcodeInfo($id)
    {
        $info = $this->dao->get($id);
        if ($info) {
            $info = $info->toArray();
        } else {
            throw new AdminException('数据不存在');
        }
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $info['label_id'] = explode(',', $info['label_id']);
        foreach ($info['label_id'] as &$item) {
            $item = (int)$item;
        }
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $info['label_id'] = $userLabelServices->getLabelList(['ids' => $info['label_id']], ['id', 'label_name']);
        $info['time'] = $info['continue_time'];
        $info['content'] = json_decode($info['content'], true);
        $info['data'] = json_decode($info['data'], true);
        $info['avatar'] = $userService->value(['uid' => $info['uid']], 'avatar');
        return $info;
    }

    /**
     * 保存渠道码数据
     * @param $id
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveQrcode($id, $data)
    {
        $data['label_id'] = implode(',', $data['label_id']);
        $data['add_time'] = time();
        $data['continue_time'] = $data['time'];
        $data['end_time'] = $data['time'] ? $data['add_time'] + ($data['time'] * 86400) : 0;
        /** @var WechatReplyServices $replyServices */
        $replyServices = app()->make(WechatReplyServices::class);
        $type = $data['type'];
        if ($data['type'] == 'url') $type = 'text';
        $content = $data['content'];
        if ($data['type'] == 'news') $content = $data['content']['list'] ?? [];
        $method = 'tidy' . ucfirst($type);
        if ($type == 'image') {
            /** @var DownloadImageService $download */
            $download = app()->make(DownloadImageService::class);
            try {
                $path = $download->thumb(true)->downloadImage($content['src'])['path'];
                $content['src'] = $path;
                $data['data'] = $replyServices->{$method}($content, 0);
                @unlink(root_path() . 'public' . $path);
            } catch (\Throwable $e) {
                Log::error('获取图片错误，原因：' . $e->getMessage());
                throw new AdminException($e->getMessage());
            }
        } else {
            $data['data'] = $replyServices->{$method}($content, 0);
        }
        $data['content'] = json_encode($data['content']);
        $data['data'] = json_encode($data['data']);
        if ($id) {
            $info = $this->dao->get($id);
            if (!$info) throw new AdminException('数据不存在');
            if ($info['image'] == '') $data['image'] = $this->getChannelCode($id);
            $info = $this->dao->update($id, $data);
            if (!$info) throw new AdminException('保存失败');
        } else {
            $info = $this->dao->save($data);
            $image = $this->getChannelCode($info['id']);
            $info = $this->dao->update($info['id'], ['image' => $image]);
            if (!$info) throw new AdminException('保存失败');
        }
        return true;
    }

    /**
     * 生成渠道码
     * @param int $id
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChannelCode($id = 0)
    {
        /** @var SystemAttachmentServices $systemAttachment */
        $systemAttachment = app()->make(SystemAttachmentServices::class);
        $name = 'wechatqrcode_' . $id . '.jpg';
        $siteUrl = sys_config('site_url');
        $imageInfo = $systemAttachment->getInfo(['name' => $name]);
        if (!$imageInfo) {
            /** @var QrcodeServices $qrCode */
            $qrCode = app()->make(QrcodeServices::class);
            //公众号
            $resCode = $qrCode->getForeverQrcode('wechatqrcode', $id);
            if ($resCode) {
                $res = ['res' => $resCode, 'id' => $resCode['id']];
            } else {
                $res = false;
            }
            if (!$res) throw new AdminException('二维码生成失败');
            $imageInfo = $this->downloadImage($resCode['url'], $name);
            $systemAttachment->attachmentAdd($name, $imageInfo['size'], $imageInfo['type'], $imageInfo['att_dir'], $imageInfo['att_dir'], 1, $imageInfo['image_type'], time(), 2);
        }
        return strpos($imageInfo['att_dir'], 'http') === false ? $siteUrl . $imageInfo['att_dir'] : $imageInfo['att_dir'];
    }

    /**下载图片
     * @param $url
     * @param $name
     * @param $type
     * @param $timeout
     * @param $w
     * @param $h
     * @return array|string
     */
    public function downloadImage($url = '', $name = '', $type = 0, $timeout = 30, $w = 0, $h = 0)
    {
        if (!strlen(trim($url))) return '';
        //TODO 获取远程文件所采用的方法
        if ($type) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //TODO 跳过证书检查
            if (stripos($url, "https://") !== FALSE) curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);  //TODO 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('user-agent:' . $_SERVER['HTTP_USER_AGENT']));
            if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//TODO 是否采集301、302之后的页面
            $content = curl_exec($ch);
            curl_close($ch);
        } else {
            try {
                ob_start();
                readfile($url);
                $content = ob_get_contents();
                ob_end_clean();
            } catch (\Exception $e) {
                return $e->getMessage();
            }
        }
        $size = strlen(trim($content));
        if (!$content || $size <= 2) return '图片流获取失败';
        $upload_type = sys_config('upload_type', 1);
        $upload = UploadService::init();
        if ($upload->to('attach/spread/agent')->stream($content, $name) === false) {
            return $upload->getError();
        }
        $imageInfo = $upload->getUploadInfo();
        $data['att_dir'] = $imageInfo['dir'];
        $data['name'] = $imageInfo['name'];
        $data['size'] = $imageInfo['size'];
        $data['type'] = $imageInfo['type'];
        $data['image_type'] = $upload_type;
        $data['is_exists'] = false;
        return $data;
    }

    /**
     * 扫码完成后方法
     * @param $qrcodeInfo
     * @param $userInfo
     * @param $spreadInfo
     * @param int $isFollow
     * @return mixed
     */
    public function wechatQrcodeRecord($qrcodeInfo, $userInfo, $spreadInfo, $isFollow = 0)
    {
        $response = $this->transaction(function () use ($qrcodeInfo, $userInfo, $spreadInfo, $isFollow) {

            //绑定用户标签
            /** @var UserLabelRelationServices $labelServices */
            $labelServices = app()->make(UserLabelRelationServices::class);
            foreach ($qrcodeInfo['label_id'] as $item) {
                $labelArr[] = [
                    'uid' => $userInfo['uid'],
                    'label_id' => $item['id'] ?? $item
                ];
            }
            $labelServices->saveAll($labelArr);

            //增加二维码扫码数量
            $this->dao->upFollowAndScan($qrcodeInfo['id'], $isFollow);

            //写入扫码记录
            /** @var WechatQrcodeRecordServices $recordServices */
            $recordServices = app()->make(WechatQrcodeRecordServices::class);
            $data['qid'] = $qrcodeInfo['id'];
            $data['uid'] = $userInfo['uid'];
            $data['is_follow'] = $isFollow;
            $data['add_time'] = time();
            $recordServices->save($data);

            //回复信息内容
            return $this->replyDataByMessage($qrcodeInfo['type'], $qrcodeInfo['data']);
        });
        return $response;
    }

    /**
     * 发送扫码之后的信息
     * @param $type
     * @param $data
     * @return Image|News|Text|Voice|void
     */
    public function replyDataByMessage($type, $data)
    {
        if ($type == 'text') {
            return Messages::textMessage($data['content']);
        } else if ($type == 'image') {
            return Messages::imageMessage($data['media_id']);
        } else if ($type == 'news') {
            $title = $data['title'] ?? '';
            $image = $data['image'] ?? '';
            $description = $data['synopsis'] ?? '';
            $url = $data['url'] ?? '';
            return Messages::newsMessage($title, $description, $url, $image);
        } else if ($type == 'url') {
            $title = $data['content'];
            $image = sys_config('h5_avatar');
            $description = $data['content'];
            $url = $data['content'];
            return Messages::newsMessage($title, $description, $url, $image);
        } else if ($type == 'voice') {
            return Messages::voiceMessage($data['media_id']);
        }
    }
}
