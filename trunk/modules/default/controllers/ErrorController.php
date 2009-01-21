<?php
require_once 'KontorX/Controller/Action.php';
class Default_ErrorController extends KontorX_Controller_Action {
	public $skin = array(
		'layout' => 'index'
	);

    /**
     * Przechywytywanie błędów
     *
     */
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

        require_once 'KontorX/Util/Functions.php';
    	$ip = KontorX_Util_Functions::getIP();

		Zend_Registry::get('loggerFramework')
			->log("$ip :: " . get_class($exception) . " " . $exception->getMessage() . "\n" .  $exception->getTraceAsString(), Zend_Log::CRIT);
    }

    /**
     * Użytkownik jest zalogowany ale ma niewystarczające uprawnienia!
     */
    public function privilegesAction() {
    	require_once 'KontorX/Util/Functions.php';
    	$ip = KontorX_Util_Functions::getIP();
    	Zend_Registry::get('loggerFramework')
			->log("$ip :: " . __FUNCTION__, Zend_Log::CRIT);
    }
}

