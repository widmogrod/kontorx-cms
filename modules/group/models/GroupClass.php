<?php
// zalerznosci
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class GroupClass extends KontorX_Db_Table_Abstract {
	protected $_name = 'group_class';

	protected $_referenceMap    = array(
        'Group' => array(
            'columns'           => 'group_id',
            'refTableClass'     => 'Group',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );
}