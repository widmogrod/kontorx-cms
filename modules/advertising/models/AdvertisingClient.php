<?php

require_once 'Zend/Db/Table/Abstract.php';

/**
 * Description of AdvertisingBlock
 *
 * @author gabriel
 */
class AdvertisingClient extends Zend_Db_Table_Abstract {
    protected $_name = 'advertising_client';

    protected $_dependentTables = array(
        'AdvertisingAdvertise');
}