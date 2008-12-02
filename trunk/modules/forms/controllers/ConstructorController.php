<?php
require_once 'KontorX/Controller/Action.php';
class Forms_ConstructorController extends KontorX_Controller_Action {

	public $skin = array(
		'layout' => 'admin'
	);
	
	public $ajaxable = array(
		'list' =>array('json'),
		'load' =>array('json'),
		'add' => array('json'),
		'delete' => array('json'),
		'preview' => array('html','ini')
	);
	
	public function init() {
		parent::init();
		
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext
			->addContext('ini',array('suffix' => 'ini'))
			->initContext();
	}
	
	public function indexAction() {
		
	}

	public function listAction() {
		$config = $this->_helper->loader->config('config.ini');

		require_once 'forms/models/Forms.php';
		$forms = new Forms($config->pathname);
		
		$this->view->rowset = $forms->fetchAll();
	}

	public function loadAction() {
		$config = $this->_helper->loader->config('config.ini');

		require_once 'forms/models/Forms.php';
		$forms = new Forms($config->pathname);

		$form = $this->_getParam('form');
		
		if (!$forms->has($form)) {
			$this->_helper->viewRenderer->render('load.error');
			return;
		}
		
		try {
			$data = $forms->load($form);

			$this->view->options = $data->options->toArray(); 
			$this->view->form 	 = $data->form->toArray();
		} catch (FormsException $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n". $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->viewRenderer->render('load.error');
		}
	}

	public function addAction() {
		$config = $this->_helper->loader->config('config.ini');

		require_once 'forms/models/Forms.php';
		$forms = new Forms($config->pathname);

		$form = $this->_getParam('form');
		$options = $this->_getParam('options');
		
		$data = array(
			'options' => $options,
			'form' => $form
		);
		
		try {
			$forms->save($options['name'], $data);

			$this->view->success = true;
		} catch (FormsException $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n". $e->getTraceAsString(), Zend_Log::ERR);
				

			$this->_helper->flashMessenger->addMessage("Formularz nie został zapisany");
		}
	}

	public function deleteAction() {
		$config = $this->_helper->loader->config('config.ini');

		require_once 'forms/models/Forms.php';
		$forms = new Forms($config->pathname);

		$form = $this->_getParam('form');
		
		try {
			$forms->delete($form);

			$this->view->success = true;
		} catch (FormsException $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n". $e->getTraceAsString(), Zend_Log::ERR);

			$this->_helper->flashMessenger->addMessage("Formularz nie został usunięty!");
		}
	}

	public function previewAction() {
		$this->_helper->layout->disableLayout();
		
		$elements = $this->getRequest()->getPost('elements',array());
		$elements = array('elements' => $elements);
		
		require_once 'Zend/Form.php';
		$form = new Zend_Form($elements);

		$this->view->elements = $elements;
		$this->view->form = $form;
	}
}
