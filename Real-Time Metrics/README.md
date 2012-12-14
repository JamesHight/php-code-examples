Real-Time Metrics
=================

All data is sent over UDP to an aggregate server and then logged to Graphite.
If for some reason the metrics server(s) go down, the application servers will remain unaffected.

	$data = array('uri' => $this->getUri(),
				'application_id' => $this->application->id,
				'user_id' => $this->user->id);

	// Initialize metrics and start execution timer	
	B72_Metrics::start('B72_Log_Metrics', $config->metrics->disabled, $data);
	
	// Track error events
	B72_Metrics::meter('error.404');
	B72_Metrics::meter('error.500');
	
	// Time external API calls to third parties or time code routes
	B72_Metrics::timerStart('facebook.login');
	// Execute some code
	B72_Metrics::timerEnd('facebook.login');
	
	// End execution timer, log peak memory usage,
	// pass all data to B72_Log_Metrics so it can sent to the metrics server
	B72_Metrics::end();