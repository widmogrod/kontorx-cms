<?php
require_once 'KontorX/Db/Table/Abstract.php';
class GoogleStats extends KontorX_Db_Table_Abstract {
	protected $_name = 'google_stats';
	
	protected $_referenceMap    = array(
        'Site' => array(
            'columns'           => 'site_id',
            'refTableClass'     => 'GoogleSite',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'url'
        ),
        'Keyword' => array(
            'columns'           => 'keyword_id',
            'refTableClass'     => 'GoogleKeyword',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'keyword'
        )
    );

}