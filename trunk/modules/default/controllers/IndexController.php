<?php
require_once 'KontorX/Controller/Action.php';
class Default_IndexController extends KontorX_Controller_Action {
	
	public $skin = array('layout' => 'home');
	
	public function indexAction() {
		$this->_forward('index','index','catalog');
//		$this->_helper->viewRenderer->setNoRender();
		
//		
//		$this->view->addHelperPath('KontorX/View/Helper');
//		
//		require_once 'catalog/models/Catalog.php';
//		$model = new Catalog();
//		
//		$grid = KontorX_DataGrid::factory($model);
//		$grid->addColumn('name',array(
//			'type' => 'Order',
//			'name' => 'Nazwa',
//			'filter' => array(
//				'type' => 'text'
//			)
//		));
//		$grid->addColumn('adress',array(
//			'type' => 'Order',
//			'name' => 'Adres',
//			'filter' => array(
//				'type' => 'text'
//			)
//		));
//		$grid->addColumn('options',array(
//			'name' => '&nbsp;',
//			'row' => array(
//				'type' => 'html',
//				'content' => '<a href="edit/{id}">zobacz wizytówkę</a>'
//			)
//		));
//
//		$paginator = Zend_Paginator::factory($grid->getAdapter()->getSelect());
//		$grid->setPaginator($paginator);
//		$grid->setPagination($this->_getParam('page'), 5);
//
////		$grid->setOrder(array('id','image','options'));
//		
//		// TODO ..
//		$grid->setValues((array) $this->_getParam('filter'));
//		
////		$grid->setRequest($this->_request->getPost('filter', array()));
////
////		$grid->enablePagination();
////		$grid->setPaginationOptions();
//		
//		$this->view->grid = $grid;
		
//		$this->_helper->actionStack
//			->actionToStack('list','index','news', array('rowCount' => 5,'pagination' => false))
//			->actionToStack('display','index','calendar', array('rowCount' => 5));
//print 1;
//		$this->_initLayout('home');
//		
//		$odf = new KontorX_Odf_Import(PUBLIC_PATHNAME . 'content.xml');
//		print (string) $odf;

//		$g = new KontorX_Util_Google('http://www.stempel.kr.com.pl');
//		$p = $g->position('pieczątki, kraków');
	}
}