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


class CommonFunc
{

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

    public function checkrobot($useragent = '') {
        static $kw_spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
        static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

        $useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
        if(strpos($useragent, 'http://') === false && CommonFunc::dstrpos($useragent, $kw_browsers)) return false;
        if(CommonFunc::dstrpos($useragent, $kw_spiders)) return true;
        return false;
    }

}