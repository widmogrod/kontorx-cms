<?php
class Management {

	/**
	 * Zapytanie wyciaga potrzebne dane
	 * 
	 * @param Zend_Controller_Request_Abstract $rq
	 * @return Zend_Db_Select
	 */
	public function selectCatalogForPromoType(Zend_Controller_Request_Abstract $rq) {
		$catalog = new Catalog();

		require_once 'Zend/Db/Select.php';
		$select = new Zend_Db_Select($catalog->getAdapter());

		$select
			->from(array('c' => 'catalog'), array('c.name','c.id'))
			->joinLeft(array('cpt' => 'catalog_promo_time'),
				'cpt.catalog_id = c.id',
					array('cpt.t_start','cpt.t_end','cpt.catalog_promo_type_id'))
			->joinLeft(array('cptp' => 'catalog_promo_type'),
				'cpt.catalog_promo_type_id = cptp.id',
					array('type' => 'cptp.name'))
			->order('cpt.catalog_promo_type_id DESC');

		$catalog->selectForRowOwner($rq, $select);

		return $select;
	}
	
	/**
	 * @param $id
	 * @param $rq
	 * @return Zend_Db_Table_Row|null
	 */
	public function findCatalogRowForUser($id, Zend_Controller_Request_Abstract $rq) {
		require_once 'catalog/models/Catalog.php';
		$catalog = new Catalog();

		$select = $catalog->selectForRowOwner($rq);
		$select->where('id = ?', $id, Zend_Db::INT_TYPE);

		try {
			return $catalog->fetchRow($select);
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return null;
		}
	}

	/**
	 * @param $id
	 * @return array
	 */
	public function findServicesRowsetForCatalogId($id) {
		require_once 'catalog/models/CatalogServiceCost.php';
		$serviceCost = new CatalogServiceCost();
		
		$selectCost = $serviceCost->select()
			->where('catalog_id = ?', $id, Zend_Db::INT_TYPE);

		try {
			$rowsetCost = $serviceCost->fetchAll($selectCost);
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString());
			return false;
		}
		

		require_once 'catalog/models/CatalogService.php';
		$service = new CatalogService();
		
		try {
			$rowsetService = $service->fetchAll();
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}

		$result = array();
		foreach ($rowsetService as $service) {
			$data = $service->toArray();
			
			if (null !== ($cost = $this->_hasServiceCost($service, $rowsetCost))) {
				$data['cost_min'] = $cost->cost_min;
				$data['cost_max'] = $cost->cost_max;
			} else {
				$data['cost_min'] = null;
				$data['cost_max'] = null;
			}

			$result[] = $data;
		}
		
		return $result;
	}
	
	/**
	 * @param $service
	 * @param $rowsetCost
	 * @return Zend_Db_Table_Row_Abstract|null
	 */
	private function _hasServiceCost(Zend_Db_Table_Row_Abstract $service, Zend_Db_Table_Rowset_Abstract $rowsetCost) {
		foreach ($rowsetCost as $cost) {
			if ($cost->catalog_service_id == $service->id) {
				return $cost;
			}
		}
		
		return null;
	}
	
	public function saveServicesCost($catalogId, Zend_Controller_Request_Abstract $rq) {
		require_once 'catalog/models/CatalogServiceCost.php';
		$serviceCost = new CatalogServiceCost();

		require_once 'Zend/Filter/Digits.php';
		$filter = new Zend_Filter_Int();
		
		$catalogId = $filter->filter($catalogId);
		
		try {
			foreach ((array) $rq->getPost('data') as $catalogServicId => $data) {
				$catalogServicId = $filter->filter($catalogServicId);

				$serviceCost
					->delete("catalog_id = '$catalogId' AND catalog_service_id = '$catalogServicId'");
					
				$data = array_filter($data);
				$data = array_intersect_key(
					$data, array_flip(array('cost_min','cost_max')));

				if ((!isset($data['cost_min']) || empty($data['cost_min']))
						&& (!isset($data['cost_max']) || empty($data['cost_max']))) {
					continue;
				}
					
				$data['cost_min'] = (float) @$data['cost_min'];
				$data['cost_max'] = (float) @$data['cost_max'];
				$data['catalog_id'] = $catalogId;
				$data['catalog_service_id'] = $catalogServicId;

				$serviceCost->insert($data);
			}
			return true;
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}			
	}
	
	public function insertImage($catalogId, $imageName) {
		require_once 'catalog/models/CatalogImage.php';
		$image = new CatalogImage(array(
			'rowClass' => 'Zend_Db_Table_Row'
		));

		try {
			$image
				->createRow(array(
					'image' => $imageName,
					'catalog_id' => $catalogId
				))
				->save();
				return true;
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}
	}

	public function setMainImage($imageId) {
		require_once 'catalog/models/CatalogImage.php';
		$image = new CatalogImage(array(
			'rowClass' => 'Zend_Db_Table_Row'
		));
		
		try {
			$row = $image->fetchRow(
				$image->select()->where('id = ?', $imageId, Zend_Db::INT_TYPE)
			);
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}
		
		if (null === $row) {
			return false;
		}

		try {
			require_once 'catalog/models/Catalog.php';
			$catalog = $row->findParentRow('Catalog');
			$catalog->catalog_image_id = $imageId;
			$catalog->save();
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}
		
		return true;
	}

	public function deleteImage($imageId) {
		require_once 'catalog/models/CatalogImage.php';
		$image = new CatalogImage(array(
			'rowClass' => 'Zend_Db_Table_Row'
		));
		
		try {
			$row = $image->fetchRow(
				$image->select()->where('id = ?', $imageId, Zend_Db::INT_TYPE)
			);
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}
		
		if (null === $row) {
			return false;
		}

		try {
			require_once 'catalog/models/Catalog.php';
			$catalog = $row->findParentRow('Catalog');
			$catalog->catalog_image_id = null;
			$catalog->save();
			$row->delete();
		} catch (Zend_Db_Exception $e) {
			Zend_Registry::get('logger')
				->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}
		
		return true;
	}

        public function saveOptions($catalogId, array $options) {
            require_once 'catalog/models/CatalogHasCatalogOptions.php';
            $model = new CatalogHasCatalogOptions();
            $adapter = $model->getAdapter();
            try {
                $adapter->beginTransaction();
                // kasuje wszystkie
                $model->delete($adapter->quoteInto("catalog_id = ?", $catalogId));
                // dodaje
                foreach ($options as $optionId) {
                    $row = $model->createRow(array(
                        'catalog_id' => (int) $catalogId,
                        'catalog_options_id' => (int) $optionId,
                    ));
                    $row->save();
                }
                $adapter->commit();
                return true;
            } catch (Zend_Db_Table_Exception $e) {
                $adapter->rollBack();
                Zend_Registry::get('logger')
                    ->log($e->getMessage() ."\n".$e->getTraceAsString(), Zend_Log::ERR);
            }

            return false;
        }
}