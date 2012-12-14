<?php

/**
 * Application level API OAuth controller
 **/

class Sit_Controller_ApiAction extends Sit_Controller_BaseAction {
	
	protected $auth;
	protected $provider;
	protected $log;
	protected $config;
	
	
	public function preDispatch() {
		$this->log = Zend_Registry::get('log');
		$this->config = Zend_Registry::get('config');

		// Application authentication and validation 
		$this->auth = Sit_Auth::getInstance();
		
		if (!$this->config->oauth->disabled) {
			// support sending key/secret over SSL, similar to OAuth 2
			$secret = $this->_getParam('oauth_consumer_secret');
			if ($secret) {
				$key = $this->_getRequiredParam('oauth_consumer_key');
				$this->auth->setToken($key);			
				if (!$this->auth->validate($secret))
					throw new Sit_Exception_Api('Invalid token/secret', Sit_Errors::PERMISSION_DENIED);
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

	}
	
	/**
	 * Throw permission denied exception
	 **/

	public function permissionDenied() {
		throw new Sit_Exception_Api('Insufficient permission', Sit_Errors::PERMISSION_DENIED);
	}
	
	/**
	 * Log current call for debugging
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
		
		// Find account associated with key
		$this->auth->setToken($provider->consumer_key);			
		if (!$this->auth->getAccount()) {
			return OAUTH_CONSUMER_KEY_UNKNOWN;
		}

		// Is their account disabled?
		if ($this->auth->isDisabled()) {
			return OAUTH_CONSUMER_KEY_REFUSED;
		}

		// Set secret to check signature
		$provider->consumer_secret = $this->auth->getSecret();
		
		return OAUTH_OK;
	}

	public function _tokenHandler($provider) {
		return OAUTH_OK;
	}
	
}