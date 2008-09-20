<?php
require_once 'KontorX/Controller/Action.php';

/**
 * Description of AdminController
 *
 * @author widmogrod
 */
class System_AdminController extends KontorX_Controller_Action {
    public function init() {
        $this->_helper->system->template('admin');
    }

    public function indexAction() {
        $config = $this->_helper->loader->config('test.ini');
        $form = new KontorX_Form_Config($config);
        $this->view->form = $form;
    }
}