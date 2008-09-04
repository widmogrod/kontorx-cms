<?php
require_once 'KontorX/View/Helper/Tree/Abstract.php';

class News_View_Helper_TreeCheckbox extends KontorX_View_Helper_Tree_Abstract {

	protected $_checked = array();
	
	public function TreeCheckbox(KontorX_Db_Table_Tree_Rowset_Abstract $rowset, array $checked = array()) {
		$this->setChecked($checked);
		return parent::tree($rowset);
	}

	/**
	 * Ustawia tablice przechowujaca `id` elementow zaznaczonych
	 *
	 * @param array $checked
	 */
	public function setChecked(array $checked) {
		$this->_checked = $checked;
	}

	/**
	 * @Overwrite
	 */
	protected function _data(KontorX_Db_Table_Tree_Row_Abstract $row) {
		$result = in_array($row->id, $this->_checked)
			? '<input type="checkbox" name="hasCategories[]" value="'.$row->id.'" checked="checked"/>'
			: '<input type="checkbox" name="hasCategories[]" value="'.$row->id.'"/>';
		return '<label>' . $result . $row->name . '</label>';
	}
}