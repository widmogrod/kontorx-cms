<?php
require_once 'KontorX/Controller/Action.php';
class Catalog_IndexController extends KontorX_Controller_Action {
	
	public $skin = array(
		'layout' => 'catalog',
		'show' => array(
			'layout' => 'catalog_show'
		)
	);

	// TODO Dodać keszowanie parametrow widoku i helperow
	// a moze kesz naglowkow! .. jakoś tak!
	public $cache = array(
		'index' => array('id' => 'params'),
		'az' => array('id' => array('param' => 'string')),
		'show' => array('id' => array('param' => 'id'))
	);

	public function init() {
		parent::init();
		$this->view->addHelperPath('KontorX/View/Helper');
	}
	
	
	public function indexAction() {
		$config = $this->_helper->loader->config('index.xml');

		require_once 'catalog/models/Catalog.php';
		$model = new Catalog();

		$grid = KontorX_DataGrid::factory($model);
		$grid->setColumns($config->dataGridColumns->toArray());
		$grid->setValues((array) $this->_getParam('filter'));
		
		// setup grid paginatior
		$select = $grid->getAdapter()->getSelect();
		$paginator = Zend_Paginator::factory($select);
		$grid->setPaginator($paginator);
		$grid->setPagination($this->_getParam('page'), 30);

		$this->view->grid = $grid;
	}

	public function addAction() {
		
	}
	
	public function updateAction() {
		
	}

	public function jsonAction() {
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();

		require_once 'catalog/models/Catalog.php';
		$catalog = new Catalog();

		$data = $catalog->fetchAllForMap();
		$this->_helper->json($data);
	}
	
	public function mapAction() {
		$config = $this->_helper->loader->config('config.ini');
		$this->view->apiKey = $config->gmap->{BOOTSTRAP}->apiKey;
		
		$this->view->id = (int) $this->_getParam('id');
	}

	public function showAction() {
		$primaryId = $this->_getParam('id');
		require_once 'Zend/Filter.php';
		$primaryId = Zend_Filter::get($primaryId, 'Int');
		
		if (null === $primaryId) {
			$this->_helper->viewRenderer->render('show.error');
			return;
		}
		
		require_once 'catalog/models/Catalog.php';
		$catalog = new Catalog();
		
		try {
			$select = $catalog->select()
				->where('id = ?', $primaryId, Zend_Db::INT_TYPE);
			$catalogRow = $catalog->fetchRow($select);
		} catch (Zend_Db_Table_Abstract $e) {
			$this->_helper->viewRenderer->render('show.error');
			return;
		}
		
		if (!$catalogRow instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('show.error');
			return;
		}

		
		$this->view->catalogRow = $catalogRow;

		$districtRow = $catalogRow->findParentRow('CatalogDistrict');
		if ($districtRow instanceof KontorX_Db_Table_Tree_Row_Abstract) {
			$this->view->districtRowset = $districtRow->findDescendant(null, true);
		}
		
		$this->view->typeRow = $catalogRow->findParentRow('CatalogType');
		$this->view->imagesRowset = $catalogRow->findDependentRowset('CatalogImage');
		$this->view->serviceRowset = $catalogRow->findManyToManyRowset('CatalogService','CatalogServiceCost');
	}

	public function azAction() {
		$this->view->az = $az = array(
			'A','B','C','Ć','D','E','F','G','H',
			'I','J','K','L','Ł','M','N','Ń','O',
			'P','R','S','Ś','T','U','W','Y','Z',
			'Ź','Ż');

		$string = strtoupper($this->_getParam('string','A'));
		if (!in_array($string, $az)) {
			$string = 'A';
		}
		
		$this->view->string = $string;

		$config = $this->_helper->loader->config('index.xml');
		
		require_once 'catalog/models/Catalog.php';
		$model = new Catalog();
		
		$grid = KontorX_DataGrid::factory($model);

		// setup select
		$select = $grid->getAdapter()->getSelect()
			->where('name LIKE ?', "$string%")
			->order('name ASC');
		
		$grid->setColumns($config->dataGridColumnsAZ->toArray());
		$grid->setValues((array) $this->_getParam('filter'));
		
		// setup grid paginatior
		$paginator = Zend_Paginator::factory($select);
		$grid->setPaginator($paginator);
		$grid->setPagination($this->_getParam('page'), 30);

		$this->view->grid = $grid;
	}

	public function categoryAction() {
		$config = $this->_helper->loader->config('index.xml');

		$categoryUrl = $this->_getParam('url', $config->default->category->url);
		$this->view->categoryUrl = $categoryUrl;

		require_once 'catalog/models/CatalogDistrict.php';
		$catalogDistrict = new CatalogDistrict();

		// sprawdzanie czy istnieje kategoria
		$select = $catalogDistrict->select();
		$select
			->where('url = ?', $categoryUrl);;

		try {
			$row = $catalogDistrict->fetchRow($select);
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$row = null;
		}

		// czy aby napewno ..
		if (!$row instanceof KontorX_Db_Table_Tree_Row_Abstract) {
			$this->_helper->viewRenderer->render('category.no.exsists');
			return;
		}

		$this->view->categoryRow = $row;

		require_once 'catalog/models/Catalog.php';
		$model = new Catalog();
		
		$grid = KontorX_DataGrid::factory($model);

		$select = $grid->getAdapter()->getSelect()
			->where('catalog_district_id = ?', $row->id);

		$grid->setColumns($config->dataGridColumns->toArray());
		$grid->setValues((array) $this->_getParam('filter'));
		
		// setup grid paginatior
		$paginator = Zend_Paginator::factory($select);
		$grid->setPaginator($paginator);
		$grid->setPagination($this->_getParam('page'), 30);

		$this->view->grid = $grid;
	}

	public function nearAction() {
		// wylanczamy layout
		$this->_helper->layout->disableLayout();

		$catalogId = $this->_getParam('id');
		
		require_once 'catalog/models/Catalog.php';
		$model = new Catalog();

		$select = $model->select()
			->where('id = ?', $catalogId, Zend_Db::INT_TYPE);
		
		try {
			$row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$row = null;
		}

		// czy aby napewno ..
		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('near.error');
			return;
		}
		
		$select = $model->select()
			->limit(10);

		try {
			$this->view->rowset = $row->findNearRowset($select)->toArray();
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}
}