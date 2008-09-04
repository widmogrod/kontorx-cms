<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Product_ImageController extends KontorX_Controller_Action_CRUD {
	protected $_modelClass = 'ProductImage';

	public $ajaxable = array(
		'list' => array('json'),
		'delete' => array('json')
	);
	
	public function init() {
		$this->_initLayout('product');
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();

		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		
		$this->view->product_id = $this->_getParam('product_id');
	}
	
	public function indexAction() {
		$this->_forward('list');
	}

	/**
	 * @Overwrite
	 */
	protected function _getModel() {
		// tak wysoko jest tworzenie obiektu
		// bo @see GalleryImage_Row nie znajduje autoloader
		// z powodu że znajduje sie w tym samym pliku co GalleryImage ..
		$model = parent::_getModel();

		// pobieranie konfiguracji
		$config = $this->_helper->loader->config();
		// ustawienie sciezki do grafik
		ProductImage_Row::setImagePath($config->path->upload);

		return $model;
	}
	
	/**
	 * @Overwrite
	 */
	protected function _listFetchAll() {
		$page = $this->_getParam('page',1);
    	$rowCount = $this->_getParam('rowCount',30);
    	$productId = $this->_getParam('product_id');

    	$model = $this->_getModel();
    	$db = $model->getAdapter();
    	
    	// select dla danych
		$select = $model->select();
		$select
			->limitPage($page, $rowCount);
		// jezeli kodane id produktu
		if ($this->_hasParam('product_id')) {
			$select->where('product_id = ?', $productId);
		}
    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
    	require_once 'Zend/Db/Select.php';
    	$select = new Zend_Db_Select($db);
    	$select
    		->from(array('table' => $model->info(Zend_Db_Table::NAME)));
    	// jezeli kodane id produktu
		if ($this->_hasParam('product_id')) {
			$select->where('product_id = ?', $productId);
		}

		// paginacja
		require_once 'Zend/Paginator.php';
    	$paginator = Zend_Paginator::factory($select);
    	$paginator->setCurrentPageNumber($page);
    	$paginator->setItemCountPerPage($rowCount);
    	$this->view->paginator = $paginator;
    	
    	return $rowset;
	}
	
	/**
	 * Overwrite
	 */
	protected function _addGetForm() {
		$form = parent::_addGetForm();
		$form->setAttrib('enctype','multipart/form-data');

		require_once 'KontorX/Form/Element/File.php';
		$form->addElement(new KontorX_Form_Element_File('image'), 'image');
		$form->getElement('image')->setLabel('image');

		return $form;
	}

	/**
	 * Overwrite
	 */
	protected function _addOnIsPost(Zend_Form $form) {
		$form->setDefault('product_id', $this->_getParam('product_id'));
		parent::_addOnIsPost($form);
	}

	/**
	 * @Overwrite
	 */
    protected function _addOnSuccess(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	// tworzenie komunikatu
    	$message = 'Rekord został dodany';
		$this->_helper->flashMessenger->addMessage($message);

		$referer = $this->_getParam('referer');
		if (null !== $referer) {
			$this->_helper->redirector->goToUrlAndExit($referer);
		} else {
			$this->_helper->redirector->goToAndExit('add', null, null,array('product_id' => $this->_getParam('product_id')));
		}
	}
	
	/**
	 * Overwrite
	 */
	protected function _editGetForm() {
		$form = parent::_editGetForm();
		$form->setAttrib('enctype','multipart/form-data');

		require_once 'KontorX/Form/Element/File.php';
		$form->addElement(new KontorX_Form_Element_File('image'), 'image');
		$form->getElement('image')
			->setLabel('image')
			->setIgnore(true);

		return $form;
	}

	/**
	 * Overwrite
	 */
	protected function _editFindRecord() {
		$row = parent::_editFindRecord();
		
		if ($row instanceof Zend_Db_Table_Row_Abstract) {
			$this->view->product_id = $row->product_id;
		}

		return $row;
	}
	
	/**
	 * @Overwrite
	 */
	protected function _deleteOnSuccess(Zend_Db_Table_Row_Abstract $row) {
		// gdy zadanie via AJAX
		if ($this->_request->isXmlHttpRequest()) {
			$this->view->success = true;
			// wlaczenie widoku
			$this->_helper->viewRenderer->setNoRender(false);
		} else {
			parent::_deleteOnSuccess($row);
		}
	}
	
	/**
	 * Generowanie miniaturek
	 * 
	 * TODO Optymalizacja
	 */
	public function thumbAction() {
		// wylaczenie renderowania widoku
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		// pobieranie konfiguracji
		$config = $this->_helper->loader->config();
		$uploadPath = $config->path->upload;
		$noImagePath = $config->default->image;

		$type = $this->_getParam('type');

		$imageName = $this->_getParam('file');
		$imagePath = $uploadPath . '/' . basename($imageName);

		// na starcie wysylane sa naglowki - to przez cache!
		$this->_response->setHeader('Content-type', image_type_to_mime_type(IMAGETYPE_JPEG));

//		$cache = Zend_Registry::get('cacheOutputImages');
//		// rozpoczęcie keszowania
//		if ($cache->start(sha1("$imagePath-$type")) !== false) {
//			return;
//		}

		// czy obrazek istnieje
		if (!is_file($imagePath)) {
			// logowanie zdarzeń
			$logger = Zend_Registry::get('logger');
			$logger->log("::thumbAction() noe exsists $imagePath", Zend_Log::DEBUG);

			$this->_helper->redirector->goToUrlAndExit($this->baseURL  .$noImagePath);
		}

		try {
			$image = new KontorX_Image($imagePath);
		} catch (KontorX_Image_Exception $e) {
			// logowanie zdarzeń
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" .  $e->getTraceAsString(), Zend_Log::NOTICE);

			$this->_helper->redirector->goToUrlAndExit($this->baseURL  .$noImagePath);
		}

		$types = $config->image->resize->type->toArray();

		if (array_key_exists($type, $types)) {
			$x 		= @$types[$type]['x'];
			$y 		= @$types[$type]['y'];
			$xCrop 	= @$types[$type]['crop']['x'];
			$yCrop 	= @$types[$type]['crop']['y'];
			$style 	= @$types[$type]['style'];

			switch ($style) {
				case 'max':
					if (is_numeric($x) && null === $y) {
						$image->resizeToMaxWidth($x);
					} else
					if (is_numeric($y) && null === $x) {
						$image->resizeToMaxHeight($y);
					} else
					if (is_numeric($x) && is_numeric($y)) {
						$image->resizeToMax($x, $y);
					}
					break;
				case 'crop':
					$image->resizeToMaxWidth($x);
					$image->crop(0,0,$xCrop,$yCrop);
					break;
				default: $image->resize($x, $y);
			}
		} else {
			$image->resize(50,50);
		}

		// print - bo naglowej jest wysylany wyzej
		$img = $image->display(IMAGETYPE_JPEG, null, true);
		$this->_response->setBody($img);
		
		// zapisywanie miniaturki do odpowiedniego katalogu
		// jest tutaj mała sztuczka
		// nazwa katalogu i pliku odpowiada sciezce generowanej przez ZF
		// zatem gdy miniaturka bedzie istniec omijamy generator miniaturek
		// i kierujemy sie bezposrednio do sciezki z miniaturka - leprza wydajnosc
		$thumbDirname  = $uploadPath . '/' . $type;
		$thumbPathname = $thumbDirname . '/' .$imageName;
		if (!is_dir($thumbDirname)) {
			if (!@mkdir($thumbDirname,0755)) {
				// logowanie zdarzeń
				$logger = Zend_Registry::get('logger');
				$logger->log('Product_ImageController::thumbAction mkdir('.$thumbDirname.')' , Zend_Log::WARN);
				return;
			}
		}
		if (!@file_put_contents($thumbPathname, $img)) {
			// logowanie zdarzeń
			$logger = Zend_Registry::get('logger');
			$logger->log('Product_ImageController::thumbAction file_put_contents('.$thumbPathname.')', Zend_Log::WARN);
		}

//		$cache->end(array('images','gallery','frontend'));
	}
}