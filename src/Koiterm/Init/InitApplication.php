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
use Koiterm\Config\GlobalCfg;
use Koiterm\Orm\Db as DB;
use Koiterm\Base\WeixinBaseApi as wx_base;


class InitApplication
{
    var $var = array();
    var $config =array();
    var $cachelist = array();
    var $init_db = true;
    var $init_setting = true;
    var $init_user = true;
    var $init_session = true;
    var $init_cron = true;
    var $init_misc = true;
    var $init_mobile = true;
    var $init_wx = true;

    var $initated = false;

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
    public function __construct($cfg = '')
    {
        error_reporting(E_ALL || ~E_NOTICE);
        $this->_initEnv();
        $this->_initConfig();
        $this->_initInput();
        $this->_initOutput();
    }
    public function init() {
        if(!$this->initated) {
            $this->_initDb();
            $this->_initSetting();
            $this->_initWeixin();
            $this->_initMobile();
            $this->_initMisc();
        }
        $this->initated = true;
    }

    private function _initWeixin() {
        $openid = null;
        if($this->init_wx){
            $openid = wx_base::get_userid();
            if(isset($_SESSION['wx_info']) || isset($_COOKIE['wx_info'])){
                $wx_info = unserialize($_SESSION['wx_info'] ? $_SESSION['wx_info'] : $_COOKIE['wx_info']);
            }else if(isset($openid)){
                $wx_info = wx_base::get_user_info($openid);
                $s = serialize($wx_info);
                setcookie("wx_info", $s, time()+360000000);
                $_SESSION['wx_info'] = $s;
                CommonFunc::setglobal('wx_info', $wx_info);
            }else{
                return NULL;
            }
        }
    }


    private function _xssCheck() {

        static $check = array('"', '>', '<', '\'', '(', ')', 'CONTENT-TRANSFER-ENCODING');

        if(isset($_GET['formhash']) && $_GET['formhash'] !== formhash()) {
            exit('request_tainting');
        }

        if($_SERVER['REQUEST_METHOD'] == 'GET' ) {
            $temp = $_SERVER['REQUEST_URI'];
        } elseif(empty ($_GET['formhash'])) {
            $temp = $_SERVER['REQUEST_URI'].file_get_contents('php://input');
        } else {
            $temp = '';
        }

        if(!empty($temp)) {
            $temp = strtoupper(urldecode(urldecode($temp)));
            foreach ($check as $str) {
                if(strpos($temp, $str) !== false) {
                    exit('request_tainting');
                }
            }
        }

        return true;
    }

    private function _initMisc() {
        if($this->config['security']['urlxssdefend'] && !defined('DISABLEXSSCHECK')) {
            $this->_xssCheck();
        }
        if(!$this->init_misc) {
            return false;
        }
        $this->var['formhash'] = CommonFunc::formhash();
        define('FORMHASH', $this->var['formhash']);
    }

    private function _initMobile() {
        if(!$this->init_mobile) {
            return false;
        }

        if(!$this->var['setting'] || !$this->var['setting']['mobile']['allowmobile'] || !is_array($this->var['setting']['mobile']) || IS_ROBOT) {
            $nomobile = true;
            $unallowmobile = true;
        }

        $mobile = CommonFunc::getgpc('mobile');
        $mobileflag = isset($this->var['mobiletpl'][$mobile]);
        if($mobile === 'no') {
            dsetcookie('mobile', 'no', 3600);
            $nomobile = true;
        } elseif($this->var['cookie']['mobile'] == 'no' && $mobileflag) {
            checkmobile();
            dsetcookie('mobile', '');
        } elseif($this->var['cookie']['mobile'] == 'no') {
            $nomobile = true;
        } elseif(!($mobile_ = CommonFunc::checkmobile())) {
            $nomobile = true;
        }
        if(!$mobile || $mobile == 'yes') {
            $mobile = isset($mobile_) ? $mobile_ : 2;
        }

        if($nomobile || (!$this->var['setting']['mobile']['mobileforward'] && !$mobileflag)) {
            if($_SERVER['HTTP_HOST'] == $this->var['setting']['domain']['app']['mobile'] && $this->var['setting']['domain']['app']['default']) {
                CommonFunc::dheader("Location:http://".$this->var['setting']['domain']['app']['default'].$_SERVER['REQUEST_URI']);
                return false;
            } else {
                return false;
            }
        }
        if(strpos($this->var['setting']['domain']['defaultindex'],  curscript) !== false && curscript != 'blog' && !$_GET['mod']) {
            if($this->var['setting']['domain']['app']['mobile']) {
                $mobileurl = 'http://'.$this->var['setting']['domain']['app']['mobile'];
            } else {
                if($this->var['setting']['domain']['app']['forum']) {
                    $mobileurl = 'http://'.$this->var['setting']['domain']['app']['forum'].'?mobile=yes';
                } else {
                    $mobileurl = $this->var['siteurl'].'forum.php?mobile=yes';
                }
            }
            CommonFunc::dheader("location:$mobileurl");
        }
        if($mobile === '3' && empty($this->var['setting']['mobile']['wml'])) {
            return false;
        }
        define('IN_MOBILE', isset($this->var['mobiletpl'][$mobile]) ? $mobile : '2');
        CommonFunc::setglobal('gzipcompress', 0);

        $arr = array();
        foreach(array_keys($this->var['mobiletpl']) as $mobiletype) {
            $arr[] = '&mobile='.$mobiletype;
            $arr[] = 'mobile='.$mobiletype;
        }
        $arr = array_merge(array(strstr($_SERVER['QUERY_STRING'], '&simpletype'), strstr($_SERVER['QUERY_STRING'], 'simpletype')), $arr);
        $query_sting_tmp = str_replace($arr, '', $_SERVER['QUERY_STRING']);
        $this->var['setting']['mobile']['nomobileurl'] = ($this->var['setting']['domain']['app']['forum'] ? 'http://'.$this->var['setting']['domain']['app']['forum'].'/' : $this->var['siteurl']).$this->var['basefilename'].($query_sting_tmp ? '?'.$query_sting_tmp.'&' : '?').'mobile=no';

        $this->var['setting']['lazyload'] = 0;

        if('utf-8' != CHARSET) {
            if(strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
                foreach($_POST AS $pk => $pv) {
                    if(!is_numeric($pv)) {
                        $_GET[$pk] = $_POST[$pk] = $this->mobile_iconv_recurrence($pv);
                        if(!empty($this->var['config']['input']['compatible'])) {
                            $this->var['gp_'.$pk] = daddslashes($_GET[$pk]);
                        }
                    }
                }
            }
        }


        if(!$this->var['setting']['mobile']['mobilesimpletype']) {
            $this->var['setting']['imagemaxwidth'] = 224;
        }

        $this->var['setting']['regstatus'] = $this->var['setting']['mobile']['mobileregister'] ? $this->var['setting']['regstatus'] : 0 ;

        $this->var['setting']['thumbquality'] = 50;
        $this->var['setting']['avatarmethod'] = 0;

        $this->var['setting']['mobile']['simpletypeurl'] = array();
        $this->var['setting']['mobile']['simpletypeurl'][0] = $this->var['siteurl'].$this->var['basefilename'].($query_sting_tmp ? '?'.$query_sting_tmp.'&' : '?').'mobile=1&simpletype=no';
        $this->var['setting']['mobile']['simpletypeurl'][1] =  $this->var['siteurl'].$this->var['basefilename'].($query_sting_tmp ? '?'.$query_sting_tmp.'&' : '?').'mobile=1&simpletype=yes';
        $this->var['setting']['mobile']['simpletypeurl'][2] =  $this->var['siteurl'].$this->var['basefilename'].($query_sting_tmp ? '?'.$query_sting_tmp.'&' : '?').'mobile=2';
        unset($query_sting_tmp);
        ob_start();
    }

    private function _initDb() {
        if($this->init_db) {
            $driver = function_exists('mysql_connect') ? 'DriverMysql' : 'DriverMysqli';
            DB::init($driver, $this->config['db']);
        }
    }

    private function _initSetting() {
        if($this->init_setting && $this->init_db) {
            if(empty($this->var['setting'])) {
                $this->cachelist[] = 'setting';
            }

            if(empty($this->var['style'])) {
                $this->cachelist[] = 'style_default';
            }

            if(!isset($this->var['cache']['cronnextrun'])) {
                $this->cachelist[] = 'cronnextrun';
            }
        }

        !empty($this->cachelist) && CommonFunc::loadcache($this->cachelist);

        if(!is_array($this->var['setting'])) {
            $this->var['setting'] = array();
        }
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
    private function _initConfig() {
        $_config = GlobalCfg::getCfg();
        if(empty($_config)) {
            exit('config_notfound');
        }
        if(empty($_config['security']['authkey'])) {
            $_config['security']['authkey'] = md5($_config['cookie']['cookiepre'].$_config['db'][1]['dbname']);
        }
        $this->config = & $_config;
        $this->var['config'] = & $_config;
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
        if(empty($_SERVER['HTTPS'])){
            $_SERVER['HTTPS'] = 'off';
        }
        $_G['isHTTPS'] = ($_SERVER['SERVER_PORT'] == 443 || $_SERVER['HTTPS'] || strtolower($_SERVER['HTTPS']) != 'off') ? true : false;
        $_G['siteurl'] = CommonFunc::dhtmlspecialchars('http'.($_G['isHTTPS'] ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$sitepath.'/');
        $url = parse_url($_G['siteurl']);
        $_G['siteroot'] = isset($url['path']) ? $url['path'] : '';
        $_G['siteport'] = empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ':'.$_SERVER['SERVER_PORT'];
        $this->var = &$_G;
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