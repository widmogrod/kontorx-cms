<?php
require_once 'Zend/Db/Table/Abstract.php';
class RoleResource extends Zend_Db_Table_Abstract {
	protected $_name = 'role_resource';

	protected $_dependentTables = array(
		'RoleAccess',
		'RoleHasRoleResource'
	);

	/**
	 * Wyławia resource i access
	 *
	 * @return array
	 */
	public function fetchAllJoinAccess() {
		require_once 'Zend/Db/Select.php';
		$select = new Zend_Db_Select($this->getAdapter());
		
		$select
			->from(
				array('rr' => 'role_resource')
			)
			->joinLeft(
				array('ra' => 'role_access'),
				'rr.id = ra.role_resource_id',
				array(
					'action' => 'ra.name',
					'action_id' => 'ra.id'
				)
			)
			->order('rr.name ASC');
		
		$stmt = $select->query(Zend_db::FETCH_CLASS);
		return $stmt->fetchAll();
	}

	/**
	 * Prze-sortowuje tablice
	 * 
	 * Prze-sortowuje tablice
	 * wylowiona przez @see self::fetchAllJoinAccess()
	 *
	 * @return array
	 */
	public function reOrderResourceToAccess(array $array) {
		// przygotowanie rekordow
		$rowset = array();
		foreach ($array as $row) {
			if (array_key_exists($row->id, $rowset)) {
				$rowset[$row->id]['access'] += array(
					$row->action_id => $row->action
				);
			} else {
				$rowset[$row->id] = array(
					'name' => $row->name,
					'access' => array(
						$row->action_id => $row->action
					)
				);
			}
		}
		return $rowset;
	}
}
