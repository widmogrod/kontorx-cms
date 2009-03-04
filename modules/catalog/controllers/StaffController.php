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

        try {
            require_once 'KontorX/Image/Resizer.php';
            $resizer = new KontorX_Image_Resizer($resizerConfig);
            $resizer->setFilename($filename);
            $resizer->setMultiType($type);
            $image = $resizer->resize();
            $image->save();
        } catch (KontorX_Image_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::DEBUG);

            // wyswietlanie domyslnej grafiki
            $this->_helper->redirector->goToUrlAndExit(
                $this->getFrontController()->getBaseUrl()  .$config->defaultImage
            );
        }
            

//        $noImagePath = $config->default->image;
//
//        $type = $this->_getParam('type');
//
//        $imageName = $this->_getParam('file');
//        $imagePath = $uploadPath . '/' . basename($imageName);
//
//        // na starcie wysylane sa naglowki - to przez cache!
//        $this->_response->setHeader('Content-type', image_type_to_mime_type(IMAGETYPE_JPEG));
//
//        // czy obrazek istnieje
//        if (!is_file($imagePath)) {
//            // logowanie zdarzeń
//            Zend_Registry::get('logger')
//            ->log(get_class($this)."::thumbAction() noe exsists $imagePath", Zend_Log::DEBUG);
//
//            $this->_helper->redirector->goToUrlAndExit(
//                $this->getFrontController()->getBaseUrl()  .$noImagePath
//            );
//        }
//
//        try {
//            $image = new KontorX_Image($imagePath);
//        } catch (KontorX_Image_Exception $e) {
//            // logowanie zdarzeń
//            $logger = Zend_Registry::get('logger');
//            $logger->log($e->getMessage() . "\n" .  $e->getTraceAsString(), Zend_Log::NOTICE);
//
//            $this->_helper->redirector->goToUrlAndExit($this->getFrontController()->getBaseUrl()  .$noImagePath);
//        }
//
//        $types = $config->image->resize->type->toArray();
//
//        if (array_key_exists($type, $types)) {
//            $x 		= @$types[$type]['x'];
//            $y 		= @$types[$type]['y'];
//            $xCrop 	= @$types[$type]['crop']['x'];
//            $yCrop 	= @$types[$type]['crop']['y'];
//            $style 	= @$types[$type]['style'];
//
//            switch ($style) {
//                case 'max':
//                    if (is_numeric($x) && null === $y) {
//                        $image->resizeToMaxWidth($x);
//                    } else
//                    if (is_numeric($y) && null === $x) {
//                        $image->resizeToMaxHeight($y);
//                    } else
//                    if (is_numeric($x) && is_numeric($y)) {
//                        $image->resizeToMax($x, $y);
//                    }
//                    break;
//                case 'crop':
//                    $image->resizeToMaxWidth($x);
//                    $image->crop(0,0,$xCrop,$yCrop);
//                    break;
//                default: $image->resize($x, $y);
//                }
//            } else {
//                $image->resize(50,50);
//            }
//
//            // print - bo naglowej jest wysylany wyzej
//            $img = $image->display(IMAGETYPE_JPEG, null, true);
//            $this->_response->setBody($img);
//
//            // zapisywanie miniaturki do odpowiedniego katalogu
//            // jest tutaj mała sztuczka
//            // nazwa katalogu i pliku odpowiada sciezce generowanej przez ZF
//            // zatem gdy miniaturka bedzie istniec omijamy generator miniaturek
//            // i kierujemy sie bezposrednio do sciezki z miniaturka - leprza wydajnosc
//            $thumbDirname  = $uploadPath . '/' . $type;
//            $thumbPathname = $thumbDirname . '/' .$imageName;
//            if (!is_dir($thumbDirname)) {
//                if (!@mkdir($thumbDirname,0755)) {
//                    // logowanie zdarzeń
//                    $logger = Zend_Registry::get('logger');
//                    $logger->log(get_class($this).'::thumbAction mkdir('.$thumbDirname.')' , Zend_Log::WARN);
//                    return;
//                }
//            }
//            if (!@file_put_contents($thumbPathname, $img)) {
//                // logowanie zdarzeń
//                $logger = Zend_Registry::get('logger');
//                $logger->log(get_class($this).'::thumbAction file_put_contents('.$thumbPathname.')', Zend_Log::WARN);
//            } else {
//                @chmod($thumbPathname, 0755);
//            }
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