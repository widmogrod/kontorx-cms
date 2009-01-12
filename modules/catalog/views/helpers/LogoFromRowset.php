<?php
require_once 'Zend/View/Helper/Abstract.php';
class Catalog_View_Helper_LogoFromRowset extends Zend_View_Helper_Abstract {
	public function logoFromRowset($imageId, $imageRowset) {
		if (!count($imageRowset)) {
			return null;
		}

		foreach ($imageRowset as $image) {
			if ($image->id == $imageId) {
				return $image->image;
			}
		}
	}
}
