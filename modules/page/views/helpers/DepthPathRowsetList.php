<?php
require_once 'Zend/View/Helper/Abstract.php';
class Page_View_Helper_DepthPathRowsetList extends Zend_View_Helper_Abstract {
	/**
	 * Konstruktor
	 *
	 * @param KontorX_Db_Table_Tree_Rowset_Abstract $rowset
	 * @return string
	 */
	public function DepthPathRowsetList($rowset = null) {
		if (!$rowset instanceof KontorX_Db_Table_Tree_Rowset_Abstract
				|| count($rowset) < 1)
		{
			return null;
		}

		$result = '<ul>';
		foreach ($rowset as $row) {
			$result .= "<li>";
			$result .= $this->_render($row);
			$result .= "</li>";
		}
		$result .= '</ul>';

		return $result;
	}

	/**
	 * render list
	 *
	 * @param KontorX_Db_Table_Tree_Row_Abstract $row
	 * @return string
	 */
	public function _render(KontorX_Db_Table_Tree_Row_Abstract $row) {
		return '<a href="'.$this->view->url(array('url' => $row->url),'page').'">' . $row->name . '</a>';
	}
}