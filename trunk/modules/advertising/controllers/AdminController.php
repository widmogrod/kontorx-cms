<?php
require_once 'KontorX/Controller/Action.php';
class Advertising_AdminController extends KontorX_Controller_Action {
    public $skin = array(
        'layout' => 'admin_advertising',
        'config' => array(
            'filename' => 'backend_config.ini'
        )
    );

    public function indexAction() {}

    public function generateAction() {
        require_once 'Advertising.php';
        $advertise = Advertising_Model_Advertising::getInstance();

        try {
            $advertise->updateDB();
            $message = "Reklama została zaktualizowana";
        } catch (Advertising_Model_Advertising_Exception $e) {
            $message = $e->getMessage();
        }

        $this->_helper->flashMessenger->addMessage($message);

        try {
            $advertise->generateData();
            $message = "Dane zostały wygenerowane";
        } catch (Advertising_Model_Advertising_Exception $e) {
            $message = $e->getMessage();
        }

        $this->_helper->flashMessenger->addMessage($message);


        $this->_helper->redirector->goToAndExit('index');
    }
}