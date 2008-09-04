<?php
require_once 'KontorX/Controller/Action.php';
class Group_ManageController extends KontorX_Controller_Action {
	public function init() {
		$this->_initLayout('admin',null,null,'default');

		$this->view->pageUrl  = $this->_getParam('url');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();

		require_once 'user/models/User.php';
		$this->view->userId = User::getAuth(User::AUTH_USERNAME_ID);
	}

	public function usersAction() {
		require_once 'group/models/Group.php';
		$model = new Group();

		$primatyKey = $this->_getParam('group_id');
		
		if (null === $primatyKey) {
			$this->_helper->viewRenderer->render('no.exists');
			return;
		}
		
		// przygotowanie zapytania
		$select = $model->selectForSpecialCredentials($this->getRequest());
		$select->where('id = ?', $primatyKey);

		// odszukaj grupę
		try {
			$this->view->row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$this->view->row instanceof Zend_Db_Table_Row) {
			$this->_helper->viewRenderer->render('no.exists');
			return;
		}
		
		$this->view->form = $this->_getFormUser();
	}

	public function userautocompleteAction() {
		if ('ajax' != $this->_getParam('format', false)) {
			return $this->_helper->redirector('index');
		}
		if ($this->getRequest()->isPost()) {
			return $this->_helper->redirector('index');
		}

		$match = trim($this->getRequest()->getQuery('test', ''));

		require_once 'user/models/User.php';
		$model = new User();
		$select = $model->select()
			->where('username LIKE ?', "%$match%");
		$rowset = $model->fetchAll($select);

		$matched = array();
		foreach ($rowset as $row) {
			$matched[$row->id] = $row->username;
		}

		$this->_helper->autoCompleteDojo($matched);
	}
	
	protected $_formUser;

	/**
	 * Formularz @see Zend_Form
	 * 
	 * @return Zend_Form
	 */
	public function _getFormUser() {   
        if (null === $this->_formUser) {
        	require_once 'Zend/Form.php';
            $this->_formUser = new Zend_Form();
            $this->_formUser->setMethod('get')
                ->setAction($this->_helper->url('userAutocomplete','manage','group'))
                ->addElements(array(
                    'test' => array(
                    	'type' => 'text',
                    	'options' => array(
	                        'filters' => array('StringTrim'),
	                        'label' => 'Nazwa użytkownika:',
                    	)),
                    'go' => array(
                    	'type' => 'submit',
                    	'options' => array(
                    		'label' => 'Go!'
                    	))
                ));
        }
		return $this->_formUser;
	}
	
	/**
	 * Opis grupy
	 *
	 */
	public function descriptionAction() {
		require_once 'group/models/Group.php';
		$model = new Group();

		$primatyKey = $this->_getParam('group_id');
		
		if (null === $primatyKey) {
			$this->_helper->viewRenderer->render('no.exists');
			return;
		}
		
		// przygotowanie zapytania
		$select = $model->selectForSpecialCredentials($this->getRequest());
		$select->where('id = ?', $primatyKey);

		// odszukaj grupę
		try {
			$this->view->row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$this->view->row instanceof Zend_Db_Table_Row) {
			$this->_helper->viewRenderer->render('no.exists');
			return;
		}

		$form = $this->_getFormDescription($model);
		if (!$this->_request->isPost()) {
			$form->setDefaults($this->view->row->toArray());
			$this->view->form = $form;
			return;
		}

		if (!$form->isValid($this->_request->getPost())) {
			$this->view->form = $form;
			return;
		}

		try {
			$this->view->row->setFromArray($form->getValues());
			$this->view->row->save();
			$message = 'Opis został zaktualizowany';
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			$message = 'Opis nie został zaktualizowany';
		}

		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
	}

	/**
	 * Formularz @see Zend_Form
	 * 
	 * @return Zend_Form
	 */
	protected function _getFormDescription(Group $model) {
		require_once 'KontorX/Form/DbTable.php';
		$form = new KontorX_Form_DbTable($model, null, array('user_id','url','name','visible','t_create'));
		$form->getElement('description')->setLabel('Opis')->class = 'editor';
		$form->addElement('Submit','submit',array('label' => 'Zapisz','ignore' => true));
		return $form;
	}
}