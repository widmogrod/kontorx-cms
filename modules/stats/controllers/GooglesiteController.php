<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Stats_GoogleSiteController extends KontorX_Controller_Action_CRUD {
	protected $_modelClass = 'GoogleSite';

//	public $ajaxable = array(
//		'list' => array('json'),
//		'delete' => array('json')
//	);
	
	public function init() {
		$this->_initLayout('product');
//		$this->_helper->ajaxContext()
//			->setAutoJsonSerialization(false)
//			->initContext();

		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		
		$this->view->product_id = $this->_getParam('product_id');
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
    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
    	require_once 'Zend/Db/Select.php';
    	$select = new Zend_Db_Select($db);
    	$select
    		->from(array('table' => $model->info(Zend_Db_Table::NAME)));

		// paginacja
		require_once 'Zend/Paginator.php';
    	$paginator = Zend_Paginator::factory($select);
    	$paginator->setCurrentPageNumber($page);
    	$paginator->setItemCountPerPage($rowCount);
    	$this->view->paginator = $paginator;

    	return $rowset;
    }
}