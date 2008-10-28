<?php
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class Catalog extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog';
	
	protected $_dependentTables = array(
		'CatalogImage',
		'CatalogServiceCost'
	);
	
	protected $_referenceMap    = array(
        'CatalogDistrict' => array(
            'columns'           => 'catalog_district_id',
            'refTableClass'     => 'CatalogDistrict',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'CatalogType' => array(
            'columns'           => 'catalog_type_id',
            'refTableClass'     => 'CatalogType',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username'
        )
    );
}