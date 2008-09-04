<?php
require_once 'KontorX/Controller/Action.php';
class Page_AdminController extends KontorX_Controller_Action {
	public function init() {
		$this->_initLayout();
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

    public function indexAction(){
    	$this->_forward('list','page');
    }   
}