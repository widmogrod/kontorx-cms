<?php
require_once 'KontorX/Db/Table/Abstract.php';
class GalleryImageDescription extends KontorX_Db_Table_Abstract {
	protected $_name = 'gallery_image_description';
	
	protected $_referenceMap    = array(
        'GalleryImage' => array(
            'columns'           => 'gallery_image_id',
            'refTableClass'     => 'GalleryImage',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'Language' => array(
            'columns'           => 'language_url',
            'refTableClass'     => 'Language',
            'refColumns'        => 'url',
			'refColumnsAsName'  => 'name',
        	'onDelete'			=> self::CASCADE
        )
    );
}