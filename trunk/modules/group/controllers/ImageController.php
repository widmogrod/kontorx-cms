<?php
require_once 'KontorX/Controller/Action/CRUD.php';

/**
 * Obsługa grafik w galerii
 *
 * @author Marcin `widmogrod` Habryn, <widmogrod@gmail.com>
 * @license GNU GPL
 */
class Group_ImageController extends KontorX_Controller_Action_CRUD {

	protected $_modelClass = 'GroupGalleryImage';

	public $ajaxable = array(
		'list' => array('json'),
		'delete' => array('json')
	);

	public function init() {
		$this->_initLayout('admin',null,null,'default');

		$this->_helper->ajaxContext()
		->setAutoJsonSerialization(false)
		->initContext();

		$this->view->messages = $this->_helper->flashMessenger->getMessages();
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
		$config = $this->_helper->loader->config('config.ini');
		// ustawienie sciezki do grafik
		GroupGalleryImage_Row::setImagePath($config->path->upload);

		return $model;
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

    	// warunek przeszukiwania
		if ($this->_hasParam('pagination')) {
			$select->limitPage($page, $rowCount);
		}
    	if ($this->_hasParam('group_id')) {
    		$group_id = $this->_getParam('group_id');
    		$group_id = $group_id == 'null' ? '' : $group_id;
			$select->where('group_id = ?', $group_id);
		}

    	$rowset = $model->fetchAll($select);

    	if ($this->_hasParam('pagination')) {
    		return $rowset;
    	}

    	// select dla paginacji
		$this->_preparePagination($select);
    	
    	return $rowset;
    }

	/**
	 * Overwrite
	 */
	protected function _addGetForm() {
		$form = parent::_addGetForm();

		// setup @see Zend_Form
		$this->_setupZendForm($form);

		return $form;
	}

	/**
	 * Overwrite
	 */
	protected function _addOnIsPost(Zend_Form $form) {
		$form->setDefault('group_id', $this->_getParam('group_id'));
		parent::_addOnIsPost($form);
	}

	/**
     * @Overwrite
     */
    protected function _addPrepareData(Zend_Form $form) {
    	$data = parent::_addPrepareData($form);
    	
    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	$data['user_id']  = $userId;
    	$data['group_id'] = $this->_getParam('group_id');

    	return $data;
    }
	
	/**
	 * @Overwrite
	 */
    protected function _addOnSuccess(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	// tworzenie komunikatu
    	$message = 'Rekord został dodany';
		$this->_helper->flashMessenger->addMessage($message);


		$url = (($referer = $this->_getParam('referer')) == '')
			? $this->_helper->url->url(array('group_id' => $this->_getParam('group_id')))
			: $referer;

		$this->_helper->redirector->goToUrlAndExit($url);
	}
	
	/**
	 * Overwrite
	 */
	protected function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
		$form = parent::_editGetForm($row);

		// setup @see Zend_Form
		$this->_setupZendForm($form, true);

		return $form;
	}

	/**
	 * @Overwrite
	 */
	protected function _editFindRecord() {
    	$row = parent::_editFindRecord();
    	if (null === $row) {
    		return $row;
    	}

    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	// czy użytkownik jest właścicielem rekordu ?
    	// czy uzytkownk ma prawo do moderowania ?
    	if ($row->user_id == $userId
    			|| User::hasCredential(User::PRIVILAGE_MODERATE, $this->getRequest())) {
    		return $row;
    	}

    	$message = 'Nie jesteś właścicielem rekordu! oraz nie posiadasz uprawnień by móc go edytować';
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
    	return null;
	}
	
	/**
     * Ustawia opcje formularza
     *
     * @param Zend_Folm $form
     * @param bool $edit
     */
    protected function _setupZendForm(Zend_Form $form, $edit = false) {
    	$form->setAttrib('enctype','multipart/form-data');

    	require_once 'KontorX/Form/Element/File.php';
		$form->addElement(new KontorX_Form_Element_File('image'), 'image');
		$image = $form->getElement('image');
		$image->setLabel('image');

		if (true === $edit) {
			$image->setIgnore(true);
		}
    }

	/**
	 * @Overwrite
	 */
	protected function _modifyInit() {
		$this->_addModificationRule('publicated',self::MODIFY_BOOL);
	}

	protected function _deleteOnRecordNoExsists() {
		if ($this->_request->isXmlHttpRequest()) {
			$this->_helper->viewRenderer->setNoRender(false);
			$this->view->success = false;
		} else {
			parent::_deleteOnRecordNoExsists();
		}
	}
	
	protected function _deleteOnSuccess(Zend_Db_Table_Row_Abstract $row) {
		if ($this->_request->isXmlHttpRequest()) {
			$this->_helper->viewRenderer->setNoRender(false);
			$this->view->success = true;
		} else {
			parent::_deleteOnSuccess($row);
		}
	}

	protected function _deleteOnException(Zend_Exception $e) {
		if ($this->_request->isXmlHttpRequest()) {
			$this->_helper->viewRenderer->setNoRender(false);
			$this->view->success = false;
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
?>