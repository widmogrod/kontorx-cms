<?php
require_once 'KontorX/Controller/Action/CRUD.php';

class Page_BlockController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_page'
	);

	public $ajaxable = array(
		'list' => array('json')
	);

	protected $_modelClass = 'PageBlock';
	
	public function init() {
		parent::init();
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();
	}

    public function indexAction(){
    	$this->_forward('list');
    }

    /**
	 * @Overwrite
	 */
    protected function _addOnIsPost(Zend_Form $form) {
    	$form->setDefault('page_id', $this->_getParam('page_id'));
    	return parent::_addOnIsPost($form);
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
     * Zrwraca via AJAX bloki nalerzace do strony
     *
     */
    public function forpageAction() {
    	$block = new PageBlock();
    	$page_id = $this->_getParam('page_id');
    	try {
			$rowset = $block->fetchAll($block->select()->where('page_id = ?', $page_id))->toArray();
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$rowset = array();
		}
    	
		$this->_helper->json($rowset);
    }
}

