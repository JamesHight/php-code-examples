<?php

/**
 * Collect values to write directly to the Carbon server proxy
 **/

class B72_Metrics_Processsor {

	protected $meters = array();
	protected $counters = array();
	protected $histograms = array();
	protected $timers = array();

	protected $metrics;

	protected $time;

	public function  __construct() {
		$this->metrics = array();
	}

	public function setTime($value) {
		$this->time = $value;
	}

	public function clearMetrics() {
		$this->metrics = array();
	}

	public function getMetrics() {
		return $this->metrics;
	}


	public function getMeter($name) {
		if (!key_exists($name, $this->meters)) {
			$this->meters[$name] = new B72_Metrics_Meter();
		}
		return $this->meters[$name];
	}

	public function getCounter($name) {
		if (!key_exists($name, $this->counters)) {
			$this->counters[$name] = new B72_Metrics_Counter();
		}
		return $this->counters[$name];
	}

	public function getHistogram($name) {
		if (!key_exists($name, $this->histograms)) {
			$this->histograms[$name] = new B72_Metrics_Histogram();
		}
		return $this->histograms[$name];
	}

	public function getTimer($name) {
		if (!key_exists($name, $this->timers)) {
			$this->timers[$name] = new B72_Metrics_Timer();
		}
		return $this->timers[$name];
	}

	public function clear() {
		foreach ($this->meters as $meter) {
			$meter->clear();
		}

		foreach ($this->counters as $counter) {
			$counter->clear();
		}

		foreach ($this->histograms as $histogram) {
			$histogram->clear();
		}

		foreach ($this->timers as $timer) {
			$timer->clear();
		}
	}

	public function logMetrics() {
		$time = intval($this->time);
		
		foreach ($this->meters as $name=>$meter) {			
			$path = $name . '.count';
			$this->metrics[] = $path . ' ' . $meter->getCount() . ' ' . $time;
		}

		foreach ($this->counters as $name=>$counter) {
			$path = $name . '.count';
			$this->metrics[] = $path . ' ' . $counter->getCount() . ' ' . $time;
		}

		foreach ($this->histograms as $name=>$histogram) {
			$path = $name . '.min';
			$this->metrics[] = $path . ' ' . $histogram->getMin() . ' ' . $time;

			$path = $name . '.max';
			$this->metrics[] = $path . ' ' . $histogram->getMax() . ' ' . $time;

			$path = $name . '.mean';
			$this->metrics[] = $path . ' ' . $histogram->getMean() . ' ' . $time;

			$path = $name . '.count';
			$this->metrics[] = $path . ' ' . $histogram->getCount() . ' ' . $time;
		}

		foreach ($this->timers as $name=>$timer) {
			$path = $name . '.min';
			$this->metrics[] = $path . ' ' . $timer->getMin() . ' ' . $time;

			$path = $name . '.max';
			$this->metrics[] = $path . ' ' . $timer->getMax() . ' ' . $time;

			$path = $name . '.mean';
			$this->metrics[] = $path . ' ' . $timer->getMean() . ' ' . $time;

			$path = $name . '.count';
			$this->metrics[] = $path . ' ' . $timer->getCount() . ' ' . $time;
		}		
	}

}