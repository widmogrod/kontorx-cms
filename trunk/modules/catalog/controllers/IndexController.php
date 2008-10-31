<?php
require_once 'KontorX/Controller/Action.php';
class Catalog_IndexController extends KontorX_Controller_Action {
	
	public $skin = array(
		'layout' => 'catalog',
		'show' => array(
			'layout' => 'catalog_show'
		)
	);
	
	public function indexAction() {
		$this->view->addHelperPath('KontorX/View/Helper');
		
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
	
	public function mapAction() {
		
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

		$this->view->row = $row;

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
}