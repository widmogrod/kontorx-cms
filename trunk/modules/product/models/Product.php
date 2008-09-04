<?php
require_once 'KontorX/Db/Table/Abstract.php';
class Product extends KontorX_Db_Table_Abstract {
	protected $_name = 'product';
	protected $_rowClass = 'Product_Row';
	
	protected $_dependentTables = array(
		'ProductHasCategory',
		'ProductImage'
	);

	protected $_referenceMap    = array(
        'Manufacturer' => array(
            'columns'           => 'product_manufacturer_id',
            'refTableClass'     => 'ProductManufacturer',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name'
        )
    );

    public function attachToCategories($productId, array $categories) {
    	require_once 'product/models/ProductCategory.php';
		$category = new ProductHasCategory();

		$db = $this->getAdapter();
		$db->beginTransaction();

		$where = $db->quoteInto('product_id = ?', $productId);
		try {
			$category->delete($where);
			foreach ($categories as $cateogryId) {
				$data = array(
					'product_id' => $productId,
					'product_category_id' => $cateogryId
				);
				
				$category->insert($data);
			}
			$db->commit();
		} catch(Zend_Db_Table_Exception $e) {
			$db->rollBack();
			throw new Zend_Db_Table_Exception($e->getMessage());
		} catch(Zend_Db_Statement_Exception $e) {
			$db->rollBack();
			throw new Zend_Db_Table_Exception($e->getMessage());
		}
    }
}

require_once 'Zend/Db/Table/Row/Abstract.php';
class Product_Row extends Zend_Db_Table_Row_Abstract {

	/**
	 * Znajduje id kategorii do ktoeych nalerzy rekord
	 *
	 * @return array
	 */
	public function findDependentCategoriesArray() {
		$result = array();
		$rowset = $this->findDependentRowset('ProductHasCategory');
		foreach ($rowset as $row) {
			$result[] = $row->product_category_id;
		}
		return $result;
	}

	/**
	 * Klonowanie rekordu
	 *
	 * Klonowanie rekordu, przydatne przy duplikacji
	 */
	public function __clone() {
		// tylko dlatego by byl insert
		$this->_cleanData = array();

		// przygotowanie danych do duplikacji
		unset($this->_data['id']);
		$this->_data['name'] = $this->_data['name'] . ' [duplikat]';
		$this->_data['url']  = $this->_data['url']  . '-duplikat';

		// ustawienie modyfikowanych kluczy
		$this->_modifiedFields = array_combine(
			array_keys($this->_data),
			array_fill(0,count($this->_data),true));
	}
}