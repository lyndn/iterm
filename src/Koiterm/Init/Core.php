<?php
/**
 *
 * PHP Version ～7.1
 * @package   Core.class.php
 * @author    yanchao <yanchao563@yahoo.com>
 * @time      2017/02/23 22:50
 * @copyright 2017
 * @license   www.guanlunsm.com license
 * @link      yanchao563@yahoo.com
 */

namespace Koiterm\Init;
use Koiterm\Init\InitApplication;
use Koiterm\Memory\BaseMemory;
use Koiterm\Models;
class Core
{
    private static $_app;
    private static $_tables;
    private static $_memory;

    public static function app() {
        return self::$_app;
    }

    public static function creatapp() {
        if(!is_object(self::$_app)) {
            self::$_app = InitApplication::instance();
        }
        return self::$_app;
    }

    /**
     *
     * 通过 C::t 方法来调用数据层对应表的对象来实现对数据的操作
     * @param $name
     * @param bool $tName
     * @return mixed
     */
    public static function t($name,$tName = false)
    {
        return self::_makeModel($name, ($tName ? 'table_' : ""));
    }

    /**
     *
     * 创建模型对象
     * @param string $name
     * @param $type
     * @param array $p
     * @return mixed
     */
    protected static function _makeModel($name='', $type='', $p = array())
    {
        $cname = $type.$name;
        if(file_exists(__DIR__ . '/../../Koiterm/Models/' .$name .'.php')){
            $cname = "\Koiterm\Models\\$cname";
        }
        if(!isset(self::$_tables[$cname])) {
            self::$_tables[$cname] = new $cname();
        }
        return self::$_tables[$cname];
    }

    /**
     *
     * 初始化缓存
     * @return ashu_memory
     */
    public static function memory() {
        if(!self::$_memory) {
            self::$_memory = new BaseMemory();
            self::$_memory->init(self::app()->config['memory']);
        }
        return self::$_memory;
    }

}