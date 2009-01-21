<?php
require_once 'KontorX/Db/Table/Tree/Abstract.php';
class CatalogHasCatalogOptions extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog_has_catalog_options';
	
	protected $_referenceMap    = array(
        'Catalog' => array(
            'columns'           => 'catalog_id',
            'refTableClass'     => 'Catalog',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'CatalogOptions' => array(
            'columns'           => 'catalog_options_id',
            'refTableClass'     => 'CatalogOptions',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
	);
}