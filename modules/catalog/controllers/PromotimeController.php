<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Catalog_PromotimeController extends KontorX_Controller_Action_CRUD {
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
	
	protected $_modelClass = 'CatalogPromoTime';
	
	protected function _getConfigFormFilename($controller) {
		return strtolower("$controller.xml");
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
		
		$config = $this->_helper->loader->config('promotime.xml');
		
		$model = $this->_getModel();

		$select = new Zend_Db_Select($model->getAdapter());
		$select
			->from(array('cpt' => 'catalog_promo_time'),'*')
			->joinLeft(array('c' => 'catalog'),
					'cpt.catalog_id = c.id',
						array('c.name'))
			->joinLeft(array('cptp' => 'catalog_promo_type'),
					'cpt.catalog_promo_type_id = cptp.id',
						array('type' => 'cptp.name'));

		$grid = KontorX_DataGrid::factory($select);
		$grid->setColumns($config->dataGridColumns->toArray());
		$grid->setValues((array) $this->_getParam('filter'));
		
		// setup grid paginatior
		$select = $grid->getAdapter()->getSelect();
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