<?php
defined('BOOTSTRAP') or define('BOOTSTRAP', 'development');

switch (BOOTSTRAP) {
	case 'development':
		error_reporting(E_ALL);
		break;
	default:
	case 'production':
		error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
		break;
}

defined('WEB_DIRNAME') or define('WEB_DIRNAME', '/');
defined('PUBLIC_DIRNAME') or define('PUBLIC_DIRNAME', 'public_html');

// Katalogi
$basePathName = realpath(dirname(__FILE__) . '/../') . '/';
$publicPathName = $basePathName . PUBLIC_DIRNAME . '/';
$applicationPathName = $basePathName . 'application/';

// definiowanie katalogow
define('BASE_PATHNAME', $basePathName);
define('PUBLIC_PATHNAME', $publicPathName);
define('APP_PATHNAME', $applicationPathName);
define('APP_MODULES_PATHNAME', $applicationPathName . 'modules/');
define('APP_CONFIGURATION_PATHNAME', $applicationPathName . 'configuration/');

/**
 * Konfiguracja PHP
 */
ini_set('magic_quotes_runtime',false);
ini_set('magic_quotes_gpc',false);
ini_set('magic_quotes_sybase',false);
// tak by wiedziec co się spuje ;]
ini_set('display_errors', true);
ini_set('display_startup_errors', true);

/**
 * Ustawienie include path, z bibiotekami i aplikacji
 */
set_include_path(
	BASE_PATHNAME . 'library/' . PATH_SEPARATOR .
	APP_MODULES_PATHNAME . PATH_SEPARATOR .
	get_include_path()
);

/**
 * Go! Zend Framework .. 
 */
require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload('KontorX_Loader');

/**
 * Inicjowanie konfiguracji
 */
$configSystem	 	= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/system.ini", 		BOOTSTRAP, 	array('allowModifications' => true));
$configFramework 	= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/framework.ini", 	BOOTSTRAP, 	array('allowModifications' => true));
//$configApplication 	= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/application.ini", 	BOOTSTRAP, 	array('allowModifications' => true));
$configDatabase 	= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/database.ini", 	BOOTSTRAP, 	array('allowModifications' => true));
$configRouter 		= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/router.ini", 		BOOTSTRAP, 	array('allowModifications' => true));
$configCache 		= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/cache.ini", 		BOOTSTRAP, 	array('allowModifications' => true));
$configAcl 			= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/acl.ini", 			null, 		array('allowModifications' => true));

// ustawienie konfiguracji ktora jest wykorzystuywana a aplikacji
Zend_Registry::set('configFramework', $configFramework);
//Zend_Registry::set('configApplication', $configApplication);

/**
 * Dodakowe opcje konfiguracyjne
 */
require_once 'Zend/Controller/Action/HelperBroker.php';
Zend_Controller_Action_HelperBroker::addPath('KontorX/Controller/Action/Helper','KontorX_Controller_Action_Helper');

/**
 * Cache
 */
//$cacheDefault = Zend_Cache::factory(
//	$configCache->default->frontend->name,
//	$configCache->default->backend->name,
//	$configCache->default->frontend->options->toArray(),
//	$configCache->default->backend->options->toArray()
//);
$cacheDBQuery = Zend_Cache::factory(
	$configCache->dbquery->frontend->name,
	$configCache->dbquery->backend->name,
	$configCache->dbquery->frontend->options->toArray(),
	$configCache->dbquery->backend->options->toArray()
);
//$cacheOutput = Zend_Cache::factory(
//	$configCache->output->frontend->name,
//	$configCache->output->backend->name,
//	$configCache->output->frontend->options->toArray(),
//	$configCache->output->backend->options->toArray()
//);
//$cacheOutputImages = Zend_Cache::factory(
//	$configCache->outputimages->frontend->name,
//	$configCache->outputimages->backend->name,
//	$configCache->outputimages->frontend->options->toArray(),
//	$configCache->outputimages->backend->options->toArray()
//);

//$cachePage = Zend_Cache::factory(
//	$configCache->page->frontend->name,
//	$configCache->page->backend->name,
//	array(
//		'lifetime' => $configCache->page->backend->options->lifetime,
//		'debug_header' => true,
//		'regexps' => array(
//			'^/default/shop/search/' => array(
//				'cache' => true,
//				'cache_with_get_variables' => true,
//				'make_id_with_get_variables' => true
//			),
//			'^/default/shop/thumb/' => array(
//				'cache' => true,
//				'cache_with_get_variables' => true,
//				'make_id_with_get_variables' => true
//			),
//		)
//	),
//	$configCache->page->backend->options->toArray()
//);
//$cachePage->start();

require_once 'Zend/Cache.php';
$cacheDatabase = Zend_Cache::factory(
	$configCache->database->frontend->name,
	$configCache->database->backend->name,
	$configCache->database->frontend->options->toArray(),
	$configCache->database->backend->options->toArray()
);
$cacheTranslate = Zend_Cache::factory(
	$configCache->translate->frontend->name,
	$configCache->translate->backend->name,
	$configCache->translate->frontend->options->toArray(),
	$configCache->translate->backend->options->toArray()
);
// dla aplikacji
require_once 'Zend/Registry.php';
//Zend_Registry::set('cacheDefault', $cacheDefault);
Zend_Registry::set('cacheDBQuery', $cacheDBQuery);
//Zend_Registry::set('cacheOutput', $cacheOutput);
//Zend_Registry::set('cacheOutputImages', $cacheOutputImages);
// dla frameworka
require_once 'Zend/Translate.php';
//Zend_Translate::setCache($cacheTranslate);
require_once 'KontorX/Db/Table/Abstract.php';
KontorX_Db_Table_Abstract::setDefaultMetadataCache($cacheDatabase);
//KontorX_Db_Table_Abstract::setDefaultRowsetCache($cacheDatabase);

/**
 * ACL
 */
require_once 'KontorX/Acl.php';
$acl = KontorX_Acl::startMvc($configAcl);
$aclPlugin = $acl->getPluginInstance();
$aclPlugin->setNoAclErrorHandler('login','auth','user');
$aclPlugin->setNoAuthErrorHandler('login','auth','user');

/**
 * Translacja
 */
require_once 'Zend/Translate.php';
$translate = new Zend_Translate('Tmx', "$basePathName/languages/pl/validation.xml", 'pl');
require_once 'Zend/Form.php';
Zend_Form::setDefaultTranslator($translate);

/**
 * Db
 */
if(isset($configDatabase->default)) {
	$db = Zend_Db::factory(
		$configDatabase->default->adapter,
		$configDatabase->default->config->toArray()
	);
	Zend_Db_Table_Abstract::setDefaultAdapter($db);
	
	switch (BOOTSTRAP) {
		case 'development':
			require_once 'Zend/Db/Profiler/Firebug.php';
			$profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
			$profiler->setEnabled(true);
			$db->setProfiler($profiler);
			break;
	}
}

/**
 * Lokalizacja
 */
require_once 'Zend/Locale.php';

try {
    $locale = new Zend_Locale('auto');
} catch (Zend_Locale_Exception $e) {
    $locale = new Zend_Locale('pl');
}
Zend_Registry::set('Zend_Locale', $locale);

/**
 * Layout
 */
require_once 'Zend/Layout.php';
$layout = Zend_Layout::startMvc();
//$layout->setLayoutPath();

/**
 * Logger
 */
require_once 'Zend/Log.php';
$logger = new Zend_Log();
$logger->addWriter(new Zend_Log_Writer_Stream("$basePathName/logs/application.log"));
$loggerFramework = new Zend_Log();
$loggerFramework->addWriter(new Zend_Log_Writer_Stream("$basePathName/logs/framework.log"));

// w aplikacji wykorzystywane
Zend_Registry::set('logger', $logger);
Zend_Registry::set('loggerFramework', $loggerFramework);

/**
 * Front controller
 */
require_once 'Zend/Controller/Front.php';
$front = Zend_Controller_Front::getInstance();
$front->setControllerDirectory($configFramework->controller->directory->toArray());
$front->setDefaultModule($configFramework->controller->default->module);
$front->setBaseUrl($configFramework->baseUrl);
require_once 'KontorX/Controller/Plugin/i18n.php';
$front->registerPlugin(new KontorX_Controller_Plugin_i18n(),30);
//require_once 'KontorX/Controller/Plugin/Bootstrap.php';
//$front->registerPlugin(new KontorX_Controller_Plugin_Bootstrap(),0);
//$front->registerPlugin(new KontorX_Controller_Plugin_Stats(),98);

require_once 'KontorX/Controller/Plugin/System.php';
$systemPlugin = new KontorX_Controller_Plugin_System($configSystem);
$systemPlugin->setApplicationPath(APP_PATHNAME);
$systemPlugin->setPublicHtmlPath(PUBLIC_PATHNAME);
$front->registerPlugin($systemPlugin,20);

$front->throwExceptions($configFramework->throwExceptions);
$front->setParams($configFramework->params->toArray());

// konfig rutera
$front->getRouter()->addConfig($configRouter);