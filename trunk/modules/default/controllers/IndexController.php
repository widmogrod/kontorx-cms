<?php
class Default_IndexController extends KontorX_Controller_Action {
	public function init() {
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
	
	public function indexAction() {
//		$this->_helper->actionStack
//			->actionToStack('list','index','news', array('rowCount' => 5,'pagination' => false))
//			->actionToStack('display','index','calendar', array('rowCount' => 5));
		$this->_initLayout('home');
//		
//		$odf = new KontorX_Odf_Import(PUBLIC_PATHNAME . 'content.xml');
//		print (string) $odf;

//		$g = new KontorX_Util_Google('http://www.stempel.kr.com.pl');
//		$p = $g->position('pieczątki, kraków');
	}
}