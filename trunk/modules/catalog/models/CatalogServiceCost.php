<?php
require_once 'KontorX/Db/Table/Abstract.php';
class CatalogServiceCost extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog_service_cost';
	
	protected $_referenceMap    = array(
        'Catalog' => array(
            'columns'           => 'catalog_id',
            'refTableClass'     => 'Catalog',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'CatalogService' => array(
            'columns'           => 'catalog_service_id',
            'refTableClass'     => 'CatalogService',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );
}