<?php
class Product_IndexController extends KontorX_Controller_Action {
	
	public $ajaxable = array(
		'category' => array('json')
	);
	
	public function init() {
		$this->_initLayout(null,null,null,'default');
		
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();
		
		$this->view->messages = $this->_helper->flashMessenger->getMessages();

		$this->view->product_category_id  = $this->_getParam('cid');
		$this->view->product_category_url = $this->_getParam('url');
	}

	public function indexAction() {
		//$this->_initLayout('product',null,null,'default');

		require_once 'Product.php';
		$product = new Product();
		$db = $product->getAdapter();

		$select = new Zend_Db_Select($db);
		$select
			->from(array('p' => 'product'))
			->joinLeft(
				array('pi' => 'product_image'),
				"(pi.product_id = p.id AND pi.main = 1)",
				array('image')
			);

		$this->view->products = $select->query()->fetchAll(Zend_Db::FETCH_CLASS);
	}

	public function productAction() {
		$this->_initLayout('product',null,null,'default');

		require_once 'product/models/Product.php';
		$model = new Product();

		$select = $model->select();
		$select
			->where('url = ?', $this->_getParam('url'))
			->orWhere('id = ?', $this->_getParam('id'))
			->where('publicated = 1');	// tylko opublikowane

		try {
			$row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$row = null;
		}
		
		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('product.noexsists');
			return;
		}
		
		$this->view->row = $row;
		$this->view->images = $row->findDependentRowset('ProductImage');
	}
	
	public function categoryAction() {
		//$this->_initLayout('product',null,null,'default');

		$categoryId = $this->_getParam('id');
		$this->view->category_id = $categoryId;

		require_once 'product/models/ProductCategory.php';
		$productCategory = new ProductCategory();

		// sprawdzanie czy istnieje kategoria
		$select = $productCategory->select();
		$select
			->where('id = ?', $categoryId)
			->orWhere('url = ?', $this->_getParam('url'))
			->where('publicated = 1');

		try {
			$row = $productCategory->fetchRow($select);
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$row = null;
		}

		// czy aby napewno ..
		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('category.noexsists');
			return;
		}

		$this->view->row = $row;

		// pobranie kategorii przypisanych
		try {
			$this->view->rowset = $row->findDependentProductsRowset();
		} catch(Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}
	
	public function manufacturerAction() {
		
	}
}