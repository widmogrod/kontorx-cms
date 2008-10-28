<?php
require_once 'KontorX/Db/Table/Abstract.php';
class CatalogImage extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog_image';
	protected $_rowClass = 'CatalogImage_Row';

	protected $_referenceMap    = array(
        'Catalog' => array(
            'columns'           => 'catalog_id',
            'refTableClass'     => 'Catalog',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        )
    );
}

require_once 'KontorX/Db/Table/Row/FileUpload/Abstract.php';
class CatalogImage_Row extends KontorX_Db_Table_Row_FileUpload_Abstract {
	protected $_filesKeyName = 'image';
	protected $_fieldFilename = 'image';

	protected $_noUploadException = false;

	public function init() {
		self::setImagePath('./upload/catalog/image/');
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