<?php
/**
 * Zarzadzanie galerią
 *
 * @author Marcin `widmogrod` Habryn, <widmogrod@gmail.com>
 * @license GNU GPL
 */
class Gallery_AdminController extends KontorX_Controller_Action {
	public function init(){
		$this->_initLayout(null, 'gallery');
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

	/**
	 * Główny widok
	 * 
	 * Uploadowanie zdięć przypisywanie ich do galerii,
	 * tworzenie galerii wszystko AJAX
	 */
	public function indexAction() {
		
	}
}
?>