<?php
// zaleznosci
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class GalleryImage extends KontorX_Db_Table_Abstract {
	protected $_name = 'gallery_image';
	protected $_rowClass = 'GalleryImage_Row';
	
	protected $_dependentTables = array(
		'GalleryImageDescription',
	);
	
	protected $_referenceMap    = array(
        'Gallery' => array(
            'columns'           => 'gallery_id',
            'refTableClass'     => 'Gallery',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username'
        )
    );
}

require_once 'KontorX/Db/Table/Row/FileUpload/Abstract.php';

/**
 * Wspomaga operacje na rekordach ..
 */
class GalleryImage_Row extends KontorX_Db_Table_Row_FileUpload_Abstract {
	protected $_fieldFilename = 'image';
	
	protected $_noUploadException = false;
	
	public function init() {
		self::setImagePath('./upload/gallery/');
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