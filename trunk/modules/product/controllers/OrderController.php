<?php
require_once 'KontorX/Controller/Action.php';
class Product_OrderController extends KontorX_Controller_Action {

	public function init() {
		//$this->_initLayout(null,null,null,'default');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

	/**
	 * Formularz zamówienia dla kalendarze.kr.com.pl
	 *
	 */
	public function indexAction() {
		// zaladowanie formularza
		$config = $this->_helper->loader->config('order.ini');
		$form = new Zend_Form($config->form->zamowienie);

		// delikatna modyfikacja formularza
		$zalacznik = $form->getElement('zalacznik');
		$form->addElement(new KontorX_Form_Element_File('zalacznik', array(
			'label' => $zalacznik->getLabel(),
			'name' => $zalacznik->getName(),
		)));
		require_once 'KontorX/Validate/NIP.php';
		$form->getElement('NIP')
			->addValidator(new KontorX_Validate_NIP());

		if (!$this->_request->isPost()) {
			// inicjujemy widok
			$this->_initLayout('product-order',null,null,'default');
			$this->view->form = $form->render();
			return;
		}

		if (!$form->isValid($_POST)) {
			// inicjujemy widok
			$this->_initLayout('product-order',null,null,'default');
			$this->view->form = $form->render();
			return;
		}

		require_once 'product/models/Cart.php';
		$cart = new Cart();

		if (!$cart->hasProducts()) {
			$this->view->messages = 'Nie wybrano produktów';
			$this->view->form = $form->render();
			return;
		}

		$this->view->rowset = $cart->getProducts();
		
		$data = $form->getValues();
		$data = get_magic_quotes_gpc() ? array_map('stripslashes', $data) : $data;
		$this->view->assign($data);

		$observable = new KontorX_Observable();

    	require_once 'product/models/observers/SendMail.php';
    	$sendMailObserverToGuest = new Product_Model_Observer_SendMail(
    		Product_Model_Observer_SendMail::CHECKOUT_SUCCESS,
    		array(
    			'from' => $config->emailFrom,
    			'email' => $data['emailadress']
    		),
    		$data,
    		clone $this->view
    	);
    	$observable->addObserver($sendMailObserverToGuest);
    	$sendMailObserverToMe = new Product_Model_Observer_SendMail(
    		Product_Model_Observer_SendMail::CHECKOUT_SUCCESS,
    		array(
    			'from' => $config->emailFrom,
    			'email' => $config->emailCopyTo
    		),
    		$data,
    		clone $this->view
    	);
		$observable->addObserver($sendMailObserverToMe);

		// dodawanie zalacznika
		require_once 'KontorX/File/Upload.php';
		$file = new KontorX_File_Upload((array) @$_FILES['zalacznik']);
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
			$this->_helper->viewRenderer('index.form.success');
			$this->_initLayout('product-order-clean', null, null, 'default');
//			$this->_helper->flashMessenger->addMessage($message);
//			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
		} catch (KontorX_Observable_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			// inicjujemy widok
			$this->_initLayout('product-order',null,null,'default');
			
			$message = 'Wystąpił problem z przesyłaniem zamówienia';
			$this->view->messages = array($message);
			$this->view->form = $form->render();
		}
	}
	
	/**
	 * Wylistowanie zawartości z zamawianymi produktami
	 * 
	 * Wylistowanie zawartości z zamawianymi produktami, wymagane
	 * jest zatwierdzenie zgody z regulaminem i możemy zamawiać
	 */
	public function checkoutAction() {
		$this->_initLayout(null,null,null,'default');

		require_once 'product/models/Cart.php';
		$cart = new Cart();

		if (!$cart->hasProducts()) {
			$this->_helper->viewRenderer->render('checkout.empty');
			return;
		}

		$this->view->rowset = $cart->getProducts();

		if (!$this->_request->isPost()) {
			return;
		}

		if ($this->_request->getPost('accept') != '1') {
			$this->view->messages = "Musisz zaakceptować regulamin, by kontynuować realizacje zamówienia";
			return;
		}
		
		$this->_helper->redirector->goto('checkoutAccept','cart','default');
	}
}