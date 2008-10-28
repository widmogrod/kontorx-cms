<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Catalog_CatalogController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin'
	);

	protected $_modelClass = 'Catalog';

	protected function _getConfigFormFilename($controller) {
		return strtolower("$controller.xml");
	}
	
	public function listAction() {
		$this->view->addHelperPath('KontorX/View/Helper');

		$config = $this->_helper->loader->config('catalog.xml');
		
		$model = $this->_getModel();

		$grid = KontorX_DataGrid::factory($model);
		$grid->setColumns($config->dataGridColumns->toArray());
		$grid->setValues((array) $this->_getParam('filter'));
		
		// setup grid paginatior
		$select = $grid->getAdapter()->getSelect();
		$paginator = Zend_Paginator::factory($select);
		$grid->setPaginator($paginator);
		$grid->setPagination($this->_getParam('page'), 20);

		$this->view->grid = $grid;
		$this->view->actionUrl = $this->_helper->url('list');
	}

	/**
     * @Overwrite
     */
    protected function _addPrepareData(Zend_Form $form) {
    	$data = parent::_addPrepareData($form);
    	
    	require_once 'user/models/User.php';
    	$userId = User::getAuth(User::AUTH_USERNAME_ID);

    	$data['user_id'] 	  = $userId;
    	return $data;
    }
	
	/*public function init() {
		$this->_helper->acl->setAccess(true);
	}*/
}