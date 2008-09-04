<?php
require_once 'KontorX/Db/Table/Abstract.php';
class ProductManufacturer extends KontorX_Db_Table_Abstract {
	protected $_name = 'product_manufacturer';
	
	protected $_dependentTables = array(
		'Product'
	);
}