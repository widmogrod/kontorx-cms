<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Catalog_SiteController extends KontorX_Controller_Action_CRUD {

    public $skin = array (
        'layout' => 'admin_catalog',
        'config' => array(
            'filename' => 'backend_config.ini'),
        'show' => array(
            'layout' => 'catalog_show',
            'lock' => true
        )
    );

    public $contexts = array(
        'list' => array('body'),
        'add' => array('body'),
        'edit' => array('body')
    );

    protected $_modelClass = 'CatalogSite';

    protected $_configFilenameExtension = "xml";

    public function init() {
        $this->view->addHelperPath('KontorX/View/Helper');

        $contextSwitch = $this->_helper->getHelper('ContextSwitch');
        // wylanczam wylaczenie layotu
        $contextSwitch->setAutoDisableLayout(false);
        if (!$contextSwitch->hasContext('body')) {
            // nowy context
            $contextSwitch->addContext('body',array('callbacks' => array(
                'init' => array($this, 'contextSwitchBodyCallback'))));
        }
        $contextSwitch->initContext();

        parent::init();
    }

    /**
     * Callback of @see init and @see ContextSwitch
     * @return void
     */
    public function contextSwitchBodyCallback() {
        // zmieniam szablon
        $system = $this->_helper->system;
        $system->layout('admin_body');
        $system->setLayoutSectionName('admin_catalog');
        $system->lockLayoutName(true);
    }

    /**
     * @return void
     */
    public function indexAction() {
        $this->_forward('list');
    }

    /**
     * @return void
     */
    public function listAction() {
        $this->view->addHelperPath('KontorX/View/Helper');

        $config = $this->_helper->loader->config('site.xml');

        $model = $this->_getModel();
        $select = new Zend_Db_Select($model->getAdapter());
        $select
        ->from(array('cs' => 'catalog_site'),Zend_Db_Select::SQL_WILDCARD)
        ->joinLeft(array('c' => 'catalog'),
            'cs.catalog_id = c.id',
            array('catalog_name'=>'c.name'));

        $grid = KontorX_DataGrid::factory($select, $config->dataGrid);
//        $grid->setColumns($config->dataGridColumns->toArray());
//        $grid->setValues((array) $this->_getParam('filter'));

        $paginator = Zend_Paginator::factory($select);
        $grid->setPaginator($paginator);
        $grid->setPagination($this->_getParam('page'), 20);

        $this->view->grid = $grid;
        $this->view->actionUrl = $this->_helper->url('list');
    }

    /**
     * Pokaż wizytowke - jako strone
     * @return void
     */
    public function showAction() {
        $url = strtolower($this->_getParam('url'));

        if ($url === 'www') {
            $this->_forward('index','index','catalog');
            return;
        }

        try {
            $model = $this->_getModel();
            $row = $model->fetchRow(
                $model->select()->where('url = ?', $url));

            if ($row instanceof Zend_Db_Table_Row_Abstract) {
                $this->_forward('show','index','catalog',array('id' => $row->catalog_id,'_site' => 1));
                return;
            }
        } catch(Zend_Db_Table_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() ."\n". $e->getTraceAsString(),Zend_Log::ERR);
        }

        $this->_forward('index','index','catalog');
    }
}