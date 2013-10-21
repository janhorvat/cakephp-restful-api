<?php

App::import('file', 'AuthExceptions', false, array(APP . 'Lib' . DS . 'Exceptions' . DS), 'AuthExceptions.php');

class AuthComponent extends Component {
		
	public $request_timeout = 1800;
	public $settings = array();
	public $controller = null;
	
	function __construct(ComponentCollection $collection, $settings  = array()) {
		parent::__construct($collection, $settings);
		$this->settings = $settings;
	}
	
	function initialize(Controller $controller) {
		parent::initialize($controller);
		$this->controller = $controller;
	}
	
	function startup(Controller $controller) {
		$this->getData();
		if (!$this->checkSignature()) {
			throw new AuthInvalidSignatureException();
		}
	}
	
	function checkSignature() {
		$auth = trim($this->controller->request->header('X-BTZ-Authorization'));
		$auth = explode(' ', $auth);
		
		if (count($auth) >= 2) {
			$auths = array();
			parse_str($auth[1], $auths);
			
			$this->controller->api_auth = $auths;
			
			if (	isset($this->controller->api_auth['ApiKey']) && 
					isset($this->controller->api_auth['Algorithm']) && 
					isset($this->controller->api_auth['Signature']) && 
					isset($this->controller->api_auth['Timestamp'])) {
				
				if (strtolower($this->controller->api_auth['Algorithm']) == strtolower('HmacSHA1')) {
					$apiUser = $this->getApiUser($this->controller->api_auth['ApiKey']);
					if (isset($apiUser['ApiUser']['api_secret'])) {
						$api_secret = $apiUser['ApiUser']['api_secret'];
						
						$datetime = trim($this->controller->api_auth['Timestamp']);
						$requestTime = strtotime($datetime);
						
						if (abs(time() - $requestTime) <= $this->request_timeout) {
							$end_point = $this->getEndPoint();
							$data = $this->controller->request->api_data_raw;
							
							$signature = hash_hmac('sha1', 'Timestamp='.urlencode($datetime).'&Url='.urlencode($end_point).'&Data='.urlencode($data), $api_secret);
							return ($signature == $this->controller->api_auth['Signature']);
						} else {
							throw new AuthRequestTimeoutException();
						}
					} else {
						throw new AuthInvalidApiUserException();
					}
				} else {
					throw new AuthInvalidSignatureAlgorithmException();
				}
			} else {
				throw new AuthInvalidRequestException();
			}
		} else {
			throw new AuthInvalidRequestException();
		}
		
		return false;
	}
	
	function getEndPoint() {
		$end_point = preg_replace('/^\//', '', $_SERVER['REQUEST_URI']);
		return $end_point;
	}
	
	function getData() {
		$this->controller->request->api_data_raw = $this->controller->request->input();
		$this->controller->request->data = $this->controller->request->input('json_decode', true);
	}
	
	function getApiUser($api_key) {
		if (is_null($this->controller->api_user)) {
			$this->controller->loadModel('Api.ApiUser');
			$this->controller->api_user = $this->controller->ApiUser->findByApiKey($api_key);
		}
		
		return $this->controller->api_user;
	}
	
}