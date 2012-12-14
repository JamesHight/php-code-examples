<?php

 /**
  * UDP based metrics logging
  **/

class B72_Metrics {

	/**
	 * Initiate metrics and execution timer
	 *
	 * @param Class $logClass class used to log metrics
	 * @param Boolean $enabled enable/disable metrics
	 * @param Object $data optional data payload to log
	 * @param Boolean $runTime enable/disable execution timer
	 */
	public static function start($logClass, $disabled = false, $data = null, $runTime = true) {
		self::getInstance()->_start($logClass, $disabled, $data);
	}
	
	/**
	 * End run timer, send data to $logClass
	 **/
	
	public static function end() {
		self::getInstance()->_end();
	}

	/**
	 *
	 * @param String $name
	 * @param Number $val
	 */

	/*public static function gauge($name, $value) {
		self::getInstance()->_gauge($name, $value);
	}*/

	/**
	 * Increment a counter
	 *
	 * @param String $name name of counter 
	 **/
	 
	public static function counterInc($name) {
		self::getInstance()->_counterInc($name);
	}

	/**
	 * Decrement a counter
	 *
	 * @param String $name name of counter 
	 **/

	public static function counterDec($name) {
		self::getInstance()->_counterDec($name);
	}

	/**
	 * Like a counter, but only increments, use to measure rate of events
	 *
	 * @param <type> $name
	 */
	public static function meter($name, $value = 1) {
		self::getInstance()->_meter($name, $value);
	}
	
	/**
	 * Measure distribution of values over time 
	 *
	 * @param String $name name of histogram
	 * @param Number $value value to log
	 **/	

	public static function histogram($name, $value) {
		self::getInstance()->_histogram($name, $value);
	}
	
	/**
	 * Start a new timer
	 * Timers are histograms of the duration
	 *
	 * @param String $name name of timer
	 **/

	public static function timerStart($name) {
		self::getInstance()->_timerStart($name);
	}
	
	/**
	 * End a running timer
	 *
	 * @param String $name name of timer
	 **/

	public static function timerEnd($name) {
		self::getInstance()->_timerEnd($name);
	}
	

	private static $instance;
	private static function getInstance() {
		if (!self::$instance) {
			self::$instance = new B72_Metrics();
		}
		
		return self::$instance;
	}

	private $logClass = null;
	private $disabled = true;
	private $data;
	private $prefix;

	private $maxInt;

	private $runTime;
	private $startTime;

	//private $gauges = array();
	private $counters = array();
	private $meters = array();
	private $histograms = array();
	private $timers = array();

	public function  __construct() {
		$this->maxInt = pow(2, 32);
	}

	public function _start($logClass, $disabled = false, $data = null, $runTime = true) {
		if ($this->logClass) {
			return;
		}
		
		$this->logClass = $logClass;
		$this->disabled = $disabled;
		$this->data = $data;
		$this->prefix = '';
		$this->runTime = $runTime;

		if ($this->runTime) {
			$this->startTime = $this->time();
		}
	}
	
	public function _end() {
		$this->log();
	}

	/*public function _gauge($name, $value) {
		if ($this->disabled) return;

		if (!in_array($name, $this->gauges)) {
			$this->gauges[$name] = 0;
		}
		$this->gauges[$name] = $this->add($this->gauges[$name], $value);
	}*/

	public function _counterInc($name, $value = 1) {
		if ($this->disabled) return;

		$name = $this->prefix . $name;

		if (!in_array($name, $this->counters)) {
			$this->counters[$name] = 0;
		}
		
		$this->counters[$name] = $this->add($this->counters[$name], $value);
	}

	public function _counterDec($name, $value = 1) {
		$this->counterInc($name, -$value);
	}

	public function _meter($name, $value = 1) {
		if ($this->disabled) return;

		$name = $this->prefix . $name;
		
		if (!in_array($name, $this->meters)) {
			$this->meters[$name] = 0;
		}

		$this->meters[$name] = $this->add($this->meters[$name], $value);
	}

	public function _histogram($name, $value) {
		if ($this->disabled) return;

		$name = $this->prefix . $name;

		if (!in_array($name, $this->histograms)) {
			$this->histograms[$name] = 0;
		}

		$this->histograms[$name] = $this->add($this->histograms[$name], $value);
	}

	public function _timerStart($name) {
		if ($this->disabled) return;

		$name = $this->prefix . $name;

		$this->timers[$name] = $this->time();
	}

	public function _timerEnd($name) {
		if ($this->disabled) return;

		$name = $this->prefix . $name;
		
		$this->timers[$name] = $this->time() - $this->timers[$name];
	}

	private function time() {
		return microtime(true) * 1000;
	}

	private function add($val1, $val2) {
		$val1 += $val2;
		if ($val1 < 0) {
			$val1 = 0;
		}
		if ($val1 > $this->maxInt) {
			$val1 = $this->maxInt;
		}
		return $val1;
	}

	private function log() {
		if ($this->disabled) return;

		$metrics = array();

		if ($this->runTime) {
			$metrics['time'] = $this->time() - $this->startTime;
			$metrics['memory'] = memory_get_peak_usage(true);
		}

		//if (count($this->gauges)) $metrics['gauges'] = $this->gauges;
		if (count($this->counters)) $metrics['counters'] = $this->counters;
		if (count($this->meters)) $metrics['meters'] = $this->meters;
		if (count($this->histograms)) $metrics['histograms'] = $this->histograms;
		if (count($this->timers)) $metrics['timers'] = $this->timers;
		if ($this->data) $metrics['data'] = $this->data;		

		$logger = new $this->logClass;
		$logger->log($metrics);
	}
}

