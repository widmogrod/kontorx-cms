<?php
require_once 'Zend/View/Helper/Abstract.php';
class User_View_Helper_ResourcesRowsetToKeyRowArray extends Zend_View_Helper_Abstract {

	/**
	 * Przygotowuje tabliece do odpowiedniego hormatu
	 *
	 * @param Zend_Db_Table_Rowset_Abstract|array $rowset
	 * @return array
	 */
	public function resourcesRowsetToKeyRowArray($rowset) {
		if($rowset instanceof Zend_Db_Table_Rowset_Abstract) {
			$rowset = $rowset->toArray();
		} else
		if (!is_array($rowset)) {
			return array();
		}

		$result = array();
		foreach ($rowset as $row) {
			$roleResourceId = $row['role_resource_id'];
			$roleAccessId   = $row['role_access_id'];
			$deny 			= $row['deny'] == 1 ? $roleAccessId : null;

			if (array_key_exists($roleResourceId, $result)) {
				// access
				if (array_key_exists('access', $result[$roleResourceId])) {
					$result[$roleResourceId]['access'][] = $roleAccessId;
				} else {
					$result[$roleResourceId]['access'] = array($roleAccessId);
				}
				// deny
				if (array_key_exists('deny', $result[$roleResourceId])) {
					$result[$roleResourceId]['deny'][] = $deny;
				} else {
					$result[$roleResourceId]['deny'] = array($deny);
				}
			} else {
				$result[$roleResourceId] = array(
					'access' => array($roleAccessId),
					'deny'   => array($deny)
				);
			}
		}

		return $result;
	}
}