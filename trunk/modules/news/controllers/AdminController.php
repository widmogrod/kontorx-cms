<?php
require_once 'Zend/Controller/Action.php';
class News_AdminController extends Zend_Controller_Action {
	public function indexAction() {
		$this->_forward('list','news');
	}
}