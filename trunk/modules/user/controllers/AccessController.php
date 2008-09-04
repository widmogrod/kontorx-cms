<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class User_AccessController extends KontorX_Controller_Action_CRUD {
	protected $_modelClass = 'RoleAccess';

	public function init() {
		$this->_initLayout('user');
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
    	$db = $model->getAdapter();

    	// select dla danych
		$select = $model->select();
		$select
			->limitPage($page, $rowCount);

		if ($this->_hasParam('role_resource_id')) {
			$select->where('role_resource_id = ?', $this->_getParam('role_resource_id'));
		}

    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
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
    	$select = $model->select()
    		->where('name = ?', $this->_request->getPost('name'))
    		->where('role_resource_id = ?', $this->_getParam('role_resource_id'));

    	// setup @see Zend_Form
    	$this->_setupZendForm($form, $model, $select);
    	return $form;
    }

    /**
     * @Overwrite
     */
    protected function _addOnIsPost(Zend_Form $form) {
    	$form->setDefault('role_resource_id', $this->_getParam('role_resource_id'));
    	return parent::_addOnIsPost($form);
    }


	/**
     * @Overwrite
     * @return Zend_Form
     */
    protected function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
    	$form = parent::_editGetForm($row);

    	$model = $this->_getModel();
    	$select = $model->select()
    		->where('name = ?', $this->_request->getPost('name'))
    		->where('role_resource_id = ?', $this->_request->getPost('role_resource_id', $row->role_resource_id))
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
    		->getElement('name')
    		->addValidator($nameValid);
    }
}