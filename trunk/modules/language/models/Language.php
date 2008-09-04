<?php
// zaleznosci z innych modolow
require_once 'page/models/Page.php';
require_once 'news/models/News.php';
require_once 'calendar/models/CalendarContent.php';
require_once 'gallery/models/GalleryDescription.php';
require_once 'gallery/models/GalleryImageDescription.php';

require_once 'Zend/Db/Table/Abstract.php';
class Language extends Zend_Db_Table_Abstract {
	protected $_name = 'language';
	
	protected $_dependentTables = array(
		'Page',
		'News',
		'CalendarContent',
		'GalleryDescription',
		'GalleryImageDescription'
	);
}