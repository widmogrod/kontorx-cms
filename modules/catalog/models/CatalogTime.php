<?php
require_once 'KontorX/Db/Table/Tree/Abstract.php';
class CatalogTime extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog_time';
	
	protected $_referenceMap    = array(
        'Catalog' => array(
            'columns'           => 'catalog_id',
            'refTableClass'     => 'Catalog',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        )
	);
}