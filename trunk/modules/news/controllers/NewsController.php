<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class News_NewsController extends KontorX_Controller_Action_CRUD {

	protected $_modelClass = 'News';

	public function init() {
		$this->_initLayout('page');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();

		$this->view->news_id = $this->_getParam('id');
	}

	public function indexAction() {
		$this->_forward('list');
	}

	/**
     * @Overwrite
     */
    protected function _listFetchAll() {
    	$page = $this->_getParam('page',1);
    	$rowCount = $this->_getParam('rowCount',30);
    	// TODO Dodac `language_url` z konfiguracji
    	$language_url = $this->_getParam('language_url', 'pl');
    	$this->view->language_url = $language_url;

    	$model = $this->_getModel();
    	$db = $model->getAdapter();

    	// select dla danych
		$select = $model->select();
		$select
			->where('language_url = ?', $language_url)
			->order('t_create DESC')
			->limitPage($page, $rowCount);

		// przygotowanie zapytania select
    	$model->selectForRowOwner($this->getRequest(), $select);

    	$rowset = $model->fetchAll($select);

		// paginacja
		$this->_preparePagination($select);

    	// pobieranie jezykow
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
    protected function _addOnSuccess(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	// tworzenie komunikatu
    	$message = 'Rekord został dodany';
		$this->_helper->flashMessenger->addMessage($message);

		$referer = $this->_getParam('referer');
		if (null !== $referer) {
			$this->_helper->redirector->goToUrlAndExit($referer);
		} else {
			$this->_helper->redirector->goToAndExit('edit',null,null,array('id' => $row->id));
		}
	}

	/**
     * @Overwrite
     * @return Zend_Form
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
    			|| User::hasCredential(User::PRIVILAGE_MODERATE, $this->getRequest())) {
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