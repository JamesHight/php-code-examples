<?php

/**
 * Increment and decrement a value
 **/
 
class B72_Metrics_Counter extends B72_Metrics_Meter {

	public function dec($value = 1) {
		$this->inc(-$value);
	}
	
}