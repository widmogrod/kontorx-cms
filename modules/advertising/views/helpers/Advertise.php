<?php
require_once 'Zend/View/Helper/Abstract.php';

/**
 * Description of Advertise
 *
 * @author gabriel
 */
class Advertising_View_Helper_Advertise extends Zend_View_Helper_Abstract {

    /**
     * @return string
     */
    public function advertise($blockName = null) {
        if (is_string($blockName)) {
            $this->setBlockName($blockName);
        }
        return $this;
    }

    /**
     * @var string
     */
    protected $_blockName = null;

    /**
     * Ustawia nazwe bloku reklamowego
     * @return Advertising_View_Helper_Advertise
     */
    public function setBlockName($name) {
        $this->_blockName = (string) $name;
    }

    /**
     * Zwraca nazwe bloku reklawowego
     * @return string
     */
    protected function _getBlockName() {
        return $this->_blockName;
    }

    /**
     * @return string
     */
    public function toString() {
        // sprawdz czy jest nazwa bloku reklamowego
        require_once 'advertising/models/Advertising.php';
        $advertiseing =  Advertising_Model_Advertising::getInstance();
        $content =  $advertiseing->getBlockContents($this->_getBlockName());
        $content = '<div class="adv">' . implode('</div><div class="adv">', (array) $content) . '</div>';
        return $content;
    }

    /**
     * @return string
     */
    public function __toString() {
        return $this->toString();
    }
}