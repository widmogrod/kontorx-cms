<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Gallery_DescriptionController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_page'
	);

	protected $_modelClass = 'GalleryDescription';
	
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
    	if ($this->_hasParam('gallery_id')) {
			$select->where('gallery_id = ?',$this->_getParam('gallery_id'));
		}

    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
		$this->_preparePagination($select);
    	
    	return $rowset;
    }

    /**
	 * @Overwrite
	 */
	protected function _addOnIsPost(Zend_Form $form) {
		$form->setDefault('gallery_id', $this->_getParam('gallery_id'));
		parent::_addOnIsPost($form);
	}
}
?>