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
}

