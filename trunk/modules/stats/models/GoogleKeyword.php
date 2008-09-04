<?php
require_once 'KontorX/Db/Table/Abstract.php';
class GoogleKeyword extends KontorX_Db_Table_Abstract {
	protected $_name = 'google_keyword';

	protected $_dependentTables = array(
		'GoogleStats'
	);
	
	protected $_referenceMap    = array(
        'Site' => array(
            'columns'           => 'site_id',
            'refTableClass'     => 'GoogleSite',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'url'
        )
    );

}