<?php
/**
 *
 * PHP Version ～7.1
 * @package   syscahce.php
 * @author    yanchao <yanchao563@yahoo.com>
 * @time      2017/02/25 19:30
 * @copyright 2017
 * @license   www.guanlunsm.com license
 * @link      yanchao563@yahoo.com
 */

namespace Koiterm\Models;
use Koiterm\Orm\Table;
use Koiterm\Orm\Db as DB;
use Koiterm\inc\CommonFunc;

class syscache extends Table
{
    public function __construct() {
        $this->_table = 'syscache';
        $this->_pk    = 'cname';
        $this->_pre_cache_key = '';
        $this->_allowmem = CommonFunc::memory('check');
        parent::__construct();
    }

    public function fetch($cachename) {
        $data = $this->fetch_all(array($cachename));
        return isset($data[$cachename]) ? $data[$cachename] : false;
    }
    public function fetch_all($cachenames) {
        $data = array();
        $cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
        if($this->_allowmem) {
            $data = CommonFunc::memory('get', $cachenames);
            $newarray = $data !== false ? array_diff($cachenames, array_keys($data)) : $cachenames;
            if(empty($newarray)) {
                return $data;
            } else {
                $cachenames = $newarray;
            }
        }

        $query = DB::query('SELECT * FROM '.DB::table($this->_table).' WHERE '.DB::field('cname', $cachenames));
        while($syscache = DB::fetch($query)) {
            $data[$syscache['cname']] = $syscache['ctype'] ? unserialize($syscache['data']) : $syscache['data'];
            $this->_allowmem && (CommonFunc::memory('set', $syscache['cname'], $data[$syscache['cname']]));
        }

        foreach($cachenames as $name) {
            if($data[$name] === null) {
                $data[$name] = null;
                $this->_allowmem && (CommonFunc::memory('set', $name, array()));
            }
        }

        return $data;
    }

    public function insert($cachename, $data) {

        parent::insert(array(
            'cname' => $cachename,
            'ctype' => is_array($data) ? 1 : 0,
            'dateline' => TIMESTAMP,
            'data' => is_array($data) ? serialize($data) : $data,
        ), false, true);

        if($this->_allowmem && CommonFunc::memory('get', $cachename) !== false) {
            memory('set', $cachename, $data);
        }
    }

    public function update($cachename, $data) {
        $this->insert($cachename, $data);
    }

    public function delete($cachenames) {
        parent::delete($cachenames);
        if($this->_allowmem || $this->_isfilecache) {
            foreach((array)$cachenames as $cachename) {
                $this->_allowmem && CommonFunc::memory('rm', $cachename);
            }
        }
    }
}
