<?php

use app\Request;
use crmeb\services\SystemConfigService;
use crmeb\services\UploadService;
use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\ClickWordCaptchaService;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Log;

if (!function_exists('get_tree_value')) {
    /**
     * 获取
     * @param array $data
     * @param int|string $value
     * @return array
     */
    function get_tree_value(array $data, $value, array &$childrenValue = [])
    {
        foreach ($data as &$item) {
            if ($item['value'] == $value) {
                $childrenValue[] = $item['value'];
                if ($item['pid']) {
                    $value = $item['pid'];
                    unset($item);
                    return get_tree_value($data, $value, $childrenValue);
                }
            }
        }
        return $childrenValue;
    }
}

if (!function_exists('is_brokerage_statu')) {

    /**
     * 是否能成为推广人
     * @param float $price
     * @return bool
     */
    function is_brokerage_statu(float $price)
    {
        if (!sys_config('brokerage_func_status')) {
            return false;
        }
        $storeBrokerageStatus = sys_config('store_brokerage_statu', 1);
        if ($storeBrokerageStatus == 1) {
            return false;
        } else if ($storeBrokerageStatus == 2) {
            return false;
        } else {
            $storeBrokeragePrice = sys_config('store_brokerage_price', 0);
            return $price >= $storeBrokeragePrice;
        }
    }
}

if (!function_exists('time_tran')) {
    /**
     * 时间戳人性化转化
     * @param $time
     * @return string
     */
    function time_tran($time)
    {
        $t = time() - $time;
        $f = array(
            '31536000' => '年',
            '2592000' => '个月',
            '604800' => '星期',
            '86400' => '天',
            '3600' => '小时',
            '60' => '分钟',
            '1' => '秒'
        );
        foreach ($f as $k => $v) {
            if (0 != $c = floor($t / (int)$k)) {
                return $c . $v . '前';
            }
        }
    }
}

if (!function_exists('url_to_path')) {
    /**
     * url转换路径
     * @param $url
     * @return string
     */
    function url_to_path($url)
    {
        $path = trim(str_replace('/', DS, $url), DS);
        if (0 !== strripos($path, 'public'))
            $path = 'public' . DS . $path;
        return app()->getRootPath() . $path;
    }
}

if (!function_exists('path_to_url')) {
    /**
     * 路径转url路径
     * @param $path
     * @return string
     */
    function path_to_url($path)
    {
        return trim(str_replace(DS, '/', $path), '.');
    }
}

if (!function_exists('get_image_thumb')) {
    /**
     * 获取缩略图
     * @param $filePath
     * @param string $type all|big|mid|small
     * @param bool $is_remote_down
     * @return mixed|string|string[]
     */
    function get_image_thumb($filePath, string $type = 'all', bool $is_remote_down = false)
    {
        if (!sys_config('image_thumb_status')) return $filePath;
        if (!$filePath || !is_string($filePath) || strpos($filePath, '?') !== false) return $filePath;
        try {
            $upload = UploadService::getOssInit($filePath, $is_remote_down);
            $data = $upload->thumb('', $type);
            $image = $type == 'all' ? $data : $data[$type] ?? $filePath;
        } catch (\Throwable $e) {
            $image = $filePath;
            //            throw new ValidateException($e->getMessage());
            \think\facade\Log::error('获取缩略图失败，原因：' . $e->getMessage() . '----' . $e->getFile() . '----' . $e->getLine() . '----' . $filePath);
        }
        $data = parse_url($image);
        if (!isset($data['host']) && (substr($image, 0, 2) == './' || substr($image, 0, 1) == '/')) {//不是完整地址
            $image = sys_config('site_url') . $image;
        }
        //请求是https 图片是http 需要改变图片地址
        if (strpos(request()->domain(), 'https:') !== false && strpos($image, 'https:') === false) {
            $image = str_replace('http:', 'https:', $image);
        }
        return $image;
    }
}

if (!function_exists('get_thumb_water')) {
    /**
     * 处理数组获取缩略图、水印
     * @param $list
     * @param string $type
     * @param array|string[] $field 1、['image','images'] type 取值参数:type 2、['small'=>'image','mid'=>'images'] type 取field数组的key
     * @param bool $is_remote_down
     * @return array|mixed|string|string[]
     */
    function get_thumb_water($list, string $type = 'small', array $field = ['image'], bool $is_remote_down = false)
    {
        if (!$list || !$field) return $list;
        $baseType = $type;
        $data = $list;
        if (is_string($list)) {
            $field = [$type => 'image'];
            $data = ['image' => $list];
        }
        if (is_array($data)) {
            foreach ($field as $type => $key) {
                if (is_integer($type)) {//索引数组，默认type
                    $type = $baseType;
                }
                //一维数组
                if (isset($data[$key])) {
                    if (is_array($data[$key])) {
                        $path_data = [];
                        foreach ($data[$key] as $k => $path) {
                            $path_data[] = get_image_thumb($path, $type, $is_remote_down);
                        }
                        $data[$key] = $path_data;
                    } else {
                        $data[$key] = get_image_thumb($data[$key], $type, $is_remote_down);
                    }
                } else {
                    foreach ($data as &$item) {
                        if (!isset($item[$key]))
                            continue;
                        if (is_array($item[$key])) {
                            $path_data = [];
                            foreach ($item[$key] as $k => $path) {
                                $path_data[] = get_image_thumb($path, $type, $is_remote_down);
                            }
                            $item[$key] = $path_data;
                        } else {
                            $item[$key] = get_image_thumb($item[$key], $type, $is_remote_down);
                        }
                    }
                }
            }
        }
        return is_string($list) ? ($data['image'] ?? '') : $data;
    }
}
if (!function_exists('put_image')) {
    /**
     * 获取图片转为base64
     * @param $url
     * @param string $filename
     * @return bool|string
     */
    function put_image($url, string $filename = '')
    {

        if ($url == '') {
            return false;
        }
        try {
            if ($filename == '') {

                $ext = pathinfo($url);
                if ($ext['extension'] != "jpg" && $ext['extension'] != "png" && $ext['extension'] != "jpeg") {
                    return false;
                }
                $filename = time() . "." . $ext['extension'];
            }
            $pathArr = parse_url($url);
            $path = $pathArr['path'] ?? '';
            if ($path && file_exists(public_path() . trim($path, '/'))) {
                return $path;
            } else {
                //文件保存路径
                ob_start();
                $url = str_replace('phar://', '', $url);
                readfile($url);
                $img = ob_get_contents();
                ob_end_clean();
                $path = 'uploads/qrcode';
                $fp2 = fopen(public_path() . $path . '/' . $filename, 'a');
                fwrite($fp2, $img);
                fclose($fp2);
                return $path . '/' . $filename;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('make_path')) {

    /**
     * 上传路径转化,默认路径
     * @param $path
     * @param int $type
     * @param bool $force
     * @return string
     * @throws Exception
     */
    function make_path($path, int $type = 2, bool $force = false)
    {
        $path = DS . ltrim(rtrim($path));
        switch ($type) {
            case 1:
                $path .= DS . date('Y');
                break;
            case 2:
                $path .= DS . date('Y') . DS . date('m');
                break;
            case 3:
                $path .= DS . date('Y') . DS . date('m') . DS . date('d');
                break;
        }
        try {
            if (is_dir(app()->getRootPath() . 'public' . DS . 'uploads' . $path) == true || mkdir(app()->getRootPath() . 'public' . DS . 'uploads' . $path, 0777, true) == true) {
                return trim(str_replace(DS, '/', $path), '.');
            } else return '';
        } catch (\Exception $e) {
            if ($force)
                throw new \Exception($e->getMessage());
            return '无法创建文件夹，请检查您的上传目录权限：' . app()->getRootPath() . 'public' . DS . 'uploads' . DS . 'attach' . DS;
        }

    }
}

if (!function_exists('check_phone')) {
    /**
     * 手机号验证
     * @param $phone
     * @return false|int
     */
    function check_phone($phone)
    {
        return preg_match("/^1[3456789]\d{9}$/", $phone);
    }
}

if (!function_exists('check_mail')) {
    /**
     * 邮箱验证
     * @param $mail
     * @return false|int
     */
    function check_mail($mail)
    {
        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('aj_captcha_check_one')) {
    /**
     * 验证滑块1次验证
     * @param string $captchaType
     * @param string $token
     * @param string $pointJson
     * @return bool
     */
    function aj_captcha_check_one(string $captchaType, string $token, string $pointJson)
    {
        aj_get_serevice($captchaType)->check($token, $pointJson);
        return true;
    }
}

if (!function_exists('aj_captcha_check_two')) {
    /**
     * 验证滑块2次验证
     * @param string $captchaType
     * @param string $captchaVerification
     * @return bool
     */
    function aj_captcha_check_two(string $captchaType, string $captchaVerification)
    {
        aj_get_serevice($captchaType)->verificationByEncryptCode($captchaVerification);
        return true;
    }
}


if (!function_exists('aj_captcha_create')) {
    /**
     * 创建验证码
     * @return array
     */
    function aj_captcha_create(string $captchaType)
    {
        return aj_get_serevice($captchaType)->get();
    }
}

if (!function_exists('aj_get_serevice')) {

    /**
     * @param string $captchaType
     * @return ClickWordCaptchaService|BlockPuzzleCaptchaService
     */
    function aj_get_serevice(string $captchaType)
    {
        $config = Config::get('ajcaptcha');
        switch ($captchaType) {
            case "clickWord":
                $service = new ClickWordCaptchaService($config);
                break;
            case "blockPuzzle":
                $service = new BlockPuzzleCaptchaService($config);
                break;
            default:
                throw new ValidateException('captchaType参数不正确！');
        }
        return $service;
    }
}

if (!function_exists('mb_substr_str')) {

    /**
     * 截取制定长度,并使用填充
     * @param string $value
     * @param int $length
     * @param string $str
     * @return string
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2022/12/1
     */
    function mb_substr_str(string $value, int $length, string $str = '...', int $type = 0)
    {
        if (mb_strlen($value) > $length) {
            $value = mb_substr($value, 0, $length - mb_strlen($str)) . $str;
        }

        //等于1时去掉数组
        if ($type === 1) {
            $value = preg_replace('/[0-9]/', '', $value);
        }

        return $value;
    }
}

if (!function_exists('response_log_write')) {

    /**
     * 日志写入
     * @param mixed $data
     * @param string $logType
     * @param string $type
     * @param mixed $id
     * @author 等风来
     * @email 136327134@qq.com
     * @date 2023/10/8
     */
    function response_log_write(mixed $data, string $logType = \think\Log::ERROR, string $type = '', mixed $id = 0)
    {
        $request = app()->make(Request::class);

        try {

            if (!$type || !$id) {
                foreach ([
                             'adminId' => 'admin',
                             'kefuId' => 'kefu',
                             'uid' => 'user',
                             'supplierId' => 'supplier',
                             'cashierId' => 'cashier',
                             'storeId' => 'store',
                             'outId' => 'out',
                         ] as $value => $vv) {
                    if ($request->hasMacro($value)) {
                        $id = $request->{$value}();
                        $type = $vv;
                    }
                }
            }

            //日志内容
            $log = [
                $id,//管理员ID
                $type,
                $request->ip(),//客户ip
                ceil(msectime() - ($request->time(true) * 1000)),//耗时（毫秒）
                $request->method(true),//请求类型
                str_replace("/", "", $request->rootUrl()),//应用
                $request->baseUrl(),//路由
                json_encode($request->param(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),//请求参数
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),//报错数据
            ];

            Log::write(implode("|", $log), $logType);
        } catch (\Throwable $e) {

            $data = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'previous' => $e->getPrevious(),
            ];
            Log::error(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        }
    }
}

if (!function_exists('stringToIntArray')) {

    /**
     * 处理ids等并过滤参数
     * @param $string
     * @param string $separator
     * @return array
     */
    function stringToIntArray(string $string, string $separator = ',')
    {
        return !empty($string) ? array_unique(array_diff(array_map('intval', explode($separator, $string)), [0])) : [];
    }
}


if (!function_exists('image_to_base64')) {
    /**
     * 获取图片转为base64
     * @param string $avatar
     * @return bool|string
     */
    function image_to_base64($avatar = '', $timeout = 9)
    {
        try {
            if (file_exists($avatar)) {
                $app_img_file = $avatar; // 图片路径
                $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等
                $fp = fopen($app_img_file, "r"); // 图片是否可读权限
                $img_base64 = '';
                if ($fp) {
                    $filesize = filesize($app_img_file);
                    $content = fread($fp, $filesize);
                    $file_content = chunk_split(base64_encode($content)); // base64编码
                    switch ($img_info[2]) {           //判读图片类型
                        case 1:
                            $img_type = "gif";
                            break;
                        case 2:
                            $img_type = "jpg";
                            break;
                        case 3:
                            $img_type = "png";
                            break;
                    }
                    $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码
                }
                fclose($fp);
                return $img_base64;
            } else {
                $avatar = str_replace('https', 'http', $avatar);
                $url = parse_url($avatar);
                $path = $url['path'] ?? '';
                $urlPath = public_path() . $url['path'];
                //本地文件直接读取返回
                if (is_file($urlPath)) {
                    $imageType = pathinfo($path)['extension'] ?? 'jpeg';
                    return 'data:image/' . $imageType . ';base64,' . base64_encode(file_get_contents($urlPath));
                }
                $url = $url['host'];
                $header = [
                    'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
                    'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Accept-Encoding: gzip, deflate, br',
                    'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    'Host:' . $url
                ];
                $dir = pathinfo($url);
                $host = $dir['dirname'];
                $refer = $host . '/';
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_REFERER, $refer);
                curl_setopt($curl, CURLOPT_URL, $avatar);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                $data = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                if ($code == 200) {
                    return "data:image/jpeg;base64," . base64_encode($data);
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('supplier_config')) {

    /**
     * @param int $supplierId
     * @param string $name
     * @param null $default
     * @return array|string|null
     */
    function supplier_config(int $supplierId, string $name, $default = null)
    {
        if (empty($name)) {
            return $default;
        }
        /** @var SystemConfigService $configService */
        $configService = app('sysConfig');
        $configService->setSupplier($supplierId);
        $sysConfig = $configService->get($name);
        if (is_array($sysConfig)) {
            foreach ($sysConfig as &$item) {
                if (strpos($item, '/uploads/system/') !== false) {
                    $item = set_file_url($item);
                }
            }
        } else {
            if (strpos($sysConfig, '/uploads/system/') !== false) {
                $sysConfig = set_file_url($sysConfig);
            }
        }
        $config = is_array($sysConfig) ? $sysConfig : trim($sysConfig);
        if ($config === '' || $config === false) {
            return $default;
        } else {
            return $config;
        }
    }
}

if (!function_exists('getFileHeaders')) {

    /**
     * 获取文件大小头部信息
     * @param string $url
     * @param $isData
     * @return array
     */
    function getFileHeaders(string $url, $isData = true)
    {
        stream_context_set_default(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $header['size'] = 0;
        $header['type'] = 'image/jpeg';
        if (!$isData) {
            return $header;
        }
        try {
            $headerArray = get_headers(str_replace('\\', '/', $url), true);
            if (!isset($headerArray['Content-Length'])) {
                $header['size'] = 0;
            } else {
                if (is_array($headerArray['Content-Length']) && count($headerArray['Content-Length']) == 2) {
                    $header['size'] = $headerArray['Content-Length'][1];
                } else {
                    $header['size'] = $headerArray['Content-Length'] ?? 0;
                }
            }
            if (!isset($headerArray['Content-Type'])) {
                $header['type'] = 'image/jpeg';
            } else {
                if (is_array($headerArray['Content-Type']) && count($headerArray['Content-Type']) == 2) {
                    $header['type'] = $headerArray['Content-Type'][1];
                } else {
                    $header['type'] = $headerArray['Content-Type'] ?? 'image/jpeg';
                }
            }
        } catch (\Exception $e) {
        }
        return $header;
    }
}

if (!function_exists('formatFileSize')) {

    /**
     * 格式化文件大小
     * @param $size
     * @return mixed|string|null
     */
    function formatFileSize($size)
    {
        if (!$size) {
            return '0KB';
        }
        try {
            $toKb = 1024;
            $toMb = $toKb * 1024;
            $toGb = $toMb * 1024;
            if ($size >= $toGb) {
                return round($size / $toGb, 2) . 'GB';
            } elseif ($size >= $toMb) {
                return round($size / $toMb, 2) . 'MB';
            } elseif ($size >= $toKb) {
                return round($size / $toKb, 2) . 'KB';
            } else {
                return $size . 'B';
            }
        } catch (\Exception $e) {
            return '0KB';
        }
    }

}

if (!function_exists('secsToStr')) {

    /**
     * 时间戳转成 n天n时n分
     * @param int $secs
     * @return string
     */
    function secsToStr(int $secs): string
    {
        $r = '';
        if (!$secs) return $r;
        if ($secs >= 86400) {
            $days = floor($secs / 86400);
            $secs = $secs % 86400;
            $r = $days . '天';
        }
        if ($secs >= 3600) {
            $hours = floor($secs / 3600);
            $secs = $secs % 3600;
            $r .= $hours . '小时';
        }
        if ($secs >= 60) {
            $minutes = floor($secs / 60);
            $secs = $secs % 60;
            $r .= $minutes . '分钟';
        }
        if ($secs) {
            $r .= $secs . '秒';
        }
        return $r;
    }
}

    if (!function_exists('filter_str')) {
        /**
         * 过滤字符串敏感字符
         * @param $str
         * @return array|mixed|string|string[]|null
         * @throws Exception
         */
        function filter_str($str)
        {
            $param_filter_data = sys_config('param_filter_data');
            $param_filter_type = sys_config('param_filter_type', 3);
            $rules = preg_split('/\r\n|\r|\n/', base64_decode($param_filter_data));
            if ($param_filter_data) {
                switch ($param_filter_type) {
                    case 2://报错
                        foreach ($rules as $rule) {
                            if (preg_match($rule, $str)) {
                                var_dump($rule,$str);
                                throw new \Exception('您的参数存在非要请求,已被拦截');
                            }
                        }
                        break;
                    case 3://过滤
                        if (filter_var($str, FILTER_VALIDATE_URL)) {
                            $url = parse_url($str);
                            if (!isset($url['scheme'])) return $str;
                            $host = $url['scheme'] . '://' . $url['host'];
                            $str = $host . preg_replace($rules, '', str_replace($host, '', $str));
                        } else {
                            $str = preg_replace($rules, '', $str);
                        }
                        break;
                }
            }
            return $str;
        }
    }

if (!function_exists('msectime')) {
    /**
     * 毫秒时间戳
     *
     * @return float
     */
    function msectime()
    {
        [$mSec, $sec] = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($mSec) + floatval($sec)) * 1000);
    }
}
if (!function_exists('timeConverter')) {

    /**
     * 一小时内【*分钟前】
     * 1天内显示【*小时前】
     * 跨天显示【*天前】
     * 跨月显示【月份-日期】
     * 跨年显示【年份-月份-日期】
     * @param int $timestamp
     * @return string
     */
    function timeConverter(int $timestamp): string
    {
        $currentTime = time();
        $timeDifference = $currentTime - $timestamp;

        // 获取当前时间和时间戳对应的时间
        $currentYear = date('Y', $currentTime);
        $currentMonth = date('m', $currentTime);
        $currentDay = date('d', $currentTime);

        $timestampYear = date('Y', $timestamp);
        $timestampMonth = date('m', $timestamp);
        $timestampDay = date('d', $timestamp);

        // 刚刚
        if ($timeDifference < 60) {
            return "刚刚";
        }

        // 几分钟前
        if ($timeDifference < 3600) {
            $minutesAgo = floor($timeDifference / 60);
            return "{$minutesAgo}分钟前";
        }

        // 1天内：显示 *小时前
        if ($timeDifference < 86400 && $currentDay == $timestampDay) {
            $hoursAgo = floor($timeDifference / 3600);
            return "{$hoursAgo}小时前";
        }

        // 跨天但在同一个月内：显示 *天前
        if ($currentMonth == $timestampMonth && $currentYear == $timestampYear) {
            $daysAgo = floor($timeDifference / 86400);
            $daysAgo = $daysAgo > 0 ? $daysAgo : 1;
            return "{$daysAgo}天前";
        }

        // 跨月但在同一年内：显示 月份-日期
        if ($currentYear == $timestampYear) {
            return date('m-d', $timestamp);
        }

        // 跨年：显示 年份-月份-日期
        return date('Y-m-d', $timestamp);
    }
}

if (!function_exists('checkCoordinates')) {
    /**
     * 检测经纬度数据
     * @param $longitude
     * @param $latitude
     * @return bool
     */
    function checkCoordinates($longitude, $latitude)
    {
        if ($longitude) {
            $longitudePattern = '/^(-?\d{1,3}(?:\.\d+)?)$/'; // 经度，允许1到3位整数，后面跟着小数
            if (!preg_match($longitudePattern, $longitude)) {
                return false; // 经度格式不正确
            }
            // 检查经纬度是否在有效范围内
            if (($longitude < -180) || ($longitude > 180)) {
                return false; // 经度超出范围
            }
        }
        if ($latitude) {
            $latitudePattern = '/^[-+]?([0-8]?\d(\.\d+)?|90(\.0+)?)$/'; // 纬度，允许-90到90，包括小数部分
            if (!preg_match($latitudePattern, $latitude)) {
                return false; // 纬度格式不正确
            }
            if (($latitude < -90) || ($latitude > 90)) {
                return false; // 纬度超出范围
            }
        }
        // 如果所有检查都通过，则返回true
        return true;
    }
}
