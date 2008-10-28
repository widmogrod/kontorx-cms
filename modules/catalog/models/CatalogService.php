<?php
require_once 'KontorX/Db/Table/Abstract.php';
class CatalogService extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog_service';
	protected $_rowClass = 'CatalogService_Row';
	
	protected $_dependentTables = array(
		'CatalogSetviceCost'
	);
}

require_once 'KontorX/Db/Table/Row/FileUpload/Abstract.php';
class CatalogService_Row extends KontorX_Db_Table_Row_FileUpload_Abstract {
	protected $_filesKeyName = 'ico';
	protected $_fieldFilename = 'ico';

	protected $_noUploadException = false;

	public function init() {
		self::setImagePath('./upload/catalog/ico_service/');
		parent::init();
	}

	public function setNoUploadException($flag = true) {
		$this->_noUploadException = (bool) $flag;
	}

	public function _insert() {
		parent::_insert();

		if ($this->hasMessages() && !$this->_noUploadException) {
			require_once 'KontorX/Db/Table/Row/FileUpload/Exception.php';
			$message = implode("\n",$this->getMessages());
			throw new KontorX_Db_Table_Row_FileUpload_Exception($message);
		}
	}
}