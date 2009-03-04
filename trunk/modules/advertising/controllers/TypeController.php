<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Advertising_TypeController extends KontorX_Controller_Action_CRUD {

    public $skin = array(
        'layout' => 'admin_advertising',
        'config' => array(
            'filename' => 'backend_config.ini'
        )
    );

    protected $_modelClass = 'AdvertisingType';
    protected $_configFilenameExtension = 'xml';

    public function init() {
        parent::init();

        $this->view->addHelperPath('KontorX/View/Helper');
    }

    public function indexAction() {
        $this->_forward('list');
    }

    public function listAction() {
        $params = (array) $this->_getParam('filter');
        $config = $this->_helper->loader->config('type.xml');
        $model  = $this->_getModel();

        $grid = KontorX_DataGrid::factory($model);
        $grid->setColumns($config->dataGridColumns->toArray());

        // filtrowanie parametrow
        require_once 'KontorX/Filter/MagicQuotes.php';
        $f = new KontorX_Filter_MagicQuotes();
        $params = $f->filter($params);

        $grid->setValues($params);

        // setup grid paginatior
        $select = $grid->getAdapter()->getSelect();
        $paginator = Zend_Paginator::factory($select);
        $grid->setPaginator($paginator);
        $grid->setPagination($this->_getParam('page'), 20);

        $this->view->grid = $grid;
        $this->view->actionUrl = $this->_helper->url('list');
    }
}