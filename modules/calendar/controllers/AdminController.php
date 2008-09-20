<?php
require_once 'KontorX/Controller/Action.php';
class Calendar_AdminController extends KontorX_Controller_Action {
	public $skin = array('layout' => 'admin_calendar_calendar');

	public function indexAction() {
		$this->_forward('list','calendar');
	}
}