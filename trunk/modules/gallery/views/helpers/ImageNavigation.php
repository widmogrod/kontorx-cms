<?php
require_once 'Zend/View/Helper/Abstract.php';
class Gallery_View_Helper_ImageNavigation extends Zend_View_Helper_Abstract {

	/**
	 * Wyszukiwanie preview i next
	 *
	 * @param Zend_Db_Table_Rowset_Abstract|array $rowset
	 * @param string $current
	 * @return string
	 */
	public function imageNavigation($rowset, $current) {
		$this->view->next = $current;
		$this->view->prev = $current;

		if (is_scalar($rowset) || is_resource($rowset)) {
			return '<!-- Gallery_View_Helper_ImageNavigation:: przekazany parametr $rowset jest nieprawidłowy-->';
		}

		$old  = null;
		$prev = null;
		$find = false;
		foreach ($rowset as $row) {
			// next
			if ($find === true) {
				$this->view->next = $row->image;
				break;
			}
			// current
			if ($current == $row->image) {
				$find = true;
				// prev
				if (null !== $old) {
					$this->view->prev = $old->image;
				}
			}
			$old = $row;
		}

		return $this->view->render('_helpers/image-navigation.phtml');
	}
}
?>