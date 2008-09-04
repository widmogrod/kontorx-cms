<?php
require_once 'KontorX/Controller/Action.php';
class Stats_GoogleController extends KontorX_Controller_Action {

	public $ajaxable = array(
		'stats' => array('json')
	);
	
	public function init() {
		$this->_initLayout('stats_google');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
		
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();
	}

	public function indexAction() {
		$month 	= $this->_getParam('month', date('m'));
		$day 	= $this->_getParam('day', date('d'));
		$year 	= $this->_getParam('year', date('Y'));
		$time 	= mktime(0,0,0, $month, $day, $year);

		$this->view->month = $month;
		$this->view->date  = $this->_getParam('date', date('Y-m-d', $time));

		require_once 'stats/models/GoogleSite.php';
		$model = new GoogleSite();

		try {
			$rowsetSite = $model->fetchAll();
			$this->view->rowsetSite = $rowsetSite;
		} catch (Zend_Db_Table_Exception $e) {
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if ($this->_hasParam('site_id')) {
			$rowSite = $this->_findActiveSiteById($this->_getParam('site_id'), $rowsetSite);
		} else {
			$rowSite = $rowsetSite->current();
		}

		$this->view->site_id = $rowSite->id;
	}

	public function statsAction() {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();

		$row = $this->_fetchRowSite($this->_getParam('site_id'));
		if (null === $row) {
			// TODO konsekwencje JSON view dla tej akcji!
			$this->_forward('index');
			return;
		}

//		require_once 'Zend/Db/Select.php';
//		$select = new Zend_Db_Select($db);

		$select = $row->select();
//		$select
//			->from(array('gs' => 'google_stats'))
//			->join(array('gk' => 'google_keyword'),array('keyword' => 'gk.keyword'),'gk.site_id = gs.site_id')
//			->where('gs.site_id = ?', $this->_getParam('site_id'));

		$time = strtotime($this->_getParam('date', time()));
		$firstDayInMonth = date('Y-m-d', mktime(0,0,0,date('m', $time),1,  date('Y',$time)));
		$lastDayInMonth	 = date('Y-m-d', mktime(0,0,0,date('m', $time)+1,1,date('Y',$time)));

		$select->where("time BETWEEN '$firstDayInMonth' AND '$lastDayInMonth' ");
		
		try {
			$rowset = $row->findManyToManyRowset('GoogleKeyword', 'GoogleStats',null, null, $select);
			$this->view->rowset = $rowset;
		} catch (Zend_Db_Table_Exception $e) {
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	public function generatestatsAction() {
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		$site_id = $this->_getParam('site_id');
		
		$row = $this->_fetchRowSite($site_id);
		if (null === $row) {
			$message = 'Rekord nie istnieje';
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
			return;
		}

		// przygotowanie adresu strony
		$site = str_ireplace(array('http://','https://'), '',$row->url);

		// sprawdz tylko okreslone slowo kluczowe
		if ($this->_hasParam('keyword_id')) {
			
		}
		// sprawdz wszystkie slowa kluczowe
		else {
			$rowsetKeyword = $row->findDependentRowset('GoogleKeyword');
		}

		// brak dalszych operacji gdy nie ma slow kluczowych
		if (!count($rowsetKeyword)) {
			$message = 'Strona nie posiada słów kluczowych';
			$this->_helper->flashMessenger->addMessage($message);
			$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
			return;
		}

		require_once 'stats/models/GoogleStats.php';
		$stats = new GoogleStats();
		
		$google = new KontorX_Util_Google($site);
		foreach ($rowsetKeyword as $rowKeyword) {
			$position = $google->position($rowKeyword->keyword);

			$data = array(
				'position' 	=> $position,
				'site_id'  	=> $site_id,
				'keyword_id'=> $rowKeyword->id,
				'time'		=> date('Y-m-d')
			);

			try {
				$stats->insert($data);
			} catch (Zend_Db_Statement_Exception $e) {
				// statement prawdopodobnie dlatego że klucz nie jest unikalny tj.
				// wartosc daty slowa kluczowego i strony nie moze się powtarzać!
				$logger = Zend_Registry::get('logger');
				$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::WARN);
			}
		}

		$message = 'Słówa kluczowye zostały sprawdzone';
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
	}
	
	/**
	 * Wyszukuje strony
	 *
	 * @param integer $id
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _fetchRowSite($id, Zend_Db_Table_Select $select = null) {
		require_once 'stats/models/GoogleSite.php';
		$model = new GoogleSite();
		
		$select = null === $select
			? $model->select() : $select;

		$select->where('id = ?', $id);

		try {
			return $model->fetchRow($select);
		} catch (Zend_Db_Table_Exception $e) {
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
		
		return null;
	}
	
	/**
	 * Wyszukuje w stosie stron - stronę o danym id
	 *
	 * @param integer $id
	 * @param Zend_Db_Table_Rowset_Abstract $rowset
	 * @return Zend_Db_Table_Row_Abstract
	 */
	protected function _findActiveSiteById($id, Zend_Db_Table_Rowset_Abstract $rowset) {
		do {
			$row = $rowset->current();

			if ($row->id == $id) {
				return $row;
			}

			$rowset->next();
		} while ($rowset->valid());

		return null;
	}
}