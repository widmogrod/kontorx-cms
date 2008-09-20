<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Product_AdminController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin_product',
	);

	protected $_modelClass = 'Product';

	public function init() {
		parent::init();
		$this->view->product_id = $this->_getParam('id');
	}

	public function indexAction() {
		$this->_forward('list');
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
		$select
			->limitPage($page, $rowCount);
    	$rowset = $model->fetchAll($select);

    	// select dla paginacji
    	require_once 'Zend/Db/Select.php';
    	$select = new Zend_Db_Select($db);
    	$select
    		->from(array('table' => $model->info(Zend_Db_Table::NAME)));

		// paginacja
		require_once 'Zend/Paginator.php';
    	$paginator = Zend_Paginator::factory($select);
    	$paginator->setCurrentPageNumber($page);
    	$paginator->setItemCountPerPage($rowCount);
    	$this->view->paginator = $paginator;
    	
    	return $rowset;
    }

	/**
	 * @Overwrite
	 */
	protected function _addGetForm() {
		$form = parent::_addGetForm();
		// dodanie filtrow
		require_once 'KontorX/Filter/Word/Rewrite.php';
		$form->getElement('url')
			->addFilter(new KontorX_Filter_Word_Rewrite());

		return $form;
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
			$this->_helper->redirector->goToAndExit('edit',null,null,array('id' => $row->id));
		}
	}

	/**
	 * @Overwrite
	 */
	protected function _editGetForm() {
		$form = parent::_editGetForm();
		// dodanie filtrow
		require_once 'KontorX/Filter/Word/Rewrite.php';
		$form->getElement('url')
			->addFilter(new KontorX_Filter_Word_Rewrite());

		return $form;
	}

	/**
	 * Duplikowanie produktu
	 *
	 */
	public function duplicateAction() {
		$form = $this->_duplicateGetForm();

		// wyszukanie rekordu do duplikacji
		try {
    		$row = $this->_duplicateFindRecord();
    	} catch (Zend_Db_Table_Exception $e) {
    		$row = $this->_duplicateFindOnException($e);
    	}

    	// czy rekord istnieje
		if (false === $row || null === $row) {
			$this->_duplicateOnRecordNoExsists();
			return;
		}

		// TODO W przyszlosci dodac uchwyt generujacy
		// sprawdzanie danych nie tylko post!
    	if (!$this->_request->isPost()) {
    		$this->_duplicateOnIsPost($form, $row);
    		return;
    	}

		if (!$this->_duplicateIsValid($form, $row)) {
    		$this->_duplicateOnIsValid($form, $row);
    		return;
    	}

		try {
			$duplicated = $this->_duplicateInsert($form, $row);
			$this->_duplicateOnSuccess($duplicated);
		} catch (Zend_Db_Table_Row_Exception $e) {
			$this->_duplicateOnException($e);
		} catch (Zend_Db_Table_Abstract $e) {
			$this->_duplicateOnException($e);
		} catch (Zend_Db_Statement_Exception $e) {
			$this->_duplicateOnException($e);
		}
	}

	/**
	 * Formularz duplikacji
	 * 
	 * @return Zend_Form
	 */
	protected function _duplicateGetForm() {
		$config = $this->_helper->loader->config('admin.ini');
		$form = new Zend_Form($config->form->duplicate);
		return $form;
	}

    /**
     * Uchwyt dla akcji wyszukania rekordu
     *
     * @return Zend_Db_Table_Row_Abstract|bool
     */
    protected function _duplicateFindRecord() {
    	$model = $this->_getModel();
    	return $model->find($this->_getParam('id'))->current();
    }

    /**
     * Uchwyt dla wyjatku podczas nieudanego zapytania
     *
     * Uchwyt dla wyjatku podczas nieudanego zapytania dla
     * operacji wyszukania rekordu
     * 
     * @param Zend_Db_Table_Exception $e
     * @return bool
     */
    protected function _duplicateFindOnException(Zend_Exception $e) {
    	// logowanie wyjatku
		$logger = Zend_Registry::get('logger');
		$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		return false;
    }

	/**
     * Uchwyt dla akcji przed wysłaniem danych POST
     *
     * @param Zend_Form $form
     * @param Zend_Db_Table_Row_Abstract
     */
    protected function _duplicateOnIsPost(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	$form->setDefaults($row->toArray());
    	$this->view->form = $form->render();
    }
    
 	/**
     * Uchwyt akcji walidujacej formularz
     *
     * @return bool
     */
    protected function _duplicateIsValid(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	return $form->isValid($this->_request->getPost());
    }
    
	/**
     * Uchwyt dla akcji gdy dane sa niepoprawne
     *
     * @param Zend_Form $form
     * @param Zend_Db_Table_Row_Abstract
     */
    protected function _editOnIsValid(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	$this->view->form = $form->render();
    }
    
    /**
     * Uchwyt dla akcji gdy nie zostanie znaleziony rekord o edycji
     *
     */
    protected function _duplicateOnRecordNoExsists() {
    	$message = 'Rekord o podanym kluczu głównym nie istnieje w systemie';
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
    }

    /**
     * Akcja duplikujaca rekord
     *
     * @param Zend_Form $form
     * @param Zend_Db_Table_Row_Abstract $row
     * @return Zend_Db_Table_Row_Abstract
     */
    protected function _duplicateInsert(Zend_Form $form, Zend_Db_Table_Row_Abstract $row) {
    	// duplikujemy
    	$duplicated = clone $row;
		$duplicated->save();

		$productId = $duplicated->id;

		// duplikuj grafiki
		if ($form->getValue('image') == 1) {
			$images = $row->findDependentRowset('ProductImage');
			if (count($images) > 0) {
				foreach ($images as $image) {
					$imageDuplicat = clone $image;
					$imageDuplicat->product_id = $productId;
					
					try {
						$imageDuplicat->save();
					} catch (Zend_Db_Table_Row_Exception $e) {
						// logowanie wyjatku
						$logger = Zend_Registry::get('logger');
						$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::WARN);
					}
				}
			}
		}

		// duplikuj przypisanie do kategorii
    	if ($form->getValue('category') == 1) {
    		$hasCategories = $row->findDependentCategoriesArray();
    		if (!empty($hasCategories)) {
    			// @see Product
    			$model = $this->_getModel();
    			try {
					$model->attachToCategories($productId, $hasCategories);
				} catch (Zend_Db_Table_Exception $e) {
					// logowanie wyjatku
					$logger = Zend_Registry::get('logger');
					$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::WARN);
				}
    		}
		}

		return $duplicated;
    }
    
	/**
     * Uchwyt akcji po sukcesie w duplikacji rekordu
     *
     * @param Zend_Db_Table_Row_Abstract $row
     */
    protected function _duplicateOnSuccess(Zend_Db_Table_Row_Abstract $row) {
   		$primaryKey = $row->id;
    	
    	// tworzenie komunikatu
    	$message = 'Rekord został zduplikowany';
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToAndExit('edit',null,null,array('id'=>$primaryKey));
		
		$referer = $this->_getParam('referer');
    	if (null !== $referer) {
			$this->_helper->redirector->goToUrlAndExit($referer);
		} else {
			$this->_helper->redirector->goToAndExit('edit',null,null,array('id'=>$primaryKey));
		}
    }
    
	/**
     * Uchwyt dla wyjatku podczas nieudanego zapytania
     *
     * Uchwyt dla wyjatku podczas nieudanego zapytania dla
     * operacji duplikacji rekordu
     * 
     * @param Zend_Db_Table_Exception $e
     */
    protected function _duplicateOnException(Zend_Exception $e) {
    	// logowanie wyjatku
		$logger = Zend_Registry::get('logger');
		$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		
		$message = 'Rekord nie został zduplikowany';
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
    }
}