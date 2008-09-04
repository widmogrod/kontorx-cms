<?php
require_once 'Zend/View/Helper/Abstract.php';
class Gallery_View_Helper_GalleryCategory extends Zend_View_Helper_Abstract {

	/**
	 * @var GalleryCategory
	 */
	protected $_model = null;
	
	public function __construct() {
		require_once 'gallery/models/GalleryCategory.php';
		$this->_model = new GalleryCategory();
	}
	
	public function galleryCategory($category_id = null) {
		$select = $this->_model->select();
		$select
			->where('visible = 1');

		try {
			$this->view->rowset = $this->_model->fetchAll($select);
			$scriptName = 'gallery-category.phtml';
		} catch (Zend_Db_Table_Exception $e) {
			$scriptName = 'gallery-category-noexsists.phtml';
		}

		$result = $this->view->render("_helpers/$scriptName");

		if (null === $category_id) {
			return $result;
		}
		
		// TODO Dodac renderowanie galerii w kategorii
	}

	protected function _getGallery() {
		
	}
}
?>