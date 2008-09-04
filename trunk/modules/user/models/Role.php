<?php
require_once 'Zend/Db/Table/Abstract.php';
class Role extends Zend_Db_Table_Abstract {
	protected $_name = 'role';

	protected $_dependentTables = array(
		'Role', // czy nie przygrandzilem za bardzo!?.
		'User',
		'RoleHasRoleResource',
//		'RoleResourceHasRoleAccess'
	);
	
	protected $_referenceMap    = array(
        'Role' => array(
            'columns'           => 'role_id',
            'refTableClass'     => 'Role',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );
}
