<?php

/**
 * Base metrics class
 **/

class B72_Metrics_Metric {

	private $maxInt;
	protected $count;
	public $type;

	public function  __construct($type) {
		$this->maxInt = pow(2, 32);
		$this->count = 0;
		$this->type = $type;
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

}