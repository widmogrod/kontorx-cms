<?php
class Admin_IndexController extends KontorX_Controller_Action {
	public function init() {
		$this->_initLayout();
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
	
	public function indexAction() {
	}
}