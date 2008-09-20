<?php
require_once 'KontorX/Controller/Action.php';
class Calendar_IndexController extends KontorX_Controller_Action {
	public $skin = array('layout' => 'calendar');

	/**
	 * Pokazuje wydarzenia w kalendarium
	 *
	 */
	public function indexAction() {
		$page 	  = $this->_getParam('page', 1);
		$rowCount = $this->_getParam('rowCount', 10);

		require_once 'calendar/models/Calendar.php';
		$model = new Calendar();

		$select = $model->select()
			->limitPage($page, $rowCount);
			
		// okreslamy przedzial czasowy rekordow
		$year  = $this->_getParam('year',date('Y'));
		$month = $this->_getParam('month');
		$model->selectSetupForTimeRange($select, $year, $month);

        try {
			$this->view->rowset = $model->fetchAllByTime(time(), $select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('index.error');
		}
	}

	/**
	 * Listuje wydarzenia w kalendarium
	 *
	 */
	public function listAction() {
		$page 	  = $this->_getParam('page', 1);
		$rowCount = $this->_getParam('rowCount', 10);

		require_once 'calendar/models/Calendar.php';
		$model = new Calendar();

		$select = $model->select()
			->limitPage($page, $rowCount);
		
		try {
			$this->view->rowset = $model->fetchAllByTime(time(), $select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('index.error');
		}
	}
	
	/**
	 * Wyswietla wydarzenie w kalendarium
	 *
	 */
	public function displayAction() {
		require_once 'calendar/models/Calendar.php';
		$model = new Calendar();

		$select = $model->select()
			->where('id = ?', $this->_getParam('id'));

		try {
			$this->view->row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Row_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('display.error');
			return;
		} catch (Zend_Db_Statement_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('display.error');
			return;
		}

		if (!$this->view->row instanceof Zend_Db_Table_Row_Abstract) {
			$this->_helper->viewRenderer->render('display.error');
		}

		$select = $this->view->row->select()
			->limit(1)
			->where('language_url = ?', $this->getLanguage());

		try {
			require_once 'calendar/models/CalendarContent.php';
			$rowset = $this->view->row->findDependentRowset('CalendarContent', null, $select);
			$this->view->rowContent = $rowset->current();
		} catch (Zend_Db_Table_Row_Exception $e) {
			var_dump($e->getMessage());
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			return;
		}
	}
}