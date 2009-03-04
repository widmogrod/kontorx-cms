<?php

require_once 'KontorX/Db/Table/Abstract.php';

/**
 * Description of AdvertisingBlock
 *
 * @author gabriel
 */
class AdvertisingAdvertise extends KontorX_Db_Table_Abstract {
    protected $_name = 'advertising_advertise';

    protected $_referenceMap    = array(
        'AdvertisingType' => array(
            'columns'           => 'advertising_type',
            'refTableClass'     => 'AdvertisingType',
            'refColumns'        => 'name',
            'onDelete'		=> self::CASCADE,
            'onUpdate'		=> self::CASCADE
        ),
        'AdvertisingBlock' => array(
            'columns'           => 'advertising_block',
            'refTableClass'     => 'AdvertisingBlock',
            'refColumns'        => 'name',
            'onDelete'		=> self::CASCADE,
            'onUpdate'		=> self::CASCADE
        ),
        'AdvertisingClient' => array(
            'columns'           => 'advertising_client_id',
            'refTableClass'     => 'AdvertisingClient',
            'refColumns'        => 'id',
            'refColumnsAsName'  => 'name',
            'onDelete'		=> self::CASCADE
        )
    );
}