<?php
/**
 *
 * PHP Version ～7.1
 * @package   CommonFunc.php
 * @author    yanchao <yanchao563@yahoo.com>
 * @time      2017/02/23 21:39
 * @copyright 2017
 * @license   www.guanlunsm.com license
 * @link      yanchao563@yahoo.com
 */
/**
 * 尽量不要在此文件中添加全局函数
 * 请在source/class/helper/目录下创建相应的静态函数集类文件
 * 类的静态方法可以在产品中所有地方使用，使用方法类似：helper_form::submitcheck()
 **/
namespace Koiterm\Inc;
use Koiterm\Base\KoError;
use Koiterm\Init\Core as C;

class CommonFunc
{

    /**
     * 读取缓存
     * @param $cachenames - 缓存名称数组或字串
     */
    public static function loadcache($cachenames, $force = false) {
        global $_G;
        static $loadedcache = array();
        $cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
        $caches = array();
        foreach ($cachenames as $k) {
            if(!isset($loadedcache[$k]) || $force) {
                $caches[] = $k;
                $loadedcache[$k] = true;
            }
        }
        if(!empty($caches)) {
            $cachedata = C::t('syscache')->fetch_all($caches);
            //公共读取的缓存区
            foreach($cachedata as $cname => $data) {
                if($cname == 'setting') {
                    $_G['setting'] = $data;
                } elseif($cname == 'usergroup_'.$_G['groupid']) {
                    $_G['cache'][$cname] = $_G['group'] = $data;
                } elseif($cname == 'style_default') {
                    $_G['cache'][$cname] = $_G['style'] = $data;
                } else {
                    $_G['cache'][$cname] = $data;
                }
            }
        }
        return true;
    }

    /* 检查是否是微信浏览器访问 */
    public static function is_wechat_browser(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false){
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取微信开发参数
     * @return mixed
     */
    public static function getwxconfig(){
        return C::t('wechat_config')->fetch(1);
    }

    /**
     * 获取用户基本信息
     * @param $uid
     * @return mixed
     */
    public static function getuserbyuid($uid) {
        static $users = array();
        if(empty($users[$uid])) {
            $users[$uid] = C::t('common_member')->fetch_by_openid($uid);
        }
        if(!isset($users[$uid]['self']) && $uid == getglobal('uid') && getglobal('uid')) {
            $users[$uid]['self'] = 1;
        }
        return $users[$uid];
    }

    /**
     * 获取全局变量 $_G 当中的某个数值
     * @example
     * $v = getglobal('test'); // $v = $_G['test']
     * $v = getglobal('test/hello/ok');  // $v = $_G['test']['hello']['ok']
     *
     * @global  $_G
     * @param string $key
     * @return type
     */
    public static function getglobal($key, $group = null) {
        global $_G;
        $key = explode('/', $group === null ? $key : $group.'/'.$key);
        $v = &$_G;
        foreach ($key as $k) {
            if (!isset($v[$k])) {
                return null;
            }
            $v = &$v[$k];
        }
        return $v;
    }

    /**
     * 内存读写接口函数
     * <code>
     * memory('get', 'keyname') === false;//缓存中没有这个keyname时结果为true
     * </code>
     *  * @param 命令 $cmd (set|get|rm|check|inc|dec)
     * @param 键值 $key
     * @param 数据 $value 当$cmd=get|rm时，$value即为$prefix；当$cmd=inc|dec时，$value为$step，默认为1
     * @param 有效期 $ttl
     * @param 键值的前缀 $prefix
     * @return mix
     *
     * @example set : 写入内存 $ret = memory('set', 'test', 'ok')
     * @example get : 读取内存 $data = memory('get', 'test')
     * @example rm : 删除内存  $ret = memory('rm', 'test')
     * @example check : 检查内存功能是否可用 $allow = memory('check')
     */
    public static function memory($cmd, $key='', $value='', $ttl = 0, $prefix = '') {
        if($cmd == 'check') {
            return  C::memory()->enable ? C::memory()->type : '';
        } elseif(C::memory()->enable && in_array($cmd, array('set', 'get', 'rm', 'inc', 'dec'))) {
            switch ($cmd) {
                case 'set': return C::memory()->set($key, $value, $ttl, $prefix); break;
                case 'get': return C::memory()->get($key, $value); break;
                case 'rm': return C::memory()->rm($key, $value); break;
                case 'inc': return C::memory()->inc($key, $value ? $value : 1); break;
                case 'dec': return C::memory()->dec($key, $value ? $value : -1); break;
            }
        }
        return null;
    }

    /**
     * 字符串方式实现 preg_match("/(s1|s2|s3)/", $string, $match)
     * @param string $string 源字符串
     * @param array $arr 要查找的字符串 如array('s1', 's2', 's3')
     * @param bool $returnvalue 是否返回找到的值
     * @return bool
     */

    public static function dstrpos($string, $arr, $returnvalue = false) {
        if(empty($string)) return false;
        foreach((array)$arr as $v) {
            if(strpos($string, $v) !== false) {
                $return = $returnvalue ? $v : true;
                return $return;
            }
        }
        return false;
    }

    /**
     * 检查邮箱是否有效
     * @param $email 要检查的邮箱
     * @param 返回结果
     */
    public static function isemail($email) {
        return strlen($email) > 6 && strlen($email) <= 32 && preg_match("/^([A-Za-z0-9\-_.+]+)@([A-Za-z0-9\-]+[.][A-Za-z0-9\-.]+)$/", $email);
    }

    /**
     * 产生随机码
     * @param $length - 要多长
     * @param $numberic - 数字还是字符串
     * @return 返回字符串
     */
    function random($length, $numeric = 0) {
        $seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
        $seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
        if($numeric) {
            $hash = '';
        } else {
            $hash = chr(rand(1, 26) + rand(0, 1) * 32 + 64);
            $length--;
        }
        $max = strlen($seed) - 1;
        for($i = 0; $i < $length; $i++) {
            $hash .= $seed{mt_rand(0, $max)};
        }
        return $hash;
    }

    /**
     * 判断一个字符串是否在另一个字符串中存在
     *
     * @param string 原始字串 $string
     * @param string 查找 $find
     * @return boolean
     */
    function strexists($string, $find) {
        return !(strpos($string, $find) === FALSE);
    }

    /**
    得到时间戳
     */
    function dmktime($date) {
        if(strpos($date, '-')) {
            $time = explode('-', $date);
            return mktime(0, 0, 0, $time[1], $time[2], $time[0]);
        }
        return 0;
    }

    /**
     * 针对uft-8进行特殊处理的strlen
     * @param string $str
     * @return int
     */
    function dstrlen($str) {
        if(strtolower(CHARSET) != 'utf-8') {
            return strlen($str);
        }
        $count = 0;
        for($i = 0; $i < strlen($str); $i++){
            $value = ord($str[$i]);
            if($value > 127) {
                $count++;
                if($value >= 192 && $value <= 223) $i++;
                elseif($value >= 224 && $value <= 239) $i = $i + 2;
                elseif($value >= 240 && $value <= 247) $i = $i + 3;
            }
            $count++;
        }
        return $count;
    }

    /**
     * 根据中文裁减字符串
     * @param $string - 字符串
     * @param $length - 长度
     * @param $doc - 缩略后缀
     * @return 返回带省略号被裁减好的字符串
     */
    public static function cutstr($string, $length, $dot = ' ...') {
        if(strlen($string) <= $length) {
            return $string;
        }

        $pre = chr(1);
        $end = chr(1);
        $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), $string);

        $strcut = '';
        if(strtolower(CHARSET) == 'utf-8') {

            $n = $tn = $noc = 0;
            while($n < strlen($string)) {

                $t = ord($string[$n]);
                if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1; $n++; $noc++;
                } elseif(194 <= $t && $t <= 223) {
                    $tn = 2; $n += 2; $noc += 2;
                } elseif(224 <= $t && $t <= 239) {
                    $tn = 3; $n += 3; $noc += 2;
                } elseif(240 <= $t && $t <= 247) {
                    $tn = 4; $n += 4; $noc += 2;
                } elseif(248 <= $t && $t <= 251) {
                    $tn = 5; $n += 5; $noc += 2;
                } elseif($t == 252 || $t == 253) {
                    $tn = 6; $n += 6; $noc += 2;
                } else {
                    $n++;
                }

                if($noc >= $length) {
                    break;
                }

            }
            if($noc > $length) {
                $n -= $tn;
            }

            $strcut = substr($string, 0, $n);

        } else {
            $_length = $length - 1;
            for($i = 0; $i < $length; $i++) {
                if(ord($string[$i]) <= 127) {
                    $strcut .= $string[$i];
                } else if($i < $_length) {
                    $strcut .= $string[$i].$string[++$i];
                }
            }
        }

        $strcut = str_replace(array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

        $pos = strrpos($strcut, chr(1));
        if($pos !== false) {
            $strcut = substr($strcut,0,$pos);
        }
        return $strcut.$dot;
    }

    /**
     * 分页
     * @param $num - 总数
     * @param $perpage - 每页数
     * @param $curpage - 当前页
     * @param $mpurl - 跳转的路径
     * @param $maxpages - 允许显示的最大页数
     * @param $page - 最多显示多少页码
     * @param $autogoto - 最后一页，自动跳转
     * @param $simple - 是否简洁模式（简洁模式不显示上一页、下一页和页码跳转）
     * @return 返回分页代码
     */
    public static function multi($num, $perpage, $curpage, $mpurl, $maxpages = 0, $page = 10, $autogoto = FALSE, $simple = FALSE, $jsfunc = FALSE) {
        return $num > $perpage ? \Koiterm\Base\HelperPage::multi($num, $perpage, $curpage, $mpurl, $maxpages, $page, $autogoto, $simple, $jsfunc) : '';
    }

    /**
     * 只有上一页下一页的分页（无需知道数据总数）
     * @param $num - 本次所取数据条数
     * @param $perpage - 每页数
     * @param $curpage - 当前页
     * @param $mpurl - 跳转的路径
     * @return 返回分页代码
     */
    public static function simplepage($num, $perpage, $curpage, $mpurl) {
        return \Koiterm\Base\HelperPage::simplepage($num, $perpage, $curpage, $mpurl);
    }

    /*
    * 递归创建目录
    */
    public static function dmkdir($dir, $mode = 0777, $makeindex = TRUE){
        if(!is_dir($dir)) {
            dmkdir(dirname($dir), $mode, $makeindex);
            @mkdir($dir, $mode);
            if(!empty($makeindex)) {
                @touch($dir.'/index.html'); @chmod($dir.'/index.html', 0777);
            }
        }
        return true;
    }

    /**
     * 刷新重定向
     */
    function dreferer($default = '') {
        global $_G;

        $default = empty($default) && $_ENV['curapp'] ? $_ENV['curapp'].'.php' : '';
        $_G['referer'] = !empty($_GET['referer']) ? $_GET['referer'] : $_SERVER['HTTP_REFERER'];
        $_G['referer'] = substr($_G['referer'], -1) == '?' ? substr($_G['referer'], 0, -1) : $_G['referer'];


        $reurl = parse_url($_G['referer']);

        if(!$reurl || (isset($reurl['scheme']) && !in_array(strtolower($reurl['scheme']), array('http', 'https')))) {
            $_G['referer'] = '';
        }

        if(!empty($reurl['host']) && !in_array($reurl['host'], array($_SERVER['HTTP_HOST'], 'www.'.$_SERVER['HTTP_HOST'])) && !in_array($_SERVER['HTTP_HOST'], array($reurl['host'], 'www.'.$reurl['host']))) {
            if(!in_array($reurl['host'], $_G['setting']['domain']['app']) && !isset($_G['setting']['domain']['list'][$reurl['host']])) {
                $domainroot = substr($reurl['host'], strpos($reurl['host'], '.')+1);
            }
        } elseif(empty($reurl['host'])) {
            $_G['referer'] = $_G['siteurl'].'./'.$_G['referer'];
        }

        $_G['referer'] = durlencode($_G['referer']);
        return $_G['referer'];
    }

    /**
     * url序列化
     * @param $url
     * @return mixed
     */
    public static function durlencode($url) {
        static $fix = array('%21', '%2A','%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        static $replacements = array('!', '*', ';', ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        return str_replace($fix, $replacements, urlencode($url));
    }


    /**
     * 重建数组
     * @param <string> $array 需要反转的数组
     * @return array 原数组与的反转后的数组
     */
    function renum($array) {
        $newnums = $nums = array();
        foreach ($array as $id => $num) {
            $newnums[$num][] = $id;
            $nums[$num] = $num;
        }
        return array($nums, $newnums);
    }

    /**
     * 字节格式化单位
     * @param $filesize - 大小(字节)
     * @return 返回格式化后的文本
     */
    function sizecount($size) {
        if($size >= 1073741824) {
            $size = round($size / 1073741824 * 100) / 100 . ' GB';
        } elseif($size >= 1048576) {
            $size = round($size / 1048576 * 100) / 100 . ' MB';
        } elseif($size >= 1024) {
            $size = round($size / 1024 * 100) / 100 . ' KB';
        } else {
            $size = intval($size) . ' Bytes';
        }
        return $size;
    }



    /**
     * 安全的 intval， 可以支持 int(10) unsigned
     * 支持最大整数 0xFFFFFFFF 4294967295
     * @param mixed $int string|int|array
     * @return mixed
     */
    function dintval($int, $allowarray = false) {
        $ret = intval($int);
        if($int == $ret || !$allowarray && is_array($int)) return $ret;
        if($allowarray && is_array($int)) {
            foreach($int as &$v) {
                $v = dintval($v, true);
            }
            return $int;
        } elseif($int <= 0xffffffff) {
            $l = strlen($int);
            $m = substr($int, 0, 1) == '-' ? 1 : 0;
            if(($l - $m) === strspn($int,'0987654321', $m)) {
                return $int;
            }
        }
        return $ret;
    }



    /**
     * 去掉slassh
     * @param $string
     * @return array|string
     */
    public static function dstripslashes($string) {
        if(empty($string)) return $string;
        if(is_array($string)) {
            foreach($string as $key => $val) {
                $string[$key] = dstripslashes($val);
            }
        } else {
            $string = stripslashes($string);
        }
        return $string;
    }

    /**
     * HTML转义字符
     * @param $string - 字符串
     * @param $flags 参见手册 htmlspecialchars
     * @return 返回转义好的字符串
     */
    public static function dhtmlspecialchars($string, $flags = null) {
        if(is_array($string)) {
            foreach($string as $key => $val) {
                $string[$key] = dhtmlspecialchars($val, $flags);
            }
        } else {
            if($flags === null) {
                $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
                if(strpos($string, '&amp;#') !== false) {
                    $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
                }
            } else {
                if(PHP_VERSION < '5.4.0') {
                    $string = htmlspecialchars($string, $flags);
                } else {

                    if(strtolower(CHARSET) == 'utf-8') {
                        $charset = 'UTF-8';
                    } else {
                        $charset = 'ISO-8859-1';
                    }
                    $string = htmlspecialchars($string, $flags, $charset);
                }
            }
        }
        return $string;
    }

    public static function getgpc($k, $type='GP') {
        $type = strtoupper($type);
        switch($type) {
            case 'G': $var = &$_GET; break;
            case 'P': $var = &$_POST; break;
            case 'C': $var = &$_COOKIE; break;
            default:
                if(isset($_GET[$k])) {
                    $var = &$_GET;
                } else {
                    $var = &$_POST;
                }
                break;
        }

        return isset($var[$k]) ? $var[$k] : NULL;

    }


    /**
     * 设置全局 $_G 中的变量
     * @global <array> $_G
     * @param <string> $key 键
     * @param <string> $value 值
     * @return true
     *
     * @example
     * setglobal('test', 1); // $_G['test'] = 1;
     * setglobal('config/test/abc') = 2; //$_G['config']['test']['abc'] = 2;
     *
     */
    public static function setglobal($key , $value, $group = null) {
        global $_G;
        $key = explode('/', $group === null ? $key : $group.'/'.$key);
        $p = &$_G;
        foreach ($key as $k) {
            if(!isset($p[$k]) || !is_array($p[$k])) {
                $p[$k] = array();
            }
            $p = &$p[$k];
        }
        $p = $value;
        return true;
    }

    /**
     * 设置cookie
     * @param $var - 变量名
     * @param $value - 变量值
     * @param $life - 生命期
     * @param $prefix - 前缀
     */
    public static function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false)
    {
        global $_G;

        $config = $_G['config']['cookie'];

        $_G['cookie'][$var] = $value;
        $var = ($prefix ? $config['cookiepre'] : '').$var;
        $_COOKIE[$var] = $value;

        if($value == '' || $life < 0) {
            $value = '';
            $life = -1;
        }

        if(defined('IN_MOBILE')) {
            $httponly = false;
        }

        $life = $life > 0 ? getglobal('timestamp') + $life : ($life < 0 ? getglobal('timestamp') - 31536000 : 0);
        $path = $httponly && PHP_VERSION < '5.2.0' ? $config['cookiepath'].'; HttpOnly' : $config['cookiepath'];

        $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
        if(PHP_VERSION < '5.2.0') {
            setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure);
        } else {
            setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure, $httponly);
        }
    }

    /**
     * 获取cookie
     */

    public static function getcookie($key) {
        global $_G;
        return isset($_G['cookie'][$key]) ? $_G['cookie'][$key] : '';
    }

    /**
     * 获取文件扩展名
     */
    public static function fileext($filename) {
        return addslashes(strtolower(substr(strrchr($filename, '.'), 1, 10)));
    }


    public static function dheader($string, $replace = true, $http_response_code = 0) {
        $islocation = substr(strtolower(trim($string)), 0, 8) == 'location';
        if(defined('IN_MOBILE') && strpos($string, 'mobile') === false && $islocation) {
            if (strpos($string, '?') === false) {
                $string = $string.'?mobile='.IN_MOBILE;
            } else {
                if(strpos($string, '#') === false) {
                    $string = $string.'&mobile='.IN_MOBILE;
                } else {
                    $str_arr = explode('#', $string);
                    $str_arr[0] = $str_arr[0].'&mobile='.IN_MOBILE;
                    $string = implode('#', $str_arr);
                }
            }
        }
        $string = str_replace(array("\r", "\n"), array('', ''), $string);
        if(empty($http_response_code) || PHP_VERSION < '4.3' ) {
            @header($string, $replace);
        } else {
            @header($string, $replace, $http_response_code);
        }
        if($islocation) {
            exit();
        }
    }

    /**
     * 检测移动设备
     * @return bool|string
     */
    public static function checkmobile() {
        global $_G;
        $mobile = array();
        static $touchbrowser_list =array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
            'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
            'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
            'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
            'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
            'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
            'benq', 'haier', '^lct', '320x320', '240x320', '176x220', 'windows phone');
        static $wmlbrowser_list = array('cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'alcatel', 'ericsson', 'bird', 'daxian', 'dbtel', 'eastcom',
            'pantech', 'dopod', 'philips', 'haier', 'konka', 'kejian', 'lenovo', 'benq', 'mot', 'soutec', 'nokia', 'sagem', 'sgh',
            'sed', 'capitel', 'panasonic', 'sonyericsson', 'sharp', 'amoi', 'panda', 'zte');

        static $pad_list = array('ipad');

        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if(CommonFunc::dstrpos($useragent, $pad_list)) {
            return false;
        }
        if(($v = CommonFunc::dstrpos($useragent, $touchbrowser_list, true))){
            $_G['mobile'] = $v;
            return '2';
        }
        if(($v = CommonFunc::dstrpos($useragent, $wmlbrowser_list))) {
            $_G['mobile'] = $v;
            return '3'; //wml版
        }
        $brower = array('mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop');
        if(CommonFunc::dstrpos($useragent, $brower)) return false;

        $_G['mobile'] = 'unknown';
        if(isset($_G['mobiletpl'][$_GET['mobile']])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 蜘蛛封禁
     * @param string $useragent
     * @return bool
     */
    public static function checkrobot($useragent = '') {
        static $kw_spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
        static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

        $useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
        if(strpos($useragent, 'http://') === false && CommonFunc::dstrpos($useragent, $kw_browsers)) return false;
        if(CommonFunc::dstrpos($useragent, $kw_spiders)) return true;
        return false;
    }

    /**
     * 表单hash生成
     * @param string $specialadd
     * @return mixed
     */
    public static function formhash($specialadd = '') {
        global $_G;
        $hashadd = defined('IN_ADMINCP') ? 'Only For Ashu! Admin YanChao' : '';
        return substr(md5(substr($_G['timestamp'], 0, -7).$_G['username'].$_G['uid'].$_G['authkey'].$hashadd.$specialadd), 8, 8);
    }

    /**
     * 格式化时间
     * @param $timestamp - 时间戳
     * @param $format - dt=日期时间 d=日期 t=时间 u=个性化 其他=自定义
     * @param $timeoffset - 时区
     * @return string
     */
    public static function dgmdate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = '') {
        global $_G;
        $_G['setting']['dateconvert'] = "1";
        $format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
        static $dformat, $tformat, $dtformat, $offset, $lang;
        if($dformat === null) {
            $dformat = "Y-n-j";
            $tformat = 'H:i';
            $dtformat = $dformat.' '.$tformat;
            $offset = "8";
            $sysoffset = "8";
            $offset = $offset == 9999 ? ($sysoffset ? $sysoffset : 0) : $offset;
            $lang = array(
                'before' => '前',
                'day' => '天',
                'yday' => '昨天',
                'byday' => '前天',
                'hour' => '小时',
                'half' => '半',
                'min' => '分钟',
                'sec' => '秒',
                'now' => '刚刚',
            );
        }
        $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
        $timestamp += $timeoffset * 3600;
        $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
        if($format == 'u') {
            $todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
            $s = gmdate(!$uformat ? $dtformat : $uformat, $timestamp);
            $time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
            if($timestamp >= $todaytimestamp) {
                if($time > 3600) {
                    $return = intval($time / 3600).'&nbsp;'.$lang['hour'].$lang['before'];
                } elseif($time > 1800) {
                    $return = $lang['half'].$lang['hour'].$lang['before'];
                } elseif($time > 60) {
                    $return = intval($time / 60).'&nbsp;'.$lang['min'].$lang['before'];
                } elseif($time > 0) {
                    $return = $time.'&nbsp;'.$lang['sec'].$lang['before'];
                } elseif($time == 0) {
                    $return = $lang['now'];
                } else {
                    $return = $s;
                }
                if($time >=0 && !defined('IN_MOBILE')) {
                    $return = '<span title="'.$s.'">'.$return.'</span>';
                }
            } elseif(($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
                if($days == 0) {
                    $return = $lang['yday'].'&nbsp;'.gmdate($tformat, $timestamp);
                } elseif($days == 1) {
                    $return = $lang['byday'].'&nbsp;'.gmdate($tformat, $timestamp);
                } else {
                    $return = ($days + 1).'&nbsp;'.$lang['day'].$lang['before'];
                }
                if(!defined('IN_MOBILE')) {
                    $return = '<span title="'.$s.'">'.$return.'</span>';
                }
            } else {
                $return = $s;
            }
            return $return;
        } else {
            return gmdate($format, $timestamp);
        }
    }
}