<?php
require_once 'KontorX/Controller/Action.php';
class User_IndexController extends KontorX_Controller_Action {
	public $skin = array(
		'layout' => 'user'
	);

	public $scaffolding = array(
		'index' => array(
			'add',
			'callbacks' => array(
				'TRIGGER_GET_MODEL' => '_getIndexModel'
			)
		)
	);
	
	public function init() {
		$this->_helper->scaffolding();

		require_once 'user/models/User.php';
		$this->view->userId = User::getAuth(User::AUTH_USERNAME_ID);
	}

	public function indexAction() {
		
	}

	public function _getIndexModel() {
		require_once 'user/models/User.php';
		return $model = new User();
	}
}