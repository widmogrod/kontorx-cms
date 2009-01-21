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
//	public $cache = array(
//		'index' => array('id' => 'params'),
//		'az' => array('id' => array('param' => 'string')),
//		'show' => array('id' => array('param' => 'id'))
//	);
	
	public $contexts = array(
		'mapdata' => array('json'),
		'map' => array('html')
	);

	public function init() {
		parent::init();

		$contextSwitch = $this->_helper->getHelper('ContextSwitch');
		if (!$contextSwitch->hasContext('html')) {
			$contextSwitch
				->addContext('html', array(
					'suffix' => 'html',
					'headers'   => array('Content-Type' => 'text/html'),
				));
		}
		$contextSwitch
			->setAutoJsonSerialization(false)
			->initContext();

		$this->view->addHelperPath('KontorX/View/Helper');
	}
	
	public function indexAction() {
		$config = $this->_helper->loader->config('index.xml');

		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$select = new Zend_Db_Select($db);
		
//		$select1 = new Zend_Db_Select($db);
//		$select1
//			->from(array('cpt' => 'catalog_promo_time'),
//					'cpt.catalog_promo_type_id')
//			->joinLeft(array('c' => 'catalog'),
//					'cpt.catalog_id = c.id', '*')
//			->joinLeft(array('ci' => 'catalog_image'),
//					'ci.id = c.catalog_image_id',
//						array('image' => 'ci.image'))
//			->order('cpt.catalog_promo_type_id DESC');
		
		$select = new Zend_Db_Select($db);
		$select
			->from(array('c' => 'catalog'),'*')
			->join(array('cd' => 'catalog_district'),
					'cd.id = c.catalog_district_id',
						array('district' => 'cd.name'))
			->joinLeft(array('cpt' => 'catalog_promo_time'),
					'c.id = cpt.catalog_id '.
//					'AND cpt.catalog_promo_type_id = 3 '.	// sortuje tylko promocujne +
					'AND NOW() BETWEEN cpt.t_start AND cpt.t_end',
						array('cpt.catalog_promo_type_id'))
			->joinLeft(array('ci' => 'catalog_image'),
					'ci.id = c.catalog_image_id',
						array('image' => 'ci.image'))
			->order('cpt.catalog_promo_type_id DESC');
			
						
		$grid = KontorX_DataGrid::factory($select);
		$grid->setColumns($config->dataGridColumns->toArray());
		$grid->setValues((array) $this->_getParam('filter'));
		
//		// setup grid paginatior
//		$select = $grid->getAdapter()->getSelect();
//		
		// inicjowanie alfabetycznego sortowania
		$this->_initAlphabetical($select);
		
		$paginator = Zend_Paginator::factory($select);
		$grid->setPaginator($paginator);
		$grid->setPagination($this->_getParam('page'), 30);

		$this->view->grid = $grid;
	}

	public function addAction() {
		
	}
	
	public function updateAction() {
		
	}
	
	public function errorAction() {
		$config = $this->_helper->loader->config('index.xml');
		
		require_once 'Zend/Form.php';
		$form = new Zend_Form($config->forms->error);

		require_once 'KontorX/Observable/Form.php';
		$observable = new KontorX_Observable_Form($form);
		
		require_once 'catalog/models/Observer/Form.php';
		$formObserver = new Catalog_Observer_Form(
			Catalog_Observer_Form::ERROR_NOTICE,
			$config->config->error
		);
		$observable->addObserver($formObserver);

		$request = $this->getRequest();

		if (!$request->isPost()) {
			$form->setDefaults($this->_getErrorFormDefaultValues());
			$this->view->form = $form;
			return;
		}

		try {
			if (!$observable->isValid($request->getPost())) {
				$this->view->form = $form;
				return;
			}
		} catch (KontorX_Observable_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n". $e->getTraceAsString(),Zend_Log::ERR);
		}

		$message = "Komunikat o błędzie został wysłany, dziękujemy!";
		$this->_helper->flashMessenger($message);
		$this->_helper->redirector->goToUrlAndExit(
			$form->getValue('referer')
		);
	}

	private function _getErrorFormDefaultValues() {
		$referer = getenv("HTTP_REFERER");
		if (null === $referer) {
			$referer = $this->_getParam('id');
		}
		return array(
			'referer' => $referer,
			'message' => "Zostały znalezione następujące błędy:\n".
						 " 1. \n".
						 " 2. \n".
						 " 3. \n"
		);
	}

	public function mapdataAction() {
		require_once 'catalog/models/Catalog.php';
		$catalog = new Catalog();

		try {
			$data   = $catalog->fetchAllForMap();
			$format = $this->_getParam('format','json');
			$catalog->saveCacheMapData($data, $format, PUBLIC_PATHNAME);
			
			$this->view->data = $data;
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('mapdata.error');
		}
	}
	
	public function mapAction() {
		$config = $this->_helper->loader->config('config.ini');
		$this->view->apiKey = $config->gmap->{BOOTSTRAP}->apiKey;
		
		$id = $this->view->id = (int) $this->_getParam('id');
		if ($id > 0) {
			require_once 'catalog/models/Catalog.php';
			$catalog = new Catalog();

			try {
				$this->view->row = $catalog->find($id)->current();
			} catch (Zend_Db_Table_Abstract $e) {
				Zend_Registry::get('logger')
					->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			}
		}		
	}

	public function showAction() {
		$primaryId = $this->_getParam('id');
		require_once 'Zend/Filter.php';
		$primaryId = Zend_Filter::get($primaryId, 'Int');
		
		if (null === $primaryId) {
			$this->_helper->viewRenderer->render('show.error');
			return;
		}

		$config = $this->_helper->loader->config('config.ini');
		$this->view->apiKey = $config->gmap->{BOOTSTRAP}->apiKey;
		
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
		
		$this->_setupModelCatalogType();
		$this->view->typeRow = $catalogRow->findParentRow('CatalogType');
		$this->_setupModelCatalogImage();
		$this->view->imagesRowset = $catalogRow->findDependentRowset('CatalogImage');
		$this->_setupModelCatalogService();
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

		$this->view->categoryRow = $row;

		require_once 'catalog/models/Catalog.php';
		$model = new Catalog();
		
		$grid = KontorX_DataGrid::factory($model);

		$select = $grid->getAdapter()->getSelect()
			->where('catalog_district_id = ?', $row->id);
		
		// inicjowanie alfabetycznego sortowania
		$this->_initAlphabetical($select);

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

	private function _initAlphabetical(Zend_Db_Select $select) {
		$this->view->az = $az = array(
			'A','B','C','Ć','D','E','F','G','H',
			'I','J','K','L','Ł','M','N','Ń','O',
			'P','R','S','Ś','T','U','W','Y','Z',
			'Ź','Ż');

		$string = $this->_getParam('string');
		$string = strtoupper($string);

		$this->view->string = $string;
		
		if (in_array($string, $az)) {
			// setup select
			$select
				->where('name LIKE ?', "$string%")
				->order('name ASC');
		}
	}
	
	private function _setupModelCatalogType() {
		$config = $this->_helper->loader->config();
		$path = $config->path->upload->type;
		$path = $this->_helper->system()->getPublicHtmlPath($path);

		require_once 'catalog/models/CatalogType.php';
		CatalogType_Row::setUploadPath($path);
	}

	private function _setupModelCatalogImage() {
		$config = $this->_helper->loader->config();
		$path = $config->path->upload->image;
		$path = $this->_helper->system()->getPublicHtmlPath($path);

		require_once 'catalog/models/CatalogImage.php';
		CatalogImage_Row::setUploadPath($path);
	}
	
	private function _setupModelCatalogService() {
		$config = $this->_helper->loader->config();
		$path = $config->path->upload->service;
		$path = $this->_helper->system()->getPublicHtmlPath($path);

		require_once 'catalog/models/CatalogService.php';
		CatalogService_Row::setUploadPath($path);
	}
}