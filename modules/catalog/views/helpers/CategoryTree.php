<?php
require_once 'KontorX/View/Helper/Tree/Abstract.php';
class Catalog_View_Helper_CategoryTree extends KontorX_View_Helper_Tree_Abstract {

	private $_url = null;

	public function __construct() {
		
	}

	public function init(KontorX_Db_Table_Tree_Rowset_Abstract $rowset = null) {
		if (null === $rowset && null === $this->_rowset) {
			if (isset($this->view->catalogDistrictRowset)) {
				$rowset = $this->view->catalogDistrictRowset;
			} else {
				require_once 'catalog/models/CatalogDistrict.php';
				if (Zend_Registry::isRegistered('cacheDBQuery')) {
					// cache ON
					$cache = Zend_Registry::get('cacheDBQuery');
					if (!($rowset = $cache->load(get_class($this)))) {
						$rowset = $this->_loadRowset();
						$cache->save($rowset);
					}
				} else {
					$rowset = $this->_loadRowset();
				}
			}
			
			// ustawiamy rowset
			$this->setRowset($rowset);
		}
		
		// ustawamy aktywne rekordy
		if (isset($this->view->categoryUrl)) {
			$this->setActiveUrl($this->view->categoryUrl);
		}
		if (isset($this->view->categoryId)) {
			$this->setActiveId($this->view->categoryId);
		}
		
		// setup url
		$this->_url = $this->view->url(array(
			'module' => 'catalog',
			'controller' => 'index',
			'action' => 'category',
			'url' => 'URL'
		),'catalogCategory',true);
	}
	
	/**
	 * @param KontorX_Db_Table_Tree_Rowset_Abstract $rowset
	 * @return Catalog_View_Helper_CategoryTree
	 */
	public function CategoryTree(KontorX_Db_Table_Tree_Rowset_Abstract $rowset = null) {
		if (null !== $rowset) {
			$this->init($rowset);
		}

		return $this;
	}
	
	/**
	 * @return string
	 */
	public function toString() {
		$this->init();
		return $this->tree($this->_rowset);
	}
	
	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}
	
	/**
	 * @var KontorX_Db_Table_Tree_Rowset_Abstract
	 */
	private $_rowset = null;
	
	/**
	 * @param KontorX_Db_Table_Tree_Rowset_Abstract $rowset
	 * @return Catalog_View_Helper_CategoryTree
	 */
	public function setRowset(KontorX_Db_Table_Tree_Rowset_Abstract $rowset) {
		$this->_rowset = $rowset;
		return $this;
	}
	
	/**
	 * @var integer
	 */
	private $_activeId = null;
	
	/**
	 * @param integer $id
	 * @return Catalog_View_Helper_CategoryTree
	 */
	public function setActiveId($id) {
		$this->_activeId = (int) $id;
		return $this;
	}
	
	/**
	 * @var string
	 */
	private $_activeUrl = null;
	
	/**
	 * @param string $url
	 * @return Catalog_View_Helper_CategoryTree
	 */
	public function setActiveUrl($url) {
		$this->_activeUrl = (string) $url;
		return $this;
	}

	/**
	 * @return KontorX_Db_Table_Tree_Row_Abstract|null
	 */
	private function _loadRowset() {
		// auto load!
		$district = new CatalogDistrict();

		try {
			return $district->fetchAll();
		} catch (Zend_Db_Table_Exception $e) {}
	}
	
	protected function _data(KontorX_Db_Table_Tree_Row_Abstract $row) {
		if($this->_activeId == $row->id
		   || $this->_activeUrl == $row->url) 
		{
			$result = "<a class='selected' href='".str_replace('URL',$row->url,$this->_url)."'>$row->name</a>";
		} else {
			$result = "<a href=".str_replace('URL',$row->url,$this->_url).">$row->name</a>";			
		}
		
		return $result;
	}
}