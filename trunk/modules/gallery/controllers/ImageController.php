<?php
require_once 'KontorX/Controller/Action/CRUD.php';

/**
 * Obsługa grafik w galerii
 *
 * @author Marcin `widmogrod` Habryn, <widmogrod@gmail.com>
 * @license GNU GPL
 */
class Gallery_ImageController extends KontorX_Controller_Action_CRUD {

	protected $_modelClass = 'GalleryImage';

	public $contexts = array(
		'list' => array('json'),
		'delete' => array('json'),
                'upload' => array('json','dojo')
	);

	public function init() {
		$this->_initLayout('page');

//                $new = new Zend_Controller_Action_Helper_ContextSwitch();

		$this->_helper->contextSwitch()
                    ->addContext('dojo', array(
                        'suffix'    => 'dojo',
                        'headers'   => array('Content-Type' => 'application/xml'),
                    ))
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
		GalleryImage_Row::setImagePath($config->path->upload);

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
    	if ($this->_hasParam('gallery_id')) {
    		$gallery_id = $this->_getParam('gallery_id');
    		$gallery_id = $gallery_id == 'null' ? '' : $gallery_id;
			$select->where('gallery_id = ?', $gallery_id);
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
		$form->setDefault('gallery_id', $this->_getParam('gallery_id'));
		parent::_addOnIsPost($form);
	}

	/**
     * @Overwrite
     */
    protected function _addPrepareData(Zend_Form $form) {
    	$data = parent::_addPrepareData($form);

    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	$data['user_id'] 	  = $userId;
    	$data['gallery_id'] = $this->_getParam('gallery_id');
    	$data['publicated'] = isset($data['publicated']) ? (int) $data['publicated'] : 1;

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
			? $this->_helper->url->url(array('gallery_id' => $this->_getParam('gallery_id')))
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
			parent::_deleteOnException($e);
		}
	}

	/**
	 * Uploadowanie grafiki
	 *
	 * Akcja wylacznie dla AJAX response JSON,
	 * Uploaduje plik do katalogu
	 */
	public function uploadAction() {
		$success = false;
		$message = null;
		$info = array();

		// pobieranie konfiguracji
		$config = $this->_helper->loader->config();
		$path = $config->path->upload;

		// uploadowanie pliku
        $files = isset($_FILES['photoupload'])
            ? $_FILES['photoupload']
            : (array) @$_FILES['Filedata'];

		$file = new KontorX_File_Upload($files);
		if (!$file->isUploaded()) {
			$success = false;
			$message = "Plik nie został przesłany";
		} else {
			if (!$file->move($path, true)) {
				$success = false;
				$message = $file->getErrors(true);
			} else {
				try {
					// dodaj info o grafice do DB
                                        require_once 'gallery/models/GalleryImage.php';
					$image = new GalleryImage();
					$row = $image->createRow(array(
						'image' => $file->getGenerateUniqFileName()
					));

					$row->setNoUploadException(true);
					$id = $row->save();

					$success = true;
					$message = "Plik został przesł wysłany na serwer";
					$info = array(
						'id' => $id,
						'path' => $path . $file->getGenerateUniqFileName()
					);
				} catch (Zend_Db_Table_Exception $e) {
					$success = false;
					$message = "Wystąpił problem z zapisaniem danych do bazy danych";

					// usun grafike ktora zostala uploadowana
					unlink($path . $file->getGenerateUniqFileName());

					// logowanie wyjatku
					$logger = Zend_Registry::get('logger');
					$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
				}
			}
		}
		$file->clean();

                $data = array(
                  'success' => $success,
                  'message' => $message,
                  'info' => (array) @$info
                );

                if ($this->_hasParam('format')) {
			$this->view->data = $data;
		} else {
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
		}
	}

	/**
	 * Przypisanie grafiki do galerii
	 *
	 */
	public function imagetogalleryAction() {
		// wylaczenie renderowania widoku
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		$image_id   = $this->_getParam('image_id');
		$gallery_id = $this->_getParam('gallery_id');

		$image = new GalleryImage();

		// wyszukanie rekordu do edycji
		try {
			$row = $image->find($image_id)->current();
		} catch (Zend_Db_Table_Exception $e) {
			$row = null;
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		// czy rekord istnieje
		if (false === $row || null === $row) {
			$response = array(
				'success' => false,
				'message' => 'Grafika nie istnieje'
				);
				print Zend_Json::encode($response);
				return;
		}

		try {
			$row->gallery_id = $gallery_id;
			$row->save();

			$success = true;
			$message = 'Grafika została przypisana do galerii';
		} catch(Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$success = false;
			$message = 'Grafika nie została przypisana do galerii';
		}

		$response = array(
			'success' => $success,
			'message' => $message
		);
		print Zend_Json::encode($response);
	}

	/**
	 * Usuwa dowiązanie grafiki do galerii
	 *
	 */
	public function removeimagefromgalleryAction() {
		// wylaczenie renderowania widoku
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		$image_id   = $this->_getParam('id');

		$image = new GalleryImage();

		// wyszukanie rekordu do edycji
		try {
			$row = $image->find($image_id)->current();
		} catch (Zend_Db_Table_Exception $e) {
			$row = null;
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		// czy rekord istnieje
		if (false === $row || null === $row) {
			$response = array(
				'success' => false,
				'message' => 'Grafika nie istnieje'
				);
				print Zend_Json::encode($response);
				return;
		}

		try {
			$row->gallery_id = '';
			$row->save();

			$success = true;
			$message = 'Grafika została usunięta z galerii';
		} catch(Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$success = false;
			$message = 'Grafika nie została usunięta z galerii';
		}

		$response = array(
			'success' => $success,
			'message' => $message
		);
		print Zend_Json::encode($response);
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
		} else {
			@chmod($thumbPathname, 0755);
		}

//		$cache->end(array('images','gallery','frontend'));
	}
}
?>