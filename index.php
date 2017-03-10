<?php
/**
 *
 * PHP Version ～7.1
 * @package   demo.php
 * @author    yanchao <yanchao563@yahoo.com>
 * @time      2017/02/23 22:42
 * @copyright 2017
 * @license   www.guanlunsm.com license
 * @link      yanchao563@yahoo.com
 */
require './vendor/autoload.php';
use Koiterm\Init\Core;
$C = new Core();
$koiterm = $C->creatapp();
$koiterm->init();
?>