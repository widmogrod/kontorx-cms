<?php
require_once 'Zend/Db/Table/Abstract.php';
class Blocks extends Zend_Db_Table_Abstract  {
	protected $_name = 'page_blocks';

	protected $_dependentTables = array(
		'PageBlock'
	);	
}