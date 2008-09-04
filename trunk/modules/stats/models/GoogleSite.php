<?php
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class GoogleSite extends KontorX_Db_Table_Abstract {
	protected $_name = 'google_site';
	
	protected $_dependentTables = array(
		'GoogleStats',
		'GoogleKeyword'
	);

	protected $_referenceMap    = array(
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username'
        )
    );

}