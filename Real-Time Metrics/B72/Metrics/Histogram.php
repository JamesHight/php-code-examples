<?php

/**
 * Track value deviation over time
 **/

class B72_Metrics_Histogram {

	protected $min;
	protected $max;
	protected $mean;
	protected $count;		

	public function  __construct() {
		$this->min = null;
		$this->max = null;

		$this->mean = null;

		$this->count = 0;
	}

	public function clear() {
		$this->min = null;
		$this->max = null;
		$this->mean = null;
		$this->count = 0;
	}
	
	public function update($value) {
		$this->count++;

		if ($this->max === null) {
			$this->max = $value;
		}
		else if ($value > $this->max){
			$this->max = $value;
		}

		if ($this->min === null) {
			$this->min = $value;
		}
		else if ($value < $this->min){
			$this->min = $value;
		}

		$this->updateVariance($value);

	}

	protected function updateVariance($value) {
		$oldMean = $this->mean;
		if ($this->count == 1) {
			$this->mean = $value;
		}
		else {
			$this->mean = $oldMean + (($value - $oldMean) / $this->count);
		}
	}

	public function getMin() {
		return $this->min === null ? 0 : $this->min;
	}

	public function getMax() {
		return  $this->max === null ? 0 : $this->max;
	}

	public function getMean() {
		return  $this->mean === null ? 0 : $this->mean;
	}

	public function getCount() {
		return $this->count;
	}
	
}