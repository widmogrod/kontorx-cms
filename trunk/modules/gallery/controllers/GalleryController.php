<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Gallery_GalleryController extends KontorX_Controller_Action_CRUD {

	public $ajaxable = array(
		'list' => array('json')
	);

	protected $_modelClass = 'Gallery';
	
	public function init() {
		$this->_initLayout('page');
		
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();

		$this->view->messages = $this->_helper->flashMessenger->getMessages();
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
			->limitPage($page, $rowCount);
		// warunek przeszukiwania
    	if ($this->_hasParam('gallery_category_id')) {
			$select->where('gallery_category_id = ?',$this->_getParam('gallery_category_id'));
		}

    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
		$this->_preparePagination($select);
    	
    	return $rowset;
    }

	/**
     * @Overwrite
     */
    protected function _addGetForm() {
    	$form = parent::_addGetForm();

		$model = $this->_getModel();
    	$select = $model->select()
    		->where('url = ?', $this->_request->getPost('url'));

    	// setup @see Zend_Form
    	$this->_setupZendForm($form, $model, $select);

    	return $form;
    }

    /**
     * @Overwrite
     */
    protected function _addOnIsPost(Zend_Form $form) {
    	$form
    		->setDefault('gallery_category_id', $this->_getParam('gallery_category_id'))
    		->setDefault('name',$this->_getParam('news_name'))
    		->setDefault('url',$this->_getParam('news_url'));
    	parent::_addOnIsPost($form);
    }
    
	/**
     * @Overwrite
     */
    protected function _addPrepareData(Zend_Form $form) {
    	$data = parent::_addPrepareData($form);
    	
    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	$data['user_id'] 	  		 = $userId;
    	$data['gallery_category_id'] = $this->_getParam('gallery_category_id');
    	return $data;
    }

	/**
     * @Overwrite
     */
    public function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
    	$form = parent::_editGetForm($row);
    	
    	$model = $this->_getModel();
    	$select = $model->select()
    		->where('url = ?', $this->_request->getPost('url'))
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
     * Ustawia opcje formularza
     *
     * @param Zend_Folm $form
     * @param Zend_Db_Table_Abstract $model
     * @param Zend_Db_Select $select
     */
    protected function _setupZendForm(Zend_Form $form, Zend_Db_Table_Abstract $model, Zend_Db_Select $select) {
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