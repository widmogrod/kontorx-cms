<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class News_CategoryController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_page'
	);

	protected $_modelClass = 'NewsCategory';

	public function init() {
		parent::init();		
		$this->view->news_id = $this->_getParam('news_id');
	}

	public function indexAction() {
		$this->_forward('index');
	}

	/**
	 * Overwrite
	 */
	protected function _addGetForm() {
		$form = parent::_addGetForm();
		$form->removeElement('path');
		// dodanie filtrow
		require_once 'KontorX/Filter/Word/Rewrite.php';
		$form->getElement('url')
			->addFilter(new KontorX_Filter_Word_Rewrite());

		return $form;
	}

    /**
     * Overwrite
     */
    protected function _addInsert(Zend_Form $form) {
    	$data = $this->_addPrepareData($form);

    	// dodawanie rekordu
    	$model = $this->_getModel();
    	$row = $model->createRow($data);
    	
    	// jezeli jest rodzic to dodanie do rodzica
    	if($this->_hasParam('parent_id')) {
    		$parentId  = $this->_getParam('parent_id');
    		$where = $model->select()->where('id = ?',$parentId);
    		$parentRow = $model->fetchRow($where);
    		if($parentRow instanceof Zend_Db_Table_Row_Abstract) {
    			$row->setParentRow($parentRow);
    		} else {
    			$message = 'Rekord rodzica nie istnieje';
				$this->_helper->flashMessenger->addMessage($message);
    		}
    	}

    	$row->save();
    	
    	return $row;
    }

    /**
	 * @Overwrite
	 */
    protected function _addOnSuccess(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	$parentId = $this->_getParam('parent_id', $row->id);

    	// tworzenie komunikatu
    	$message = 'Rekord został dodany';
		$this->_helper->flashMessenger->addMessage($message);

		$referer = $this->_getParam('referer');
		if (null !== $referer) {
			$this->_helper->redirector->goToUrlAndExit($referer);
		} else {
			$this->_helper->redirector->goToAndExit('add', null, null, array('parent_id' => $parentId));
		}
    }

	/**
	 * Overwrite
	 */
	protected function _editGetForm() {
		$form = parent::_addGetForm();
		$form->removeElement('path');
		// dodanie filtrow
		require_once 'KontorX/Filter/Word/Rewrite.php';
		$form->getElement('url')
			->addFilter(new KontorX_Filter_Word_Rewrite());

		return $form;
	}

	/**
	 * Przpisanie rekordu do kategori 1:n
	 *
	 */
	public function attachAction() {
		$newsId = $this->_getParam('news_id');
		$this->view->news_id = $newsId;

		// sprawdzanie czy produkt istnieje
		require_once 'news/models/News.php';
		$news = new News();

		try {
			$row = $news->find($newsId)->current();
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$row = null;
		}

		// czy aby napewno ..
		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			$message = 'Rekord o podanym kluczu głównym nie istnieje w systemie';
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
			return;
		}

		// pobranie kategorii przypisanych
		try {
			$this->view->hasCategoriesArray = $row->findDependentCategoriesArray();
		} catch(Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
		
		// pobieranie listy kategorii
		require_once 'news/models/NewsCategory.php';
		$category = new NewsCategory();
		try {
			$this->view->rowset = $category->fetchAll();
		} catch(Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		// czy jest POST
		if (!$this->_request->isPost()) {
			return;
		}

		$hasCategories = (array) $this->_getParam('hasCategories');

		try {
			$news->attachToCategories($newsId, $hasCategories);

			$message = 'Rekord został przypisany do kategorii';
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goTo('edit','news',null,array('id' => $newsId));
		} catch(Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$message = 'Rekord został przypisany do kategorii';
			$this->view->message = $message;
		}
	}

	/**
	 * @Overwrite
	 */
	protected function _modifyInit() {
		$this->_addModificationRule('publicated',self::MODIFY_BOOL);
	}
}