<?php
require_once 'KontorX/Db/Table/Tree/Abstract.php';
class CatalogPromoTime extends KontorX_Db_Table_Abstract {
	protected $_name = 'catalog_promo_time';
	
	protected $_referenceMap    = array(
        'CatalogPromoType' => array(
            'columns'           => 'catalog_promo_type_id',
            'refTableClass'     => 'CatalogPromoType',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'Catalog' => array(
            'columns'           => 'catalog_id',
            'refTableClass'     => 'Catalog',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );
}