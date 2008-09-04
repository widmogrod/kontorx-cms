<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Calendar_CalendarController extends KontorX_Controller_Action_CRUD {
	protected $_modelClass = 'Calendar';

	public function init() {
		$this->_initLayout('calendar_calendar');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
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

    	$model = $this->_getModel();
    	
    	// select dla danych
		$select = $model->select();
		$select
			->limitPage($page, $rowCount);

		// przygotowanie zapytania select
    	$model->selectForRowOwner($this->getRequest(), $select);

		$rowset = $model->fetchAll($select);

    	// select dla paginacji
    	$this->_preparePagination($select);
    	
    	return $rowset;
    }

	/**
     * @Overwrite
     */
    protected function _addPrepareData(Zend_Form $form) {
    	$data = parent::_addPrepareData($form);

    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	$data['user_id'] 	  = $userId;
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

    	$request = $this->getRequest();
    	$controller = $request->getControllerName();
    	$module	 	= $request->getModuleName();

    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	// czy użytkownik jest właścicielem rekordu ?
    	// czy uzytkownk ma prawo do moderowania ?
    	if ($row->user_id == $userId
    			|| User::hasCredential(User::PRIVILAGE_MODERATE, $controller, $module)) {
    		return $row;
    	}

    	$message = 'Nie jesteś właścicielem rekordu! oraz nie posiadasz uprawnień by móc go edytować';
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
    	return null;
	}
}