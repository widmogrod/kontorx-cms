<?php
class Forms {
	public function __construct($path) {
		$this->setPath($path);
	}

	public function has($file) {
		return is_file($this->getPath($file));
	}
	
	public function load($file) {
		if (!$this->has($file)) {
			$message = "Nie zmaleziono formularza!";
			throw new FormsException($message);
		}

		$filename = $this->getPath($file);

		try {
			require_once 'Zend/Config/Ini.php';
			$config = new Zend_Config_Ini($filename, null, array(
				'allowModifications' => true
			));
			return $config;
		} catch (Zend_Config_Exception $e) {
			throw $e;
		}
	}

	public function save($file, array $data) {
		$filename = $this->getPath($file);
		
		require_once 'KontorX/Config/Generate.php';
		$data = KontorX_Config_Generate::factory($data, KontorX_Config_Generate::INI);

		if (@!file_put_contents($filename, $data)) {
			$message = "Błąd podczas zapisu";
			throw new FormsException($message);
		} else {
			chmod($filename, 0666);
		}
	}

	public function fetchAll() {
		$result = array();

		$filename = $this->getPath();
		$di = new DirectoryIterator($filename);
		while ($di->valid()) {
			if ($di->isFile()) {
				$name = explode('.',$di->getFilename());
				array_pop($name);
				$result[] = implode('.',$name);
			}
			$di->next();
		}
		
		return $result;
	}
	
	public function createHtml($data, Zend_Form $form, Zend_View $view) {
		if (!is_array($data)) {
			if (!$data instanceof Zend_Config) {
				$message = "Data is not array or instance of Zend_Config";
				throw new FormsException($message);
			}
			$data = $data->toArray();
		}

		$result = null;
		foreach ($form->getValues() as $name => $value) {
			$element = $data['form']['elements'][$name];

			if (is_array($element)) {
				if (isset($element['options'])
						&& isset($element['options']['label'])) {
					$label = $element['options']['label'];
				} else {
					$label = $element['name'];
				}
				$label = $view->escape($label);
				$value = $view->escape($value);

				$result .= "<dt>$label</dt>";
				$result .= "<dd>$value</dd>";
			}
		}

		return "<dl>$result</dl>";
	}
	
	private $_path = null;

	private function _prepareFilename($file) {
		return strtolower($file) . '.ini';
	}
	
	public function setPath($path) {
		if (!is_dir($path)) {
			$message = "Katalog nie istnieje";
			throw new FormsException($message);
		}
		
		$this->_path = $path;
	}
	
	public function getPath($file = null) {
		if (null !== $file) {
			$file = $this->_prepareFilename($file);
		}
		return $this->_path . DIRECTORY_SEPARATOR . $file;
	}
}

class FormsException extends Exception {}