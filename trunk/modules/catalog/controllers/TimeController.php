<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Catalog_TimeController extends KontorX_Controller_Action_CRUD {

	public $skin = array(
		'layout' => 'admin_catalog',
		'config' => array(
			'filename' => 'backend_config.ini'
		)
	);

	public $contexts = array(
		'list' => array('body'),
		'add' => array('body'),
		'edit' => array('body')
	);

	protected $_modelClass = 'CatalogTime';
	
	protected function _getConfigFormFilename($controller) {
    	$controller = strtolower($controller);
    	return "$controller.xml";
    }
	
	public function init() {
		$contextSwitch = $this->_helper->getHelper('ContextSwitch');
		$contextSwitch
			// wylanczam wylaczenie layotu
			->setAutoDisableLayout(false)
			// nowy context
			->addContext('body',array(
				'callbacks' => array(
					'init' => array($this, 'contextSwitchBodyCallback')
				)
			))
			->initContext();

		parent::init();
	}
    
	public function listAction() {
		$this->view->addHelperPath('KontorX/View/Helper');
		
		$config = $this->_helper->loader->config('time.xml');
		
		$model = $this->_getModel();
		$select = new Zend_Db_Select($model->getAdapter());
		$select
			->from(array('ct' => 'catalog_time'),Zend_Db_Select::SQL_WILDCARD)
			->joinLeft(array('c' => 'catalog'),
					'ct.catalog_id = c.id',
						array('c.name'));

		$grid = KontorX_DataGrid::factory($select);
		$grid->setColumns($config->dataGridColumns->toArray());
		$grid->setValues((array) $this->_getParam('filter'));
		
		// setup grid paginatior
//		$select = $grid->getAdapter()->getSelect();
		$paginator = Zend_Paginator::factory($select);
		$grid->setPaginator($paginator);
		$grid->setPagination($this->_getParam('page'), 20);

		$this->view->grid = $grid;
		$this->view->actionUrl = $this->_helper->url('list');
	}
	
	/**
	 * Callback of @see init and @see ContextSwitch
	 * @return void
	 */
	public function contextSwitchBodyCallback() {
		// zmieniam szablon
		$system = $this->_helper->system;
		$system->layout('admin_body');
		$system->setLayoutSectionName('admin_catalog');
		$system->lockLayoutName(true);
	}
}