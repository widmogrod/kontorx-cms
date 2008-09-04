<?php
class Default_ErrorController extends KontorX_Controller_Action {
	public function init() {
		$this->_initLayout(null,null,null,'default');
		$this->view->messages = (array) $this->_helper->flashMessenger->getMessages();
	}

    public function errorAction() {
		$errors = $this->_getParam('error_handler');
		$exception = $errors->exception;
		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
				$this->_helper->viewRenderer->render('error.nocontroller');
				break;
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				$this->_helper->viewRenderer->render('error.noaction');
				break;

            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
            	$this->view->message = $exception->getMessage();
            	$this->view->trace = $exception->getTrace();
				break;

			default:
				$this->view->message = $exception->getMessage();
            	$this->view->trace = $exception->getTrace();
				break;
        }

		$loggerFramework = Zend_Registry::get('loggerFramework');
		$loggerFramework->log($exception->getMessage() . "\n" .  $exception->getTraceAsString(), Zend_Log::CRIT);
    }

    public function privilegesAction() {

    }
}

