<?php
require_once 'KontorX/Db/Table/Abstract.php';
class GalleryCategory extends KontorX_Db_Table_Abstract {
	protected $_name = 'gallery_category';
	
	protected $_dependentTables = array(
		'Gallery',
	);
}