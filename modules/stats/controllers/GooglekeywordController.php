<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Stats_GoogleKeywordController extends KontorX_Controller_Action_CRUD {
	protected $_modelClass = 'GoogleKeyword';

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
		// opcje wyszukiwania
		if ($this->_hasParam('site_id')) {
			$select->where('site_id = ?', $this->_getParam('site_id'));
		}
    	$rowset = $model->fetchAll($select);

    	$select->reset(Zend_Db_Select::LIMIT_COUNT);
    	$select->reset(Zend_Db_Select::LIMIT_OFFSET);

//    	// select dla paginacji
//    	require_once 'Zend/Db/Select.php';
//    	$select = new Zend_Db_Select($db);
//    	$select
//    		->from(array('table' => $model->info(Zend_Db_Table::NAME)));

		// paginacja
		require_once 'Zend/Paginator.php';
    	$paginator = Zend_Paginator::factory($select);
    	$paginator->setCurrentPageNumber($page);
    	$paginator->setItemCountPerPage($rowCount);
    	$this->view->paginator = $paginator;

    	return $rowset;
    }

    /**
	 * @Overwrite
	 */
    protected function _addOnIsPost(Zend_Form $form) {
    	$form->setDefault('site_id', $this->_getParam('site_id'));
    	return parent::_addOnIsPost($form);
    }

    protected function _addOnSuccess(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	// tworzenie komunikatu
    	$message = 'Rekord został dodany';
		$this->_helper->flashMessenger->addMessage($message);

		$referer = $this->_getParam('referer');
		if (null !== $referer) {
			$this->_helper->redirector->goToUrlAndExit($referer);
		} else {
			$this->_helper->redirector->goToAndExit('add',null,null,array('site_id' => $this->_getParam('site_id')));
		}
    }
}