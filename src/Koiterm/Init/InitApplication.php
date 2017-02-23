<?php
/**
 *
 * PHP Version ～7.1
 * @package   InitApplication.php
 * @author    yanchao <yanchao563@yahoo.com>
 * @time      2017/02/23 15:02
 * @copyright 2017
 * @license   www.guanlunsm.com license
 * @link      yanchao563@yahoo.com
 */

namespace Koiterm\Init;
use Koiterm\Inc\CommonFunc;

class InitApplication
{
    var $var = array();
    var $superglobal = array(
        'GLOBALS' => 1,
        '_GET' => 1,
        '_POST' => 1,
        '_REQUEST' => 1,
        '_COOKIE' => 1,
        '_SERVER' => 1,
        '_ENV' => 1,
        '_FILES' => 1,
    );

    public function __construct()
    {
        $this->_initEnv();
        $this->_initInput();
        $this->_initOutput();
    }

    static function &instance() {
        static $object;
        if(empty($object)) {
            $object = new self();
        }
        return $object;
    }

    private function _getScriptUrl() {
        if(!isset($this->var['PHP_SELF'])){
            $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
            if(basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                $this->var['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
            } else if(basename($_SERVER['PHP_SELF']) === $scriptName) {
                $this->var['PHP_SELF'] = $_SERVER['PHP_SELF'];
            } else if(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->var['PHP_SELF'] = $_SERVER['ORIG_SCRIPT_NAME'];
            } else if(($pos = strpos($_SERVER['PHP_SELF'],'/'.$scriptName)) !== false) {
                $this->var['PHP_SELF'] = substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
            } else if(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->var['PHP_SELF'] = str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
                $this->var['PHP_SELF'][0] != '/' && $this->var['PHP_SELF'] = '/'.$this->var['PHP_SELF'];
            } else {
                exit('request_tainting');
            }
        }
        return $this->var['PHP_SELF'];
    }

    private function _getClientIp() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
            foreach ($matches[0] AS $xip) {
                if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                    $ip = $xip;
                    break;
                }
            }
        }
        return $ip;
    }

    public function timezone_set($timeoffset = 0) {
        if(function_exists('date_default_timezone_set')) {
            @date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
        }
    }

    private function _initEnv()
    {
        error_reporting(E_ERROR);
        if(PHP_VERSION < '5.3.0') {
            set_magic_quotes_runtime(0);
        }
        define('g_magicQuotesGpc_', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());
        define('g_iconvEnable_', function_exists('iconv'));
        define('g_mbEnable_', function_exists('mb_convert_encoding'));
        define('g_extObgzip_', function_exists('ob_gzhandler'));
        define('g_timestamp_', time());
        $this->timezone_set();

        define('g_isRobot_', CommonFunc::checkrobot());

        foreach ($GLOBALS as $key => $value) {
            if (!isset($this->superglobal[$key])) {
                $GLOBALS[$key] = null;
                unset($GLOBALS[$key]);
            }
        }
        global $_G;
        $_G = array(
            'uid' => 0,
            'username' => '',
            'adminid' => 0,
            'groupid' => 1,
            'sid' => '',
            'formhash' => '',
            'connectguest' => 0,
            'timestamp' => g_timestamp_,
            'starttime' => microtime(true),
            'clientip' => $this->_getClientIp(),
            'remoteport' => $_SERVER['REMOTE_PORT'],
            'referer' => '',
            'charset' => '',
            'gzipcompress' => '',
            'authkey' => '',
            'timenow' => array(),
            'widthauto' => 0,
            'disabledwidthauto' => 0,
            'PHP_SELF' => '',
            'siteurl' => '',
            'siteroot' => '',
            'siteport' => '',
            'config' => array(),
            'setting' => array(),
            'member' => array(),
            'group' => array(),
            'cookie' => array(),
            'style' => array(),
            'cache' => array(),
            'session' => array(),
            'lang' => array(),
            'mobile' => '',
            'mobiletpl' => array('1' => 'mobile', '2' => 'touch', '3' => 'wml', 'yes' => 'mobile'),
        );
        $_G['PHP_SELF'] = CommonFunc::dhtmlspecialchars($this->_getScriptUrl());
        $_G['basefilename'] = basename($_G['PHP_SELF']);
        $sitepath = substr($_G['PHP_SELF'], 0, strrpos($_G['PHP_SELF'], '/'));

        $_G['isHTTPS'] = ($_SERVER['SERVER_PORT'] == 443 || $_SERVER['HTTPS'] || strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
        $_G['siteurl'] = CommonFunc::dhtmlspecialchars('http'.($_G['isHTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$sitepath.'/');
        $url = parse_url($_G['siteurl']);
        $_G['siteroot'] = isset($url['path']) ? $url['path'] : '';
        $_G['siteport'] = empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ':'.$_SERVER['SERVER_PORT'];
        $this->var = & $_G;
    }

    private function _initInput()
    {
        if (isset($_GET['GLOBALS']) ||isset($_POST['GLOBALS']) ||  isset($_COOKIE['GLOBALS']) || isset($_FILES['GLOBALS'])) {
            exit('request_tainting');
        }

        if(g_magicQuotesGpc_) {
            $_GET = CommonFunc::dstripslashes($_GET);
            $_POST = CommonFunc::dstripslashes($_POST);
            $_COOKIE = CommonFunc::dstripslashes($_COOKIE);
        }

        if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
            $_GET = array_merge($_GET, $_POST);
        }

        if(isset($_GET['page'])) {
            $_GET['page'] = rawurlencode($_GET['page']);
        }

        if(!(!empty($_GET['handlekey']) && preg_match('/^\w+$/', $_GET['handlekey']))) {
            unset($_GET['handlekey']);
        }
    }
    private function _initOutput()
    {
        if(!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
            $this->config['output']['gzip'] = false;
        }
    }
}