<?php
namespace Koiterm\Base;
use Koiterm\Inc\CommonFunc;

class WeixinBaseApi{
    private $table;
    public  static $openid;

    //取得微网站用户的ID
    public function get_userid()
    {
        $is_id = false;
        if (CommonFunc::is_wechat_browser()) {
            //先读取session中保存的openid
            if (!empty($this->var['session']['openid'])) {
                self::$openid = trim($this->var['session']['openid']);
            } elseif (!empty($_REQUEST['openid'])) {
                self::$openid = trim($_REQUEST['openid']);
            } elseif (!empty($_SESSION['wechat_id'])) {
                self::$openid = trim($_SESSION['wechat_id']);
                $is_id = 1;
            } elseif (!empty($_COOKIE['wechat_id'])){
                self::$openid = trim($_COOKIE['wechat_id']);
                $is_id = 1;
            } else {
                $code = self::get_oauth2_code();
                if (!$code) {
                    return false;
                }
                self::$openid = self::get_oauth2_openid($code);
            }
            if (!empty(self::$openid)) {
                //存储为session
                if(empty($is_id)){
                    $_SESSION['wechat_id'] = self::$openid;
                    setcookie("wechat_id", self::$openid, time()+360000000);
                }else{
                    unset($is_id);
                }
                return self::$openid;
            } else {
                return false;
            }
        } else {
            return ;
        }
        return NULL;
    }


    //取得网页用户授权接口中code参数
    public static function get_oauth2_code()
    {
        if(empty($_GET['code'])){
            $row = CommonFunc::getwxconfig();
            $redirect_uri = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $para = array(
                "appid"         => $row['appid'],
                "redirect_uri"  => $redirect_uri,
                "response_type" => 'code',
                "scope"         => 'snsapi_base',
                "state"         => '123#wechat_redirect'
            );
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $para['appid'] ."&redirect_uri=" . $para['redirect_uri'] ."&response_type=" . $para['response_type'] ."&scope=" . $para['scope'] ."&state=" . $para['state'];
            self::url_redirect($url);
        } else{
            return !empty($_GET['code']) ? $_GET['code'] : '';
        }
    }

    // URL重定向
    public static function url_redirect($url,$time=0,$msg='')
    {
        //多行URL地址支持
        $url = str_replace(array("\n", "\r"), '', $url);
        if(empty($msg))
            $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
        if (!headers_sent()) {
            if(0===$time) {
                header("Location: ".$url);
            }else {
                header("refresh:{$time};url={$url}");
                echo($msg);
            }
            exit();
        }else {
            $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
            if($time!=0)
                $str .= $msg;
            exit($str);
        }
    }

    //取得网页用户授权接口中openid
    public static function get_oauth2_openid($code){
        $row = getwxconfig();
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$row['appid']."&secret=".$row['appsecret']."&code=".$code."&grant_type=authorization_code";
        $rets =  self::curl_get_contents($url);
        $ret_arr = json_decode($rets,true);
        if(!empty($ret_arr['openid'])){
            return 	$ret_arr['openid'];
        } else{
            header("Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $row['appid'] ."&redirect_uri=" . $row['redirect'] ."&response_type=code&scope=snsapi_base&state=" . STATE . "#wechat_redirect");
            exit();
        }
    }

    public static function get_user_info(&$wechatid){
        if(!empty($wechatid)) {
            self::access_token();
            $ret = CommonFunc::getwxconfig(1);
            $access_token = $ret['access_token'];
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$wechatid";
            $res_json = self::curl_get_contents($url);
            $w_user = json_decode($res_json,TRUE);
            if($w_user['errcode'] == '40001')
            {
                $access_token = self::new_access_token();
                $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$wechatid";
                $res_json = self::curl_get_contents($url);
                $w_user = json_decode($res_json,TRUE);
            }
            return $w_user;
        }
    }



    public static function access_token()
    {
        $ret = getwxconfig(1);
        $appid = $ret['appid'];
        $appsecret = $ret['appsecret'];
        $access_token = $ret['access_token'];
        $dateline = $ret['dateline'];
        $time = time();
        if(($time - $dateline) >= 7200-20)
        {
            $access_token = self::new_access_token();
        }else if(empty($access_token))
        {
            $access_token = self::new_access_token();
        }
        return $access_token;
    }

   public static function new_access_token()
    {
        $ret = getwxconfig(1);
        $appid = $ret['appid'];
        $appsecret = $ret['appsecret'];
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $ret_json = self::curl_get_contents($url);
        $ret = json_decode($ret_json);
        if($ret->access_token)
        {
            C::t('wxch_config')->update_accesstoken($ret->access_token);
        }
        return $ret->access_token;
    }

    public static function curl_get_contents($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.9; rv:26.0) Gecko/20100101 Firefox/26.0");
        curl_setopt($ch, CURLOPT_REFERER,$url);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }


    public static function curl_grab_page($url,$data,$proxy='',$proxystatus='',$ref_url='')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($proxystatus == 'true')
        {
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        if(!empty($ref_url))
        {
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLOPT_REFERER, $ref_url);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 200);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        ob_start();
        return curl_exec ($ch);
        ob_end_clean();
        curl_close ($ch);
        unset($ch);
    }
}
