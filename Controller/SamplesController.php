<?php
class SamplesController extends SamplesAppController {

	public function api_v1_view($id) {
		
		$this->layout = false;
		$this->ApiAuth = $this->Components->load('ApiAuth');
		$this->ApiOut = $this->Components->load('ApiOutput');
		$this->ApiAuth->initialize($this);
		$this->ApiAuth->startup($this);
		$this->ApiOut->initialize($this);
              
		$samples = $this->Sample->find('all', array('conditions' => array('Sample.id' => $id)));
		
		$this->ApiOut->success($samples);
	}
	
	public function beforeFilter() {
		parent::beforeFilter();
		if (isset($this->FrontAuth)) {
			$this->FrontAuth->allow('api_v1_view');
		}
	}	
}
