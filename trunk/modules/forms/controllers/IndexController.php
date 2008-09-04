<?php
class Forms_IndexController extends KontorX_Controller_Action {
	public function init() {
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
	
	public function indexAction() {
	}

	public function showAction() {
		// inicjujemy widok
		$this->_initLayout('product-order',null,null,'default');
		// wczytywanie konfiguracji
		$config = $this->_helper->loader->loadConfig('index.ini');
		// sprawdzenie czy istnieje formularz
		$id = $this->_getParam('id');
		if (!isset($config->form->{$id})) {
			$this->_helper->viewRenderer->render('show.noexsists');
			return;
		}

		// zaladowanie formularza
		$form = new Zend_Form($config->form->{$id});
		// delikatna modyfikacja formularza
		$zalacznik = $form->getElement('zalacznik');
		$form->addElement(new KontorX_Form_Element_File('zalacznik', array(
			'label' => $zalacznik->getLabel(),
			'name' => $zalacznik->getName(),
		)));

		$form->setAction($form->getAction()."/id/$id");

		if (!$this->_request->isPost()) {
			$this->view->form = $form->render();
			return;
		}

		if (!$form->isValid($_POST)) {
			$this->view->form = $form->render();
			return;
		}

		$data = $form->getValues();
		$data = get_magic_quotes_gpc() ? array_map('stripslashes', $data) : $data;
		
		$observable = new KontorX_Observable();

    	require_once 'forms/models/observers/SendMailObserver.php';
    	$sendMailObserverToGuest = new Default_SendMailObserver(
    		Default_SendMailObserver::FORM_SEND,
    		array(
    			'from' => $config->emailFrom,
    			'email' => $data['emailadress']
    		),
    		$data
//    		$this->view
    	);
//		if(null !== $at) {
//    		$sendMailObserverToGuest->getMail()->addAttachment($at);
//    	}
    	$observable->addObserver($sendMailObserverToGuest);
    	$sendMailObserverToMe = new Default_SendMailObserver(
    		Default_SendMailObserver::FORM_SEND,
    		array(
    			'from' => $config->emailFrom,
    			'email' => $config->emailCopyTo
    		),
    		$data
//    		$this->view
    	);
//		if(null !== $at) {
//    		$sendMailObserverToMe->getMail()->addAttachment($at);
//    	}
		$observable->addObserver($sendMailObserverToMe);
		
		// dodawanie zalacznika
		$file = new KontorX_Request_Files((array) @$_FILES['zalacznik']);
		$at = null;
		if ($file->isUploaded()) {
			$at = $sendMailObserverToGuest
				->getMail()
				->createAttachment(file_get_contents($file->getFileTempName()));
			$at->filename = $file->getName();
			$sendMailObserverToMe->getMail()
				->addAttachment($at);
		}

		try {
			$observable->notify();
			
			$message = 'Zamówienie zostało wysłane';
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
		} catch (KontorX_Observable_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$message = 'Wystąpił problem z przesyłaniem zamówienia';
			$this->view->messages = array($message);
			$this->view->form = $form->render();
		}
	}
}