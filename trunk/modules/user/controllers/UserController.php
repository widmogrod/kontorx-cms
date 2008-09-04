<?php
require_once 'KontorX/Controller/Action.php';
class User_UserController extends KontorX_Controller_Action {

	public function init() {
		$this->_initLayout('user',null,null,'default');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

	public function indexAction() {
		$this->_forward('settings');
	}

	public function displayAction() {
		$this->_initLayout('full',null,null,'default');
		$userId = $this->_getParam('id', $this->_getUserId());

		$model = $this->_getModel();

		try {
			$row = $model->find($userId)->current();
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$row instanceof Zend_Db_Table_Row) {
			$this->_helper->viewRenderer->render('display.no.exsists');
			return;
		}

		$this->view->row = $row;
		
		// dane personalne
		try {
			$this->view->rowPersonal = $row->findDependentRowset('UserPersonal')->current();
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		// właściciel grup
		try {
			require_once 'group/models/Group.php';
			$this->view->rowsetGroup = $row->findDependentRowset('Group');
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}
	
	/**
	 * Wyświetla grupy, których założycielem jest dany użytkownik
	 *
	 */
	public function groupAction() {
		require_once 'group/models/Group.php';
		$group = new Group();

		$select = $group->select()
			->where('user_id = ?', $this->_getUserId());

		try {
			$this->view->rowset = $group->fetchAll($select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	/**
	 * Ustawienie danych osobowych
	 *
	 */
	public function personalAction() {
		$model = $this->_getPersonalModel();

		// przygotowanie zapytania
		$select = $model->select()
			->limit(1)
			->where('user_id = ?', $this->_getUserId());

		// odszukaj dane
		try {
			$row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Exception $e) {
			$row = null;
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$row instanceof Zend_Db_Table_Row) {
			$row = $model->createRow();
		}

		$form  = $this->_getPersonalForm();
		if (!$this->_request->isPost()) {
			$form->setDefaults($row->toArray());
			$this->view->form = $form;
			return;
		}

		if (!$form->isValid($this->_request->getPost())) {
			$this->view->form = $form;
			return;
		}

		$data = $form->getValues();
		$data['user_id'] = $this->_getUserId();
		$data = $this->_prepareData($data);

		try {
			$row->setFromArray($data);
			$row->save();
			$message = "Twoje dane zostały zapisane";
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			$message = "Twoje dane NIE zostały zapisane";
		}
		
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
	}

	protected $_personalModel = null;

	/**
	 * Zwraca obiekt @KontorX_Db_Table_Abstract
	 *
	 * @return KontorX_Db_Table_Abstract
	 */
	protected function _getPersonalModel() {
		if (null === $this->_personalModel) {
			require_once 'user/models/UserPersonal.php';
			$this->_personalModel = new UserPersonal();
		}
		return $this->_personalModel;
	}
	
	/**
	 * Zwraca obiekt @Zend_Form
	 *
	 * @return Zend_Form
	 */
	protected function _getPersonalForm() {
		$model = $this->_getPersonalModel();

		$config = $this->_helper->loader->config('personal.ini');
		
		$options = $config->form;
		$ignoreColumns = $config->default->ignore->toArray();

		require_once 'KontorX/Form/DbTable.php';
		$form = new KontorX_Form_DbTable($model, $options, $ignoreColumns);
		$form->addDecorator('Description');
		$form->addElement('submit','submit',array('label' => 'Zapisz dane!','ignore' => true));
		return $form;
	}

	/**
     * Przygotowanie danych
     *
     * @param array $data
     * @return array
     */
    protected function _prepareData(array $data) {
    	$data = get_magic_quotes_gpc() ? array_map('stripslashes', $data) : $data;
    	return $data;
    }

	protected $_userId = null;
	
	protected function _getUserId() {
		if (null === $this->_userId) {
			require_once 'user/models/User.php';
			$this->_userId = User::getAuth(User::AUTH_USERNAME_ID);
		}
		return $this->_userId;
	}

	/**
	 * Strona ustawień
	 *
	 */
	public function settingsAction() {
		$password  = $this->_getFormChangePassword();
		$interface = $this->_getFormChangeInterface();

		$this->view->password  = $password->render();
		$this->view->interface = $interface->render();
	}

	/**
	 * Zmiana hasła
	 *
	 */
	public function passwordAction() {
		require_once 'user/models/User.php';
		$userId			= User::getAuth(User::AUTH_USERNAME_ID);
		$userUsername	= User::getAuth(User::AUTH_EMAIL);

		$form = $this->_getFormChangePassword();

		if (!$this->_request->isPost()) {
			$this->view->form = $form->render();
			return;
		}

		if (!$form->isValid($this->_request->getPost())) {
			$this->view->form = $form->render();
			return;
		}

		// ma być wyżej dlatego ze wczytuje @see User
		// a nizej wywolujemy User:: - statycznie
		$model = $this->_getModel();

		// przygotowanie walidacji formularza
		$password = User::saltPassword($userUsername, $form->getValue('password_n'));
		
		$select = $model->select()
			->where('id = ?', $userId);

		try {
			$row = $model->fetchRow($select);
			$row->password = $password;
			$row->save();
			$message = "Hasło zostało zmienione";
		} catch (Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$message = "Hasło NIE zostało zmienione!";
		}

		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
	}

	/**
	 * Zmiana interfejsu
	 *
	 */
	public function interfaceAction() {
		$userId			= User::getAuth(User::AUTH_USERNAME_ID);
		$userInterface  = User::getAuth(User::AUTH_INTERFACE);
		
		$form = $this->_getFormChangeInterface();
		
		if (!$this->_request->isPost()) {
			$form->setDefault('interface', $userInterface);
			$this->view->form = $form->render();
			return;
		}

		if (!$form->isValid($this->_request->getPost())) {
			$this->view->form = $form->render();
			return;
		}

		// ma być wyżej dlatego ze wczytuje @see User
		// a nizej wywolujemy User:: - statycznie
		$model = $this->_getModel();

		$select = $model->select()
			->where('id = ?', $userId);

		// przygotowanie walidacji formularza
		$interface = $form->getValue('interface');
		// TODO Walidacja poprawnosci interfejsu!

		try {
			$row = $model->fetchRow($select);
			$row->interface = $interface;
			$row->save();
			$message = "Interfejs został zmieniony";
		} catch (Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$message = "Interfejs nie został zmieniony";
		}

		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
	}
	
	/**
	 * Zwraca obiekt @see Zend_Form
	 *
	 * @return Zend_Form
	 */
	protected function _getFormChangePassword() {
		$config = $this->_helper->loader->config('user.ini');
		// towrzenie nowej instancji Zend_Form
		require_once 'Zend/Form.php';
		$form = new Zend_Form($config->form->change_password);

		// ma być wyżej dlatego ze wczytuje @see User
		// a nizej wywolujemy User:: - statycznie
		$model = $this->_getModel();
		
		// przygotowanie walidacji formularza
		$username = User::getAuth(User::AUTH_EMAIL);
		$password = User::saltPassword($username, $this->_request->getPost('password'));

		$select = $model->select()
			->where('password = ?', $password)
			->where('id = ?', User::getAuth(User::AUTH_USERNAME_ID));

		$form->getElement('password')
			->addValidator(new KontorX_Validate_DbTable($model, $select, true));
		
		$form->getElement('password_n')
			->addValidator(new KontorX_Validate_Compare($this->_request->getPost('password_r'), true));

		return $form;
	}

	/**
	 * Zwraca obiekt @see Zend_Form
	 *
	 * @return Zend_Form
	 */
	protected function _getFormChangeInterface() {
		$config = $this->_helper->loader->config('user.ini');
		// towrzenie nowej instancji Zend_Form
		require_once 'Zend/Form.php';
		$form = new Zend_Form($config->form->change_interface);

		$this->_getModel(); // tylko poto ze zalancza klase @see User
		$form->setDefault('interface', User::getAuth(User::AUTH_INTERFACE));
		
		return $form;
	}

	protected $_model = null;
	
	/**
	 * Instancja @see User
	 *
	 * @return User
	 */
	protected function _getModel() {
		if (null === $this->_model) {
			require_once 'user/models/User.php';
			$this->_model = new User();
		}
		return $this->_model;
	}
}
?>