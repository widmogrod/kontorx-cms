<?php
require_once 'KontorX/Controller/Action.php';
class Gallery_GwtController extends KontorX_Controller_Action {
	public $skin = array(
		'layout' => 'admin_gwt'
	);

	public function indexAction() {
		$this->view->inlineScript();
	}

	public function rpcAction() {
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		
		$server = new Zend_XmlRpc_Server();

		require_once 'gallery/models/gwt/Gallery.php';
		require_once 'gallery/models/gwt/GalleryCategory.php';
		require_once 'gallery/models/gwt/GalleryImage.php';
		// Ustawienie sciezki uploadowania plikow
		$config = $this->_helper->loader->config();
		$path = $config->path->upload;
		$path = $this->_helper->system()->getPublicHtmlPath($path);
		GalleryImage_Row::setImagePath($path);

		Zend_XmlRpc_Server_Fault::attachFaultException('Exception');

		$server->setClass('GWT_Gallery','gallery');
		$server->setClass('GWT_GalleryCategory','category');
		$server->setClass('GWT_GalleryImage','image');

		$request = new Zend_XmlRpc_Request_Http();
		print $server->handle($request);
	}
}