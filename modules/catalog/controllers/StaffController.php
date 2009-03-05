<?php
require_once 'KontorX/Controller/Action/CRUD.php';
class Catalog_StaffController extends KontorX_Controller_Action_CRUD {

    public $skin = array(
        'layout' => 'admin_catalog',
        'config' => array(
            'filename' => 'backend_config.ini'
        ));

    public $contexts = array(
        'list' => array('body'),
        'add' => array('body'),
        'edit' => array('body')
    );

    protected $_modelClass = 'CatalogStaff';

    protected $_configFilenameExtension = "xml";

    public function init() {
        $contextSwitch = $this->_helper->getHelper('ContextSwitch');
        $contextSwitch
        // wylanczam wylaczenie layotu
        ->setAutoDisableLayout(false)
        // nowy context
        ->addContext('body',array('callbacks' => array(
                'init' => array($this, 'contextSwitchBodyCallback'))))
        ->initContext();

        parent::init();
    }

    public function listAction() {
        $this->view->addHelperPath('KontorX/View/Helper');

        $config = $this->_helper->loader->config('time.xml');

        $model = $this->_getModel();
        $select = new Zend_Db_Select($model->getAdapter());
        $select
        ->from(array('ct' => 'catalog_time'),Zend_Db_Select::SQL_WILDCARD)
        ->joinLeft(array('c' => 'catalog'),
                                'ct.catalog_id = c.id',
            array('c.name'));

        $grid = KontorX_DataGrid::factory($select);
        $grid->setColumns($config->dataGridColumns->toArray());
        $grid->setValues((array) $this->_getParam('filter'));

        $paginator = Zend_Paginator::factory($select);
        $grid->setPaginator($paginator);
        $grid->setPagination($this->_getParam('page'), 20);

        $this->view->grid = $grid;
        $this->view->actionUrl = $this->_helper->url('list');
    }

    /**
     * Generowanie miniaturek
     *
     * TODO Optymalizacja
     */
    public function thumbAction() {
        // wylaczenie renderowania widoku
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        // pobieranie konfiguracji
        $config = $this->_helper->loader->config('staff.xml');

        $resizerConfig = $config->resizer->toArray();
        $resizerConfig['dirname'] = $this->_helper
            ->system()
            ->getPublicHtmlPath(
                $resizerConfig['dirname']
            );

        $type = $this->_getParam('type');
        $filename = $this->_getParam('file');
        $thumbPathname = $type . DIRECTORY_SEPARATOR . $filename;

        try {
            // resizing
            require_once 'KontorX/Image/Resizer.php';
            $resizer = new KontorX_Image_Resizer($resizerConfig);
            $resizer->setFilename($filename);
            $resizer->setMultiType($type);
            $image = $resizer->resize();

            // display
            $image->display();

            // save
            require_once 'KontorX/File/Write.php';
            $write = new KontorX_File_Write($options);
            $write->setBasedir($resizer->getDirname());
            $write->setForce(true);
            $write->write($thumbPathname, $image->toString());

            return;
        } catch (KontorX_Image_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::WARN);
        } catch (KontorX_File_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::WARN);
        }

        // wyswietlanie domyslnej grafiki
        $this->_helper->redirector->goToUrlAndExit(
            $this->getFrontController()->getBaseUrl()  .$config->defaultImage
        );
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
}