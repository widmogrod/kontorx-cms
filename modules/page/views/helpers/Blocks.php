<?php
require_once 'Zend/View/Helper/Abstract.php';
class Page_View_Helper_Blocks extends Zend_View_Helper_Abstract {
	protected $_blocks = array();
        protected $_blockName = null;
	/**
	 * Konstruktor
	 *
	 * @param Zend_Db_Table_Rowset_Abstract|object|null $blocks
	 * @param string|null $blockName
	 * @return Page_View_Helper_Blocks|string
	 */
	public function blocks($blocks = null, $blockName = null) {
		if (null === $blocks) {
			$blocks = $this->view->pageBlocks;
		}
                
                $this->_blockName = (string) $blockName;

		if (empty($blocks)) {
			print '<!-- Page_View_Helper_Blocks::blocks $blocks is empty or is not object-->';
			return $this;
		}
		// przygotowywujemy bloki
		foreach ($blocks as $block) {
			$this->_blocks[$block->block_name] = $block->content;
		}

		return $this;
	}

        public function  __toString() {
            return array_key_exists($this->_blockName, $this->_blocks)
                ? $this->_blocks[$this->_blockName]
                : ('<div class="block">' . implode('</div><div class="block">', $this->_blocks) . '</div>');
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