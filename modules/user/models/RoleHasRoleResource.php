<?php
require_once 'Zend/Db/Table/Abstract.php';
class RoleHasRoleResource extends Zend_Db_Table_Abstract {
	protected $_name = 'role_has_role_resource';

	protected $_dependentTables = array();

	protected $_referenceMap    = array(
        'Role' => array(
            'columns'           => 'role_id',
            'refTableClass'     => 'Role',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			'onDelete'			=> self::CASCADE
        ),
        'Resource' => array(
            'columns'           => 'role_resource_id',
            'refTableClass'     => 'RoleResource',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
        	'onDelete'			=> self::CASCADE
        )
    );

    /**
     * Sortuje tabliec a'la ACL
     *
     * Sortuje tabliec a'la moje wydanie ACL
     * @see KontorX_Acl
     * 
     * @param array $array
     * @return array
     */
    public function reOrderToAclArray(array $array) {
    	$result = array();
    	foreach ($array as $row) {
    		$role 	  = $row->role;
    		$resource = $row->resource;
    		$access   = $row->access;
    		$forbiden = $row->deny == 1 ? 'deny' : 'allow';

    		if (array_key_exists($role, $result)) {
    			// niestety takie manewracje ale ponizsze przyklady
    			// nie dzialaja ..
    			// $result[$role] += $data
    			// array_merge($result[$role], $data);
    			// array_merge_recursive($result[$role], $data);
    			if (array_key_exists($resource, $result[$role])) {
    				if (!array_key_exists($forbiden, $result[$role][$resource])) {
    					$result[$role][$resource][$forbiden] = array();
    				}
    				$result[$role][$resource][$forbiden][] = $access;
    			} else {
    				$result[$role][$resource] = array(
	    				$forbiden => array($access)
	    			);
    			}
    		} else {
    			$result[$role] = array(
	    			$resource => array(
	    				$forbiden => array($access)
	    			)
	    		);
    		}
    	}
    	return $result;
    }
    
    /**
     * Wylawia wszystkie uprawnienia
     * 
     * Wylawia wszystkie uprawnienia z odpowiednio
     * przyporzadkowanymi nazwami kolumn i wartosci
     * z innych tabel w formacie a'la moje wydanie - ACL
     *
     * @return array
     */
    public function fetchAllAsAclFormatNames() {
    	require_once 'Zend/Db/Select.php';
		$select = new Zend_Db_Select($this->getAdapter());
		
		$select
			->from(
				array('rhrr' => 'role_has_role_resource'),
				array('deny')
			)
			->joinLeft(
				array('r' => 'role'),
				'rhrr.role_id = r.id',
				array('role' => 'r.name')
			)
			->joinLeft(
				array('ra' => 'role_access'),
				'rhrr.role_access_id = ra.id',
				array('access' => 'ra.name')
			)
			->joinLeft(
				array('rr' => 'role_resource'),
				'rhrr.role_resource_id = rr.id',
				array('resource' => 'rr.name')
			)
			->order('rr.name ASC');
		
		$stmt = $select->query(Zend_db::FETCH_CLASS);
		$rowset = $stmt->fetchAll();
		return $rowset;
    }
    
    /**
     * Wstawia do bazy rekord(y)
     *
     * Wstawia do bazy rekord(y) z posortowane
     * z surowej tablicy $_POST
     * 
     * @param array $data
     * @param integer $roleId
     */
    public function insertRowsFromRawData(array $array, $roleId) {
    	// przeszukuje i analizuje dane
		foreach ($array as $resourceId => $rowset) {
			if (isset($rowset['resource'])) {
				$resourceId = (int) $rowset['resource'];
			}
			
			$dataBase = array(
				'role_id' => $roleId,
				'role_resource_id' => $resourceId
			);
			
			// jesli są wyszczegułowione akcje
			$access = array();
			if (isset($rowset['access'])) {
				$access = (array) $rowset['access'];
				
				// jakie akcje z akcji wybranych w/w
				// nie maja dostępu
				$deny = array();
				if (isset($rowset['deny'])) {
					$deny = (array) $rowset['deny'];
				}

				foreach ($access as $accessId) {
					$dataExt = $dataBase;
					$dataExt += array(
						'role_access_id' => $accessId,
						'deny'			 => (int) in_array($accessId, $deny)
					);
					
					$this->insert($dataExt);
				}
			}
			// dla kazdej akcji
			else {
				$this->insert($dataBase);
			}
		}
    }
}
