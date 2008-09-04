<?php
require_once 'KontorX/Controller/Action.php';
class Group_IndexController extends KontorX_Controller_Action {

//	public $scaffolding = array(
//		'newsadd' => array(
//			'add',
//			'callbacks' => array(
//				'TRIGGER_GET_MODEL' => 'getNewsModel'
//			)
//		)
//	);

	public function init() {
		$this->_initLayout('group',null,null,'default');

		$this->view->pageUrl  = $this->_getParam('url');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();

		require_once 'user/models/User.php';
		$this->view->userId = User::getAuth(User::AUTH_USERNAME_ID);
		
//		$this->_helper->scaffolding();
	}

	public function indexAction() {
		require_once 'group/models/Group.php';
		$model = new Group();

		$this->view->year = $year = $this->_getParam('year',date('Y'));
		
		// przygotowanie zapytania
		$select = $model->selectForSpecialCredentials($this->getRequest());
		$select
			->where('YEAR(t_create) = ?', $year)
			->order('t_create DESC');

		try {
			$this->view->rowset = $model->fetchAll($select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	public function displayAction() {
		require_once 'group/models/Group.php';
		$model = new Group();

		$primatyKey = $this->_getParam('group_id', $this->_getParam('id'));
		
		if (null === $primatyKey) {
			$this->_helper->viewRenderer->render('display.no.exists');
			return;
		}
		
		// przygotowanie zapytania
		$select = $model->selectForSpecialCredentials($this->getRequest());
		$select->where('id = ?', $primatyKey);

		// odszukaj grupę
		try {
			$this->view->row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$this->view->row instanceof Zend_Db_Table_Row) {
			$this->_helper->viewRenderer->render('display.no.exists');
			return;
		}

		// odszukaj właściciela
		try {
			$this->view->rowUser = $this->view->row->findParentRow('User');
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
			// TODO Nie znajduje właściciela jest ŹLE!!
		}

		// przygotowanie zapytania select
		$select = $model->select()
			->order('username ASC');

		// odszukaj użytkowników należących do grupy
		try {
			$this->view->rowsetUser = $this->view->row->findManyToManyRowset('User','GroupHasUser',null,null,$select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		$select = $model->select()
			->where('publicated = 1');
		// odszukaj aktualności należące do grupy
		try {
			$this->view->rowsetNews = $this->view->row->findDependentRowset('GroupNews', null, $select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		$select = $model->select()
			->limit(5);
		// odszukaj grafiki należące do grupy
		try {
			$this->view->rowsetImage = $this->view->row->findDependentRowset('GroupGalleryImage', null, $select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
	}

	public function galleryAction() {
		require_once 'group/models/Group.php';
		$model = new Group();

		$primatyKey = $this->_getParam('group_id', $this->_getParam('id'));
		
		if (null === $primatyKey) {
			$this->_helper->viewRenderer->render('display.no.exists');
			return;
		}
		
		// przygotowanie zapytania
		$select = $model->selectForSpecialCredentials($this->getRequest());
		$select->where('id = ?', $primatyKey);

		// odszukaj grupę
		try {
			$this->view->row = $model->fetchRow($select);
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}

		if (!$this->view->row instanceof Zend_Db_Table_Row) {
			$this->_helper->viewRenderer->render('display.no.exists');
			return;
		}

		// odszukaj grafiki należące do grupy
		try {
			$this->view->rowsetImage = $this->view->row->findDependentRowset('GroupGalleryImage');
		} catch (Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
		
		// sprawdzam czy jest zaznaczona grafika
		if ($this->_hasParam('image')) {
			$this->view->image = $this->_getParam('image');
		}
		// jak nie to pierwsza z stosu jest domyslna
		else {
			$this->view->image = count($this->view->rowsetImage)
				? $this->view->rowsetImage->current()->image
				: null;
		}
	}
}