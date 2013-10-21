<?php

class OutputComponent extends Component {
	
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
	
	function json($data) {
		$this->controller->changeContentType = false;
		$this->controller->response->type('application/json');
		$data = json_encode($data);
		$this->signature($data);
		$this->controller->set('_response', $data);
	}
	
	function success($data = null, $success_type = null, $success_msg = null) {
		$envelope = array(
			'result' => array(
				'status' => 'success',
				'type' => $success_type,
				'msg' => $success_msg
			),
			'data' => $data
		);
		$this->json($envelope);
	}
	
	function error($data = null, $error_type = null, $error_msg = null) {
		$envelope = array(
			'result' => array(
				'status' => 'error',
				'type' => $error_type,
				'msg' => $error_msg
			),
			'data' => $data
		);
		$this->json($envelope);
	}
	
	function signature($data) {
		$apiUser = $this->controller->api_user;
		$timestamp = date(DATE_RFC2822);
		$signature = hash_hmac('sha1', 'Timestamp='.urlencode($timestamp).'&Data='.urlencode($data), $apiUser['ApiUser']['api_secret']);
		$this->controller->response->header(array(
				'X-BTZ-Signature' => 'BTZ-HTTP Algorithm=HmacSHA1&Signature='.$signature.'&Timestamp='.urlencode($timestamp)));
	}
	
	function beforeRender(Controller $controller) {
		$controller->viewPath = 'Elements';
		$controller->view = 'raw';
	}
	
}