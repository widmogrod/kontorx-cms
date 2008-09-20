<?php
require_once 'KontorX/Controller/Action.php';
class Group_AdminController extends KontorX_Controller_Action {
	public $skin = array(
		'layout' => 'admin'
	);

    public function indexAction(){
    	$this->_forward('list','group');
    }   
}