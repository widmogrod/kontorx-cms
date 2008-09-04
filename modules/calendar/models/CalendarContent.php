<?php
require_once 'language/models/Language.php';

require_once 'Zend/Db/Table/Abstract.php';
class CalendarContent extends Zend_Db_Table_Abstract {
	protected $_name = 'calendar_content';

	protected $_referenceMap    = array(
		'Calendar' => array(
            'columns'           => 'calendar_id',
            'refTableClass'     => 'Calendar',
            'refColumns'        => 'id',
			'refColumnsAsName'  => 'name',
			// ryzykowne ale po co puste relacje!
			'onDelete'			=> self::CASCADE
        ),
		'Language' => array(
            'columns'           => 'language_url',
            'refTableClass'     => 'Language',
            'refColumns'        => 'url',
			'refColumnsAsName'  => 'name',
			// ryzykowne ale po co puste relacje!
			'onDelete'			=> self::CASCADE
        )
    );
}