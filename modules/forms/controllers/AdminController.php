<?php
require_once 'KontorX/Controller/Action.php';
class Forms_AdminController extends KontorX_Controller_Action {
	
	public function indexAction() {
		$this->_forward('index','constructor');
	}
}