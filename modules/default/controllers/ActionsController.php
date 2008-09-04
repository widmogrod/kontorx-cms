<?php
require_once 'KontorX/Controller/Action.php';
class Default_ActionsController extends KontorX_Controller_Action {

	/**
	 * Formularz kontaktowy
	 *
	 */
	public function contactAction() {
		$config = $this->_helper->loader->config('actions.ini');
		$form = new Zend_Form($config->form->kontakt);

		if (!$this->_request->isPost()) {
    		$this->view->form = $form->render();
    		return;
    	}

    	if (!$form->isValid($_POST)) {
    		$this->view->form = $form->render();
    		return;
    	}

    	// przygotowanie danych
    	$data = $form->getValues();
    	$data = get_magic_quotes_gpc() ? array_map('stripslashes', $data) : $data;

    	$observable = new KontorX_Observable();

    	require_once 'default/models/observers/SendMailObserver.php';
    	$sendMailObserverToGuest = new Default_SendMailObserver(
    		Default_SendMailObserver::MAIL_SEND,
    		array(
    			'from' => $config->kontakt->emailFrom,
    			'email' => $data['emailadress']
    		),
    		$data
//    		$this->view
    	);
    	$observable->addObserver($sendMailObserverToGuest);
    	$sendMailObserverToMe = new Default_SendMailObserver(
    		Default_SendMailObserver::MAIL_SEND,
    		array(
    			'from' => $config->kontakt->emailFrom,
    			'email' => $config->kontakt->emailCopyTo
    		),
    		$data
//    		$this->view
    	);
		$observable->addObserver($sendMailObserverToMe);

		try {
			$observable->notify();
			
			$message = 'Wiadomość została wysłana';
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
		} catch (KontorX_Observable_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$message = 'Wystąpił problem z przesyłaniem wiadomości';
			$this->view->messages = array($message);
			$this->view->form = $form->render();
		}
	}
}