<?php
require_once 'KontorX/Db/Table/Abstract.php';
class ProductHasCategory extends KontorX_Db_Table_Abstract {
	protected $_name = 'product_has_product_category';

	protected $_referenceMap    = array(
        'Product' => array(
            'columns'           => 'product_id',
            'refTableClass'     => 'Product',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        ),
        'Category' => array(
            'columns'           => 'product_category_id',
            'refTableClass'     => 'ProductCategory',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );
}