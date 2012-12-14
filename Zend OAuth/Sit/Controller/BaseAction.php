<?php

/**
 * Base class for all other controllers
 * Adds simple helper functions
 **/

class Sit_Controller_BaseAction extends Zend_Controller_Action {

	protected $jsReservedWords = array( 'break', 'do', 'instanceof',
            'typeof', 'case', 'else','new', 'var', 'catch',
            'finally', 'return', 'void', 'continue',  'for',
            'switch', 'while', 'debugger', 'function', 'this',
            'with',  'default', 'if', 'throw', 'delete', 'in',
            'try', 'class', 'enum',  'extends', 'super', 'const',
            'export', 'import', 'implements', 'let',  'private',
            'public', 'yield', 'interface', 'package', 'protected', 
            'static', 'null', 'true', 'false'
        );
	
	/** 
	 * Handle XHR2 OPTIONS Request 
	 **/
	public function preDispatch() {
		if ($this->_request->isOptions()) {
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
			exit;
		}
	}    

	/**
	 * Check that a JSONP callback is valid	 
	 **/

	protected function validCallback($callback) {        
 
        foreach(explode('.', $callback) as $identifier) {
            if(!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*(?:\[(?:".+"|\'.+\'|\d+)\])*?$/', $identifier)) {
                return false;
            }
            if(in_array($identifier, $this->jsReservedWords)) {
                return false;
            }
        }
 
        return true;    
	}
	
	/**
	 * Output JSON instead of page
	 * @param Object $object
	 */
	
	protected function jsonOutput($object) {		
		$jsonString = Sit_Json::encode($object);
		
		
		// See if we should encode as JsonP		
		$func = $this->_getParam('callback');
		if ($func) {
			$this->_response->setHeader('Content-Type', 'application/javascript', true);
			if (!$this->validCallback($func)) {
				throw new Sit_Exception_Api("Invalid callback", Sit_Errors::PARAMETER);
			}

			$jsonString = $func . '(' . $jsonString . ')';
		}
		else  {
			// Send response back using easyXDM? Mainly used for file uploads.
			$easyXDM = $this->_getParam('easyXDM');
			if ($easyXDM) { 
				if (!$this->validCallback($easyXDM)) {
					throw new Sit_Exception_Api("Invalid easyXDM callback", Sit_Errors::PARAMETER);
				}
				$jsonString = '<html><head></head><body><script>parent.rpc.' . 
					$easyXDM . '(' . $jsonString . ');</script></body></html>';
			}
		}
		
		$this->_response
			->setHeader('Access-Control-Allow-Origin', '*')
			->setBody($jsonString)->sendResponse();
		
		B72_Metrics::end();

		// quick exit for faster execution
		if (!Zend_Session::$_unitTestEnabled) {
			exit;
		}

		$this->_helper->viewRenderer->setNoRender();
	}
	
   	
    /**
	 * Output JSON response message
	 * @param Object $data
	 * @param string $status
	 */
    
    protected function response($data = null, $status = Sit_Status::OK) {
    	$message = array();
    	$message['status'] = $status;
    	if ($data !== null) {
    		$message['data'] = $data;
    	}

    	$this->jsonOutput($message);
    }
        
    /**
	 * Get required request parameter
	 * @param string $field
	 */    
    protected function _getRequiredParam($field) {
    	$value = $this->_getParam($field, NULL);
    	if ($value == NULL) {
			throw new Sit_Exception_Api("Missing '$field' paramter.", Sit_Errors::PARAMETER);
    	}
    	return $value;
    }
    
    
    /**
	 * Get request parameter as an integer
	 * @param string $field
	 * @param int $default
	 */
    
	protected function _getInt($field, $default = 0) {
    	$val = $this->_getParam($field, null);
    	if ($val !== null) {
    		return intval($val);
    	}
    	else {
    		return $default;
    	}
    }
    
    /**
	 * Get request parameter as an integer
	 * If it does not exist throw an API error
	 * @param string $field
	 */
    
	protected function _getRequiredInt($field) {
    	$val = $this->_getRequiredParam($field);
    	return intval($val);    	
    }
    
    /**
	 * Get request parameter as a float
	 * @param string $field
	 * @param float $default
	 */

	protected function _getFloat($field, $default = 0) {
    	$val = $this->_getParam($field, null);
    	if ($val !== null) {
    		return floatval($val);			
    	}
    	else {
    		return $default;
    	}
    }

    /**
	 * Get request parameter as a float
	 * If it does not exist throw an API error
	 * @param string $field
	 */

	protected function _getRequiredFloat($field) {
    	$val = $this->_getRequiredParam($field, null);
    	return floatval($val);
    }
    
    /**
	 * Get request parameter as a bool
	 * @param string $field
	 * @param boolean $default
	 */
	
	protected function _getBool($field, $default = false) {
    	$val = $this->_getParam($field, null);
    	if ($val !== null) {
			if ($val && $val != 'false')
				return true;
    	}
    	
		return $default;    	
    }
    
    /**
	 * Get request parameter as a bool
	 * If it does not exist throw an API error
	 * @param string $field
	 */
	
	protected function _getRequiredBool($field) {
    	$val = $this->_getRequiredParam($field, null);
		if ($val && $val != 'false')
			return true;
    	return false;
    }
    
    /**
     * Get Doctrine table
     * @param string $class name of table class
     * @return Doctrine_Table
     */
    
    protected function _getTable($class) {
    	return Doctrine_Core::getTable($class);
    }  

    /**
     * Return current URI
     **/
    
	protected function getUri() {
		if (isset($_SERVER['REQUEST_URI'])) {
			return $_SERVER['REQUEST_URI'];
		}
		$request = $this->getRequest();
		return '/' . $request->getModuleName() . '/' . $request->getControllerName() . '/' . $request->getActionName();
	}

	/**
	 * Throw 404 exception
	 **/

	protected function notFound() {
		throw new Zend_Controller_Action_Exception('File not found.', 404);
	}

}

