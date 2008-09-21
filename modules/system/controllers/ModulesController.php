<?php
require_once 'KontorX/Controller/Action.php';

/**
 * Description of AdminController
 *
 * @author widmogrod
 */
class System_ModulesController extends KontorX_Controller_Action {
	
	public $skin = array(
		'layout' => 'admin'
	);

    public function indexAction() {
    	require_once 'system/models/SystemConfig.php';
    	$model = new SystemConfig();

    	// pobieramy data
    	$rowset = $model->fetchAllAppModulesConfigPathnames();
    	$this->view->rowset = $rowset;

    	// wyszukujemy pliku konfiguracyjnego ..
    	$configKey = $this->_getParam('config');
    	$configKeys = explode('_', $configKey);
    	$moduleName = $configKeys[0];
    	$configName = @$configKeys[1];
    	if (!array_key_exists($moduleName, $rowset)) {
    		return;
    	}
    	$rowsetModule = $rowset[$moduleName];
    	if (!array_key_exists($configKey, $rowsetModule)) {
    		return ;
    	}

    	$configPath = $model->getModulesPath($moduleName) . '/' . $configName;

    	try {
    		$config = new Zend_Config_Ini($configPath);
    		$form = $this->_initForm($config);
    	} catch (Zend_Config_Exception $e) {
    		$this->_helper->flashMessenger->addMessage($e->getMessage());
    		$this->_helper->redirector->goToAndExit('index');
    	}

    	if (!$this->_request->isPost()) {
    		$this->view->form = $form;
    		return;
    	}

    	if (!$form->isValid($_POST)) {
    		$this->view->form = $form;
    		return;
    	}
    	
    	$post = $form->getValues(true);

    	try {
    		$model->saveAppModuleConfig($post, $moduleName, $configName);
    		$message = "Konfiguracja została zapisna!";
    	} catch (SystemConfigException $e) {
    		$message = $e->getMessage();
    	}
    	
    	$this->_helper->flashMessenger->addMessage($message);
    	$this->_helper->redirector->goToUrlAndExit(getenv('HTTP_REFERER'));
    }

    /**
     * Zwraca formularz @see KontorX_Form_Config
     *
     * @param Zend_Config $config
     * @return KontorX_Form_Config
     */
    protected function _initForm(Zend_Config $config) {
    	$options = array(
        	'elements' => array(
        		'zapisz' => array(
	        		'type' => 'submit',
	        		'options' => array('label' => 'Zapisz', 'class' => 'action save', 'ignore' => true)
        		)
        	)
        );
        
        require_once 'KontorX/Form/Config.php';
        $form = new KontorX_Form_Config($config, $options);
        
        return $form;
    }
}