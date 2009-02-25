<?php
require_once 'KontorX/Db/Table/Tree/Abstract.php';
class CatalogHasCatalogStaff extends KontorX_Db_Table_Abstract {
    protected $_name = 'catalog_has_catalog_staff';

    protected $_referenceMap    = array(
        'Catalog' => array(
            'columns'           => 'catalog_id',
            'refTableClass'     => 'Catalog',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'name',
            'onDelete'		=> self::CASCADE
        ),
        'CatalogStaff' => array(
            'columns'           => 'catalog_staff_id',
            'refTableClass'     => 'CatalogStaff',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'fullname',
            'onDelete'		=> self::CASCADE
        ),
    );
}