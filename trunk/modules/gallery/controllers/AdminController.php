<?php
require_once 'KontorX/Controller/Action.php';

/**
 * Zarzadzanie galerią
 *
 * @author Marcin `widmogrod` Habryn, <widmogrod@gmail.com>
 * @license GNU GPL
 */
class Gallery_AdminController extends KontorX_Controller_Action {
	public $skin = array(
		'layout' => 'admin_gallery'
	);

	/**
	 * Główny widok
	 * 
	 * Uploadowanie zdięć przypisywanie ich do galerii,
	 * tworzenie galerii wszystko AJAX
	 */
	public function indexAction() {}
}