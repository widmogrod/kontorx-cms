<?php

require_once 'Zend/Db/Table/Abstract.php';

/**
 * Description of AdvertisingBlock
 *
 * @author gabriel
 */
class AdvertisingBlock extends Zend_Db_Table_Abstract {
    protected $_name = 'advertising_block';

    protected $_dependentTables = array(
        'AdvertisingAdvertise');
}