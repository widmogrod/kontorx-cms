<?php
require_once 'KontorX/Controller/Action.php';
class Catalog_ManagementController extends KontorX_Controller_Action {

	public $skin = array(
		'layout' => 'manage'
	);
	
	public $ajaxable = array(
		'service' => array('html'),
		'images' => array('html'),
		'imagemain' => array('json'),
		'imagedelete' => array('json')
	);
	
	public $contexts = array(
		'imageupload' => array('html')
	);

	public function init() {
		// informuja jaki kontroller
		$this->view->placeholder('navigation')->controller = 'management';

		parent::init();

		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->initContext();
		
		$contextSwitch = $this->_helper->getHelper('ContextSwitch');
		if (!$contextSwitch->hasContext('html')) {
			$contextSwitch
				->addContext('html', array(
					'headers'   => array('Content-Type' => 'text/html'),
				));
		}
		$contextSwitch->initContext();
	}
	
	public function indexAction(){
		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$rowset = $manage->findCatalogRowsetForUser($this->getRequest());
		$this->view->rowset = $rowset;
	}
	
	/**
	 * Edycja podstrawowych danych z modelu @see Contact
	 * @return void
	 */
	public function editAction() {
		// ustawienie akcji
		$type 	= strtolower($this->_getParam('type'));
		$this->view->type = $type;
		$action = 'edit';
		$this->view->placeholder('navigation')->action = "$action.$type";

		require_once 'catalog/models/Management.php';
		$manage = new Management();
		
		$rq = $this->getRequest();
		
		$row = $manage->findCatalogRowForUser($this->_getParam('id'), $rq);
		$this->view->row = $row;

		if (null === $row) {
			 $this->_helper->viewRenderer->render('edit.error');
			 return;
		}
		
		// GMap API
		$configMain = $this->_helper->loader->config('config.ini');
		$this->view->apiKey = $configMain->gmap->{BOOTSTRAP}->apiKey;

		$form = $this->_getEditForm($row, $type);
		$this->view->form = $form;
		
		if (!$rq->isPost()) {
			$form->setDefaults($row->toArray());
			return;
		}

		if (!$form->isValid($rq->getPost())) {
			return;
		}

		try {
			$data = $rq->getPost();
			$data = get_magic_quotes_gpc() ? array_map('stripslashes', $data) : $data;

			if (isset($data['user_id'])) {
				unset($data['user_id']);
			}
			
			$row->setFromArray($data);
			$row->save();
			
			$message = "Wizytówka została zedytowana";
			$this->_helper->flashMessenger($message);
			
			$this->_helper->redirector->goToUrlAndExit(
				$this->_helper->url->url(array())
			);
		} catch(Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString());

			$message = "Wizytówka nie została zedytowana, proszę spróbować jeszcze raz";
			$this->_helper->flashMessenger($message);
		}
	}

	/**
	 * Przygotowanie formularza do edycji
	 * 
	 * @param Zend_Db_Table_Row_Abstract$row
	 * @param string $type
	 * @return KontorX_Form_DbTable
	 */
	private function _getEditForm(Zend_Db_Table_Row_Abstract $row, $type = null) {
		$type = strtolower($type);
		if (!in_array($type, array('default','contact','map','meta'))) {
			$type = 'default';
		}

		$config = $this->_helper->loader->config('management.xml');
		$form = new KontorX_Form_DbTable($row->getTable(), $config->form->{$type});

		return $form;
	}

	public function serviceAction() {
		// ustawienie akcji
		$this->view->placeholder('navigation')->action = 'service';

		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$id = $this->_getParam('id');
		$rq = $this->getRequest();

		// Czy rekord nalerzy do uzytkownika!?
		if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
			$this->_helper->viewRenderer->render('edit.error');
			 return;
		}

		$this->view->row = $row;
		$this->view->rowset = $manage->findServicesRowsetForCatalogId($id);
		
		if (!$rq->isPost()) {
			return;
		}

		if ($manage->saveServicesCost($id, $rq)) {
			$message = "Usługi zostały zapisane";
		} else {
			$message = "Usługi nie zostały zapisane";
		}
		
		$this->_helper->flashMessenger($message);
		$this->_helper->redirector->goToUrlAndExit(
			$this->_helper->url->url(array())
		);
	}

	/**
	 * Listowanie grafik + formularz uploadu dla grafiki
	 * 
	 * @return void
	 */
	public function imagesAction() {
		// ustawienie akcji
		$this->view->placeholder('navigation')->action = 'images';

		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$id = $this->_getParam('id');
		$rq = $this->getRequest();

		// Czy rekord nalerzy do uzytkownika!?
		if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
			$this->_helper->viewRenderer->render('edit.error');
			 return;
		}
		
		$this->view->row = $row;
		
		try {
			require_once 'catalog/models/CatalogImage.php';
			$this->view->rowset = $row->findDependentRowset('CatalogImage');
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	/**
	 * Uploaduj grafikę
	 * 
	 * @return void
	 */
	public function imageuploadAction() {
		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$id = $this->_getParam('id');
		$rq = $this->getRequest();

		// Czy rekord nalerzy do uzytkownika!?
		if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
			$this->_helper->viewRenderer->render('edit.error');
			return;
		}

		$filename = 'image';

		require_once 'Zend/File/Transfer/Adapter/Http.php';
		$file = new Zend_File_Transfer_Adapter_Http();

		// destination
		$config = $this->_helper->loader->config();
		$path = $config->path->upload->image;
		$file->setDestination($path, $filename);

		require_once 'Zend/Validate/File/IsImage.php';
		$file->addValidator(new Zend_Validate_File_IsImage(), true);

		require_once 'KontorX/Filter/Word/Rewrite.php';
		$filterRewrite = new KontorX_Filter_Word_Rewrite();

		$newFilename = $filterRewrite->filter($file->getFileName($filename));
		$newFilename = md5(time()) . $newFilename . '.' . substr(strrchr($filename, '.'), 1);
		$newPathname = "{$path}/{$newFilename}";

		require_once 'Zend/Filter/File/Rename.php';
		$filterRename = new Zend_Filter_File_Rename(array('target' => $newPathname));
		$file->addFilter($filterRename);

		$message = null;
		if (!$file->isUploaded($filename)) {
			$message = "Plik nie został uploaowany";
		} else		
		if (!$file->isValid($filename)) {
			$message = "Plik nie jest poprawny";
		} else
		if (!$file->receive()) {
			$messages = array();
			foreach ($file->getmsg() as $message) {
				$messages[] = $message;
			}
			$message = implode('<br/>', $messages);
		} else
		if (!$manage->insertImage($id, $newFilename)) {
			$message = "Plik nie został zapisany w bazie danych! proszę spróbuj jeszcze raz";
		} else {
			$message = "Plik został wysłany na serwer";
		}
		
		if (!$this->_hasParam('format')) {
			// zwykła akcja redirect
			$this->_helper->flashMessenger($message);
			$this->_helper->redirector->goToUrlAndExit(
				$this->_helper->url->url(array('action'=>'images','id'=>$id))
			);
		} else {
			// działaj sobie. ..
			$this->view->msg = array($message);
		}
	}
	
	/**
	 * Ustawienie wybranej grafiki jako logo
	 * 
	 * @return void
	 */
	public function imagemainAction() {
		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$id = $this->_getParam('id');
		if ($manage->setMainImage($id)) {
			$this->view->success = true;
			$message = "Logo zostało ustawione";
		} else {
			$this->view->success = false;
			$message = "Logo nie zostało ustawione!";
		}
		
		if (!$this->_hasParam('format')) {
			// zwykła akcja redirect
			$this->_helper->flashMessenger($message);
			$this->_helper->redirector->goToUrlAndExit(
				getenv('HTTP_REFERER')
			);
		}
	}

	/**
	 * Usuwa grafikę
	 * 
	 * @return void
	 */
	public function imagedeleteAction() {
		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$id = $this->_getParam('id');
		if ($manage->deleteImage($id)) {
			$this->view->success = true;
			$message = "Fotografia została usunięta";
		} else {
			$this->view->success = false;
			$message = "Fotografia nie została usunięta";
		}
		
		if (!$this->_hasParam('format')) {
			// zwykła akcja redirect
			$this->_helper->flashMessenger($message);
			$this->_helper->redirector->goToUrlAndExit(
				getenv('HTTP_REFERER')
			);
		}
	}
}