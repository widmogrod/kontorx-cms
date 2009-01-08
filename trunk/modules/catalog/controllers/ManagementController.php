<?php
require_once 'KontorX/Controller/Action.php';
class Catalog_ManagementController extends KontorX_Controller_Action {

	public $skin = array(
		'layout' => 'administration'
	);
	
	public $ajaxable = array(
		'service' => array('html'),
		'images' => array('html')
	);

	public function init() {
		parent::init();

		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->initContext();
	}
	
	public function indexAction(){
		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$rowset = $manage->findCatalogRowsetForUser($this->getRequest());
		$this->view->rowset = $rowset;
	}
	
	public function editAction() {
		require_once 'catalog/models/Management.php';
		$manage = new Management();
		
		$rq = $this->getRequest();
		
		$row = $manage->findCatalogRowForUser($this->_getParam('id'), $rq);
		$this->view->row = $row;

		if (null === $row) {
			 $this->_helper->viewRenderer->render('edit.error');
			 return;
		}

		$form = $this->_getEditForm($row);
		$this->view->form = $form;
		
		if (!$rq->isPost()) {
			$form->setDefaults($row->toArray());
			return;
		}

		if (!$form->isValid($rq->getPost())) {
			return;
		}

		try {
			$data = $rq->getPost();

			if (isset($data['user_id'])) {
				unset($data['user_id']);
			}
			
			$row->setFromArray($data);
			$row->save();
			
			$message = "Wizytówka została zedytowana";
			$this->_helper->flashMessenger($message);
			
			$this->_helper->redirector->goToUrlAndExit(
				$this->_helper->url->url(array())
			);
		} catch(Zend_Db_Table_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString());

			$message = "Wizytówka nie została zedytowana, proszę spróbować jeszcze raz";
			$this->_helper->flashMessenger($message);
		}
	}

	/**
	 * @return Catalog_Form_ManagementCatalogEditForm
	 */
	private function _getEditForm(Zend_Db_Table_Row_Abstract $row) {
		require_once 'Catalog/Form/ManagementCatalogEditForm.php';
		$form = new Catalog_Form_ManagementCatalogEditForm($row->getTable());
		
		return $form;
	}

	public function serviceAction() {
		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$id = $this->_getParam('id');
		$rq = $this->getRequest();

		// Czy rekord nalerzy do uzytkownika!?
		if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
			$this->_helper->viewRenderer->render('edit.error');
			 return;
		}

		$this->view->row = $row;
		$this->view->rowset = $manage->findServicesRowsetForCatalogId($id);
		
		if ($rq->isPost()) {
			if ($manage->saveServicesCost($id, $rq)) {
				$message = "Usługi zostały zapisane";
				$this->_helper->flashMessenger($message);

//				$this->_helper->redirector->goToUrlAndExit(
//					$this->_helper->url->url(array())
//				);
			} else {
				$message = "Usługi nie zostały zapisane";
				$this->_helper->flashMessenger($message);
			}
		}
	}

	public function imagesAction() {
		require_once 'catalog/models/Management.php';
		$manage = new Management();

		$id = $this->_getParam('id');
		$rq = $this->getRequest();

		// Czy rekord nalerzy do uzytkownika!?
		if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
			$this->_helper->viewRenderer->render('edit.error');
			 return;
		}
		
		$this->view->row = $row;
		
		try {
			require_once 'catalog/models/CatalogImage.php';
			$this->view->rowset = $row->findDependentRowset('CatalogImage');
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
		}
	}
}