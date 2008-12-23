<?php
require_once 'KontorX/Gwt/Db/Table/Decorator/Abstract.php';
require_once 'gallery/models/GalleryImage.php';

class GWT_GalleryImage extends KontorX_Gwt_Db_Table_Decorator_Abstract {

	public function __construct() {
		parent::__construct(new GalleryImage());
	}
}