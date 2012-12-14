<?php

class B72_Metrics_Stats_Weighted {

	private $alpha;

	private $initialized;
	private $currentRate;
	private $uncounted;
	private $tickInterval;

	public function  __construct($alpha) {
		$this->alpha = $alpha;

		$this->initialized = false;
		$this->currentRate = 0.0;
		$this->uncounted = 0;
	}


	public function update($value = 1) {
		$this->uncounted += $value;
	}

	public function tick($interval) {
		$instantRate = $this->uncounted / $interval;
		$this->uncounted = 0;

		if($this->initialized) {
			$this->currentRate += $this->alpha * ($instantRate - $this->currentRate);
		}
		else {
			$this->currentRate = $instantRate;
			$this->initialized = true;
		}
	}

	public function rate() {
		return $this->currentRate * 1000;
	}
	
}