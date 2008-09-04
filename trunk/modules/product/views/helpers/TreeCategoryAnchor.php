<?php
require_once 'KontorX/View/Helper/Tree/Abstract.php';

class Product_View_Helper_TreeCategoryAnchor extends KontorX_View_Helper_Tree_Abstract {

	/**
	 * Url dla anchora
	 *
	 * @var string
	 */
	protected $_url = null;

	/**
	 * Przechowuje liste kategorii
	 *
	 * @var KontorX_Db_Table_Tree_Rowset_Abstract
	 */
	protected $_rowset = null;
	
	protected $_active = null;

	public function TreeCategoryAnchor(KontorX_Db_Table_Tree_Rowset_Abstract $rowset = null, $active = null) {
		// generowanie url
		$this->_url = $this->view->url(array(
			'module' => 'product',
			'controller' => 'index',
			'action' => 'category',
			'url' => '{url}'
		),'frontend',true, false);

		$this->_active = $active;

		$rowset = null === $rowset
			? $this->_getCategoriesRowset()
			: $rowset;

		if (!$rowset instanceof KontorX_Db_Table_Tree_Rowset_Abstract) {
			print '<p>lista kategorii nie istnieje</p>';
		} else {
			return parent::tree($rowset);
		}
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
	protected function _data(KontorX_Db_Table_Tree_Row_Abstract $row) {
		// szybsze niz bezposrednie wywolywanie za kazdym razem $this->url() ..
		$url = str_replace('{url}', $row->url, $this->_url);
		$attr = $this->_active == $row->url ? 'class="selected"' : '';
		return "<a $attr href=\"$url\">$row->name</a>";
	}
}