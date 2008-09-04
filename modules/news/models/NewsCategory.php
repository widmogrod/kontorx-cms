<?php
require_once 'KontorX/Db/Table/Tree/Abstract.php';
class NewsCategory extends KontorX_Db_Table_Tree_Abstract  {
	protected $_name = 'news_category';
	protected $_level = 'path';
	
	protected $_rowClass = 'NewsCategory_Row';

	protected $_dependentTables = array(
		'NewsHasCategory'
	);
}

// czemu nie KontorX_Db_Table_Tree_Row_Abstract? - zobacz źrodlo!
require_once 'KontorX/Db/Table/Tree/Row.php';
class NewsCategory_Row extends KontorX_Db_Table_Tree_Row {

	/**
	 * Znajduje aktualnosci nalerzace do kategorii
	 * 
	 * @return KontorX_Db_Table_Tree_Rowset_Abstract
	 */
	public function findDependentProductsRowset(Zend_Db_Select $select = null) {
		$select = (null === $select)
			? $this->getDependentProductsSelect($select)
			: $select;

		$stmt = $select->query();
		$result = $stmt->fetchAll(Zend_Db::FETCH_CLASS);
		return $result;
	}

	/**
	 * Zwraca Zend_Db_Select ..
	 *
	 * @return Zend_Db_Select
	 */
	public function getDependentProductsSelect(Zend_Db_Select $select = null) {
		if (null === $select) {
			$db = $this->_table->getAdapter();
			$select = new Zend_Db_Select($db);
		}
		$select
			->from(array('nhnc'=>'news_has_news_category'),array('category_id'=>'nhnc.news_category_id'))
			->joinInner(
				array('n' => 'news'),
				"n.id = nhnc.news_id",
				Zend_Db_Select::SQL_WILDCARD
			)
			->where('n.publicated = 1')
			->where('nhnc.news_category_id = ?', $this->id);

		return $select;
	}

	/**
	 * Zwraca Zend_Db_Select ..
	 *
	 * @return Zend_Db_Select
	 */
	public function getDependentProductsSelectPaginator() {
		$db = $this->_table->getAdapter();
		$select = new Zend_Db_Select($db);
		$select
			->from(array('nhnc'=>'news_has_news_category'))
			->joinInner(
				array('n' => 'news'),
				"n.id = nhnc.news_id"
			)
			->where('n.publicated = 1')
			->where('nhnc.news_category_id = ?', $this->id);

		return $select;
	}
}