<?php
require_once 'KontorX/View/Helper/Tree/Abstract.php';
class Page_View_Helper_TreePageRowsetAnchor extends KontorX_View_Helper_Tree_Abstract {

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

	/**
	 * Konstruktor
	 *
	 * @param KontorX_Db_Table_Tree_Rowset_Abstract $rowset
	 * @param string $active
	 * @return string
	 */
	public function TreePageRowsetAnchor($rowset, $active = null) {
		// generowanie url
		$this->_url = $this->view->url(array(
			'url' => '{url}'
		),'page',true, false);

		$this->_active = $active;

		// warunkie kiedy przetwarzamy
		if ($rowset instanceof KontorX_Db_Table_Tree_Rowset_Abstract
				&& count($rowset)) {
			return parent::tree($rowset);
		}
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