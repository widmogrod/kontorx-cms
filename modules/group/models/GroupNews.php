<?php
// zalerznosci
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class GroupNews extends KontorX_Db_Table_Abstract {
	protected $_name = 'group_news';

	protected $_referenceMap    = array(
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username'
        ),
        'Group' => array(
            'columns'           => 'group_id',
            'refTableClass'     => 'Group',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );
}