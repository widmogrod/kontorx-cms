<?php
require_once 'KontorX/Controller/Action.php';
class Catalog_ManagementController extends KontorX_Controller_Action {

    public $skin = array(
                'layout' => 'manage'
    );

    public $ajaxable = array(
                'service' => array('html'),
                'images' => array('html'),
                'imagemain' => array('json'),
                'imagedelete' => array('json')
    );

    public $contexts = array(
                'imageupload' => array('html')
    );

    public function init() {
        // informuja jaki kontroller
        $this->view->placeholder('navigation')->controller = 'management';

        parent::init();

        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->initContext();

        $contextSwitch = $this->_helper->getHelper('ContextSwitch');
        if (!$contextSwitch->hasContext('html')) {
            $contextSwitch
            ->addContext('html', array(
                                        'headers'   => array('Content-Type' => 'text/html'),
                ));
        }
        $contextSwitch->initContext();
    }

    /**
     * Listuje wszystkie gabinety
     *
     * @return void
     */
    public function indexAction(){
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'index';
        // konfiguracja
        $config = $this->_helper->loader->config('management.xml');

        // model
        require_once 'catalog/models/Management.php';
        $manage = new Management();
        $select = $manage->selectCatalogForPromoType($this->getRequest());

        // ~widok
        try {
            require_once 'KontorX/DataGrid.php';
            $grid = KontorX_DataGrid::factory($select);
            $grid->setColumns($config->dataGridColumns->toArray());
            $grid->setValues((array) $this->_getParam('filter'));

            // setup grid paginatior
            require_once 'Zend/Paginator.php';
            $paginator = Zend_Paginator::factory($select);

            $grid->setPaginator($paginator);
            $grid->setPagination($this->_getParam('page'), 10);

            $this->view->grid = $grid;
        } catch (Zend_Db_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
        }
    }

    /**
     * Edycja podstrawowych danych z modelu @see Contact
     * @return void
     */
    public function editAction() {
        $type = $this->_getParam('type','default');
        $this->view->type = $type;

        // ustawienie akcji
        $this->view->placeholder('navigation')->action = "edit.$type";


        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $rq = $this->getRequest();

        $row = $manage->findCatalogRowForUser($this->_getParam('id'), $rq);
        $this->view->row = $row;

        if (null === $row) {
            $this->_helper->viewRenderer->render('edit.error');
            return;
        }

        // GMap API
        $configMain = $this->_helper->loader->config('config.ini');
        $this->view->apiKey = $configMain->gmap->{BOOTSTRAP}->apiKey;

        $form = $this->_getFormEdit($row, $type);
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
            $data = get_magic_quotes_gpc() ? array_map('stripslashes', $data) : $data;

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
     * Przygotowanie formularza do edycji
     *
     * @param Zend_Db_Table_Row_Abstract$row
     * @param string $type
     * @return KontorX_Form_DbTable
     */
    private function _getFormEdit(Zend_Db_Table_Row_Abstract $row, $type = null) {
        $type = strtolower($type);
        if (!in_array($type, array('default','contact','map','meta'))) {
            $type = 'default';
        }

        $config = $this->_helper->loader->config('management.xml');
        $form = new KontorX_Form_DbTable($row->getTable(), $config->form->{$type});

        return $form;
    }

    /**
     * Usługi
     *
     * @todo Dodać możliwość dodania opisu
     * @return void
     */
    public function serviceAction() {
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'service';

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

        if (!$rq->isPost()) {
            return;
        }

        if ($manage->saveServicesCost($id, $rq)) {
            $message = "Usługi zostały zapisane";
        } else {
            $message = "Usługi nie zostały zapisane";
        }

        $this->_helper->flashMessenger($message);
        $this->_helper->redirector->goToUrlAndExit(
            $this->_helper->url->url(array())
        );
    }

    /**
     * Listowanie grafik + formularz uploadu dla grafiki
     *
     * @return void
     */
    public function imagesAction() {
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'images';

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

    /**
     * Uploaduj grafikę
     *
     * @return void
     */
    public function imageuploadAction() {
        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id = $this->_getParam('id');
        $rq = $this->getRequest();

        // Czy rekord nalerzy do uzytkownika!?
        if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
            $this->_helper->viewRenderer->render('edit.error');
            return;
        }

        $filename = 'image';

        require_once 'Zend/File/Transfer/Adapter/Http.php';
        $file = new Zend_File_Transfer_Adapter_Http();

        // destination
        $config = $this->_helper->loader->config();
        $path = $config->path->upload->image;
        $file->setDestination($path, $filename);

        require_once 'Zend/Validate/File/IsImage.php';
        $file->addValidator(new Zend_Validate_File_IsImage(), true);

        require_once 'KontorX/Filter/Word/Rewrite.php';
        $filterRewrite = new KontorX_Filter_Word_Rewrite();

        $newFilename = $filterRewrite->filter($file->getFileName($filename));
        $newFilename = md5(time()) . $newFilename . '.' . substr(strrchr($filename, '.'), 1);
        $newPathname = "{$path}/{$newFilename}";

        require_once 'Zend/Filter/File/Rename.php';
        $filterRename = new Zend_Filter_File_Rename(array('target' => $newPathname));
        $file->addFilter($filterRename);

        $message = null;
        if (!$file->isUploaded($filename)) {
            $message = "Plik nie został uploaowany";
        } else
        if (!$file->isValid($filename)) {
            $message = "Plik nie jest poprawny";
        } else
        if (!$file->receive()) {
            $messages = array();
            foreach ($file->getmsg() as $message) {
                $messages[] = $message;
            }
            $message = implode('<br/>', $messages);
        } else
        if (!$manage->insertImage($id, $newFilename)) {
            $message = "Plik nie został zapisany w bazie danych! proszę spróbuj jeszcze raz";
        } else {
            $message = "Plik został wysłany na serwer";
        }

        if (!$this->_hasParam('format')) {
            // zwykła akcja redirect
            $this->_helper->flashMessenger($message);
            $this->_helper->redirector->goToUrlAndExit(
                $this->_helper->url->url(array('action'=>'images','id'=>$id))
            );
        } else {
            // działaj sobie. ..
            $this->view->msg = array($message);
        }
    }

    /**
     * Ustawienie wybranej grafiki jako logo
     *
     * @return void
     */
    public function imagemainAction() {
        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id = $this->_getParam('id');
        if ($manage->setMainImage($id)) {
            $this->view->success = true;
            $message = "Logo zostało ustawione";
        } else {
            $this->view->success = false;
            $message = "Logo nie zostało ustawione!";
        }

        if (!$this->_hasParam('format')) {
            // zwykła akcja redirect
            $this->_helper->flashMessenger($message);
            $this->_helper->redirector->goToUrlAndExit(
                getenv('HTTP_REFERER')
            );
        }
    }

    /**
     * Usuwa grafikę
     *
     * @return void
     */
    public function imagedeleteAction() {
        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id = $this->_getParam('id');
        if ($manage->deleteImage($id)) {
            $this->view->success = true;
            $message = "Fotografia została usunięta";
        } else {
            $this->view->success = false;
            $message = "Fotografia nie została usunięta";
        }

        if (!$this->_hasParam('format')) {
            // zwykła akcja redirect
            $this->_helper->flashMessenger($message);
            $this->_helper->redirector->goToUrlAndExit(
                getenv('HTTP_REFERER')
            );
        }
    }

    /**
     * Godziny otwarcia
     *
     * @return void
     */
    public function timeAction() {
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'time';

        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id = $this->_getParam('id');
        $rq = $this->getRequest();

        // Czy rekord nalerzy do uzytkownika!?
        if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
            $this->_helper->viewRenderer->render('edit.error');
            return;
        }

        $model = new CatalogTime();

        try {
            $row = $model->find($id)->current();
        } catch (Zend_Db_Table_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
        }

        // nie znaleziono rekordu to tworzymy
        if (!$row instanceof Zend_Db_Table_Row_Abstract) {
            $row = $model->createRow(array(
                                'catalog_id' => (int) $id
                ));
        }

        $form = $this->_getFormTime($row);

        if (!$rq->isPost()) {
            $form->setDefaults($row->toArray());
            $this->view->form = $form;
            return;
        }

        if (!$form->isValid($this->getRequest()->getPost())) {
            $this->view->form = $form;
            return;
        }

        try {
            $row->setFromArray($form->getValues());
            $row->save();
            $message = "Godziny otwarcia zostały zapisane";
        } catch (Zend_Db_Table_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
            $message = "Godziny otwarcia nie zostały zapisane";
        }

        $this->_helper->flashMessenger($message);
        $this->_helper->redirector->goToUrlAndExit(
            $this->_helper->url->url(array())
        );
    }

    /**
     * Formularz z godzinami
     *
     * @return KontorX_Form_DbTable
     */
    private function _getFormTime(Zend_Db_Table_Row_Abstract $row) {
        $config = $this->_helper->loader->config('management.xml');
        $form = new KontorX_Form_DbTable($row->getTable(), $config->form->time);

        return $form;
    }

    /**
     * Opcje gabinetu
     *
     * @todo Dodać możliwośc dodania opcji na wizytówke gabinetu
     * @return void
     */
    public function optionsAction() {
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'options';

        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id = $this->_getParam('id');
        $rq = $this->getRequest();

        // Czy rekord nalerzy do uzytkownika!?
        if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
            $this->_helper->viewRenderer->render('edit.error');
            return;
        }

        $options = new CatalogOptions();
        $this->view->optionsArray = $options->fetchAllOptionsArray();

        if (!$rq->isPost()) {
            $hasOptions = new CatalogHasCatalogOptions();
            $this->view->options = $hasOptions->fetchRowOptionsArray($id);
            return;
        }

        // filtrowanie
        $this->view->options = $rq->getPost('options');
        $options = array_intersect(
            (array) array_keys($this->view->optionsArray),
            (array) $this->view->options);

        $message = ($manage->saveOptions($id, $options))
            ? "Zmiany zostały zapisane"
            : "Zmiany NIE zostały zapisane!";

        $this->_helper->flashMessenger($message);
        $this->_helper->redirector->goToUrlAndExit(
            $this->_helper->url->url(array())
        );
    }

    /**
     * Zarządzanie personelem
     * 
     * @return void
     */
    public function staffAction() {
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'staff';

        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id = $this->_getParam('id');
        $rq = $this->getRequest();

        // Czy rekord nalerzy do uzytkownika!?
        if (null === ($catalog = $manage->findCatalogRowForUser($id, $rq))) {
            $this->_helper->viewRenderer->render('edit.error');
            return;
        }

        require_once 'catalog/models/CatalogStaff.php';
        $staff = new CatalogStaff();

        $row  = $staff->createRow();
        $form = $this->_getFormStaff($row);
        $form->setAction("catalog/management/staffupdate/id/$id");
        $this->view->form = $form;

        $this->view->rowset = $catalog->findDependentRowset('CatalogStaff');
    }

    /**
     * Tworzenie aktualizacja personelu
     *
     * @return void
     */
    public function staffupdateAction() {
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'staff';

        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id = $this->_getParam('id');
        $rq = $this->getRequest();

        // Czy rekord nalerzy do uzytkownika!?
        if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
            $this->_helper->viewRenderer->render('edit.error');
            return;
        }

        require_once 'catalog/models/CatalogStaff.php';
        $staff = new CatalogStaff();

        $row = null;
        $staffId = $this->_getParam('staff_id');

        if ($this->_hasParam('staff_id')) {
            try {
                $select = $staff->select()
                ->where("catalog_id = ?", $id, Zend_Db::INT_TYPE)
                ->where("id = ?", $staffId, Zend_Db::INT_TYPE);

                $row  = $staff->fetchRow($select);
            } catch (Zend_Db_Table_Exception $e) {
                $this->_helper->viewRenderer->render('staffupdate.error');
                return;
            }
        } else {
            $row  = $staff->createRow();
        }

        $form = $this->_getFormStaff($row);
//        $form->setAction($form->getAction() . "/id/$id");

        if (!$rq->isPost()) {
            $form->setDefaults($row->toArray());
            $this->view->form = $form;
            return;
        }

        if (!$form->isValid($rq->getPost())) {
            $this->view->form = $form;
            return;
        }

        $this->view->form = $form;

        try {
            $row->setFromArray($form->getValues());
            $row->catalog_id = $id;
            $row->save();

            $message = "Dane personalne zostały zapisane";
            $this->_helper->flashMessenger($message);
            $this->_helper->redirector->goToUrlAndExit(
                getenv('HTTP_REFERER')
//                $this->_helper->url->url(array('staff_id' => $staffId))
            );

        } catch (Zend_Db_Table_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);

            $message = "Dane personalne NIE zostały zapisane";
            $this->_helper->flashMessenger($message);
        }
    }

    /**
     * Usuwanie personelu
     *
     * @return void
     */
    public function staffdeleteAction() {
        // ustawienie akcji
        $this->view->placeholder('navigation')->action = 'staff';

        require_once 'catalog/models/Management.php';
        $manage = new Management();

        $id      = $this->_getParam('id');
        $staffId = $this->_getParam('staff_id');
        $rq      = $this->getRequest();

        // Czy rekord nalerzy do uzytkownika!?
        if (null === ($row = $manage->findCatalogRowForUser($id, $rq))) {
            $this->_helper->viewRenderer->render('edit.error');
            return;
        }

        require_once 'catalog/models/CatalogStaff.php';
        $staff = new CatalogStaff();

        try {
            $select = $staff->select()
            ->where("catalog_id = ?", $id, Zend_Db::INT_TYPE)
            ->where("id = ?", $staffId, Zend_Db::INT_TYPE);

            $row = $staff->fetchRow($select);
        } catch (Zend_Db_Table_Exception $e) {
            $this->_helper->viewRenderer->render('staffdelete.error');
            return;
        }

        if (!$row instanceof Zend_Db_Table_Row_Abstract) {
            $this->_helper->viewRenderer->render('staffdelete.error');
            return;
        }

        try {
            $row->delete();

            $message = "Osoba została usunięta";
        } catch (Zend_Db_Table_Exception $e) {
            Zend_Registry::get('logger')
            ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);

            $message = "Osoba NIE została usunięta";
        }

        $this->_helper->flashMessenger($message);
        $this->_helper->redirector->goToUrlAndExit(
            getenv('HTTP_REFERER')
        );
    }

    /**
     * Formularz tworzenia personelu
     *
     * @return KontorX_Form_DbTable
     */
    private function _getFormStaff(Zend_Db_Table_Row_Abstract $row) {
        $config = $this->_helper->loader->config('management.xml');
        $form = new KontorX_Form_DbTable($row->getTable(), $config->form->staff);
        
        return $form;
    }
}