<?php
namespace Koiterm\Models;

use Koiterm\Orm\Table;
use Koiterm\Orm\Db as DB;
use Koiterm\inc\CommonFunc;

class wechat_config extends table{

    public function __construct() {
        $this->_table = 'wechat_config';
        $this->_pk    = 'id';
        $this->_pre_cache_key = 'wxch_config_';

        parent::__construct();
    }

    public function getconfig($id=1){
        $config = DB::fetch_first('SELECT * FROM %t WHERE id=%s', array($this->_table, $id));
        return $config;
    }

    public function update_accesstoken($token, $id = 1) {
        return DB::update($this->_table,array('access_token'=>$token,'dateline'=>time()),array('id'=>1));
    }

}