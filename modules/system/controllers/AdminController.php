<?php
require_once 'KontorX/Controller/Action.php';

/**
 * Description of AdminController
 *
 * @author widmogrod
 */
class System_AdminController extends KontorX_Controller_Action {
	
    public function indexAction() {
    	$this->_forward('index','settings');
    }
}