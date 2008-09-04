<?php
require_once 'KontorX/Controller/Action.php';
class User_IndexController extends KontorX_Controller_Action {

	public $scaffolding = array(
		'index' => array(
			'add',
			'callbacks' => array(
				'TRIGGER_GET_MODEL' => '_getIndexModel'
			)
		)
	);
	
	public function init() {
		$this->_initLayout('user',null,null,'default');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		
		$this->_helper->scaffolding();
	}

	public function indexAction() {
		
	}

	public function _getIndexModel() {
		require_once 'user/models/User.php';
		return $model = new User();
	}
}