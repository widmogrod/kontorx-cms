<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Gallery_CategoryController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_page'
	);

	public $ajaxable = array(
		'list' => array('json')
	);
	
	protected $_modelClass = 'GalleryCategory';

	public function init() {
		parent::init();		
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();
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