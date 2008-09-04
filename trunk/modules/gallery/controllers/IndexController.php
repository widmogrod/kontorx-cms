<?php
require_once 'KontorX/Controller/Action.php';
class Gallery_IndexController extends KontorX_Controller_Action {

	public function init() {
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}
	
	/**
	 * Listuje categorie i galerie
	 *
	 */
	public function indexAction() {
		$this->_initLayout('gallery',null,null,'default');

		// TODO Może byc przypadek ze `language_url` o `i18n` jest NULL
		$i18n = $this->_frontController->getParam('i18n');
		// TODO Dodac `language_url` z konfiguracji
    	$language = $this->_getParam('language_url', $i18n);
		
		$rowsetCategory = $this->_fetchRowsetCategory();
		$this->view->rowsetCategory = $rowsetCategory;

		if (null == $rowsetCategory) {
			$this->_helper->viewRenderer->render('index.no.category');
			return;
		}

		// sprawdzam czy wybrano kategorię
		if (!$this->_hasParam('category_id')
				&& !$this->_hasParam('category_url')){
			$rowCategory = $rowsetCategory->current();
		}
		// jak nie to pierwsza z stosu jest domyslna
		else {
			$categoryKey = $this->_getParam('category_id', $this->_getParam('category_url'));
			$rowCategory = $this->_findActiveCategoryByKey($categoryKey, $rowsetCategory);
		}

		if (!$rowCategory instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('index.no.category');
			return;
		}

		$this->view->category_id 	= $rowCategory->id;
		$this->view->rowCategory 	= $rowCategory;

		// wyszukuje galerie nalezace do kategorii
		$rowsetGallery = $this->_fetchRowsetGallery($rowCategory);
		$this->view->rowsetGallery = $rowsetGallery;

		if (null == $rowsetCategory) {
			$this->_helper->viewRenderer->render('index.no.gallery');
			return;
		}
		
		// sprawdzam czy wybrano galerię
		if (!$this->_hasParam('gallery_id')
				&& !$this->_hasParam('gallery_url')) {
			$rowGallery = $rowsetGallery->current();
		}
		// jak nie to pierwsza z stosu jest domyslna
		else {
			$galleryKey = $this->_getParam('gallery_id', $this->_getParam('gallery_url'));
			$rowGallery = $this->_findActiveGalleryByKey($galleryKey, $rowsetGallery);
		}

		if (!$rowGallery instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('index.no.gallery');
			return;
		}

		$this->view->gallery_id 			= $rowGallery->id;
		$this->view->rowGallery 			= $rowGallery;
		$this->view->rowGalleryDescription 	= $this->_fetchRowsetGalleryDescription($rowGallery);
		
		// widocznosc rekordow jest okreslona w metodzie!
		$request = $this->getRequest();
		$rowsetImage = $rowGallery->findDependentImagesRowset($language, $this->view->gallery_id, $request);
		$this->view->rowsetImages = $rowsetImage;

		if (null == $rowsetImage) {
			// TODO Brak galerii
			return;
		}
		
		// sprawdzam czy jest zaznaczona grafika
		if ($this->_hasParam('image')) {
			$image = $this->_getParam('image');
		}
		// jak nie to pierwsza z stosu jest domyslna
		else {
			$image = count($rowsetImage)
				? current($rowsetImage)
				: null;
		}

		if (is_object($image)) {
			$image = $image->image;
			$image;
		}

		$this->view->imageRow = $this->_findActiveImageByImage($image, $rowsetImage);
		
		$this->view->image = $image;
	}

	/**
	 * Pokazuje galerie
	 * 
	 * Pokazuje kategorie bez prawego panelu
	 * z mozliwoscia wyboru kategorii i galerii
	 *
	 */
	public function galleryAction() {
		require_once 'gallery/models/Gallery.php';
		$model = new Gallery();

		$select = $model->select()
			->where('url = ?', $this->_getParam('gallery_url'))
			->where('publicated = 1');

		try {
			$rowGallery = $model->fetchRow($select);
		} catch (Zend_Db_Table_Row_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$rowGallery instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('index.no.gallery');
			return;
		}

		$this->view->gallery_id 			= $rowGallery->id;
		$this->view->rowGallery 			= $rowGallery;
		$this->view->rowGalleryDescription 	= $this->_fetchRowsetGalleryDescription($rowGallery);

		require_once 'gallery/models/GalleryImage.php';
		$modelImage = new GalleryImage();

		// select for gallery image
		$select = $modelImage->select();
		if ($this->_hasParam('rowCount')) {
			$select->limitPage(1, (int) $this->_getParam('rowCount'));
		}

		$rowsetImages = $rowGallery->findDependentRowset($modelImage, null, $select);
		$this->view->rowsetImages = $rowsetImages;

		if (null == $rowsetImages) {
			// TODO Brak zdięć w galerii
			return;
		}

		// sprawdzam czy jest zaznaczona grafika
		if ($this->_hasParam('image')) {
			$image = $this->_getParam('image');
		}
		// jak nie to pierwsza z stosu jest domyslna
		else {
			$image = count($rowsetImages)
				? $rowsetImages->current()->image
				: null;
		}

		$this->view->image = $image;
	}
	
	/**
	 * Wyszukuje wszystkie aktywne kategorie
	 *
	 * @param $select Zend_Db_Table_Select
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	protected function _fetchRowsetCategory(Zend_Db_Table_Select $select = null) {
		require_once 'gallery/models/GalleryCategory.php';
		$model = new GalleryCategory();

		$select = (null === $select)
			? $model->select() : $select;

		$select
			->where('publicated = 1');

		try {
			return $model->fetchAll($select);
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		return null;
	}
	
	/**
	 * Wyszukanie kategorii
	 *
	 * @param $select Zend_Db_Table_Select
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _fetchRowCategory(Zend_Db_Table_Select $select = null) {
		require_once 'gallery/models/GalleryCategory.php';
		$model = new GalleryCategory();

		$categoryId  = $this->_getParam('category_id');
		$categoryUrl = $this->_getParam('category_url');

		$select = (null === $select)
			? $model->select() : $select;

		$select
			->where('id = ?', $categoryId)
			->where('url = ?', $categoryUrl)
			->where('publicated = 1');	// tylko opublikowane

		try {
			$row = $model->fetchRow($select);
			return $row;
		} catch (Zend_Db_Table_Abstract $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		return null;
	}

	/**
	 * Wyszukuje w stosie kategorii - kategorię o danym id lub url
	 *
	 * @param integer $categoryKey
	 * @param Zend_Db_Table_Rowset_Abstract $rowset
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _findActiveCategoryByKey($categoryKey, Zend_Db_Table_Rowset_Abstract $rowset) {
		do {
			$row = $rowset->current();

			if ($row->id == $categoryKey || $row->url == $categoryKey) {
				return $row;
			}

			$rowset->next();
		} while ($rowset->valid());

		return null;
	}
	
	/**
	 * Wyszukuje galerie nalezace do kategorii
	 *
	 * @param Zend_Db_Table_Row_Abstract $category
	 * @return Zend_Db_Table_Rowset_Abstract
	 */
	protected function _fetchRowsetGallery(Zend_Db_Table_Row_Abstract $category) {
		require_once 'gallery/models/Gallery.php';
		$gallery = new Gallery();

		$request = $this->getRequest();
		// okreslamy widocznosc rekordow dla użytkownika
		$select = $gallery->selectForSpecialCredentials($request);
		// okreslamy przedzial czasowy rekordow
		$year  = $this->view->year  = $this->_getParam('year',date('Y'));
		$month = $this->view->month = $this->_getParam('month');
		$gallery->selectSetupForTimeRange($select, $year, $month);
		
		$select
			->where('publicated = 1');
		
		try {
			return $category->findDependentRowset($gallery, null, $select);
		} catch (Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		return null;
	}

	/**
	 * Znajduje opis do galerii
	 *
	 * @param Zend_Db_Table_Row_Abstract $row
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _fetchRowsetGalleryDescription(Zend_Db_Table_Row_Abstract $row) {
		// TODO Może byc przypadek ze `language_url` o `i18n` jest NULL
		$i18n = $this->_frontController->getParam('i18n');
		// TODO Dodac `language_url` z konfiguracji
    	$language_url = $this->_getParam('language_url', $i18n);

		$select = $row->select()
			->limit(1)
			->where('language_url = ?',$language_url);

		try {
			require_once 'gallery/models/GalleryDescription.php';
			$rowset = $row->findDependentRowset('GalleryDescription', null, $select);
			return $rowset->current();
		} catch (Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		return null;
	}
	
	/**
	 * Wyszukuje w stosie galerii - galerii o danym id lub url
	 *
	 * @param integer $key
	 * @param Zend_Db_Table_Rowset_Abstract $rowset
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _findActiveGalleryByKey($key, Zend_Db_Table_Rowset_Abstract $rowset) {
		do {
			$row = $rowset->current();
			if ($row->id == $key || $row->url == $key) {
				return $row;
			}

			$rowset->next();
		} while ($rowset->valid());

		return null;
	}

	/**
	 * Wyszukuje w stosie galerii - galerii o danym id lub url
	 *
	 * @param string $image
	 * @param Zend_Db_Table_Rowset_Abstract|array $rowset
	 * @return Zend_Db_Table_Row_Abstract|stdObject
	 */
	protected function _findActiveImageByImage($image, $rowset) {
		do {
			$row = current($rowset);
			if ($row->image == $image) {
				return $row;
			}
			next($rowset);
		} while (!$rowset);

		return null;
	}
}