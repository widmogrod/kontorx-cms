<?php
require_once 'Zend/Db/Table/Abstract.php';
class UserPersonal extends Zend_Db_Table_Abstract {
	protected $_name = 'user_personal';
	
	protected $_referenceMap    = array(
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username',
			'onDelete'			=> self::CASCADE
        )
    );
}