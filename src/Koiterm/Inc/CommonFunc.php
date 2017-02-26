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

namespace Koiterm\Inc;
use Koiterm\Init\Core as C;

class CommonFunc
{

    /**
     * 读取缓存
     * @param $cachenames - 缓存名称数组或字串
     */
    function loadcache($cachenames, $force = false) {
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
    function is_wechat_browser(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false){
            return false;
        } else {
            return true;
        }
    }

    function getwxconfig(){
        return C::t('wechat_config')->fetch(1);
    }

    function getuserbyuid($uid) {
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
     *
     * @return type
     */
    function getglobal($key, $group = null) {
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
    function memory($cmd, $key='', $value='', $ttl = 0, $prefix = '') {
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

    function dstrpos($string, $arr, $returnvalue = false) {
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
     * 去掉slassh
     * @param $string
     * @return array|string
     */
    function dstripslashes($string) {
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
    public function dhtmlspecialchars($string, $flags = null) {
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

    function getgpc($k, $type='GP') {
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
    function setglobal($key , $value, $group = null) {
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


    function dheader($string, $replace = true, $http_response_code = 0) {
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

    function checkmobile() {
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

    public function checkrobot($useragent = '') {
        static $kw_spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
        static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

        $useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
        if(strpos($useragent, 'http://') === false && CommonFunc::dstrpos($useragent, $kw_browsers)) return false;
        if(CommonFunc::dstrpos($useragent, $kw_spiders)) return true;
        return false;
    }

    function formhash($specialadd = '') {
        global $_G;
        $hashadd = defined('IN_ADMINCP') ? 'Only For Ashu! Admin YanChao' : '';
        return substr(md5(substr($_G['timestamp'], 0, -7).$_G['username'].$_G['uid'].$_G['authkey'].$hashadd.$specialadd), 8, 8);
    }

}