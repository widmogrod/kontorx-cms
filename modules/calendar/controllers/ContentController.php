<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Calendar_ContentController extends KontorX_Controller_Action_CRUD {
	public $skin = array('layout' => 'admin_calendar_calendar');

	protected $_modelClass = 'CalendarContent';
	
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

		if ($this->_hasParam('calendar_id')) {
			$select->where('calendar_id = ?', $this->_getParam('calendar_id'));
		}

    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
    	$this->_preparePagination($select);
    	
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
    	$select = $model->select()
    		->where('calendar_id = ?', $this->_getParam('calendar_id'))
    		->where('language_url = ?', $this->_getParam('language_url'));

    	// setup @see Zend_Form
    	$this->_setupZendForm($form, $model, $select);
    	return $form;
    }
    
    /**
     * @Overwrite
     */
    protected function _addPrepareData(Zend_Form $form) {
    	$data = parent::_addPrepareData($form);

    	// TODO Przemysleć url !
    	$data['language_url'] 	= $this->_getParam('language_url','pl');
    	$data['calendar_id'] 	= $this->_getParam('calendar_id');
    	return $data;
    }

	/**
     * @Overwrite
     * @return Zend_Form
     */
    protected function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
    	$form = parent::_editGetForm($row);

    	$model = $this->_getModel();
    	$select = $model->select()
    		->where('calendar_id = ?', $this->_getParam('calendar_id'))
    		->where('language_url = ?', $this->_getParam('language_url'))
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
    	require_once 'KontorX/Validate/DbTable.php';
    	$nameValid = new KontorX_Validate_DbTable($model, $select);

    	$form
    		->getElement('content')
    		->addValidator($nameValid);
    }
}