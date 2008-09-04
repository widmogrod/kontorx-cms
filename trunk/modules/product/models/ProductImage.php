<?php
require_once 'KontorX/Db/Table/Abstract.php';
class ProductImage extends KontorX_Db_Table_Abstract {
	protected $_name = 'product_image';
	protected $_rowClass = 'ProductImage_Row';
	
	protected $_referenceMap    = array(
        'Product' => array(
            'columns'           => 'product_id',
            'refTableClass'     => 'Product',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );
}

require_once 'KontorX/Db/Table/Row/FileUpload/Abstract.php';

/**
 * Wspomaga operacje na rekordach ..
 */
class ProductImage_Row extends KontorX_Db_Table_Row_FileUpload_Abstract {
	protected $_fieldFilename = 'image';
	
	protected $_noUploadException = false;
	
	public function init() {
		self::setImagePath('./upload/product/');
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

	/**
	 * Klonowanie rekordu
	 *
	 * Klonowanie rekordu, przydatne przy duplikacji
	 */
	public function __clone() {
		// tylko dlatego by byl insert
		$this->_cleanData = array();

		// generowanie sciezek do pliku
		$pathOrginal 	 = $this->_getUploadPath() . '/' . $this->image;
		$imageDuplicated = substr(md5(time()),0,5) . 'D' . $this->image;
		$pathDuplicated  = $this->_getUploadPath() . '/' . $imageDuplicated;

		// sprawdzam czy plk istnieje
		if (!is_file($pathOrginal)) {
			// logowanie zdarzenia
			$logger = Zend_Registry::get('logger');
			$logger->log(__CLASS__ . __FILE__ . __LINE__, Zend_Log::ERR);
		} else
		// czy udalo się go skopiować
		if(!@copy($pathOrginal, $pathDuplicated)) {
			// logowanie zdarzenia
			$logger = Zend_Registry::get('logger');
			$logger->log(__CLASS__ . __FILE__ . __LINE__, Zend_Log::ERR);
		}

		// przygotowanie danych do duplikacji
		unset($this->_data['id']);
		$this->_data['product_id']  = null;
		$this->_data['image']  		= $imageDuplicated;

		// ustawienie modyfikowanych kluczy
		$this->_modifiedFields = array_combine(
			array_keys($this->_data),
			array_fill(0,count($this->_data),true));

		// @see {$this->_insert()}
		$this->setNoUploadException(true);
	}
}