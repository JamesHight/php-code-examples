<?php

/**
 * Log data to a UDP metrics server
 * Used by B72_Metrics
 **/

class B72_Log_Metrics {
	
	/**
	 * @param Object $data data to write to server
	 **/
	 
	public function log($data) {
		
		// Fetch application configuration
		$config = Zend_Registry::get('config');
		
		// Add prefix for Graphite
		$data['data']['prefix'] = $config->metrics->prefix;
		
		$data = Sit_Json::encode($data);
		
		if ($config->metrics->disabled) {
			return;
		}
		
		// Send data to metrics server
		// Ignore any connection errors
		try {
			$host = $config->metrics->server;
			$port = $config->metrics->port;
			$fp = fsockopen("udp://$host", $port, $errno, $errstr);
			if (! $fp) {
				return;
			}
			fwrite($fp, $data);
			fclose($fp);
		}
		catch (Exception $e) {
		
		}
	}
	
}