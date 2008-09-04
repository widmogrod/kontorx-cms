<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class User_RoleController extends KontorX_Controller_Action_CRUD {
	protected $_modelClass = 'Role';

	public function init() {
		$this->_initLayout('user');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

	public function indexAction() {
		$this->_forward('list');
	}

	public function aclgenerateAction() {
		// wylaczenie widoku
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();

		require_once 'user/models/RoleHasRoleResource.php';
		$rhrr = new RoleHasRoleResource();
		
		try {
			$rowset = $rhrr->fetchAllAsAclFormatNames();
			$aclArray = $rhrr->reOrderToAclArray($rowset);
			
			require_once 'KontorX/Config/Generate.php';
			$iniGenerate = KontorX_Config_Generate::factory($aclArray, KontorX_Config_Generate::INI);

			$iniAlc = (string) $iniGenerate;

			$filename = APP_CONFIGURATION_PATHNAME . 'acl.ini';

			if(!is_writable($filename) || !@file_put_contents($filename, $iniAlc)) {
				$message = 'Wystąpił błąd podczas zapisu uprawnień';
			} else {
				$message = 'Zapis uprawnień zakończony powodzeniem';
			}
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjątku
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$message = 'Wystąpił problem podczas wyławiania uprawnień z bazy danych';
		}

		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(
			$this->_helper->url->url(array('action' => 'list'))
		);
	}
	
	/**
	 * Wizualne tworzenie uprawnień
	 *
	 */
	public function creatorAction() {
		$this->_initLayout('user_role_creator');

		$roleId = $this->_getParam('id');
		
		require_once 'user/models/RoleHasRoleResource.php';
		$rhrr = new RoleHasRoleResource();

		$select = $rhrr->select()
			->where('role_id = ?', $roleId);

		try {
			// wyłów - jeżeli istnieją - uprawnienia
			$this->view->resources = $rhrr->fetchAll($select);
		} catch (Zend_Db_Table_Exception $e) {}
		
		require_once 'user/models/RoleResource.php';
		$resource = new RoleResource();

		try {
			// pobierz szkielet uprawnień
			$rowset = $resource->fetchAllJoinAccess();
			// przesortuj szkielet uprawnień dla widoku
			$rowset = $resource->reOrderResourceToAccess($rowset);

			$this->view->rowset = $rowset;
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjątku
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('creator.error');
		}
	}

	/**
	 * Zapis uprawnień
	 *
	 */
	public function saveAction() {
		$this->_helper->viewRenderer->setNoRender();

		if (!$this->_request->isPost()) {
			$this->_forward('creator');
			return;
		}

		$roleId = $this->_getParam('id');
		$data = (array) $this->_request->getPost('data');
		
		require_once 'user/models/RoleHasRoleResource.php';
		$rhrr = new RoleHasRoleResource();

		$db = $rhrr->getAdapter();
		$db->beginTransaction();

		$success = false;
		
		try {
			// profilaktycznie czyscimy baze gdyby
			// rola posiadala jakieś uprawnienia
			try {
				$where = $db->quoteInto('role_id = ?', $roleId);
				$rhrr->delete($where);
			} catch (Zend_Db_Statement_Exception $e) {}

			// dodajemy rekordy
			$rhrr->insertRowsFromRawData($data, $roleId);
			$db->commit();
			
			$success = true;
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjątku
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$db->rollBack();
		} catch (Zend_Db_Statement_Exception $e) {
			// logowanie wyjątku
			Zend_Registry::get('logger')
				->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);

			$db->rollBack();
		}

		switch ($success) {
			case true:
				$message = 'Uprawnienia zostały utworzone';
				$url	 = array('action' => 'list');
				break;
			case false:
				$message = 'Uprawnienia NIE zostały utworzone';
				$url	 = array('action' => 'creator');
				break;
		}
		
		$this->_helper->flashMessenger->addMessage($message);
		$this->_helper->redirector->goToUrlAndExit(
			$this->_helper->url->url(array('action' => 'creator')));
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
		$this->_preparePagination($select);

    	return $rowset;
    }

    /**
     * @Overwrite
     * @return Zend_Form
     */
    protected function _addGetForm() {
    	$form = parent::_addGetForm();

    	$model = $this->_getModel();
    	$select = $model->select()
    		->where('name = ?', $this->_request->getPost('name'));

    	// setup @see Zend_Form
    	$this->_setupZendForm($form, $model, $select);
    	return $form;
    }

	/**
     * @Overwrite
     * @return Zend_Form
     */
    protected function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
    	$form = parent::_editGetForm($row);

    	$model = $this->_getModel();
    	$select = $model->select()
    		->where('name = ?', $this->_request->getPost('name'))
    		->where('id <> ?', $this->_getParam('id'));

    	// setup @see Zend_Form
    	$this->_setupZendForm($form, $model, $select);
    	return $form;
    }

    /**
     * Ustawia opcje formularza
     *
     * @param Zend_Folm $form
     * @param Zend_Db_Table_Abstract $model
     * @param Zend_Db_Select $select
     */
    protected function _setupZendForm(Zend_Form $form, Zend_Db_Table_Abstract $model, Zend_Db_Select $select) {
    	require_once 'KontorX/Validate/DbTable.php';
    	$nameValid = new KontorX_Validate_DbTable($model, $select);

    	$form
    		->getElement('name')
    		->addValidator($nameValid);
    }
}