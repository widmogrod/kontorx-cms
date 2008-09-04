<?php
class Product_View_Helper_TreeSelect {

	protected $_rowset = null;
	
	public function treeSelect($attr = null, KontorX_Db_Table_Tree_Rowset_Abstract $rowset = null) {
		$rowset = null === $rowset
			? $this->_getCategoriesRowset()
			: $rowset;

		if (!$rowset instanceof KontorX_Db_Table_Tree_Rowset_Abstract) {
			print '<p>lista kategorii nie istnieje</p>';
			return;
		}
		
		$result = '<select '.(string) $attr.'>';
		foreach ($rowset as $row) {
			$result .= $this->_render($row);
		}
		$result .= '</select>';
		return $result;
	}

	/**
	 * Zwraca liste kategorii
	 *
	 * @return KontorX_Db_Table_Tree_Rowset_Abstract
	 */
	protected function _getCategoriesRowset() {
		// zapewnia pojedyncze ladowanie rekodow
		if ($this->_rowset instanceof KontorX_Db_Table_Tree_Rowset_Abstract) {
			return $this->_rowset;
		}

		require_once 'product/models/ProductCategory.php';
		$category = new ProductCategory();

		$select = $category->select();
		$select
			->where('publicated = 1');

		try {
			$this->_rowset = $category->fetchAll($select);
		} catch (Zend_Db_Table_Exception $e) {
			// logowanie wyjatku
			$logger = Zend_Registry::get('logger');
			$logger->log($e->getMessage() . "\n" . $e->getTraceAsString(), Zend_Log::ERR);
		}
		
		return $this->_rowset;
	}

	/**
	 * @Overwrite
	 */
	protected function _render(KontorX_Db_Table_Tree_Row_Abstract $row) {
		return '<option value="'.$row->id.'" >' . str_repeat('&nbsp;&nbsp;', $row->depth) . $row->name . '</option>';
	}
}