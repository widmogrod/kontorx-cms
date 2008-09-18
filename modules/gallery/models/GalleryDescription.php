<?php
require_once 'KontorX/Db/Table/Abstract.php';
class GalleryDescription extends KontorX_Db_Table_Abstract {
	protected $_name = 'gallery_description';

	protected $_referenceMap    = array(
        'Gallery' => array(
            'columns'           => 'gallery_id',
            'refTableClass'     => 'Gallery',
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