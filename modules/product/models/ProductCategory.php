<?php
require_once 'KontorX/Db/Table/Tree/Abstract.php';
class ProductCategory extends KontorX_Db_Table_Tree_Abstract {
	protected $_name = 'product_category';
	protected $_level = 'path';

	protected $_rowClass = 'ProductCategory_Row';
	
	protected $_dependentTables = array(
		'ProductHasCategory'
	);
}

// czemu nie KontorX_Db_Table_Tree_Row_Abstract? - zobacz źrodlo!
require_once 'KontorX/Db/Table/Tree/Row.php';
class ProductCategory_Row extends KontorX_Db_Table_Tree_Row {

	/**
	 * Znajduje produkty nalerzace do kategorii
	 * 
	 * @return KontorX_Db_Table_Tree_Rowset_Abstract
	 */
	public function findDependentProductsRowset() {
		$db = $this->_table->getAdapter();
		$select = new Zend_Db_Select($db);
		$select
			->from(array('phpc'=>'product_has_product_category'),array('category_id'=>'phpc.product_category_id'))
			->joinInner(
				array('p' => 'product'),
				"p.id = phpc.product_id",
				Zend_Db_Select::SQL_WILDCARD
			)
			->joinLeft(
				array('pi' => 'product_image'),
				"(pi.product_id = p.id AND (pi.thumb = 1))",
				array('image' => 'pi.image')
			)
//			->joinLeft(
//				array('pp'=>'product_promotion'),
//				'(pp.product_id = p.id) AND ("'.date('Y-m-d').'" BETWEEN pp.promotion_start AND pp.promotion_end)',
//				array('promotion' => 'pp.price')
//			)
			->where('p.publicated = 1')
			->where('phpc.product_category_id = ?', $this->id)
			->order('pi.thumb DESC');

		$stmt = $select->query();
		$result = $stmt->fetchAll(Zend_Db::FETCH_CLASS);
		return $result;
//		$select = $this->select();
//		$select
//			->from(array('p' => 'product'))
//			->joinLeft(
//				array('pi' => 'product_image'),
//				"(pi.product_id = p.id AND pi.main = 1)",
//				array('image')
//			);
//
//		return $this->findManyToManyRowset('Product','ProductHasCategory', null, null, $select);
	}
}