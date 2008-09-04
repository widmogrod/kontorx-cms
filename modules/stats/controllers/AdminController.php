<?php
class Stats_AdminController extends KontorX_Controller_Action {
	public function init() {
		$this->_initLayout('stats');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
	
	public function indexAction() {
		$month 	= $this->_getParam('month', date('m'));
		$day 	= $this->_getParam('day', date('d'));
		$year 	= $this->_getParam('year', date('Y'));
		$time 	= mktime(0,0,0, $month, $day, $year);

		$this->view->month = $month;
		$this->view->date  = $this->_getParam('date', date('Y-m-d', $time));
	}
}