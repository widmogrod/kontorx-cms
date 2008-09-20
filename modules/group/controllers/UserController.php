<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Group_UserController extends KontorX_Controller_Action_CRUD {

	public $skin = array(
		'layout' => 'admin_group'
	);

	/**
     * @Overwrite
     */
	protected $_modelClass = 'GroupHasUser';

    public function indexAction(){
    	$this->_forward('list');
    }

    /**
     * @Overwrite
     */
    protected function _listFetchAll() {
    	$model = $this->_getModel();
		// przygotowanie zapytania select
    	$select = $model->select();
    	if ($this->_hasParam('group_id')) {
    		$select->where($this->_getParam('group_id'));
    	}
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
    protected function _addOnIsPost(Zend_Form $form) {
    	$form->setDefault('group_id', $this->_getParam('group_id'));
    	parent::_addOnIsPost($form);
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
    		->where('user_id = ?',  $this->_request->getPost('user_id'))
    		->where('group_id = ?', $this->_request->getPost('group_id'));

    	require_once 'KontorX/Validate/DbTable.php';
    	$nameValid = new KontorX_Validate_DbTable($model, $select);

    	$form
    		->getElement('user_id')
    		->addValidator($nameValid);
    }
}