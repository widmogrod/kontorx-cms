<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class News_CommentController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_page'
	);

	protected $_modelClass = 'NewsComment';

	public function init() {
		parent::init();
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

    	$model = $this->_getModel();
    	$db = $model->getAdapter();
    	
    	// select dla danych
		$select = $model->select();
		$select
			->order('t_create DESC')
			->limitPage($page, $rowCount);

		// warunki przeszukiwania
		if ($this->_hasParam('news_id')) {
			$select->where('news_id = ?', $this->_getParam('news_id'));
		}

    	$rowset = $model->fetchAll($select);

		// paginacja
		$this->_preparePagination($select);
    	
    	return $rowset;
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
    			|| User::hasCredential(User::PRIVILAGE_MODERATE, 'comment', 'news')) {
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
    	$data['news_id'] = $this->_getParam('news_id');
    	return $data;
    }
}