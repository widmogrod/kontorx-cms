<?php
require_once 'Zend/Db/Table/Abstract.php';
class RoleAccess extends Zend_Db_Table_Abstract {
	protected $_name = 'role_access';

	protected $_referenceMap    = array(
        'Resource' => array(
            'columns'           => 'role_resource_id',
            'refTableClass'     => 'RoleResource',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        )
    );
}
