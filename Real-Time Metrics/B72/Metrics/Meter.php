<?php

/**
 * Increment a value starting at 0
 **/

class B72_Metrics_Meter {
		
	protected $maxInt;
	protected $count;

	public function  __construct() {
		$this->maxInt = pow(2, 32);
		$this->count = 0;
	}

	public function inc($value = 1) {
		$this->count = $this->add($this->count, $value);
	}

	protected function add($val1, $val2) {
		$val1 += $val2;
		if ($val1 < 0) {
			$val1 = 0;
		}
		if ($val1 > $this->maxInt) {
			$val1 = $this->maxInt;
		}
		return $val1;
	}

	public function set($value) {
		$this->count = $value;
	}

	public function clear() {
		$this->count = 0;
	}

	public function getCount() {
		return $this->count;
	}
	

}