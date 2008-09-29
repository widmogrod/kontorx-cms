<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Page_BlocksController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_page'
	);

	protected $_modelClass = 'Blocks';

    public function indexAction(){
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
			->limitPage($page, $rowCount);
		// male filtrowanie
		if ($this->_hasParam('page_id')) {
			$select->where('page_id = ?', $this->_getParam('page_id'));
		}

    	$rowset = $model->fetchAll($select);

		// paginacja
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
    		->where('name = ?', $this->_request->getPost('name'));

    	require_once 'KontorX/Validate/DbTable.php';
    	$nameValid = new KontorX_Validate_DbTable($model, $select);

    	$form
    		->getElement('name')
    		->addValidator($nameValid);

		require_once 'KontorX/Filter/Word/Rewrite.php';
		$form->getElement('name')
			->addFilter(new KontorX_Filter_Word_Rewrite());
    }
}