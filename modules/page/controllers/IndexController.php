<?php
require_once 'KontorX/Controller/Action.php';
class Page_IndexController extends KontorX_Controller_Action {
	public $skin = array(
		'layout' => 'page'
	);

	public function init() {
		parent::init();
		$this->view->pageUrl  = $this->_getParam('url');
	}

	/**
	 * Wyswietla strone wraz z dodatkowymi apletami
	 *
	 */
	public function pageAction() {
		$url	  = $this->_getParam('url');
		$language = $this->_helper->system->language();

		require_once 'page/models/Page.php';
		$model = new Page();

		$request = $this->getRequest();
		// okreslamy widocznosc rekordow dla użytkownika
		$select = $model->selectForSpecialCredentials($request);

		$row = null;
		try {
			$row = $model->fetchRowPublic($url, $language, $select);
		} catch (Zend_Db_Table_Abstract $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$row instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('page.noexsists');
			return;
		}

		// czy jest redirect ?
		if ($row->redirect != '') {
			$this->_helper->redirector->goToUrlAndExit($row->redirect);
			return;
		}

		$this->view->row = $row;
		
		// wyszukanie bloków dla strony
		$this->_pageHelperBlocks($row);

		// public select
		$select = $model->selectPublic($language);
		// okreslamy widocznosc rekordow dla użytkownika
		$select = $model->selectForSpecialCredentials($request, $select);

		// sciezka zagnieżdżenia rekordu
		$this->_pageHelperPath($row, $select);
		
		// rekordy podstron lub w przypadku braku podstron rekordy nadstron
		$this->_pageHelperSubPages($row, $select);
	}

	/**
	 * Wyszukanie bloków dla strony @see PageBlock
	 *
	 * @param Zend_Db_Table_Row_Abstract $row
	 */
	protected function _pageHelperBlocks(Zend_Db_Table_Row_Abstract $row) {
		try {
			$this->view->pageBlocks = $row->findDependentBlocksRowset();
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	/**
	 * Sciezka zagnieżdżenia rekordu
	 *
	 * @param Zend_Db_Table_Row_Abstract $row
	 * @param Zend_Db_Select $select
	 */
	protected function _pageHelperPath(Zend_Db_Table_Row_Abstract $row, Zend_Db_Select $select) {
		try {
			$this->view->pageDescendants = $row->findDescendant(clone $select, true);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	/**
	 * Rekordy podstron lub w przypadku braku podstron rekordy nadstron
	 *
	 * @param Zend_Db_Table_Row_Abstract $row
	 * @param Zend_Db_Select $select
	 */
	protected function _pageHelperSubPages(Zend_Db_Table_Row_Abstract $row, Zend_Db_Select $select) {
		try {
			$parentChildrens = $row->findChildrens(1, clone $select);
			// nie ma dzieci to trzymamy się rodzica
			// o nizszym poziomie zagnieżdżenia);
			// root level sie nie liczy bo po co!
			if (!count($parentChildrens) && $row->depth > 0) {
				$parentChildrens = $row->findParents(1, $select);
			}
			$this->view->pageChildrens = $parentChildrens;
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}
}