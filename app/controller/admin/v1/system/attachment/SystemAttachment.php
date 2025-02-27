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
namespace app\controller\admin\v1\system\attachment;

use app\controller\admin\AuthController;
use app\services\other\CacheServices;
use app\services\system\admin\SystemAdminServices;
use app\services\system\attachment\SystemAttachmentServices;
use think\annotation\Inject;

/**
 * 图片管理类
 * Class SystemAttachment
 * @package app\controller\admin\v1\file
 */
class SystemAttachment extends AuthController
{
    /**
     * @var SystemAttachmentServices
     */
    #[Inject]
    protected SystemAttachmentServices $service;

    /**
     * 显示列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['pid', 0],
            ['file_type', 1],
            ['name', '', '', 'real_name']
        ]);
        $where['type'] = 1;
        return $this->success($this->service->getImageList($where));
    }

    /**
     * 删除指定资源
     *
     * @param string $ids
     * @return \think\Response
     */
    public function delete()
    {
        [$ids] = $this->request->postMore([
            ['ids', '']
        ], true);
        $this->service->del($ids);
        return $this->success('删除成功');
    }

    /**
     * 图片上传
     * @param int $upload_type
     * @param int $type
     * @return mixed
     */
    public function upload($upload_type = 0, $type = 0)
    {
        [$pid, $file] = $this->request->postMore([
            ['pid', 0],
            ['file', 'file'],
        ], true);
        $res = $this->service->upload((int)$pid, $file, (int)$upload_type, (int)$type);
        return $this->success('上传成功', $res);
    }

    /**
     * 移动图片
     * @return mixed
     */
    public function moveImageCate()
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['images', '']
        ]);
        $this->service->move($data);
        return $this->success('移动成功');
    }

    /**
     * 修改文件名
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $realName = $this->request->post('real_name', '');
        if (!$realName) {
            return $this->fail('文件名称不能为空');
        }
        $this->service->update($id, ['real_name' => $realName]);
        return $this->success('修改成功');
    }

    /**
     * 获取上传类型
     * @return mixed
     */
    public function uploadType()
    {
        $data['upload_type'] = (string)sys_config('upload_type', 1);
        return app('json')->success($data);
    }

    /**
     * 视频分片上传
     * @return mixed
     */
    public function videoUpload()
    {
        $data = $this->request->postMore([
            ['chunkNumber', 0],//第几分片
            ['currentChunkSize', 0],//分片大小
            ['chunkSize', 0],//总大小
            ['totalChunks', 0],//分片总数
            ['file', 'file'],//文件
            ['md5', ''],//MD5
            ['filename', ''],//文件名称
            ['pid', 0],//分类ID
        ]);
        $fileHandle = $this->request->file($data['file']);
        if (!$fileHandle) return $this->fail('上传信息为空');
        $res = $this->service->videoUpload($data, $fileHandle);
        return app('json')->success($res);
    }

    /**
     * 保存云端视频记录
     * @return mixed
     */
    public function saveVideoAttachment()
    {
        $data = $this->request->postMore([
            ['path', ''],//视频地址
            ['cover_image', ''],//封面地址
            ['pid', 0],//分类ID
            ['upload_type', 1],//上传类型
        ]);
        $res = $this->service->saveOssVideoAttachment($data, 1, 0, (int)$data['upload_type']);
        return app('json')->success($res);
    }

    /**
     * 获取扫码上传页面链接以及参数
     * @return \think\Response
     */
    public function scanUploadQrcode(CacheServices $services)
    {
        [$pid] = $this->request->getMore([
            ['pid', 0]
        ], true);
        $uploadToken = md5(time());
        $upload_file_size_max = config('upload.filesize');//文件上传大小kb
        $services->setDbCache('scan_upload', $uploadToken, 68400);
        $url = sys_config('site_url') . '/app/upload?pid=' . $pid . '&cache=scan_upload&type=1&relation_id=0&upload_file_size_max=' . $upload_file_size_max . '&token=' . $uploadToken;
        return app('json')->success(['url' => $url]);
    }

    /**删除二维码
     * @return \think\Response
     */
    public function removeUploadQrcode(CacheServices $services)
    {
        $services->delectDbCache('scan_upload');
        return app('json')->success();
    }

    /**获取扫码上传的图片数据
     * @param $scan_token
     * @return \think\Response
     */
    public function scanUploadImage($scan_token)
    {
        return app('json')->success($this->service->scanUploadImage($scan_token));
    }

    /**网络图片上传
     * @return \think\Response
     */
    public function onlineUpload()
    {
        $data = $this->request->postMore([
            ['pid', 0],
            ['images', []]
        ]);
        $this->service->onlineUpload($data);
        return app('json')->success('上传完成');
    }

    /**获取上传信息
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAdminsData()
    {
        /** @var SystemAdminServices $adminServices */
        $adminServices = app()->make(SystemAdminServices::class);
        $data['is_way'] = $adminServices->value(['id' => $this->adminId], 'is_way');
        $data['upload_file_size_max'] = config('upload.filesize');//文件上传大小kb
        return app('json')->success($data);
    }

    /**保存上传信息
     * @param int $is_way
     * @return \think\Response
     */
    public function setAdminsData(int $is_way = 0)
    {
        /** @var SystemAdminServices $adminServices */
        $adminServices = app()->make(SystemAdminServices::class);
        if (!in_array($is_way, [0, 1, 2])) return app('json')->fail('参数有误！');
        $res = $adminServices->update($this->adminId, ['is_way' => $is_way]);
        if ($res) return app('json')->success('ok');
        else return app('json')->fail('保存失败！');
    }
}
