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

class Core
{
    private static $_app;

    public static function app() {
        return self::$_app;
    }

    public static function creatapp() {
        if(!is_object(self::$_app)) {
            self::$_app = InitApplication::instance();
        }
        return self::$_app;
    }
}