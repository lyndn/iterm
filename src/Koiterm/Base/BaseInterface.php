<?php
/**
 *
 * PHP Version ～7.1
 * @package   Base.php
 * @author    yanchao <yanchao563@yahoo.com>
 * @time      2017/02/25 19:34
 * @copyright 2017
 * @license   www.guanlunsm.com license
 * @link      yanchao563@yahoo.com
 */

namespace Koiterm\Base;

abstract class BaseInterface
{
    private $_e;
    private $_m;

    public function __construct() {

    }

    public function __set($name, $value) {
        $setter='set'.$name;
        if(method_exists($this,$setter)) {
            return $this->$setter($value);
        } elseif($this->canGetProperty($name)) {
            throw new Exception('The property "'.get_class($this).'->'.$name.'" is readonly');
        } else {
            throw new Exception('The property "'.get_class($this).'->'.$name.'" is not defined');
        }
    }

    public function __get($name) {
        $getter='get'.$name;
        if(method_exists($this,$getter)) {
            return $this->$getter();
        } else {
            throw new Exception('The property "'.get_class($this).'->'.$name.'" is not defined');
        }
    }

    public function __call($name,$parameters) {
        throw new Exception('Class "'.get_class($this).'" does not have a method named "'.$name.'".');
    }

    public function canGetProperty($name)
    {
        return method_exists($this,'get'.$name);
    }

    public function canSetProperty($name)
    {
        return method_exists($this,'set'.$name);
    }

    public function __toString() {
        return get_class($this);
    }

    public function __invoke() {
        return get_class($this);
    }

}
