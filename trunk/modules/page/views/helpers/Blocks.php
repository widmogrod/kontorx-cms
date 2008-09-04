<?php
require_once 'Zend/View/Helper/Abstract.php';
class Page_View_Helper_Blocks extends Zend_View_Helper_Abstract {
	protected $_blocks = array();

	/**
	 * Konstruktor
	 *
	 * @param Zend_Db_Table_Rowset_Abstract $blocks
	 * @param string $url
	 * @return Page_View_Helper_Blocks|string
	 */
	public function blocks($blocks, $url = null) {
		if (!$blocks instanceof Zend_Db_Table_Rowset_Abstract) {
			return '<!-- Page_View_Helper_Blocks::blocks $blocks not instanceof Zend_Db_Table_Rowset_Abstract-->';
		}
		// przygotowywujemy bloki
		foreach ($blocks as $block) {
			$this->_blocks[$block->block_key] = $block->content;
		}
		return null === $url
			? $this
			: $this->{$url};
	}

	/**
	 * Isset
	 *
	 * @param string $name
	 * @return bool
	 */
	public function __isset($name) {
		return array_key_exists($name, $this->_blocks);
	}
	
	/**
	 * getter
	 *
	 * @param string $name
	 * @return string
	 */
	public function __get($name) {
		return array_key_exists($name, $this->_blocks)
			? $this->_blocks[$name]
			: '<!-- Page_View_Helper_Blocks::__get block `'.$name.'` do not exsists-->';
	}
}