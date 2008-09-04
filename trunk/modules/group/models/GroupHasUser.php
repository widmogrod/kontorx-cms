<?php
// zalerznosci
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class GroupHasUser extends KontorX_Db_Table_Abstract {
	protected $_name = 'group_has_user';

	protected $_referenceMap    = array(
        'Group' => array(
            'columns'           => 'group_id',
            'refTableClass'     => 'Group',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username'
        )
    );
}