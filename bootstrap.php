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
$basePathName = dirname(dirname(__FILE__)) . '/';
$publicPathName = $basePathName . PUBLIC_DIRNAME;
$applicationPathName = $basePathName . 'application/';

// definiowanie katalogow
define('BASE_PATHNAME', $basePathName);
define('PUBLIC_PATHNAME', $publicPathName);
define('APP_PATHNAME', $applicationPathName);
define('APP_MODULES_PATHNAME', $applicationPathName . 'modules/');
define('APP_CONFIGURATION_PATHNAME', $applicationPathName . 'configuration/');
define('TMP_PATHNAME', $basePathName . 'tmp/');

/**
* Konfiguracja PHP
*/
ini_set('magic_quotes_runtime',0);
ini_set('magic_quotes_gpc',0);
ini_set('magic_quotes_sybase',0);
// tak by wiedziec co się spuje ;]
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/**
* Ustawienie include path, z bibiotekami i aplikacji
*/
//set_include_path(
//	BASE_PATHNAME . 'library/' . PATH_SEPARATOR .
//	APP_MODULES_PATHNAME . PATH_SEPARATOR .
//	get_include_path()
//);

$paths = array(
    BASE_PATHNAME . 'library/',
    APP_MODULES_PATHNAME,
    APP_PATHNAME,
    //    realpath(dirname(__FILE__) . '/../library'),
'.'
);
set_include_path(implode(PATH_SEPARATOR, $paths));


/**
* Go! Zend Framework ..
*/
require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload('KontorX_Loader');

/**
* Inicjowanie konfiguracji
*/
$configSystem = new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/system.ini", BOOTSTRAP, array('allowModifications' => true));
//$configSystem = new KontorX_Config_Vars($configSystem);
$configFramework = new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/framework.ini", BOOTSTRAP, array('allowModifications' => true));
$configFramework = KontorX_Config_Vars::decorate($configFramework);
//$configFramework = new KontorX_Config_Vars($configFramework);
//$configApplication 	= new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/application.ini", 	BOOTSTRAP, 	array('allowModifications' => true));
$configDatabase = new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/database.ini",BOOTSTRAP, array('allowModifications' => true));
$configRouter = new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/router.ini", BOOTSTRAP, array('allowModifications' => true));
$configCache = new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/cache.ini", BOOTSTRAP, array('allowModifications' => true));
$configCache = KontorX_Config_Vars::decorate($configCache);
$configAcl = new Zend_Config_Ini(APP_CONFIGURATION_PATHNAME . "/acl.ini", null,	array('allowModifications' => true));

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
//)
require_once 'Zend/Cache.php';
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
//Zend_Debug::dump($configCache->page->toArray());
//$cachePage = Zend_Cache::factory(
//    new KontorX_Cache_Frontend_Page(array(
//        'lifetime' => 86400,
//        'debug_header' > true,
//        'regexps' => array(
//            '^/stomatolog/(?P<id>\d+)' => array('cache' => true),
//            '^/stomatolodzy/(?P<id>[\ws\_]+)' => array('cache' => true)
//        )
//    )),
//    $configCache->page->backend->name,
//    null,
//    $configCache->page->backend->options->toArray()
//);
//$cachePage->start();

$cacheDatabase = Zend_Cache::factory(
    $configCache->database->frontend->name,
    $configCache->database->backend->name,
    $configCache->database->frontend->options->toArray(),
    $configCache->database->backend->options->toArray()
);
//$cacheTranslate = Zend_Cache::factory(
//	$configCache->translate->frontend->name,
//	$configCache->translate->backend->name,
//	$configCache->translate->frontend->options->toArray(),
//	$configCache->translate->backend->options->toArray()
//);
// dla aplikacji
require_once 'Zend/Registry.php';
//Zend_Registry::set('cacheDefault', $cacheDefault);
Zend_Registry::set('cacheDBQuery', $cacheDBQuery);
//Zend_Registry::set('cacheOutput', $cacheOutput);
//Zend_Registry::set('cacheOutputImages', $cacheOutputImages);
// dla frameworka
//require_once 'Zend/Translate.php';
//Zend_Translate::setCache($cacheTranslate);
require_once 'Zend/Db/Table/Abstract.php';
Zend_Db_Table_Abstract::setDefaultMetadataCache($cacheDatabase);
require_once 'KontorX/Db/Table/Abstract.php';
KontorX_Db_Table_Abstract::setDefaultResultCache($cacheDatabase);
//KontorX_Db_Table_Abstract::setDefaultRowsetCache($cacheDatabase);

/**
* ACL
*/
require_once 'KontorX/Acl.php';
$acl = KontorX_Acl::startMvc($configAcl);
$aclPlugin = $acl->getPluginInstance();
$aclPlugin->setNoAclErrorHandler('login','auth','user');
$aclPlugin->setNoAuthErrorHandler('privileges','error','default');

/**
* Translacja
*/
require_once 'Zend/Translate.php';
$translator = new Zend_Translate('Tmx', "$basePathName/languages/pl/validation.xml", 'pl');
require_once 'Zend/Validate/Abstract.php';
Zend_Validate_Abstract::setDefaultTranslator($translator);

$translator = new Zend_Translate('Tmx', "$basePathName/languages/pl/application.xml", 'pl');
require_once 'Zend/Form.php';
Zend_Form::setDefaultTranslator($translator);

/**
* Db
*/
if(isset($configDatabase->default)) {
    $db = Zend_Db::factory(
        $configDatabase->default->adapter,
        $configDatabase->default->config->toArray()
    );
    Zend_Db_Table_Abstract::setDefaultAdapter($db);

    if (defined('DEBUG')) {
        require_once 'Zend/Db/Profiler/Firebug.php';
        $profiler = new Zend_Db_Profiler_Firebug('All DB Queries');
        $profiler->setEnabled(true);
        $db->setProfiler($profiler);
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

if (defined('DEBUG')) {
    require_once 'KontorX/Controller/Plugin/Debug.php';
    $front->registerPlugin(KontorX_Controller_Plugin_Debug::getInstance(),3);
}

require_once 'KontorX/Controller/Plugin/i18n.php';
$front->registerPlugin(new KontorX_Controller_Plugin_i18n(),30);
require_once 'KontorX/Controller/Plugin/Bootstrap.php';
$front->registerPlugin(new KontorX_Controller_Plugin_Bootstrap(),98);
//$front->registerPlugin(new KontorX_Controller_Plugin_Stats(),98);

require_once 'KontorX/Controller/Plugin/System.php';
$systemPlugin = new KontorX_Controller_Plugin_System($configSystem);
$systemPlugin->setApplicationPath(APP_PATHNAME);
$systemPlugin->setPublicHtmlPath(PUBLIC_PATHNAME);
$systemPlugin->setTempPath(TMP_PATHNAME);
$front->registerPlugin($systemPlugin,20);

$front->throwExceptions($configFramework->throwExceptions);
$front->setParams($configFramework->params->toArray());

// konfig rutera
$front->getRouter()->addConfig($configRouter);