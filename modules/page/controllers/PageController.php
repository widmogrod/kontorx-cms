<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Page_PageController extends KontorX_Controller_Action_CRUD {

	/**
     * @Overwrite
     */
	protected $_modelClass = 'Page';

	/**
     * @Overwrite
     */
	public function init() {
		$this->_initLayout('page');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

    public function indexAction(){
    	$this->_forward('list');
    }

    public function moveAction() {
    	$model = $this->_getModel();
    	$primatyKey = $this->_getParam('id');
    	try {
    		$row = $model->find($primatyKey)->current();
    	} catch (Zend_Db_Table_Exception $e) {
    		Zend_Registry::get('logger')
    			->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
    		$row = null;
    	}

    	if (!$row instanceof Zend_Db_Table_Row_Abstract) {
    		$message = "Rekord o podanym identyfikatorze nie istnieje";
    		$this->_helper->flashMessenger->addMessage($message);
    		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
    		return;
    	}

    	try {
    		$this->view->rowset = $model->fetchAll();
    	} catch (Zend_Db_Table_Exception $e) {
    		Zend_Registry::get('logger')
    			->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
    	}
    	
    	if (!$this->_request->isPost()) {
    		return;
    	}

    	$parentKey = $this->_request->getPost('parent_key');

    	try {
    		$parentRow = $model->find($parentKey)->current();
    		$row->setParentRow($parentRow);
    		$row->save();
    		$message = "Rekord został przeniesiony";
    	} catch (Zend_Db_Table_Exception $e) {
    		Zend_Registry::get('logger')
    			->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
    		$message = "Rekord NIE został przeniesiony";
    	}
    	
    	$this->_helper->flashMessenger->addMessage($message);
    	$this->_helper->redirector->goToUrlAndExit(
    		$this->_helper->url->url(array())
    	);
    }
    
    /**
     * @Overwrite
     */
    protected function _listFetchAll() {
    	// TODO Dodac `language_url` z konfiguracji
    	$language_url = $this->_getParam('language_url', 'pl');
    	$this->view->language_url = $language_url;

    	$model = $this->_getModel();
    	$db = $model->getAdapter();
    	
    	// select dla danych
		$select = $model->select();
		$select
			->where('language_url = ?', $language_url);

		// przygotowanie zapytania select
    	$model->selectForRowOwner($this->getRequest(), $select);

    	$rowset = $model->fetchAll($select);

    	// pobieranie jezykow
    	require_once 'language/models/Language.php';
    	$language = new Language();
    	$this->view->language = $language->fetchAll();
    	
    	return $rowset;
    }

	/**
     * @Overwrite
     * @return Zend_Form
     */
    protected function _addGetForm() {
    	$form = parent::_addGetForm();

    	$model = $this->_getModel();
    	$select = $model->select();

    	// setup @see Zend_Form
    	$this->_setupZendForm($form, $model, $select);
    	return $form;
    }

    /**
     * @Overwrite
     */
    protected function _addPrepareData(Zend_Form $form) {
    	$data = parent::_addPrepareData($form);
    	
    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	$data['user_id'] 	  = $userId;
    	// TODO Przemysleć url !
    	$data['language_url'] = $this->_getParam('language_url', 'pl');

    	return $data;
    }
    
	/**
     * Overwrite
     */
    protected function _addInsert(Zend_Form $form) {
    	$data = $this->_addPrepareData($form);

    	// dodawanie rekordu
    	$model = $this->_getModel();
    	$row = $model->createRow($data);
    	
    	// jezeli jest rodzic to dodanie do rodzica
    	if($this->_hasParam('parent_id')) {
    		$parentId  = $this->_getParam('parent_id');
    		$where = $model->select()->where('id = ?',$parentId);
    		$parentRow = $model->fetchRow($where);
    		if($parentRow instanceof Zend_Db_Table_Row_Abstract) {
    			$row->setParentRow($parentRow);
    		} else {
    			$message = 'Rekord rodzica nie istnieje';
				$this->_helper->flashMessenger->addMessage($message);
    		}
    	}

    	$row->save();
    	
    	return $row;
    }

    /**
	 * @Overwrite
	 */
	protected function _editFindRecord() {
    	$row = parent::_editFindRecord();
    	if (null === $row) {
    		return $row;
    	}

    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	// czy użytkownik jest właścicielem rekordu ?
    	// czy uzytkownk ma prawo do moderowania ?
    	if ($row->user_id == $userId
    			|| User::hasCredential(User::PRIVILAGE_MODERATE, 'page', 'page')) {
    		return $row;
    	}

    	$message = 'Nie jesteś właścicielem rekordu! oraz nie posiadasz uprawnień by móc go edytować';
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
    	return null;
	}
    
	/**
	 * @Overwrite
	 */
	protected function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
		$form = parent::_editGetForm($row);

    	$model = $this->_getModel();
    	$select = $model->select()
    		->where('id <> ?', $this->_getParam('id'));

    	// setup @see Zend_Form
    	$this->_setupZendForm($form, $model, $select);
    	return $form;
	}

	/**
     * Ustawia opcje formularza
     *
     * @param Zend_Folm $form
     * @param Zend_Db_Table_Abstract $model
     * @param Zend_Db_Select $select
     */
    protected function _setupZendForm(Zend_Form $form, Zend_Db_Table_Abstract $model, Zend_Db_Select $select) {
    	$select
    		->where('url = ?', $this->_request->getPost('url'))
    		// TODO przemyslec url!
    		->where('language_url = ?', $this->_getParam('language_url','pl'));

    	require_once 'KontorX/Validate/DbTable.php';
    	$nameValid = new KontorX_Validate_DbTable($model, $select);

    	$form
    		->getElement('url')
    		->addValidator($nameValid);

		require_once 'KontorX/Filter/Word/Rewrite.php';
		$form->getElement('url')
			->addFilter(new KontorX_Filter_Word_Rewrite());
    }
	
	/**
	 * @Overwrite
	 */
	protected function _modifyInit() {
		$this->_addModificationRule('publicated',self::MODIFY_BOOL);
	}
}