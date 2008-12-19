<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Catalog_ServiceController extends KontorX_Controller_Action_CRUD {
	public $skin = array(
		'layout' => 'admin'
	);

	protected $_modelClass = 'CatalogService';
	
	protected function _getModel() {
		if (null == $this->_model) {
			$this->_model = parent::_getModel();

			$config = $this->_helper->loader->config();
			$path = $config->path->upload->service;
			$path = $this->_helper->system()->getPublicHtmlPath($path);
			
			CatalogService_Row::setUploadPath($path);
		}
		return $this->_model;
	}

	public function listAction() {
		$this->view->addHelperPath('KontorX/View/Helper');
		
		$config = $this->_helper->loader->config('service.ini');
		
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
	 * Overwrite
	 */
	protected function _addGetForm() {
		$form = parent::_addGetForm();

		// setup @see Zend_Form
		$this->_setupZendForm($form);

		return $form;
	}

	protected function _addInsert(Zend_Form $form) {
		$data = $this->_addPrepareData($form);

		try {
			// dodawanie rekordu
	    	$model = $this->_getModel();
	    	$row = $model->createRow($data);
	    	$row->save();
		} catch (KontorX_Db_Table_Row_FileUpload_Exception $e) {
			if($row->hasMessages()) {
				$flashMessenger = $this->_helper->flashMessenger;
				foreach ($row->getMessages() as $message) {
					$flashMessenger->addMessage($message);
				}
			}
			
			throw new KontorX_Db_Table_Row_FileUpload_Exception(implode("\n",$row->getMessages()));
		}

		return $row;
	}
	
	/**
	 * Overwrite
	 */
	protected function _editGetForm(Zend_Db_Table_Row_Abstract $row) {
		$form = parent::_editGetForm($row);

		// setup @see Zend_Form
		$this->_setupZendForm($form, true);

		return $form;
	}
	
	/**
     * Ustawia opcje formularza
     *
     * @param Zend_Folm $form
     * @param bool $edit
     */
    protected function _setupZendForm(Zend_Form $form, $edit = false) {
    	$form->setAttrib('enctype','multipart/form-data');

    	require_once 'KontorX/Form/Element/File.php';
		$form->addElement(new KontorX_Form_Element_File('ico'), 'ico');
		$image = $form->getElement('ico');
		$image->setLabel('ico');

		if (true === $edit) {
			$image->setIgnore(true);
		}
    }
}