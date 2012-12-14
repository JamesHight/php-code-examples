<?php

/**
 * User level API OAuth controller
 **/

class Sit_Controller_UserAction extends Sit_Controller_BaseAction {

	protected $user;
	protected $application;
	protected $provider;
	protected $log;
	
    public function preDispatch() {
		$this->log = Zend_Registry::get('log');

		$config = Zend_Registry::get('config');
		if (!$config->oauth->disabled) {
			
			$secret = $this->_getParam('oauth_consumer_secret');
			if ($secret) {
				// support sending key/secret over SSL, similar to OAuth 2

				$key = $this->_getRequiredParam('oauth_consumer_key');
				$userOauth = Core_Model_Mongo_UserOauth::findOne(array('data.token' => $key));
				
				if (!$userOauth || $userOauth->data['secret'] != $secret) 
					throw new Sit_Exception_Api('Key unknown', Sit_Errors::PERMISSION_DENIED);
				
				$this->user = $this->_getTable('Core_Model_User')->find($userOauth->user_id);
				$this->application = $this->_getTable('Core_Model_Application')->find($userOauth->application_id);
				
				if ($this->user->disabled) 
					throw new Sit_Exception_Api('Account disabled', Sit_Errors::PERMISSION_DENIED);
			}
			else {
				// use standard OAuth 1
				try {
					$this->provider = new OAuthProvider();
					$this->provider->consumerHandler(array($this,'_consumerHandler'));
					$this->provider->timestampNonceHandler(array($this,'_timestampNonceChecker'));
					$this->provider->tokenHandler(array($this,'_tokenHandler'));
					$this->provider->is2LeggedEndpoint(true);
					$this->provider->checkOAuthRequest();
				}
				catch (OAuthException $e) {
					// Problem with signature or other OAuth error
					throw new Sit_Exception_Api($e->getMessage(), Sit_Errors::OAUTH_ERROR, $e);
				}
			}
		}
		else { 
			// DEBUG
			$this->user = $this->_getTable('Core_Model_User')->find(1);
			$this->application = $this->_getTable('Core_Model_Application')->find(1);
		}

		// Start Metrics Logging
		$data = array('uri' => $this->getUri(),
					'application_id' => $this->application->id,
					'user_id' => $this->user->id);
		B72_Metrics::start('Sit_Log_Metrics', $config->metrics->disabled, $data);
    }

	
	/**
	 * Log current API call
	 **/

    protected function _logCall() {
    	$params = $this->_getAllParams();
    	$apiLog = new Core_Model_Mongo_ApiLog();
    	$apiLog->params = $params;
    	$apiLog->site_id = $this->user->Site->id;
		$apiLog->app_id = $this->application->id;
		$apiLog->user_id = $this->user->id;
    	$apiLog->ip = $_SERVER['REMOTE_ADDR'];
    	$apiLog->time = time();

    	$apiLog->save();
    }

	public function _timestampNonceChecker($provider) {
		//Since we are only communicating over SSL we aren't doing a nonce check to prevent replay attacks
		return OAUTH_OK;
	}


	public function _consumerHandler($provider) {
		
		// Find user associated with key
		$userOauth = Core_Model_Mongo_UserOauth::findOne(array('data.token' => $provider->consumer_key));

		if (!$userOauth) {
			return OAUTH_CONSUMER_KEY_UNKNOWN;
		}

		$this->user = $this->_getTable('Core_Model_User')->find($userOauth->user_id);

		// Is their account disabled?
		if ($this->user->disabled) {
			return OAUTH_CONSUMER_KEY_REFUSED;
		}

		// Find application user is connecting with
		$this->application = $this->_getTable('Core_Model_Application')
									->find($userOauth->application_id);

		// Set secret to check signature
		$provider->consumer_secret = $userOauth->data['secret'];		
		
		return OAUTH_OK;
	}

	public function _tokenHandler($provider) {
		return OAUTH_OK;
	}
	
	/**
	 * Find user by OAuth key
	 **/
	
	private function findUser($key) {
		$userOauth = Core_Model_Mongo_UserOauth::findOne(array('data.token' => $key));

		if (!$userOauth) {
			return OAUTH_CONSUMER_KEY_UNKNOWN;
		}

		$this->user = $this->_getTable('Core_Model_User')->find($userOauth->user_id);

		if ($this->user->disabled) {
			return OAUTH_CONSUMER_KEY_REFUSED;
		}

		$this->application = $this->_getTable('Core_Model_Application')->find($userOauth->application_id);

		$provider->consumer_secret = $userOauth->data['secret'];

		$userOauth->time = time();
		$userOauth->save();
	}

}

