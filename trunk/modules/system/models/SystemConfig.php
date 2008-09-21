<?php
class SystemConfigException extends Zend_Exception {}

/**
 * SystemConfig
 *
 * @version 1.2
 */
class SystemConfig {
	public function __construct(Zend_Controller_Front $front = null) {
		if (null !== $front) {
			$this->setFrontController($front);
		}

		$front = $this->getFrontController();

		// katalog do konfiguracji applikacji
		$systemPlugin = $front->getPlugin('KontorX_Controller_Plugin_System');
		$configPath   = $systemPlugin->getApplicationPath(
			KontorX_Controller_Plugin_System::APPLICATION_CONFIGURATION_DIRNAME);
		$this->setConfigPath($configPath);

		// katalog do modułów
		$configModulesPath   = $systemPlugin->getApplicationPath(
			KontorX_Controller_Plugin_System::APPLICATION_MODULES_DIRNAME);
		$this->setModulesPath($configModulesPath);
	}

	/**
	 * @var string
	 */
	protected $_configAppPath = null;
	
	/**
	 * Ustawia sciezke do katalogu konfiguracji applikacji
	 *
	 * @param string $path
	 */
	public function setConfigPath($path) {
		if (!is_dir($path)) {
			throw new SystemConfigException("Directory `$path` do not exsists!");
		}
		$this->_configAppPath = (string) $path;
	}

	/**
	 * Zwraca sciezke do katalogu konfiguracji applikacji
	 *
	 * @return string
	 */
	public function getConfigPath() {
		return $this->_configAppPath;
	}

	/**
	 * @var string
	 */
	protected $_modulesPath = null;
	
	/**
	 * Zwraca sciezke do katalogu z modułami
	 *
	 * @param string $path
	 */
	public function setModulesPath($path) {
		if (!is_dir($path)) {
			throw new SystemConfigException("Directory `$path` do not exsists!");
		}
		$this->_modulesPath = (string) $path;
	}

	/**
	 * Zwraca sciezke do katalogu z modułami
	 *
	 * @param string $moduleName
	 * @return string
	 */
	public function getModulesPath($moduleName = null) {
		$return = $this->_modulesPath;
		if (null !== $moduleName) {
			$return .= "/$moduleName";
		}
		return $return;
	}

	/**
	 * @var array
	 */
	protected $_appConfigPathnames = null;
	
	/**
	 * Zwtaca tablicę plików nazw konfiguracyjnych
	 * 
	 * Zwraca tablice z wszystkimi plikami konfiuracyjnymi systemu
	 *
	 * @return array
	 */
	public function fetchAllAppConfigPathnames() {
		if (null === $this->_appConfigPathnames) {
			$result = array();
			$path = $this->getConfigPath();
			foreach (new DirectoryIterator($path) as $file) {
				if ($file->isFile()
						&& $file->isReadable()
								&& $file->isWritable()) {
					$result[$file->getFilename()] = $file->getPathname();
				}
			}
			$this->_appConfigPathnames = $result;
		}
		return $this->_appConfigPathnames;
	}

	/**
	 * @var array
	 */
	protected $_appModulesConfigPathnames = null;
	
	/**
	 * Zwtaca tablicę plików nazw konfiguracyjnych
	 * 
	 * Zwraca tablice z wszystkimi plikami konfiuracyjnymi systemu
	 *
	 * @return array
	 */
	public function fetchAllAppModulesConfigPathnames() {
		if (null === $this->_appModulesConfigPathnames) {
			$result = array();
			$path = $this->getModulesPath();
			foreach (new DirectoryIterator($path) as $file) {
				$mPath = $file->getPathname();
				// tylko katalogi modulow
				if ($file->isDir() && !$file->isDot()) {
					$mResult = array();
					// szukamy teraz konfiguracji
					foreach (new DirectoryIterator($mPath) as $mFile) {
						$mFilename = $mFile->getFilename();
						if ($mFile->isFile()
								&& $mFile->isReadable()
									&& $mFile->isWritable()
										&& substr($mFilename, -3) == 'ini') {
							$mResult[$file->getFilename() . '_' . $mFilename] = $mFilename;
						}
					}
					$result[$file->getFilename()] = $mResult;
				}
			}
			$this->_appModulesConfigPathnames = $result;
		}
		return $this->_appModulesConfigPathnames;
	}

	
	
	/**
	 * Zapis konfiguracji
	 *
	 * @param array $data
	 * @param string $configFileName
	 */
	public function saveAppConfig(array $data, $configFileName) {
		$paths = $this->fetchAllAppConfigPathnames();
		
		if (!array_key_exists($configFileName, $paths)) {
			throw new SystemConfigException("Config filename do not exsists!");
		}

		$path = $paths[$configFileName];

		if (!is_writeable($path)) {
			throw new SystemConfigException("Config filename is not writable");
		}

		require_once 'KontorX/Config/Generate.php';
		$g = KontorX_Config_Generate::factory($data, KontorX_Config_Generate::INI);

		$data = $g->__toString();

		if (!@file_put_contents($path, $data)) {
			throw new SystemConfigException("Can't save configuration to config filename");
		}
	}

	/**
	 * Zapis konfiguracji
	 *
	 * @param array $data
	 * @param string $configFileName
	 */
	public function saveAppModuleConfig(array $data, $moduleName, $configFileName) {
		$paths = $this->fetchAllAppModulesConfigPathnames();

		if (!array_key_exists($moduleName, $paths)) {
			throw new SystemConfigException("Config module do not exsists!");
		}
		$configKey = $moduleName . '_' . $configFileName;
		if (!array_key_exists($configKey, $paths[$moduleName])) {
			throw new SystemConfigException("Config filename do not exsists!");
		}

		$path = $this->getModulesPath($moduleName) . '/' . $configFileName;

		if (!is_writeable($path)) {
			throw new SystemConfigException("Config filename is not writable");
		}

		require_once 'KontorX/Config/Generate.php';
		$g = KontorX_Config_Generate::factory($data, KontorX_Config_Generate::INI);

		$data = $g->__toString();

		if (!@file_put_contents($path, $data)) {
			throw new SystemConfigException("Can't save configuration to config filename");
		}
	}

	/**
	 * @var Zend_Controller_Front
	 */
	protected $_frontController = null;
	
	/**
	 * Ustawienie instancji @see Zend_Controller_Front
	 *
	 * @param Zend_Controller_Front $front
	 */
	public function setFrontController(Zend_Controller_Front $front) {
		$this->_frontController = $front;
	}
	
	/**
	 * Zwraca instancję @see Zend_Controller_Front
	 *
	 * @return Zend_Controller_Front
	 */
	public function getFrontController() {
		if (null === $this->_frontController) {
			$this->_frontController = Zend_Controller_Front::getInstance();
		}
		return $this->_frontController;
	}
}