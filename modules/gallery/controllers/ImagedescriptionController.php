<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Gallery_ImagedescriptionController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_page'
	);

	protected $_modelClass = 'GalleryImageDescription';
	
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
    	if ($this->_hasParam('gallery_image_id')) {
			$select->where('gallery_image_id = ?',$this->_getParam('gallery_image_id'));
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
		$data['gallery_image_id'] = $this->_getParam('gallery_image_id');

		return $data;
	}
	
	/**
     * @Overwrite
     */
    public function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
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
    		->where('language_url = ?', $this->_request->getPost('language_url'))
    		->where('gallery_image_id = ?' ,$this->_getParam('gallery_image_id'));

    	require_once 'KontorX/Validate/DbTable.php';
    	$nameValid = new KontorX_Validate_DbTable($model, $select);

    	$form
    		->getElement('language_url')
    		->addValidator($nameValid);
    }
}