<?php
namespace Koiterm\Memory;

class Memory_driver_eaccelerator
{

	public function init($config) {

	}

	public function get($key) {
		return eaccelerator_get($key);
	}

	public function set($key, $value, $ttl = 0) {
		return eaccelerator_put($key, $value, $ttl);
	}

	public function rm($key) {
		return eaccelerator_rm($key);
	}

	public function clear() {
		return @eaccelerator_clear();
	}

}