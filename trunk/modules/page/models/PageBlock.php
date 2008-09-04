<?php
require_once 'Zend/Db/Table/Abstract.php';
class PageBlock extends Zend_Db_Table_Abstract  {
	protected $_name = 'page_block';
	
	protected $_referenceMap    = array(
        'Page' => array(
            'columns'           => 'page_id',
            'refTableClass'     => 'Page',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'Block' => array(
            'columns'           => 'block_key',
            'refTableClass'     => 'Blocks',
            'refColumns'        => 'name',
			'refColumnsAsName'  => 'name',
        	'onDelete'			=> self::CASCADE
        )
    );
}