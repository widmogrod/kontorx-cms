<?php
//require_once 'admin/models/observers/SendMailObserver.php';
require_once 'KontorX/Controller/Action.php';
class User_AuthController extends KontorX_Controller_Action {
	
	public function init() {	
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		$this->_initLayout(null,'user_login');
	}

	public function indexAction() {
		$this->_forward('login');
	}

	public function loginAction() {
		$config = $this->_helper->loader->config('auth.ini');

		$form = new Zend_Form($config->form->login);

		if (!$this->_request->isPost()) {
			$this->view->form = $form->render();
			return;
		}

		if (!$form->isValid($_POST)) {
			$this->view->form = $form->render();
			return;
		}

		$db = KontorX_Db_Table_Abstract::getDefaultAdapter();

		$authAdapter = new Zend_Auth_Adapter_DbTable($db);
		$authAdapter->setTableName('user');
		$authAdapter->setIdentityColumn('email');
		$authAdapter->setCredentialColumn('password');
		$authAdapter->setCredentialTreatment("? AND registered = 1");

		$email 	  = $form->getValue('email');
		$password = $form->getValue('password');
		$password = User::saltPassword($email, $password);

		$authAdapter->setIdentity($email);
		$authAdapter->setCredential($password);

		$auth = Zend_Auth::getInstance();
		$result = $auth->authenticate($authAdapter);

		if (!$result->isValid()) {
			// logowanie zdarzeń
			$logger = Zend_Registry::get('logger');
			$logger->log(implode("\n", $result->getMessages()), Zend_Log::NOTICE);

			$message = "Zostały podane niepoprawne dane, lub twoje konto nie zostało aktywowane";
			$this->view->messages = array($message);

			$this->view->form = $form->render();
			return;
		}

		Zend_Session::regenerateId();

		User::setIdentity($authAdapter);
		$identity = $result->getIdentity();
		
		// dodawanie czas ostatniego logowania
		try {
			$user = new User();
			$where = array();
			$where[] = 'registered = 1';
			$where[] = $user->getAdapter()->quoteInto('email = ?', $email);
			$where[] = $user->getAdapter()->quoteInto('password = ?', $password);
			$row = $user->fetchRow(implode(' AND ', $where));
			if ($row instanceof Zend_Db_Table_Row_Abstract) {
				$row->last_visite = date('Y-m-d H:i:s');
				$row->save();
			} else {
				// logowanie zdarzeń
				$logger = Zend_Registry::get('logger');
				$logger->log("Zalogowano użytkownika `$email` a nie znaleziono rekordu", Zend_Log::DEBUG);
			}
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie zdarzeń
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::NOTICE);
		}
		
		$message = "Zostałeś zalogowany pomyślnie";
		$this->_helper->flashMessenger->addMessage($message);

		if (!strlen(getenv('HTTP_REFERER'))) {
			$this->_helper->redirector->goToAndExit('index','index','admin');
		} else {
			$this->_helper->redirector->goToAndExit('index','index','admin');
//			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
		}
	}
	
	public function logoutAction() {
		Zend_Auth::getInstance()->clearIdentity();
		$this->_forward('login');
		$this->_helper->redirector->goToUrl(getenv('HTTP_REFERER'));
	}
}