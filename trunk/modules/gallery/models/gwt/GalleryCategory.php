<?php
require_once 'KontorX/Gwt/Db/Table/Decorator/Abstract.php';
class GWT_GalleryCategory extends KontorX_Gwt_Db_Table_Decorator_Abstract {

	public function __construct() {
		require_once 'gallery/models/GalleryCategory.php';
		parent::__construct(new GalleryCategory());
	}
}