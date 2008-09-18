<?php
// zalerznosci
require_once 'user/models/User.php';

require_once 'KontorX/Db/Table/Abstract.php';
class Group extends KontorX_Db_Table_Abstract {
	protected $_name = 'group';

	protected $_dependentTables = array(
		'GroupClass',
		'GroupHasUser',
		'GroupGalleryImage'
	);

	protected $_referenceMap    = array(
        'User' => array(
            'columns'           => 'user_id',
            'refTableClass'     => 'User',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'username'
        )
    );

	public function fetchTimeRange() {
    	$select = $this->select()
    		->from($this->_name)
    		->columns(array(
    			'year' => 'YEAR(t_create)',
    			'month' => 'MONTH(t_create)'
    		))
    		->group('DATE_FORMAT(t_create,"%Y-%m")')
    		->order('t_create DESC');

    	return $this->fetchAll($select);
    }
}

require_once 'KontorX/Db/Table/Row.php';
class Group_Row extends KontorX_Db_Table_Row {
	public function fetchPublicNews() {
		require_once 'GroupNews.php';
		$this->findDependentRowset('GroupNews');
	}
}