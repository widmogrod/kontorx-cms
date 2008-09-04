<?php
require_once 'KontorX/View/Helper/Renderer.php';
class News_View_Helper_ContentRenderer extends KontorX_View_Helper_Renderer {
	public function contentRenderer($content) {
		return parent::renderer($content);
	}

	public $url = null;
	
	protected function _preContent($content) {
		return str_ireplace('[galeria]', "{{action:gallery;index;gallery;gallery_url=$this->url}}", $content);
	}
}