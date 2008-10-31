<?php
require_once 'KontorX/View/Helper/Tree/Abstract.php';
class Catalog_View_Helper_CategoryTree extends KontorX_View_Helper_Tree_Abstract {

	private $_url = null;

	public function CategoryTree(KontorX_Db_Table_Tree_Rowset_Abstract $rowset = null) {
		if (null === $rowset) {
			if (isset($this->view->catalogDistrictRowset)) {
				$rowset = $this->view->catalogDistrictRowset;
			} else {
				// auto load!
				require_once 'catalog/models/CatalogDistrict.php';
				$district = new CatalogDistrict();

				$select = $district->select()
					->where('url = ?', 'krakow');
				
				try {
					$row = $district->fetchRow($select);
				} catch (Zend_Db_Table_Exception $e) {
					$row = null;
				}

				if (!$row instanceof KontorX_Db_Table_Tree_Row_Abstract) {
					throw new Zend_View_Exception("row for 'krakow' no exsists!");
				}

				try {
					$rowset = $row->findChildrens();
				} catch (Zend_Db_Table_Exception $e) {
					$rowset = null;
				}
			}
		}

		if (!$rowset instanceof KontorX_Db_Table_Tree_Rowset_Abstract) {
			throw new Zend_View_Exception("rowset is incorrect");
		}

		$this->_url = $this->view->url(array(
			'module' => 'catalog',
			'controller' => 'index',
			'action' => 'category',
			'url' => 'URL'
		),'catalogCategory',true);

		return $this->tree($rowset);
	}
	
	protected function _data(KontorX_Db_Table_Tree_Row_Abstract $row) {
		if ($this->view->categoryUrl == $row->url) {
			$result = "<a class='selected' href='".str_replace('URL',$row->url,$this->_url)."'>$row->name</a>";
		} else {
			$result = "<a href=".str_replace('URL',$row->url,$this->_url).">$row->name</a>";			
		}
		
		return $result;
	}
}