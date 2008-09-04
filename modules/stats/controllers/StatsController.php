<?php
/**
 * Dodawanie danych statystycznych o odwiedzinach
 *
 */
class Stats_StatsController extends KontorX_Controller_Action {

	public $ajaxable = array(
		'list' => array('json'),
		'listPageVisit' => array('json'),
		'listBrowserVisit' => array('json')
	);
	
	public function init() {
		$this->_helper->ajaxContext()
			->setAutoJsonSerialization(false)
			->initContext();
	}

	public function indexAction() {
		$this->_helper->viewRenderer->setNoRender(true);
		$this->_helper->layout->disableLayout();

		$visitor = false;
		// sprawdz czy jest odwiedzajacy - cookie
		if (isset($_COOKIE['visitor'])) {
			// juz byl
			$visitor = true;
		} else {
			// nowy
			setcookie('visitor','1',7600 ,'/s');
		}

		// pobieranie danych
		$ip	   = KontorX_Util_Functions::getIP();
		$url   = getenv('REQUEST_URI');
		$time  = date('Y-m-d H:i:s');
		$agent = getenv('HTTP_USER_AGENT');
		$browser = KontorX_Util_Functions::getBrowser();
		$referer = getenv('HTTP_REFERER');

		$data = array(
			'ip' 	=> $ip,
			'url' 	=> $url,
			'time' 	=> $time,
			'agent' => $agent,
			'browser' => $browser,
			'referer' => $referer,
			'visitor' => (int) $visitor
		);

		require_once 'stats/models/Stats.php';
		$model = new Stats();
		$row   = $model->createRow($data);

		try {
			$row->save();
		} catch(Zend_Db_Table_Row_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}
	
	public function listAction() {
		require_once 'stats/models/Stats.php';
		$model = new Stats();

		$select = $model->select();
			$select->order('time ASC');

		if ($this->_hasParam('date')) {
			$time =strtotime($this->_getParam('date', time()));
			$firstDayInMonth = date('Y-m-d H:i:s', mktime(0,0,0,date('m', $time),1, date('Y',$time)));
			$lastDayInMonth	 = date('Y-m-d H:i:s', mktime(0,0,0,date('m', $time)+1,1,date('Y',$time)));
			$select->where("time BETWEEN '$firstDayInMonth' AND '$lastDayInMonth' ");
		}

		try {
			$this->view->rowset = $model->fetchAll($select);
		} catch(Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	public function listpagevisitAction() {
		require_once 'stats/models/Stats.php';
		$model = new Stats();

		$select = $model->select();
		$select
			->from('stats',array(Zend_Db_Select::SQL_WILDCARD, 'count'=>'count(url)', 'countIP'=>'count(ip)'))
			->group('url');
		
		if ($this->_hasParam('date')) {
			$time =strtotime($this->_getParam('date', time()));
			$firstDayInMonth = date('Y-m-d H:i:s', mktime(0,0,0,date('m', $time),1, date('Y',$time)));
			$lastDayInMonth	 = date('Y-m-d H:i:s', mktime(0,0,0,date('m', $time)+1,1,date('Y',$time)));
			$select->where("time BETWEEN '$firstDayInMonth' AND '$lastDayInMonth' ");
		}
			
		$rowset = $model->fetchAll($select)->toArray();
		$this->view->rowset = $rowset;
	}

	public function listbrowservisitAction() {
		require_once 'stats/models/Stats.php';
		$model = new Stats();

		$select = $model->select();
		$select
			->from('stats',array(Zend_Db_Select::SQL_WILDCARD, 'count'=>'count(browser)'))
			->group('browser');

		if ($this->_hasParam('date')) {
			$time =strtotime($this->_getParam('date', time()));
			$firstDayInMonth = date('Y-m-d H:i:s', mktime(0,0,0,date('m', $time),1, date('Y',$time)));
			$lastDayInMonth	 = date('Y-m-d H:i:s', mktime(0,0,0,date('m', $time)+1,1,date('Y',$time)));
			$select->where("time BETWEEN '$firstDayInMonth' AND '$lastDayInMonth' ");
		}
		
		$rowset = $model->fetchAll($select)->toArray();
		$this->view->rowset = $rowset;
	}
}