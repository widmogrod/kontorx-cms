<?php
require_once 'KontorX/Controller/Action.php';
class News_IndexController extends KontorX_Controller_Action {
	public $skin = array(
		'display' => array(
			'layout' => 'full'
		),
		'add-comment' => array(
			'layout' => 'full'
		),
		'search' => array(
			'layout' => 'news'
		),
		'list' => array(
			'layout' => 'news'
		),
		'category' => array(
			'layout' => 'news'
		)
	);
	
	public function indexAction() {
		$this->_forward('list',null,null, $this->_getAllParams());
	}

	/**
	 * Wyświetl aktualność
	 *
	 */
	public function displayAction() {
		// wyszukuje aktualnosci
		$row = $this->_fetchRowNews();

		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('display.noexsists');
			return;
		}

		$this->view->row 		= $row;
//		$this->view->rowsetImages	= $this->_fetchGalleryImages($row);
		$this->view->comments 	= $this->_fetchNewsComments($row);
		$this->view->form 		= $this->_getFormComment()->render();
	}

	/**
	 * Wyławia z BD galerie i zdięcia galerii
	 *
	 * @param Zend_Db_Table_Row_Abstract $row
	 */
	public function _fetchGalleryImages(Zend_Db_Table_Row_Abstract $row) {
		try {
			$rowsetGallery = $row->findManyToManyRowset('Gallery','NewsHasGallery');
		} catch (Zend_Db_Table_Abstract $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			return array();
		}

		if (null === $rowsetGallery->current()) {
			return array();
		}

		$row = $rowsetGallery->current();

		try {
			require_once 'gallery/models/GalleryImage.php';
			return $row->findDependentRowset('GalleryImage');
		} catch (Zend_Db_Table_Abstract $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}
	
	/**
	 * Wyszukanie aktualnosci
	 *
	 * @param $select Zend_Db_Table_Select
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _fetchRowNews(Zend_Db_Table_Select $select = null) {
		require_once 'news/models/News.php';
		$model = new News();

		$url = $this->_getParam('url');
		$language = $this->_helper->system->language();

    	$request = $this->getRequest();
    	$select  = $model->selectForSpecialCredentials($request);
    	
		try {
			$row = null;
			$row = $model->fetchRowPublic($url, $language, $select);
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
		
		return $row;
	}

	/**
	 * Zwraca komentarze do aktualnosci
	 *
	 * @param Zend_Db_Table_Row_Abstract $row
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _fetchNewsComments(Zend_Db_Table_Row_Abstract $row) {
		require_once 'news/models/NewsComment.php';
		$model = new NewsComment();

		$select = $model->select();
		$select
			->where('news_id = ?', $row->id)
			->order('id DESC');

		try {
			$rowset = $model->fetchAll($select);
			return $rowset;
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		return null;
	}
	
	/**
	 * Formlarz do komentowania
	 *
	 * @return Zend_Form
	 */
	protected function _getFormComment() {
		$config = $this->_helper->loader->config('index.ini');
		require_once 'Zend/Form.php';
		$form = new Zend_Form($config->form->comment);
		require_once 'Zend/Form/Element/Hidden.php';
		$form->addElement(new Zend_Form_Element_Hidden('url',array('value' => $this->_getParam('url'))),'url');
		return $form;
	}

	/**
	 * Dodaj komentarz do aktualności
	 *
	 */
	public function addCommentAction() {
		// wyszukuje aktualnosci
		$row = $this->_fetchRowNews();
		
		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			$message = 'Rekord nie istnieje';
			$this->_helper->flashMessenger($message);
			$this->_helper->redirector->goToAndExit('display',null,null,array('url' => $this->_getParam('url')));
			return;
		}

		$this->view->row = $row;
		
		// tylko dane POST moga przejsc
		if (!$this->_request->isPost()) {
			$this->view->form = $form->render();
			return;
		}

		// formlarz do komentowania
		$form = $this->_getFormComment();

		// czy poprawne dane
		if (!$form->isValid($_POST)) {
			$this->view->form = $form->render();
			return;
		}

		require_once 'news/models/NewsComment.php';
		$comment = new NewsComment();

		// TODO Dodać parsowanie danych
		$data = array(
			'news_id' => $row->id,
			'site' => $form->getValue('site'),
			'email' => $form->getValue('email'),
			'username' => $form->getValue('username'),
			'content' => $form->getValue('content')
		);
		
		$row = $comment->createRow($data);

		try {
			$row->save();
			$message = 'Komentarz został dodany';
		} catch (Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$message = 'Komentarz nie został dodany';
		}

		$this->_helper->flashMessenger($message);
		$this->_helper->redirector->goToAndExit('display',null,null,array('url' => $url));
	}

	/**
	 * Wyszukiwanie
	 *
	 * @return void
	 */
	public function searchAction() {	
		$description = $this->_getParam('description');
		$this->view->description = (null === $description) ? true : $description; 
		$pagination = $this->_getParam('pagination');
		$this->view->is_pagination = (null === $pagination) ? true : $pagination; 

		require_once 'news/models/News.php';
		$model = new News();

		$page = $this->_getParam('page',1);
    	$rowCount = $this->_getParam('rowCount',30);
		$language = $this->_helper->system->language();
		
		$request = $this->getRequest();
		// okreslamy widocznosc rekordow dla użytkownika
		$select = $model->selectForSpecialCredentials($request);
		$select = $model->selectPublic($language, $select);

		// wuszukiwanie
		$searchText = $this->_request->getQuery('s');
		$this->view->text = $searchText;
		$searchText = $this->_prepareSearchText($searchText);

		if (null === $searchText) {
			$this->_helper->viewRenderer->render('search.noexsists');
		} else {
			$select
				->where('name LIKE ?', "%$searchText%");
		}

		$select
			->limitPage($page, $rowCount)
			->order('t_create DESC');

		try {
			$this->view->rowset = $model->fetchAll($select);
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$this->_helper->viewRenderer->render('search.noexsists');
			return;
		}
		
		// przygotowanie paginacji
		if ($this->view->is_pagination) {
			$this->_preparePagination($select);
		}
	}

	protected function _prepareSearchText($searchText) {
		require_once 'Zend/Filter.php';
		$filters = new Zend_Filter();

		require_once 'Zend/Filter/HtmlEntities.php';
		$filters->addFilter(new Zend_Filter_HtmlEntities(ENT_COMPAT,'UTF-8'));

		require_once 'Zend/Filter/StripTags.php';
		$filters->addFilter(new Zend_Filter_StripTags());

		require_once 'KontorX/Filter/SearchText.php';
		$filters->addFilter(new KontorX_Filter_SearchText());
		
		$searchText = $filters->filter($searchText);
		return $searchText == '' ? null : $searchText;
	}
	
	/**
	 * Wylistuj aktualność
	 *
	 * @return void
	 */
	public function listAction() {	
		$description = $this->_getParam('description');
		$this->view->description = (null === $description) ? true : (bool) $description; 
		$pagination = $this->_getParam('pagination');
		$this->view->is_pagination = (null === $pagination) ? true : (bool) $pagination; 

		require_once 'news/models/News.php';
		$model = new News();

		$page = $this->_getParam('page',1);
    	$rowCount = $this->_getParam('rowCount',30);
    	$language = $this->_helper->system->language();

    	$request = $this->getRequest();
		// okreslamy widocznosc rekordow dla użytkownika
		$select = $model->selectForSpecialCredentials($request);
		$select = $model->selectPublic($language, $select);
		// okreslamy przedzial czasowy rekordow
		$year  = $this->_getParam('year',date('Y'));
		$month = $this->_getParam('month');
		$model->selectSetupForTimeRange($select, $year, $month);
		
		$select
			->limitPage($page, $rowCount)
			->order('t_create DESC');

		try {
			$this->view->rowset = $model->fetchAll($select);
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			
			$this->_helper->viewRenderer->render('list.noexsists');
			return;
		}
		
		// przygotowanie paginacji
		if ($this->view->is_pagination) {
			$this->_preparePagination($select);
		}
	}

	/**
	 * Przygotowanie paginacji
	 *
	 * @param Zend_Db_Table_Select $select
	 */
	protected function _preparePagination(Zend_Db_Select $select) {
		$page 		= (int) $this->_getParam('page',1);
    	$rowCount 	= (int) $this->_getParam('rowCount',30);

    	$select = clone $select;
    	$select
    		->reset(Zend_Db_Select::LIMIT_COUNT)
    		->reset(Zend_Db_Select::LIMIT_OFFSET);
    	
		// dlatego clone select zeby nie bylo limitow		
		require_once 'Zend/Paginator.php';
    	$paginator = Zend_Paginator::factory($select);
    	$paginator->setCurrentPageNumber($page);
    	$paginator->setItemCountPerPage($rowCount);

    	// to view
    	$this->view->paginator = $paginator;
	} 
	
	/**
	 * Wyswietla aktualnosci wzglendem kategorii
	 *
	 */
	public function categoryAction() {
		$page = $this->_getParam('page',1);
    	$rowCount = $this->_getParam('rowCount',30);
		
		$categoryUrl = $this->_getParam('url');
		$this->view->category_url = $categoryUrl;

		require_once 'news/models/NewsCategory.php';
		$category = new NewsCategory();

		// sprawdzanie czy istnieje kategoria
		$select = $category->select();
		$select
			->limitPage($page, $rowCount)
			->where('url = ?', $categoryUrl)
			->where('publicated = 1');

		try {
			$row = $category->fetchRow($select);
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

		$request = $this->getRequest();
		$select = $row->getDependentProductsSelect();
		$category->selectForSpecialCredentials($request, $select);
		$select
			->order('t_create DESC');
		// pobranie kategorii przypisanych
		try {
			$this->view->rowset = $row->findDependentProductsRowset($select);
		} catch(Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
		
		$select = $row->getDependentProductsSelectPaginator();
		$category->selectForSpecialCredentials($request, $select);

		$this->_preparePagination($select);
	}	
}