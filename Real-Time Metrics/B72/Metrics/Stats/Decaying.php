<?php

class B72_Metrics_Stats_Decaying {

	protected $count;
	protected $values;


	protected $limit;
	protected $alpha;

	public function  __construct($size, $alpha) {
		$this->count = 0;
		$this->values = array();

		$this->limit = $size;
		$this->alpha = $alpha;		
	}

	public function getValues() {
		return $this->values;
	}

	public function size() {
		return count($this->values);
	}

	public function clear() {
		$this->values = array();
		$this->count = 0;
	}

	public function update($value, $interval) {
		$r = rand(0, 1);
		if ($r == 0) $r = 0.000001;
		$priority = $this->weight($interval) / $r;
		$value = array('value' => $value,
						'priority' => $priority);

		if ($this->count < $this->limit) {
			$this->count++;
			$this->values[] = $value;
			$this->sort();
		}
		else {			
			$first = $this->values[0];
			if ($first['priority'] < $priority) {
				$this->values[] = $value;
				array_shift($this->values);
				$this->sort();
			}
		}

		$this->rescale($interval);
	}

	protected function weight($interval) {
		return exp($this->alpha * $interval);
	}

	protected function rescale($interval) {
		foreach (array_keys($this->values) as $key) {
			$this->values[$key]['priority'] *= exp(-$this->alpha * $interval);
		}
	}

	protected function sort() {
		usort($this->values, array($this, 'priorityCompare'));
	}

	public function priorityCompare($a, $b) {
		if ($a['priority'] == $b['priority']) {
			return 0;
		}
		return $a['priority'] < $b['priority'] ? -1 : 1;
	}

  
}


