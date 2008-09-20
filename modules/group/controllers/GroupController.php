<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Group_GroupController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_group'
	);

	/**
     * @Overwrite
     */
	protected $_modelClass = 'Group';

//	/**
//     * @Overwrite
//     */
//	public function init() {
//		$this->_initLayout('group');
//		$this->view->messages = $this->_helper->flashMessenger->getMessages();
//	}

    public function indexAction(){
    	$this->_forward('list');
    }

    /**
     * @Overwrite
     */
    protected function _listFetchAll() {
    	$model = $this->_getModel();
		// przygotowanie zapytania select
    	$select = $model->selectForRowOwner($this->getRequest());
		// zaznaczenie paginacji
    	$rowset = $model->fetchAll($select);
    	// przygotowanie paginacji
    	$this->_preparePagination($select);
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

    	$data['user_id'] = ($data['user_id'] == '')
    		? $userId
    		: $data['user_id'];

    	return $data;
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
    		->where('url = ?', $this->_request->getPost('url'));

    	require_once 'KontorX/Validate/DbTable.php';
    	$nameValid = new KontorX_Validate_DbTable($model, $select);

    	$form
    		->getElement('url')
    		->addValidator($nameValid);

		require_once 'KontorX/Filter/Word/Rewrite.php';
		$form->getElement('url')
			->addFilter(new KontorX_Filter_Word_Rewrite());
    }
}