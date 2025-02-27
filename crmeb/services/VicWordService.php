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


use PullWord\PullWord;

/**
 * 分词搜索类
 * Class VicWordService
 * @package crmeb\services
 */
class VicWordService
{

	/**
	 * 请求地址
	 * @var string
	 */
	protected $url = 'https://sms.crmeb.net/api/v2/open/keyword';

//	protected $url = 'http://test-api.crmeb.com/api/v2/open/keyword';


    private static $instance = null;


    public function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getWord($str)
	{
		try {
			$res = HttpService::getRequest($this->url ,['keyword' => $str]);
			$data = json_decode($res, true) ?: [];
			$data = array_column($data['data'] ?? [], 0);
		} catch (\Throwable $e) {
			$data = [];
		}
		//没有获取分词时，加上原始的词
		if (!count($data)) {
			$data[] = $str;
		}
		if ($data) {
			foreach ($data as $key => &$item) {
				$item = trim($item);
				if (!$item) unset($data[$key]);
			}
		}
		return array_merge($data);
	}

    public function getWordV1($str)
    {
        try {
            $pullWord = new PullWord($str);
            $result = $pullWord->pull()->toJson()->get();
            $result = json_decode($result, true);
            $data = is_array($result) ? array_column($result, 't') : [];
        } catch (\Throwable $e) {
            $data = [];
        }
        //没有获取分词时，加上原始的词
        if (!count($data)) {
            $data[] = $str;
        }
        return $data;
    }
}